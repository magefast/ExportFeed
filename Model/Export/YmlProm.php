<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Magefast\ExportFeed\Model\Export;

use Magefast\ExportFeed\Service\CategoryBrandRule;
use Magefast\ExportFeed\Service\GetAdditionalData;
use Magefast\ExportFeed\Service\GetProductAttribute;
use Magefast\ExportFeed\Service\PrepareService;
use Magefast\ExportFeed\Service\StopRule;
use Magento\Framework\Exception\LocalizedException;
use XMLWriter;

class YmlProm
{
    /**
     * @var XMLWriter
     */
    private XMLWriter $XMLWriter;

    /**
     * @var GetProductAttribute
     */
    private GetProductAttribute $productAttribute;

    /**
     * @var PrepareService
     */
    private PrepareService $prepareService;

    /**
     * @var GetAdditionalData
     */
    private GetAdditionalData $additionalData;
    private StopRule $stopRule;
    private CategoryBrandRule $categoryBrandRule;

    /**
     * @param XMLWriter $XMLWriter
     * @param GetProductAttribute $productAttribute
     * @param PrepareService $prepareService
     * @param GetAdditionalData $additionalData
     * @param StopRule $stopRule
     * @param CategoryBrandRule $categoryBrandRule
     */
    public function __construct(
        XMLWriter           $XMLWriter,
        GetProductAttribute $productAttribute,
        PrepareService      $prepareService,
        GetAdditionalData   $additionalData,
        StopRule            $stopRule,
        CategoryBrandRule   $categoryBrandRule
    )
    {
        $this->XMLWriter = $XMLWriter;
        $this->productAttribute = $productAttribute;
        $this->prepareService = $prepareService;
        $this->additionalData = $additionalData;
        $this->stopRule = $stopRule;
        $this->categoryBrandRule = $categoryBrandRule;
    }

