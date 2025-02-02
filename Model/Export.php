<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Magefast\ExportFeed\Model;

use Mage;
use Magefast\ExportFeed\Helper\Data;
use Magefast\ExportFeed\Service\GetAllProductsData;
use Magefast\ExportFeed\Service\GetCategoriesData;
use Magefast\ExportFeed\Service\Setting;
use Magento\Customer\Model\Group;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Store\Model\StoreManagerInterface;

class Export extends AbstractModel
{
    public const EXPORT_DIR = 'export';

    public const EXPORT_DEFAULT_FILE_NAME = 'export';
    private GetCategoriesData $categoriesData;
    private GetAllProductsData $productsData;
    private Export\YmlProm $exportYmlProm;
    private Setting $setting;
    private DirectoryList $directorylist;
    private Export\XmlGoogleShopping $xmlGoogleShopping;
    private Export\TsvGoogleShopping $tsvGoogleShopping;
    private Export\Rozetka $rozetka;
    private Group $group;
    private StoreManagerInterface $storeManager;
    private Data $helper;


    public function __construct(
        DirectoryList            $directorylist,
        StoreManagerInterface    $storeManager,
        Group                    $group,
        GetAllProductsData       $productsData,
        GetCategoriesData        $categoriesData,
        Setting                  $setting,
        Data                     $helper,
        Export\YmlProm           $exportYmlProm,
        Export\XmlGoogleShopping $xmlGoogleShopping,
        Export\TsvGoogleShopping $tsvGoogleShopping,
        Export\Rozetka           $rozetka
    )
    {
//        $state->setAreaCode('frontend');
        $this->directorylist = $directorylist;
        $this->storeManager = $storeManager;
        $this->group = $group;
        $this->exportYmlProm = $exportYmlProm;
        $this->xmlGoogleShopping = $xmlGoogleShopping;
        $this->tsvGoogleShopping = $tsvGoogleShopping;
        $this->rozetka = $rozetka;
        $this->productsData = $productsData;
        $this->categoriesData = $categoriesData;
        $this->setting = $setting;
        $this->helper = $helper;
    }

