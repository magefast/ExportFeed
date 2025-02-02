<?php

namespace Magefast\ExportFeed\Service;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\Checkout\Helper\Data;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Framework\Exception\LocalizedException;
use Magento\Wishlist\Model\Product\AttributeValueProvider;

class GetAdditionalData
{
    /**
     * @var array
     */
    private array $allProductsAttributes = [];

    /**
     * @var array
     */
    private array $productsAdditionalData = [];

    /**
     * @var Config
     */
    private Config $eavConfig;

    /**
     * @var Attribute
     */
    private Attribute $attributeFactory;

    /**
     * @var Data
     */
    private Data $checkoutHelper;

    /**
     * @var AttributeValueProvider
     */
    private AttributeValueProvider $attributeValueProvider;

    /**
     * @var ProductResourceModel
     */
    private ProductResourceModel $productResource;

    /**
     * @param Config $eavConfig
     * @param Attribute $attributeFactory
     * @param Data $checkoutHelper
     * @param AttributeValueProvider $attributeValueProvider
     * @param ProductResourceModel $productResource
     */
    public function __construct(
        Config                 $eavConfig,
        Attribute              $attributeFactory,
        Data                   $checkoutHelper,
        AttributeValueProvider $attributeValueProvider,
        ProductResourceModel   $productResource
    )
    {
        $this->eavConfig = $eavConfig;
        $this->attributeFactory = $attributeFactory;
        $this->checkoutHelper = $checkoutHelper;
        $this->attributeValueProvider = $attributeValueProvider;
        $this->productResource = $productResource;
    }

    /**
     * @param $productId
     * @param $storeId
     * @param array $excludeAttr
     * @return array
     * @throws LocalizedException
     */
    public function getAdditionalData($productId, $storeId, $excludeAttr = []): array
    {
        $key = 'store' . $storeId;
        if (isset($this->productsAdditionalData[$key][$productId])) {
            return $this->productsAdditionalData[$key][$productId];
        }

        $data = [];
        $attributes = $this->getAllProductsAttributes();
        foreach ($attributes as $attributeCode => $attributeValue) {
            if (!in_array($attributeCode, $excludeAttr)) {
                $value = $this->productResource->getAttributeRawValue($productId, $attributeCode, $storeId);
                if (empty($value)) {
                    continue;
                }

                $value = $this->productResource->getAttribute($attributeCode)->setStoreId($storeId)->getSource()->getOptionText($value);
                if (empty($value) || $value == '') {
                    continue;
                }

//                if (!$product->hasData($attributeCode)) {
//                    $value = __('N/A');
//                } elseif ((string)$value == '') {
//                    $value = __('No');
//                } elseif ($attributeValue['frontend_input'] == 'price' && is_string($value)) {
//                    $value = $this->checkoutHelper->convertPrice($value, true);
//                }

                if (!empty($value)) {
                    $valueStrlen = strlen($value);
                }
                if (is_string($value) && $valueStrlen > 0) {
                    $data[$attributeCode] = array(
                        'label' => $attributeValue['store_label'],
                        'value' => $this->normalizeString($value),
                        'code' => $attributeCode
                    );
                }
            }
        }

        $naTextValue = __('N/A');
        $naTextValue2 = 'Н/Д';

        foreach ($data as $key => $value) {
            if ($value['value'] == $naTextValue || $value['value'] == $naTextValue2) {
                unset($data[$key]);
            }
        }

        $this->productsAdditionalData[$key][$productId] = $data;

        return $data;
    }

    /**
     * @throws LocalizedException
     */
    private function getAllProductsAttributes(): array
    {
        if (count($this->allProductsAttributes) > 0) {
            return $this->allProductsAttributes;
        }

        $entityId = $this->eavConfig->getEntityType(Product::ENTITY)->getId();
        $attributes = $this->attributeFactory->getCollection()
            ->addFieldToFilter(Set::KEY_ENTITY_TYPE_ID, $entityId);

        foreach ($attributes as $attribute) {
            $attributeLoaded = $this->eavConfig->getAttribute('catalog_product', $attribute->getAttributeCode());
            if ($attributeLoaded->getIsVisibleOnFront()) {
                $this->allProductsAttributes[$attribute->getAttributeCode()] = array(
                    'attribute_code' => $attribute->getAttributeCode(),
                    'frontend_input' => $attribute->getFrontendInput(),
                    'store_label' => $this->normalizeString($attribute->getStoreLabel())
                );
            }
        }

        return $this->allProductsAttributes;
    }

    /**
     * @param $string
     * @return string
     */
    private function normalizeString($string): string
    {
        $string = mb_strtolower($string, 'UTF-8');

        $strlen = 0;
        if (!empty($string)) {
            $strlen = mb_strlen($string, 'UTF-8');
        }
        $firstChar = mb_substr($string, 0, 1, 'UTF-8');
        $then = mb_substr($string, 1, $strlen - 1, 'UTF-8');
        return mb_strtoupper($firstChar, 'UTF-8') . $then;
    }
}
