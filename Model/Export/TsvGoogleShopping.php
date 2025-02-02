<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Magefast\ExportFeed\Model\Export;

use Magefast\ExportFeed\Service\CategoryBrandRule;
use Magefast\ExportFeed\Service\StopRule;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Csv as CsvProcessor;

class TsvGoogleShopping
{
    /**
     * @var CsvProcessor
     */
    private CsvProcessor $csvProcessor;

    /**
     * @var StopRule
     */
    private StopRule $stopRule;

    /**
     * @var CategoryBrandRule
     */
    private CategoryBrandRule $categoryBrandRule;

    /**
     * @param CsvProcessor $csvProcessor
     * @param StopRule $stopRule
     * @param CategoryBrandRule $categoryBrandRule
     */
    public function __construct(
        CsvProcessor      $csvProcessor,
        StopRule          $stopRule,
        CategoryBrandRule $categoryBrandRule
    )
    {
        $this->csvProcessor = $csvProcessor;
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
         * Create CSV
         */
        $data = array();
        $data['id'] = 'ID';
        $data['title'] = 'title';
        $data['link'] = 'link';
        $data['image'] = 'Image URL';
        $data['category'] = 'category';
        $data['price'] = 'price';
        $data['sale_price'] = 'sale_price';
        $data['availability'] = 'availability';
        $data['description'] = 'description';
        $data['custom_label_0'] = 'custom_label_0';

        $productsRow[] = $data;

        /**
         * Stop rules
         */
        $this->stopRule->execute($products, $options['export_rules']);
        $this->categoryBrandRule->execute($products, $options['filter_category'], $options['brand_filter_category']);

        try {
            foreach ($products as $p) {
                /**
                 * Skip out of Stock product from Feed
                 */
                if ($p['product_stock_status'] == 'out_of_stock') {
                   // continue;
                }

                $category = '';
                if (isset($catLevel2) && count($catLevel2) > 0) {
                    if (isset($catLevel2[$p['product_cat_id']])) {
                        $category = $catLevel2[$p['product_cat_id']]['name'];
                    }
                    if ($category == '') {
                        if (isset($catLevel1) && count($catLevel1) > 0 && isset($catLevel1[$p['product_cat_id']])) {
                            $category = $catLevel1[$p['product_cat_id']]['name'];
                        }
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

                $data = [];
                $data['id'] = $p['product_sku'];
                $data['title'] = $p['product_name'];
                $data['link'] = $p['product_url'];
                $data['image'] = $imageMain;
                $data['category'] = $category;
                $data['price'] = $p['product_price'] . ' UAH';
                $data['sale_price'] = '';

                if (intval($p['product_price_old']) > intval($p['product_price'])) {
                    $data['price'] = $p['product_price_old'] . ' UAH';
                    $data['sale_price'] = $p['product_price'] . ' UAH';
                }

                $availability = $p['product_stock_status'];
                if ($p['product_stock_status'] == 'back_order') {
                    $availability = 'preorder';
                }

                if ($p['product_stock_status'] == 'in_stock') {
                    $availability = 'in stock';
                }

                if ($p['product_stock_status'] == 'out_of_stock') {
                    $availability = 'out of stock';
                }

                $data['availability'] = $availability;
                $data['description'] = $p['product_description'];

                $customLabel0 = '500';

                if (intval($p['product_price']) > 500 && 2000 >= intval($p['product_price'])) {
                    $customLabel0 = '500_2000';
                }

                if (intval($p['product_price']) > 2000) {
                    $customLabel0 = '2000';
                }

                $data['custom_label_0'] = $customLabel0;

                $productsRow[] = $data;
            }
            unset($products);
            unset($catLevel2);

            /**
             * Write to CSV file
             */
            if (!is_dir($exportDir)) {
                mkdir($exportDir, 0777, true);
            }
            $filePath = $exportDir . '/' . $fileName;

            $this->csvProcessor->setEnclosure('"');
            $this->csvProcessor->setDelimiter('|');
            $this->csvProcessor->appendData($filePath, $productsRow);

            unset($data, $productsRow);

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
