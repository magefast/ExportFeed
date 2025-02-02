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

class Rozetka
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

    /**
     * @var StopRule
     */
    private StopRule $stopRule;

    /**
     * @var CategoryBrandRule
     */
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
        $this->stopRule->execute($products, $options['export_rules'] ?? []);

//        $products

        $this->categoryBrandRule->execute($products, $options['filter_category'], $options['brand_filter_category']);

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
            $xmlWriter->writeAttribute("id", 'UAH');
            $xmlWriter->writeAttribute("rate", "1");
            $xmlWriter->endElement();

            $xmlWriter->endElement();

            /**
             *
             */
            $xmlWriter->startElement('categories');

            /**
             * Add level 1
             */
            $this->addCategories($catLevel1, $xmlWriter, false);

            /**
             * Add level 2
             */
            $this->addCategories($catLevel2, $xmlWriter);

            /**
             * Add level 3
             */
            $this->addCategories($catLevel3, $xmlWriter);

            /**
             * Add level 4
             */
            $this->addCategories($catLevel4, $xmlWriter);

            /**
             * Add level 5
             */
            $this->addCategories($catLevel5, $xmlWriter);

            $xmlWriter->endElement();

            $xmlWriter->startElement('offers');

            /**
             * Loop products
             */
            $i = 0;
            foreach ($products as $_product) {
                $v = array();

                if (!isset($_product['product_cat_id']) || $_product['product_cat_id'] == '') {
                    continue;
                }

                /**
                 * Check categories for import
                 */
                if (!isset($allovedSku[$_product['product_sku']]) && isset($options['categories_to_export'])) {
                    if (!in_array(intval($_product['product_cat_id']), $options['categories_to_export'])) {
                        continue;
                    }
                }

                $nameProduct = $this->prepareService->prepareText($_product['product_name']);

                if (isset($_product['product_model'])) {
                    $model = ($_product['product_model'] != '' ? $_product['product_model'] : $nameProduct);
                } else {
                    $model = $nameProduct;
                }

                $v['url'] = $_product['product_url'];

                $v['price'] = $_product['product_price'];
                if (isset($_product['product_price_old']) && $_product['product_price_old'] != '') {
                    $v['price_old'] = $_product['product_price_old'];
                }

                /**
                 * Promo
                 */
                if (isset($pricePromo[$_product['product_sku']])) {
                    unset($v['price_old']);
                    $v['price'] = $pricePromo[$_product['product_sku']]['price'];
                    $v['price_promo'] = $pricePromo[$_product['product_sku']]['promo'];
                }

                $v['currencyId'] = 'UAH';
                $v['categoryId'] = $_product['product_cat_id'];
                $v['name'] = $nameProduct;
                $v['stock_quantity'] = intval($_product['product_qty']);
                $v['description'] = $this->prepareService->prepareDescription($_product['product_description'], false);
                $v['vendor'] = $this->prepareService->prepareText($_product['product_manufacturer'] ?? '');

                /**
                 * For promotion
                 */
                $v['sales_notes'] = $options['sales_notes'] ?? '';

                $xmlWriter->startElement('offer');
                $xmlWriter->writeAttribute("id", $_product['product_id']);
                $xmlWriter->writeAttribute("available", "true");
                $xmlWriter->writeAttribute("type", "vendor.model");

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
                if (is_array($_product['product_media']['productImageAdditional'])) {
                    foreach ($_product['product_media']['productImageAdditional'] as $fieldName => $fieldValue) {
                        $xmlWriter->startElement('picture');
                        $xmlWriter->text($fieldValue['url']);
                        $xmlWriter->endElement();
                    }
                }


                $skipNotUnique = [];
                //$_product['product_params'][] = array('value'=>$_product['product_sku'],'label'=>'Артикул');
                if (isset($_product['product_additional']) && is_array($_product['product_additional'])) {
                    foreach ($_product['product_additional'] as $keyCode => $paramValue) {
                        if (isset($skipNotUnique[$paramValue['label']])) {
                            continue;
                        }
                        if (!empty($paramValue['value'])) {
                            $paramValue['value'] = trim($paramValue['value']);
                        }
                        if (isset($paramValue['value']) && $paramValue['value'] != '') {

                            if (!empty($paramValue['value'])) {
                                $paramValue['value'] = trim($paramValue['value']);
                            }

                            if ($paramValue['value'] == '0') {
                                continue;
                            }
                            if ($paramValue['value'] != '') {
                                $xmlWriter->startElement('param');
                                $xmlWriter->writeAttribute('name', $paramValue['label']);
                                $xmlWriter->text($paramValue['value']);
                                $xmlWriter->endElement();
                                $skipNotUnique[$paramValue['label']] = $paramValue['label'];
                            }
                        }
                    }
                }

                $xmlWriter->startElement('param');
                $xmlWriter->writeAttribute("name", 'Артикул');
                $xmlWriter->text($_product['product_sku']);
                $xmlWriter->endElement();

                $xmlWriter->endElement();

                $i++;
                if (0 == $i % 100) {
                    file_put_contents($exportFilePath, $xmlWriter->flush(true), FILE_APPEND);
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
     * @param bool $parentId
     * @return void
     */
    private function addCategories($categories, XMLWriter $xmlWriter, $parentId = true)
    {
        foreach ($categories as $c) {
            if (!empty($c['name'])) {
                $c['name'] = trim($c['name']);
            }

            $xmlWriter->startElement('category');
            $xmlWriter->writeAttribute("id", $c['id']);
            if ($parentId) {
                $xmlWriter->writeAttribute("parentId", $c['parent_id']);
            }
            $xmlWriter->text($c['name']);
            $xmlWriter->endElement();
        }
    }
}
