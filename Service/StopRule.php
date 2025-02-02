<?php

namespace Magefast\ExportFeed\Service;

use Magefast\ExportFeed\Api\ExportFeedInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;

class StopRule
{
    public const MIN_PROFIT = 150;

    /**
     * @var DirectoryList
     */
    private DirectoryList $directoryList;

    /**
     * @param DirectoryList $directoryList
     */
    public function __construct(DirectoryList $directoryList)
    {
        $this->directoryList = $directoryList;
    }

    /**
     * @param array $data
     * @param array|null $exportRule
     * @return void
     * @throws FileSystemException
     */
    public function execute(array &$data, ?array $exportRule)
    {
        if ($exportRule == null) {
            return;
        }

        foreach ($exportRule as $rule) {
            if ($rule == 'outOfStockQtyLow1') {
                $this->outOfStockQtyLow1($data);
            }
            if ($rule == 'imgSizeLow500px') {
                $this->imgSizeLow500px($data);
            }
            if ($rule == 'anyImg') {
                $this->anyImg($data);
            }
            if ($rule == 'maxNameLength') {
                $this->maxNameLength($data);
            }
            if ($rule == 'mixedRuUkrContentName') {
                $this->mixedRuUkrContentName($data);
            }
            if ($rule == 'minProfit') {
                $this->minProfit($data);
            }
            if ($rule == 'rozetkaPrice') {
                $this->rozetkaPrice($data);
            }
        }
    }

    /**
     * @param $data
     * @return void
     */
    private function outOfStockQtyLow1(&$data)
    {
        foreach ($data as $key => $value) {
            $inStock = true;
            if (0 >= $value['product_qty']) {
                $inStock = false;
            }
            if (!$value['product_stock_status']) {
                $inStock = false;
            }
            if (!$inStock) {
                unset($data[$key]);
            }
        }
    }

    /**
     * @param $data
     * @return void
     * @throws FileSystemException
     */
    private function imgSizeLow500px(&$data)
    {
        foreach ($data as $key => $value) {
            if (isset($value['product_media'])) {
                if (isset($value['product_media']['productImage1']['file'])) {
                    if (!$this->imageSizeMore500($value['product_media']['productImage1']['file'])) {
                        $data[$key]['product_media']['productImage1'] = null;
                    }
                }
                if (isset($value['product_media']['productImage2']['file'])) {
                    if (!$this->imageSizeMore500($value['product_media']['productImage2']['file'])) {
                        $data[$key]['product_media']['productImage2'] = null;
                    }
                }
                if (isset($value['product_media']['productImage3']['file'])) {
                    if (!$this->imageSizeMore500($value['product_media']['productImage3']['file'])) {
                        $data[$key]['product_media']['productImage3'] = null;
                    }
                }
                if (isset($value['product_media']['productImage4']['file'])) {
                    if (!$this->imageSizeMore500($value['product_media']['productImage4']['file'])) {
                        $data[$key]['product_media']['productImage4'] = null;
                    }
                }
                if (isset($value['product_media']['productImage5']['file'])) {
                    if (!$this->imageSizeMore500($value['product_media']['productImage5']['file'])) {
                        $data[$key]['product_media']['productImage5'] = null;
                    }
                }
                if (isset($value['product_media']['productImage6']['file'])) {
                    if (!$this->imageSizeMore500($value['product_media']['productImage6']['file'])) {
                        $data[$key]['product_media']['productImage6'] = null;
                    }
                }
            }
        }
    }

    /**
     * @param $file
     * @return bool
     * @throws FileSystemException
     */
    private function imageSizeMore500($file): bool
    {
        $filePath = $this->directoryList->getPath('media') . '/catalog/product' . $file;
        if (file_exists($filePath)) {
            $image_details = getimagesize($filePath);
            $width = $image_details[0];
            $height = $image_details[1];
            if (500 > $width || 500 > $height) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $data
     * @return void
     */
    private function anyImg(&$data)
    {
        foreach ($data as $key => $value) {
            $noImages = true;

            if (isset($value['product_media'])) {
                if (isset($value['product_media']['productImage1']['file'])) {
                    $noImages = false;
                }
                if (isset($value['product_media']['productImage2']['file'])) {
                    $noImages = false;
                }
                if (isset($value['product_media']['productImage3']['file'])) {
                    $noImages = false;
                }
                if (isset($value['product_media']['productImage4']['file'])) {
                    $noImages = false;
                }
                if (isset($value['product_media']['productImage5']['file'])) {
                    $noImages = false;
                }
                if (isset($value['product_media']['productImage6']['file'])) {
                    $noImages = false;
                }
            }

            if ($noImages) {
                unset($data[$key]);
            }
        }
    }

    /**
     * @param $data
     * @return void
     */
    private function maxNameLength(&$data)
    {
        foreach ($data as $key => $value) {
            if (isset($value['product_name'])) {
                $valueStrlen = 0;

                if (!empty($value['product_name'])) {
                    $valueStrlen = strlen($value['product_name']);
                }

                if ($valueStrlen > ExportFeedInterface::MAX_LENGTH_NAME) {
                    //Mage::log($_product['product_sku'], null, 'google_export_skip_length.log');
                    unset($data[$key]);
                }
            }
        }
    }

    /**
     * @param $data
     * @return void
     */
    private function mixedRuUkrContentName(&$data)
    {
        foreach ($data as $key => $value) {
            if (isset($value['product_name'])) {
                $valueName = $value['product_name'];
                $existUkrSymbols = false;
                foreach (ExportFeedInterface::UKR_SYMBOLS_NAME as $s) {
                    if (strpos($valueName, $s) !== false) {
                        $existUkrSymbols = true;
                    }
                }

                if ($existUkrSymbols === true) {
                    unset($data[$key]);
                    //Mage::log($_product['product_sku'], null, 'google_export_skip_symbols.log');
                }
            }
        }
    }

    /**
     * @param $data
     * @return void
     */
    private function minProfit(&$data)
    {
        foreach ($data as $key => $value) {
            if ($value['product_min_profit'] != '') {
                $profitValue = floatval($value['product_min_profit']);
                if (self::MIN_PROFIT > $profitValue) {
                    unset($data[$key]);
                }
            } else {
                unset($data[$key]);
            }
        }
    }

    /**
     * @param $data
     * @return void
     */
    private function rozetkaPrice(&$data)
    {
        foreach ($data as $key => $value) {
            if ($value['product_rozetka_price'] != '') {
                $rozetkaPrice = floatval($value['product_rozetka_price']);
                if (0 >= $rozetkaPrice) {
                    unset($data[$key]);
                }
            } else {
                unset($data[$key]);
            }
        }
    }
}
