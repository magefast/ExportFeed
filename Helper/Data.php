<?php

namespace Magefast\ExportFeed\Helper;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Customer\Model\GroupManagement;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    /**
     * @var array
     */
    private array $categoriesArray = [];

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var CategoryCollectionFactory
     */
    private CategoryCollectionFactory $categoryCollectionFactory;

    /**
     * @var AttributeRepositoryInterface
     */
    private AttributeRepositoryInterface $eavAttribute;

    /**
     * @var GroupManagement
     */
    private GroupManagement $groupManagement;

    /**
     * @var ProductCollectionFactory
     */
    private ProductCollectionFactory $productCollectionFactory;
    private TimezoneInterface $localeDate;

    /**
     * Data constructor.
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param AttributeRepositoryInterface $eavAttribute
     * @param GroupManagement $groupManagement
     * @param ProductCollectionFactory $productCollectionFactory
     * @param Registry $registry
     * @param TimezoneInterface $localeDate
     * @codeCoverageIgnore
     */
    public function __construct(
        Context                      $context,
        StoreManagerInterface        $storeManager,
        CategoryCollectionFactory    $categoryCollectionFactory,
        AttributeRepositoryInterface $eavAttribute,
        GroupManagement              $groupManagement,
        ProductCollectionFactory     $productCollectionFactory,
        Registry                     $registry,
        TimezoneInterface            $localeDate
    )
    {
        $this->storeManager = $storeManager;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->eavAttribute = $eavAttribute;
        $this->groupManagement = $groupManagement;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->registry = $registry;
        $this->localeDate = $localeDate;

        parent::__construct($context);
    }

    /**
     * @return array
     */
    public function getAdminFormData(): array
    {
        $model = $this->registry->registry('magefast_exportfeed_feed');

        $array = [];
        $categories = $model->getData('categories');
        $brandFilter = $model->getData('brand_filter_category');
        $priceGroup = $model->getData('price_settings_category');

        $array['categoriesIdArray'] = $this->prepareCategoriesId($categories);
        $array['brandFilterIdArray'] = $this->prepareBrandFilterId($brandFilter);
        $array['priceGroupIdArray'] = $this->preparepriceGroupId($priceGroup);

        return $array;
    }

    /**
     * @param $data
     * @return array
     */
    public function prepareCategoriesId($data): array
    {
        $categoriesIdArray = [];

        $categoriesIdArrayTemp = null;
        if ($data != '') {
            $categoriesIdArrayTemp = json_decode($data, true);
        }

        if (is_array($categoriesIdArrayTemp)) {
            foreach ($categoriesIdArrayTemp as $c) {
                $categoriesIdArray[$c] = $c;
            }
        }

        return $categoriesIdArray;
    }

    /**
     * @param $data
     * @return array
     */
    public function prepareBrandFilterId($data): array
    {
        $idArray = [];

        if (empty($data)) {
            return $idArray;
        }

        $data = json_decode($data, true);

        if (!is_array($data)) {
            return $idArray;
        }

        foreach ($data as $key => $value) {
            $idArrayTemp = [];
            foreach ($value as $v) {
                $idArrayTemp[$v] = $v;
            }
            $idArray[$key] = $idArrayTemp;
        }

        return $idArray;
    }

    /**
     * @param $data
     * @return array
     */
    private function preparepriceGroupId($data): array
    {
        $idArray = [];

        if (empty($data)) {
            return $idArray;
        }

        $data = json_decode($data, true);

        if (!is_array($data)) {
            return $idArray;
        }

        foreach ($data as $key => $value) {
            $idArray[$key] = $value;
        }

        return $idArray;
    }

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCategoriesLevel1(): array
    {
        $categories = $this->getCategories();

        if (empty($categories)) {
            return [];
        }

        $rootCatId = $this->storeManager->getStore(1)->getRootCategoryId();

        if (isset($categories[$rootCatId])) {
            return $categories[$rootCatId];
        }

        return [];
    }


    /**
     * @return array
     * @throws LocalizedException
     */
    private function getCategories(): array
    {
        if (!empty($this->categoriesArray)) {
            return $this->categoriesArray;
        }

        $categories = $this->categoryCollectionFactory->create()
            ->setStore(0)
            ->addFieldToFilter('is_active', array('eq' => '1'))
            //   ->addAttributeToFilter('path', array('like' => '1/' . $rootCatId . '/%'))
            ->addAttributeToSelect('path')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('level')
            ->addAttributeToSelect('parent_id');

        foreach ($categories as $category) {

            if ($category->getLevel() == 1) {
                continue;
            }

            $this->categoriesArray[$category->getParentId()][$category->getId()] = [
                'name' => $category->getName(),
                'id' => $category->getId(),
                'parent_id' => $category->getParentId()
            ];
        }

        return $this->categoriesArray;
    }

    /**
     * @param $parentId
     * @return array
     * @throws LocalizedException
     */
    public function getCategoriesLevelParent($parentId): array
    {
        $categories = $this->getCategories();

        if (empty($categories)) {
            return [];
        }

        if (isset($categories[$parentId])) {
            return $categories[$parentId];
        }

        return [];
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    public function getManufacturerOption(): array
    {
        $manufacturerAttr = $this->eavAttribute->get(Product::ENTITY, 'manufacturer');
        $allOptions = $manufacturerAttr->getSource()->getAllOptions();

        foreach ($allOptions as $key => $value) {
            if (!empty($value['value'])) {
                $value['value'] = trim($value['value']);
            }

            if (!empty($value['label'])) {
                $value['label'] = trim($value['label']);
            }

            if ($value['value'] == '' || $value['label'] == '') {
                unset($allOptions[$key]);
            }
        }

        usort($allOptions, function ($a, $b) {
            return $a['label'] <=> $b['label'];
        });

        return $allOptions;
    }

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCustomerGroupsOption(): array
    {
        $array = [];
        $groupsLoggedInGroups = $this->groupManagement->getLoggedInGroups();
        foreach ($groupsLoggedInGroups as $g) {
            $array[$g->getId()] = ['value' => $g->getId(), 'label' => $g->getCode()];
        }
        unset($groupsLoggedInGroups);
        $groupsNotLoggedInGroup = $this->groupManagement->getNotLoggedInGroup();
        foreach ($groupsNotLoggedInGroup as $g) {
            $array[$g->getId()] = ['value' => $g->getId(), 'label' => $g->getCode()];
        }
        unset($groupsNotLoggedInGroup);

        /**
         * Rozetka price
         */
        $array[99999] = array(
            'value' => 99999,
            'label' => __('Rozetka Price')
        );

        return $array;
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function getBrandsInCategoryArray(): array
    {
        $array = [];

        $categoriesAllParent = [];
        $categoriesAll = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect('path')
            ->addAttributeToSelect('level');

        foreach ($categoriesAll as $ca) {
            if ($ca->getData('level') == 1 || $ca->getData('level') == 0) {
                continue;
            }
            $path = $ca->getData('path');
            if ($path != '') {
                $path = explode('/', $path);
                $categoriesAllParent[$ca->getData('entity_id')] = $path;
            }
        }
        unset($categoriesAll);

        $collection = $this->productCollectionFactory->create();

        $collection
            ->addAttributeToSelect('entity_id')
            ->addAttributeToSelect('manufacturer')
            ->addCategoryIds();

        $collection->getSelect()->group('e.entity_id');

        foreach ($collection as $c) {
            if ($c->getData('manufacturer') != '' && $c->getCategoryIds() && is_array($c->getCategoryIds()) && count($c->getCategoryIds()) > 0) {
                if (isset($array[$c->getData('manufacturer')])) {
                    $categoryArray = $array[$c->getData('manufacturer')];
                } else {
                    $categoryArray = [];
                }
                foreach ($c->getCategoryIds() as $cId) {
                    $categoryArray[intval($cId)] = intval($cId);
                    if (isset($categoriesAllParent[intval($cId)])) {
                        foreach ($categoriesAllParent[intval($cId)] as $cap) {
                            $categoryArray[intval($cap)] = intval($cap);
                        }
                    }
                }
                $array[$c->getData('manufacturer')] = $categoryArray;
            }
        }

        return $array;
    }

    /**
     * @param $price
     * @param $specialPrice
     * @param $specialPriceFrom
     * @param $specialPriceTo
     * @param $store
     * @return array|null
     */
    public function getSpecialPrice(
        $price,
        $specialPrice,
        $specialPriceFrom,
        $specialPriceTo,
        $store = null
    ): ?array
    {
        $minAmount = [];

        if ($specialPrice !== null && $specialPrice !== false) {
            if ($this->localeDate->isScopeDateInInterval($store, $specialPriceFrom, $specialPriceTo)) {
                $finalPrice = min($price, (float)$specialPrice);
                $minAmount['min_sale_price'] = floatval($finalPrice);
                $minAmount['special_from_date'] = $specialPriceFrom;
                $minAmount['special_to_date'] = $specialPriceTo;

                return $minAmount;
            }
        }
        return null;
    }
}