    /**
     * @param $fileName
     * @param $exportDir
     * @param $data
     * @param $options
     * @param $storeId
     * @return bool
     * @throws LocalizedException
     */
    public function export($fileName, $exportDir, $data, $options, $storeId): bool
    {
        $products = $data['products'];
        $catLevel1 = $data['categories1'];
        $catLevel2 = $data['categories2'];
        $catLevel3 = $data['categories3'];
        $catLevel4 = $data['categories4'];
        $catLevel5 = $data['categories5'];
        unset($data);

        /**
         * Stop rules
         */
        $this->stopRule->execute($products, $options['export_rules']);

        $this->categoryBrandRule->execute($products, $options['filter_category'], $options['brand_filter_category']);

//        $products
        $this->prepareProductAdditionalData($products, $storeId);


        $cdataTags = ['description'];
        $exportFilePath = $exportDir . '/' . $fileName;
        file_put_contents($exportFilePath, '');

        try {
            $xmlWriter = new XMLWriter();
            $xmlWriter->openMemory();
            $xmlWriter->startDocument('1.0', 'UTF-8');
            $xmlWriter->startElement('yml_catalog');
            $xmlWriter->writeAttribute("date", $this->prepareService->getDateTimeNow());
            $xmlWriter->startElement('shop');

            $xmlWriter->writeElement('name', $options['storename'] ?? '');
            $xmlWriter->writeElement('company', $options['company'] ?? '');
            $xmlWriter->writeElement('url', $options['website_url'] ?? '');

            $xmlWriter->startElement('currencies');
            $xmlWriter->startElement('currency');
            $xmlWriter->writeAttribute("id", $options['currency'] ?? '');
            $xmlWriter->writeAttribute("rate", "1");
            $xmlWriter->endElement();
            $xmlWriter->endElement();

            /**
             *
             */
            $xmlWriter->startElement('categories');

            /**
             * Add main cat // XXX fix
             */
            $xmlWriter->startElement('category');
            $xmlWriter->writeAttribute("id", '30000');
            $xmlWriter->text('Для детей');
            $xmlWriter->endElement();
            /**
             * Add main cat // XXX fix
             */

            /**
             * Add level 1
             */
            $this->addCategories($catLevel1, $xmlWriter, '3000', '30000', 1);

            /**
             * Add level 2
             */
            $this->addCategories($catLevel2, $xmlWriter, '3000', '3000');

            /**
             * Add level 3
             */
            $this->addCategories($catLevel3, $xmlWriter, '3000', '3000');

            /**
             * Add level 4
             */
            $this->addCategories($catLevel4, $xmlWriter, '3000', '3000');

            /**
             * Add level 5
             */
            $this->addCategories($catLevel5, $xmlWriter, '3000', '3000');

            $xmlWriter->endElement();

            $xmlWriter->startElement('offers');

            /**
             * Loop products
             */
            $i = 0;
            foreach ($products as $_product) {
                $v = [];

                if (!isset($_product['product_cat_id']) || $_product['product_cat_id'] == '') {
                    continue;
                }

                /**
                 * Check categories for import
                 */
                if (isset($options['categories_to_export'])) {
                    if (!in_array(intval($_product['product_cat_id']), $options['categories_to_export'])) {
                        continue;
                    }
                }

                $nameProduct = $this->prepareService->prepareText($_product['product_name']);

                $category = '';
                if (isset($catLevel2) && count($catLevel2) > 0) {
                    if (isset($catLevel5) && count($catLevel5) > 0) {
                        if (isset($catLevel5[$_product['product_cat_id']])) {
                            $category = $catLevel5[$_product['product_cat_id']]['name'];
                        }
                    }
                    if (isset($catLevel4) && count($catLevel4) > 0) {
                        if (isset($catLevel4[$_product['product_cat_id']])) {
                            $category = $catLevel4[$_product['product_cat_id']]['name'];
                        }
                    }
                    if (isset($catLevel3) && count($catLevel3) > 0) {
                        if (isset($catLevel3[$_product['product_cat_id']])) {
                            $category = $catLevel3[$_product['product_cat_id']]['name'];
                        }
                    }
                    if (isset($catLevel2[$_product['product_cat_id']])) {
                        $category = $catLevel2[$_product['product_cat_id']]['name'];
                    }
                    if ($category == '') {
                        if (isset($catLevel1) && count($catLevel1) > 0 && isset($catLevel1[$_product['product_cat_id']])) {
                            $category = $catLevel1[$_product['product_cat_id']]['name'];
                        }
                    }
                }

                if (isset($_product['product_model'])) {
                    $model = ($_product['product_model'] != '' ? $_product['product_model'] : $nameProduct);
                } else {
                    $model = $nameProduct;
                }

                $v['url'] = $_product['product_url'];
                $v['barcode'] = $_product['product_sku'];
                $v['price'] = $_product['product_price'] ?? '';
                $v['oldprice'] = $_product['product_price'] ?? '';

                $v['currencyId'] = 'UAH';
                $v['categoryId'] = '3000' . $_product['product_cat_id'];
                $v['name'] = $nameProduct;
                $v['model'] = $model;
                $v['description'] = $this->prepareService->prepareDescription($_product['product_description'], false);
                $v['vendor'] = $this->prepareService->prepareText($_product['product_manufacturer'] ?? '');
                $v['typePrefix'] = $category;

                /**
                 * For promotion
                 */
                $v['sales_notes'] = $options['sales_notes'] ?? '';

                if (!empty($_product['product_name']) and !empty($_product['product_price'])) {

                    $xmlWriter->startElement('offer');
                    $xmlWriter->writeAttribute("id", $_product['product_id']);
                    $xmlWriter->writeAttribute("available", "true");

                    foreach ($v as $fieldName => $fieldValue) {
                        if ($fieldName != '' && !is_null($fieldName) && $fieldValue != '' && !is_null($fieldValue)) {
                            $xmlWriter->startElement($fieldName);
                            if (in_array($fieldName, $cdataTags)) {
                                $xmlWriter->writeCdata($fieldValue);
                            } else {
                                $xmlWriter->text($fieldValue);
                            }
                            $xmlWriter->endElement();
                        }
                    }

                    /**
                     * Images
                     */
                    if (isset($_product['product_media']['productImageAdditional'])) {
                        if (is_array($_product['product_media']['productImageAdditional'])) {
                            foreach ($_product['product_media']['productImageAdditional'] as $fieldName => $fieldValue) {
                                $xmlWriter->startElement('picture');
                                $xmlWriter->text($fieldValue['url']);
                                $xmlWriter->endElement();
                            }
                        }
                    }

                    /**
                     *
                     */
                    if (isset($_product['product_additional']) && count($_product['product_additional']) > 0) {
                        foreach ($_product['product_additional'] as $keyCode => $valueValue) {
                            if ($valueValue['value'] == 'Нет') {
                                continue;
                            }
                            $xmlWriter->startElement('param');
                            $xmlWriter->writeAttribute("name", $valueValue['label']);
                            $xmlWriter->text($this->prepareService->mbUcfirst($valueValue['value']));
                            $xmlWriter->endElement();
                        }
                    }

                    $xmlWriter->endElement();

                    $i++;
                    if (0 == $i % 100) {
                        file_put_contents($exportFilePath, $xmlWriter->flush(true), FILE_APPEND);
                    }
                }
            }
            unset($products, $catLevel1, $catLevel2, $catLevel3);

            $xmlWriter->endElement();
            $xmlWriter->endElement();
            $xmlWriter->endElement();

            file_put_contents($exportFilePath, $xmlWriter->flush(true), FILE_APPEND);

            return true;
        } catch (LocalizedException $e) {
            return false;
        }
    }

    /**
     * @throws LocalizedException
     */
    private function prepareProductAdditionalData(&$data, $storeId)
    {
        foreach ($data as $key => $value) {
            $data[$key]['product_additional'] = $this->additionalData->getAdditionalData($key, $storeId);
        }
    }

    /**
     * @param $categories
     * @param XMLWriter $xmlWriter
     * @param $prefixId
     * @param $prefixParentId
     * @param null $level
     * @return void
     */
    private function addCategories($categories, XMLWriter $xmlWriter, $prefixId, $prefixParentId, $level = null)
    {
        foreach ($categories as $c) {
            $parentId = $prefixParentId . $c['parent_id'];
            if ($level == 1) {
                $parentId = $prefixParentId;
            }

            if (!empty($c['name'])) {
                $c['name'] = trim($c['name']);
            }

            $xmlWriter->startElement('category');
            $xmlWriter->writeAttribute("id", $prefixId . $c['id']);
            $xmlWriter->writeAttribute("parentId", $parentId);
            $xmlWriter->text($c['name']);
            $xmlWriter->endElement();
        }
    }
}
