<?php

namespace Magefast\ExportFeed\Service;

use Magefast\ExportFeed\Model\Export as ExportModel;
use Magefast\ExportFeed\Model\FeedFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class Generator
{
    private FeedFactory $collectionFactory;
    private ExportModel $exportFeedModel;

    /**
     * @param FeedFactory $collectionFactory
     * @param ExportModel $exportFeedModel
     */
    public function __construct(
        FeedFactory $collectionFactory,
        ExportModel $exportFeedModel
    )
    {
        $this->collectionFactory = $collectionFactory;
        $this->exportFeedModel = $exportFeedModel;
    }

    /**
     * @return void
     */
    public function runExportAll(): void
    {
        $feeds = $this->collectionFactory->create();
        $feedsCollection = $feeds->getCollection();

        foreach ($feedsCollection as $feed) {
            try {
                $this->exportFeedModel->exportToFile($feed);
            } catch (FileSystemException|NoSuchEntityException|LocalizedException $e) {
            }
        }
    }
}