    /**
     * @param Feed $feed
     * @throws FileSystemException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function exportToFile(Feed $feed)
    {
        /**
         * Export dir
         */
        $exportDir = $this->directorylist->getPath('pub') . '/media/' . self::EXPORT_DIR;
        if (!file_exists($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        /**
         * Store
         */
        $storeId = $feed->getData('store_id') ?? null;

        /**
         * Customer Group
         */
        $customerGroupId = null;
        $customerGroupName = $feed->getData('customer_group');
        if ($customerGroupName && $customerGroupName != '') {
            $existingGroup = $this->group->load($customerGroupName, 'customer_group_code');
            if ($existingGroup->getCustomerGroupId() && $existingGroup->getCustomerGroupId() != null) {
                $customerGroupId = $existingGroup->getCustomerGroupId();
            }
        }

        /**
         * FileName
         */
        $fileType = $this->setting->getExportFileType($feed);
        $filename = $feed->getData('filename') . '.' . $fileType ?? self::EXPORT_DEFAULT_FILE_NAME . '.' . $fileType;

        /**
         * Get Product data
         */
        $options = [];
        $data = $this->getProducts($storeId, $options, $customerGroupId);

        if ($feed->getData('type') == 'YmlProm') {
            $exportRules = [];
            if (!empty($feed->getData('export_rules'))) {
                $exportRules = $feed->getData('export_rules');
                $exportRules = json_decode($exportRules, true);
            }

            $additionalSettings = $this->prepareAdditionalSettings($feed->getData('additional_settings_json'));
            $optionsExportFile = [
                'website_url' => 'https://xxx',
                'currency' => 'UAH',
                'sales_notes' => 'Наличные, б/н расчет.',
                'export_rules' => count($exportRules) > 0 ? $exportRules : null,
                'filter_category' => $this->helper->prepareCategoriesId($feed->getData('categories')),
                'brand_filter_category' => $this->helper->prepareBrandFilterId($feed->getData('brand_filter_category'))
            ];
            $optionsExportFile = array_merge($optionsExportFile, $additionalSettings);

            $this->exportYmlProm->export($filename, $exportDir, $data, $optionsExportFile, $storeId);
            unset($model, $data);
        }



        if ($feed->getData('type') == 'XmlGoogleShopping') {
            $exportRules = [];
            if (!empty($feed->getData('export_rules'))) {
                $exportRules = $feed->getData('export_rules');
                $exportRules = json_decode($exportRules, true);
            }

            $additionalSettings = $this->prepareAdditionalSettings($feed->getData('additional_settings_json'));
            $optionsExportFile = [
                'website_url' => 'https://xxx',
                'sales_notes' => '',
                'export_rules' => count($exportRules) > 0 ? $exportRules : null,
                'filter_category' => $this->helper->prepareCategoriesId($feed->getData('categories')),
                'brand_filter_category' => $this->helper->prepareBrandFilterId($feed->getData('brand_filter_category'))
            ];
            $optionsExportFile = array_merge($optionsExportFile, $additionalSettings);

            $this->xmlGoogleShopping->export($filename, $exportDir, $data, $optionsExportFile, $storeId);
            unset($model, $data);
        }

        if ($feed->getData('type') == 'TsvGoogleShopping') {
            $exportRules = [];
            if (!empty($feed->getData('export_rules'))) {
                $exportRules = $feed->getData('export_rules');
                $exportRules = json_decode($exportRules, true);
            }

            $additionalSettings = $this->prepareAdditionalSettings($feed->getData('additional_settings_json'));
            $optionsExportFile = [
                'website_url' => 'https://xxx',
                'sales_notes' => '',
                'export_rules' => count($exportRules) > 0 ? $exportRules : null,
                'filter_category' => $this->helper->prepareCategoriesId($feed->getData('categories')),
                'brand_filter_category' => $this->helper->prepareBrandFilterId($feed->getData('brand_filter_category'))
            ];
            $optionsExportFile = array_merge($optionsExportFile, $additionalSettings);

            $this->tsvGoogleShopping->export($filename, $exportDir, $data, $optionsExportFile, $storeId);
            unset($model, $data);
        }


        if ($feed->getData('type') == 'Rozetka') {
            $exportRules = [];
            if (!empty($feed->getData('export_rules'))) {
                $exportRules = $feed->getData('export_rules');
                $exportRules = json_decode($exportRules, true);
            }

            $additionalSettings = $this->prepareAdditionalSettings($feed->getData('additional_settings_json'));
            $optionsExportFile = [
                'website_url' => 'https://xxx',
                'currency' => 'UAH',
                'sales_notes' => 'Наличные, Visa/Mastercard, б/н расчет, кредит.',
                'export_rules' => count($exportRules) > 0 ? $exportRules : null,
                'filter_category' => $this->helper->prepareCategoriesId($feed->getData('categories')),
                'brand_filter_category' => $this->helper->prepareBrandFilterId($feed->getData('brand_filter_category'))
            ];
            $optionsExportFile = array_merge($optionsExportFile, $additionalSettings);

            $this->rozetka->export($filename, $exportDir, $data, $optionsExportFile, $storeId);
            unset($model, $data);
        }

    }

    /**
     * @param string $options
     * @param $storeId
     * @param null $customerGroupId
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getProducts($storeId, $options = [], $customerGroupId = null): array
    {
        $productsData = $this->productsData->getProductsData($storeId, $customerGroupId);

        return [
            'products' => $productsData,
            'categories1' => $this->categoriesData->getProductCategoriesLevel($storeId, 1),
            'categories2' => $this->categoriesData->getProductCategoriesLevel($storeId, 2),
            'categories3' => $this->categoriesData->getProductCategoriesLevel($storeId, 3),
            'categories4' => $this->categoriesData->getProductCategoriesLevel($storeId, 4),
            'categories5' => $this->categoriesData->getProductCategoriesLevel($storeId, 5),
            'categories_all' => $this->categoriesData->getProductCategories($storeId)
        ];
    }

    /**
     * @param $value
     * @return array
     */
    private function prepareAdditionalSettings($value): array
    {
        $array = [];

        if ($value == '') {
            return $array;
        }

        $value = json_decode($value, true);
        if (is_array($value)) {
            return $value;
        }

        return $array;
    }
}
