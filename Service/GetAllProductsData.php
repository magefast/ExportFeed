<?php

namespace Magefast\ExportFeed\Service;

use Magefast\ExportFeed\Helper\Data;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\PriceModifierInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Directory\Model\PriceCurrency;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use NumberFormatter;

class GetAllProductsData
{
    public const DEFAULT_ATTRIBUTES_TO_SELECT = [
        'entity_id',
        'name',
        'sku',
        'manufacturer',
        'description',
        'model',
        'group_price',
        'ean',
        'category_ids',
        'weight',
        'price',
        'special_price',
        'special_to_date',
        'special_from_date'
    ];

    public const ATTRIBUTE_TYPE_SELECT = ['manufacturer'];

    /**
     * @var array
     */
    private array $productsData = [];

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var GetCategoriesData
     */
    private GetCategoriesData $categoriesData;

    /**
     * @var ProductCollectionFactory
     */
    private ProductCollectionFactory $productCollectionFactory;

    /**
     * @var Stock
     */
    private Stock $stock;

    /**
     * @var PriceCurrency
     */
    private PriceCurrency $priceCurrency;

    /**
     * @var PriceModifierInterface
     */
    private PriceModifierInterface $priceModifier;

    /**
     * @var GetProductMedia
     */
    private GetProductMedia $productMedia;

    /**
     * @var NumberFormatter
     */
    private NumberFormatter $numberFormat;

    /**
     * @var NumberFormatter
     */
    private NumberFormatter $numberFormatStandard;

    /**
     * @param StoreManagerInterface $storeManager
     * @param GetCategoriesData $categoriesData
     * @param ProductCollectionFactory $productCollectionFactory
     * @param Stock $stock
     * @param PriceCurrency $priceCurrency
     * @param PriceModifierInterface $priceModifier
     * @param GetProductMedia $getProductMedia
     */
    public function __construct(
        StoreManagerInterface    $storeManager,
        GetCategoriesData        $categoriesData,
        ProductCollectionFactory $productCollectionFactory,
        Stock                    $stock,
        PriceCurrency            $priceCurrency,
        PriceModifierInterface   $priceModifier,
        GetProductMedia          $getProductMedia,
        Data                     $helper
    )
    {
        $this->storeManager = $storeManager;
        $this->categoriesData = $categoriesData;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->stock = $stock;
        $this->priceCurrency = $priceCurrency;
        $this->priceModifier = $priceModifier;
        $this->productMedia = $getProductMedia;

        $this->numberFormat = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        $this->numberFormat->setSymbol(NumberFormatter::CURRENCY_SYMBOL, 'UAH');
        $this->numberFormat->setPattern('#0 ¤');

        $this->numberFormatStandard = new NumberFormatter('en_US', NumberFormatter::IGNORE);
        $this->numberFormatStandard->setPattern('#0');

        $this->helper = $helper;
    }

    /**
     * @param $storeId
     * @param $customerGroupId
     * @return array|mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getProductsData($storeId, $customerGroupId = null)
    {
        $key = $storeId . '_' . $customerGroupId ?? '';

        if (isset($this->productsData[$key])) {
            return $this->productsData[$key];
        }

        $this->productsData[$key] = $this->processing($storeId, $customerGroupId);

        return $this->productsData[$key];
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    private function processing($storeId, $customerGroupId): array
    {
        $this->storeManager->setCurrentStore($storeId);

        $catLevel1 = $this->categoriesData->getProductCategoriesLevel($storeId, 1);
        $catLevel2 = $this->categoriesData->getProductCategoriesLevel($storeId, 2);
        $catLevel3 = $this->categoriesData->getProductCategoriesLevel($storeId, 3);
        $catLevel4 = $this->categoriesData->getProductCategoriesLevel($storeId, 4);
        $catLevel5 = $this->categoriesData->getProductCategoriesLevel($storeId, 5);

        $productCats1 = $this->categoriesData->prepareProductCategories($catLevel1);
        $productCats2 = $this->categoriesData->prepareProductCategories($catLevel2);
        $productCats3 = $this->categoriesData->prepareProductCategories($catLevel3);
        $productCats4 = $this->categoriesData->prepareProductCategories($catLevel4);
        $productCats5 = $this->categoriesData->prepareProductCategories($catLevel5);

        $productCatsAll = $this->categoriesData->getProductCategories($storeId);

        /**
         * Create Product data array
         */
        $collection = $this->productCollectionFactory->create();
        $collection->setFlag('has_stock_status_filter', true);
        $collection
            ->addStoreFilter($storeId)
            ->setStoreId($storeId)
            ->addFieldToFilter('visibility', Visibility::VISIBILITY_BOTH)
            ->addFieldToFilter('status', Status::STATUS_ENABLED)
            ->addFieldToFilter('type_id', Type::TYPE_SIMPLE)
            ->addAttributeToSelect(self::DEFAULT_ATTRIBUTES_TO_SELECT)
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addUrlRewrite();

        $collection
            ->joinField(
                'qty',
                'cataloginventory_stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left')
            ->joinTable(
                'cataloginventory_stock_item',
                'product_id=entity_id',
                ['stock_status' => 'is_in_stock', 'backorders' => 'backorders', 'use_config_backorders' => 'use_config_backorders'])
            ->addAttributeToSelect('stock_status')
            ->addFieldToFilter('stock_status', 1);

        $this->stock->addInStockFilterToCollection($collection);
        $this->stock->addIsInStockFilterToCollection($collection);

        /**
         * Customer GroupId
         */
        if ($customerGroupId) {
            $collection->addPriceData($customerGroupId);
        }

        $collection->load();

