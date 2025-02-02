<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Magefast\ExportFeed\Model\Export;

use Magefast\ExportFeed\Service\CategoryBrandRule;
use Magefast\ExportFeed\Service\PrepareService;
use Magefast\ExportFeed\Service\StopRule;
use Magento\Framework\Exception\LocalizedException;
use XMLWriter;


class XmlGoogleShopping
{
    /**
     * @var XMLWriter
     */
    private XMLWriter $XMLWriter;

    /**
     * @var PrepareService
     */
    private PrepareService $prepareService;

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
     * @param PrepareService $prepareService
     * @param StopRule $stopRule
     * @param CategoryBrandRule $categoryBrandRule
     */
    public function __construct(
        XMLWriter         $XMLWriter,
        PrepareService    $prepareService,
        StopRule          $stopRule,
        CategoryBrandRule $categoryBrandRule
    )
    {
        $this->XMLWriter = $XMLWriter;
        $this->prepareService = $prepareService;
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
        /**
         *
         */


        $cdataTags = ['g:description', 'g:product_type', 'g:google_product_category', 'g:title'];
        $exportFilePath = $exportDir . '/' . $fileName;
        file_put_contents($exportFilePath, '');

        try {
            $xmlWriter = new XMLWriter();
            $xmlWriter->openMemory();
            $xmlWriter->startDocument('1.0', 'UTF-8');
            $xmlWriter->startElement('rss');
            $xmlWriter->writeAttribute("xmlns:g", "http://base.google.com/ns/1.0");

            $xmlWriter->writeAttribute("date", $this->prepareService->getDateTimeNow());
            $xmlWriter->startElement('channel');

            $xmlWriter->writeElement('title', $options['storename'] ?? '');
            $xmlWriter->writeElement('link', $options['website_url'] ?? '');
            $xmlWriter->writeElement('g:description', $options['company'] ?? '');

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

                /**
                 * Google Category
                 */
                $google_product_category = '';

                if (isset($catLevel5) && count($catLevel5) > 0) {
                    if (isset($catLevel5[$_product['product_cat_id']])) {
                        $google_product_category = $catLevel5[$_product['product_cat_id']]['google_product_category'];
                    }
                }
                if ($google_product_category == '' && isset($catLevel4) && count($catLevel4) > 0) {
                    if (isset($catLevel4[$_product['product_cat_id']])) {
                        $google_product_category = $catLevel4[$_product['product_cat_id']]['google_product_category'];
                    }
                }
                if ($google_product_category == '' && isset($catLevel3) && count($catLevel3) > 0) {
                    if (isset($catLevel3[$_product['product_cat_id']])) {
                        $google_product_category = $catLevel3[$_product['product_cat_id']]['google_product_category'];
                    }
                }
                if ($google_product_category == '' && isset($catLevel2) && count($catLevel2) > 0) {
                    if (isset($catLevel2[$_product['product_cat_id']])) {
                        $google_product_category = $catLevel2[$_product['product_cat_id']]['google_product_category'];
                    }
                }

                $imageMain = '';

                if (isset($_product['product_media']['productImageAdditional'])) {
                    if (is_array($_product['product_media']['productImageAdditional'])) {
                        foreach ($_product['product_media']['productImageAdditional'] as $fieldName => $fieldValue) {
                            $imageMain = $fieldValue['url'];
                            unset($_product['product_media']['productImageAdditional'][$fieldName]);
                            break;
                        }
                    }
                }

                if (intval($_product['product_price_old']) > intval($_product['product_final_price'])) {
                    $v['g:price'] = $_product['product_price_old_formatted'];
                    $v['g:sale_price'] = $_product['product_final_price_formatted'];
                } else {
                    $v['g:price'] = $_product['product_final_price_formatted'];
                }
                $v['g:id'] = $_product['product_sku'];
                $v['g:title'] = $this->prepareService->prepareText($_product['product_name']);
                $v['g:link'] = $_product['product_url'];
                $v['g:ads_redirect'] = $_product['product_url'];
                $v['g:description'] = $this->prepareService->prepareDescription($_product['product_description'], true);
                $v['g:product_type'] = $google_product_category;
                $v['g:google_product_category'] = $google_product_category;
                $v['g:image_link'] = $imageMain;

                if (is_array($_product['product_media']['productImageAdditional'])) {
                    foreach ($_product['product_media']['productImageAdditional'] as $fieldName => $fieldValue) {
                        $v['g:additional_image_link'][] = $fieldValue['url'];
                    }
                }

                $v['g:condition'] = 'new';
                $v['g:availability'] = 'in_stock';
                $v['g:custom_label_1'] = '';
                $v['g:shipping_weight'] = $this->prepareWeight($_product['product_weight'] ?? null);
                $v['g:mpn'] = $_product['product_sku'];
                $v['g:brand'] = $this->prepareService->prepareText($_product['product_manufacturer'] ?? '');

                if ($this->prepareService->isEanCorrect($_product['product_ean'])) {
                    $v['g:gtin'] = $_product['product_ean'];
                } else {
                    $v['g:identifier_exists'] = 'no';
                }

                if (!empty($_product['product_name']) and !empty($_product['product_price'])) {
                    $xmlWriter->startElement('item');
                    foreach ($v as $fieldName => $fieldValue) {
                        if ($fieldName != '' && !is_null($fieldName) && $fieldValue != '' && !is_null($fieldValue)) {
                            if ($fieldName == 'g:additional_image_link') {
                                foreach ($fieldValue as $img) {
                                    $xmlWriter->startElement('g:additional_image_link');
                                    $xmlWriter->text($img);
                                    $xmlWriter->endElement();
                                }
                                continue;
                            }

                            $xmlWriter->startElement($fieldName);

                            if (in_array($fieldName, $cdataTags)) {
                                $xmlWriter->writeCdata($fieldValue);
                            } else {
                                $xmlWriter->text($fieldValue);
                            }

                            $xmlWriter->endElement();
                        }
                    }

                    $xmlWriter->endElement();

                    $i++;
                    if (0 == $i % 100) {
                        file_put_contents($exportFilePath, $xmlWriter->flush(), FILE_APPEND);
                    }
                }
            }
            unset($products, $catLevel1, $catLevel2, $catLevel3);

            $xmlWriter->endElement();
            $xmlWriter->endElement();

            file_put_contents($exportFilePath, $xmlWriter->flush(true), FILE_APPEND);

            return true;
        } catch (LocalizedException $e) {
            return false;
        }
    }

    private function prepareWeight($weight = null): string
    {
        if ($weight == null) {
            $weight = 1000;
        }

        if (!empty($weight)) {
            $weight = trim($weight);
        }

        if ($weight && $weight == '' && $weight > 1000) {
            $weight = $weight * 0.001;
        } else {
            $weight = 1000 * 0.001;
        }

        return $weight . ' kg';
    }
}
