<?php

namespace Magefast\ExportFeed\Service;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class GetCategoriesData
{
    /**
     * @var array
     */
    private array $categoriesData = [];

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var CategoryCollectionFactory
     */
    private CategoryCollectionFactory $categoryCollectionFactory;

    /**
     * @param StoreManagerInterface $storeManager
     * @param CategoryCollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        StoreManagerInterface     $storeManager,
        CategoryCollectionFactory $categoryCollectionFactory)
    {
        $this->storeManager = $storeManager;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * @throws LocalizedException
     */
    public function getProductCategoriesLevel($storeId, $level): array
    {
        $key = 'storeId' . $storeId . '_level' . $level;
        if (isset($this->categoriesData[$key])) {
            return $this->categoriesData[$key];
        }

        /**
         * Category Level
         */
        $level = $level + 1;

        $categoriesCollection = $this->categoryCollectionFactory->create()
            ->setStore($storeId)
            ->addFieldToFilter('is_active', array('eq' => '1'))
            ->addFieldToFilter('level', array('eq' => $level))
            ->addAttributeToSelect('google_category')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('parent_id');

        $rootCatId = $this->storeManager->getStore($storeId)->getRootCategoryId();

        foreach ($categoriesCollection as $c) {

            if ($c->getId() == $rootCatId) {
                continue;
            }

            $this->categoriesData[$key][$c->getId()] = array(
                'id' => $c->getId(),
                'name' => $c->getName(),
                'google_product_category' => $c->getData('google_category'),
                'parent_id' => $c->getParentId()
            );
        }
        unset($categoriesCollection);

        return $this->categoriesData[$key] ?? [];
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getProductCategories($storeId): array
    {
        $key = $storeId;
        if (isset($this->categoriesData[$key])) {
            return $this->categoriesData[$key];
        }

        /**
         * All categories
         */
        $categories = [];
        $rootCatId = $this->storeManager->getStore($storeId)->getRootCategoryId();

        $categoriesAll = $this->categoryCollectionFactory->create()
            ->setStore($storeId)
            ->addFieldToFilter('is_active', array('eq' => '1'))
            ->addAttributeToFilter('path', array('like' => '1/' . $rootCatId . '/%'))
            ->addAttributeToSelect('path')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('parent_id')
            ->addUrlRewriteToResult();

        foreach ($categoriesAll as $category) {
            $categories[$category->getId()]['name'] = $category->getName();
            $categories[$category->getId()]['path'] = $category->getPath();
            $categories[$category->getId()]['url'] = $category->getUrl();
        }
        unset($categoriesAll);

        foreach ($categories as $categoryId => $categoryValue) {
            $path = explode('/', $categoryValue['path']);
            $string = array();
            $pathIds = array();

            foreach ($path as $pathId) {
                if ($pathId == $rootCatId || $pathId == 1) {
                    continue;
                }

                if (isset($categories[$pathId]['name'])) {
                    $string[] = $categories[$pathId]['name'];
                    $pathIds[] = $pathId;
                }
            }

            $this->categoriesData[$key][$categoryId]['name'] = implode(' > ', $string);
            $this->categoriesData[$key][$categoryId]['url'] = $categoryValue['url'];
            $this->categoriesData[$key][$categoryId]['ids'] = $pathIds;
        }

        return $this->categoriesData[$key] ?? [];
    }

    /**
     * @param $array
     * @return array
     */
    public function prepareProductCategories($array): array
    {
        foreach ($array as $key => $value) {
            $array[$key] = $key;
        }

        return $array;
    }
}