        $data = [];
        foreach ($collection as $p) {
            /**
             * Get product category
             */
            $productCategoryIds = $p->getCategoryIds();
            $productCategory = '';
            if (is_array($productCategoryIds) && $productCategoryIds != null && count($productCategoryIds) != 0) {
                foreach ($productCategoryIds as $id) {
                    if (in_array($id, $productCats1)) {
                        $productCategory = $id;
                    }
                    if (in_array($id, $productCats2)) {
                        $productCategory = $id;
                    }
                    if (in_array($id, $productCats3)) {
                        $productCategory = $id;
                    }
                    if (in_array($id, $productCats4)) {
                        $productCategory = $id;
                    }
                    if (in_array($id, $productCats5)) {
                        $productCategory = $id;
                    }
                }
            }

            $id = $p->getId();
            $data[$id]['product_id'] = $id;

            foreach (self::DEFAULT_ATTRIBUTES_TO_SELECT as $code) {
                if (in_array($code, self::ATTRIBUTE_TYPE_SELECT)) {
                    $data[$id]['product_' . $code] = $p->getAttributeText($code);
                } else {
                    $data[$id]['product_' . $code] = $p->getData($code);
                }
            }

            $data[$id]['product_manufacturer_id'] = $p->getData('manufacturer') ?? null;
            $data[$id]['product_url'] = $p->getProductUrl();
            $data[$id]['product_media'] = $this->productMedia->prepareProductImages($p);
            $data[$id]['product_cat_id'] = $productCategory;
            $data[$id]['product_categories'] = $productCategoryIds;

            /**
             * Price
             */
            $price = floor($p->getPriceInfo()->getPrice('regular_price')->getValue());
            $finalPrice = floor($p->getPriceInfo()->getPrice('final_price')->getValue());
            $specialPrice = floor($p->getPriceInfo()->getPrice('special_price')->getValue());
            $specialFromDate = $p->getSpecialFromDate();
            $specialToDate = $p->getSpecialToDate();

            $minSalePrice = $this->helper->getSpecialPrice($price, $specialPrice, $specialFromDate, $specialToDate);

            $minSpecialPrice = null;
            if ($minSalePrice && isset($minSalePrice['min_sale_price'])) {
                $minSpecialPrice = $minSalePrice['min_sale_price'];
                $specialFromDate = $minSalePrice['special_from_date'];
                $specialToDate = $minSalePrice['special_to_date'];
            }

            $existSalePrice = false;
            $salePrice = null;
            $salePriceEffectiveDateFrom = date('Y-m-d');
            $salePriceEffectiveDateTo = date('Y-m-d', strtotime("+3 days"));


            if ($price > $finalPrice) {
                $existSalePrice = true;

                /**
                 * Catalog Rule prices
                 */
                $salePrice = $finalPrice;
                $salePrice = round($salePrice, 2); // Рассчитываем цену со скидкой
            }

            if (!empty($minSpecialPrice) && $price > $minSpecialPrice && $minSpecialPrice == $finalPrice) {
                $existSalePrice = true;
                $salePrice = $minSpecialPrice;

                if ($specialFromDate) {
                    $salePriceEffectiveDateFrom = date('Y-m-d', strtotime($specialFromDate)). 'T00:00+0200';
                }

                if ($specialToDate) {
                    $salePriceEffectiveDateTo = date('Y-m-d', strtotime($specialToDate)). 'T00:00+0200';
                }

                $salePrice = round($salePrice, 2); // Рассчитываем цену со скидкой
            }

            if ($existSalePrice) {
                $salePriceEffectiveDate = $salePriceEffectiveDateFrom . '/' . $salePriceEffectiveDateTo;
//                $productData['_value']['g:sale_price'] = $salePrice . ' UAH';
//                $productData['_value']['g:sale_price_effective_date'] = $salePriceEffectiveDate;
            }

            $finalPrice = $price;
            if(!empty($salePrice)) {
                $finalPrice = $salePrice;
            }

//            $finalPrice = $p->getFinalPrice();
//            $resultPrice = $this->priceModifier->modifyPrice($p->getPrice(), $p);
//            if ($resultPrice !== null) {
//                $finalPrice = $this->priceCurrency->round($resultPrice);
//            }

            $data[$id]['product_price'] = $price;
            $data[$id]['product_final_price'] = $finalPrice;
            $data[$id]['product_final_price_formatted'] = $this->currencyFormat($finalPrice);
            $data[$id]['product_price_old'] = $price;
            $data[$id]['product_price_old_formatted'] = $this->currencyFormat($price);
            $data[$id]['product_qty'] = $p->getData('qty');
            $data[$id]['product_stock_status'] = (bool)$p->getData('stock_status');
            $data[$id]['product_backorders'] = $p->getData('backorders');
            $data[$id]['product_use_config_backorders'] = $p->getData('use_config_backorders');
            $data[$id]['product_is_backorders'] = $this->isBackordersProduct($p);
            $data[$id]['product_min_profit'] = $p->getData('profit') ?? '';
            $data[$id]['product_rozetka_price'] = $p->getData('rozetka_price') ?? '';
            $data[$id]['product_ean'] = $p->getData('ean') ?? '';
        }
        unset($collection);

        return $data;
    }

    /**
     * @param $string
     * @return false|string
     */
    private function currencyFormat($string)
    {
        return $this->numberFormat->formatCurrency($string, 'UAH');
    }

    /**
     * @param $product
     * @return bool
     */
    private function isBackordersProduct($product = ''): bool
    {
        $backorders = (boolean)$product->getData('backorders');
        $qty = $product->getData('qty');

        if ($qty > 0) {
            return false;
        }

        if ($backorders && 0 >= $qty) {
            return true;
        }

        return false;
    }

    private function priceOld($price): int
    {
        // +20%
//        $priceUp = intval($price) * 0.2;
//        $priceOld = $price + $priceUp;
        return intval($price);
    }
}
