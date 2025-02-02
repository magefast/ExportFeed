<?php

namespace Magefast\ExportFeed\Controller\Adminhtml\Feed;

use Magefast\ExportFeed\Controller\Adminhtml\Feed;
use Magefast\ExportFeed\Model\Export;
use Magefast\ExportFeed\Model\FeedFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;

class Sync extends Feed
{
    /**
     * @var Export
     */
    private Export $export;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param FeedFactory $feedFactory
     * @param Export $export
     */
    public function __construct(
        Context     $context,
        Registry    $registry,
        FeedFactory $feedFactory,
        Export      $export
    )
    {
        $this->export = $export;
        parent::__construct($feedFactory, $registry, $context);
    }

    /**
     * @return ResponseInterface|Redirect|ResultInterface|void
     * @throws FileSystemException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $feed = $this->initFeed();

        if (!$feed) {
            die('---');
        }

        $this->export->exportToFile($feed);

        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data = $this->getRequest()->getPost('feed')) {
            $this->_getSession()->setData('magefast_exportfeed_feed_data', $data);
            $resultRedirect->setPath('magefast_exportfeed/*/edit', ['id' => $feed->getId(), '_current' => true]);
            return $resultRedirect;
        }
        $resultRedirect->setPath('magefast_exportfeed/*/');
        return $resultRedirect;
    }

    /**
     * @throws NoSuchEntityException
     * @throws FileSystemException
     * @throws LocalizedException
     */
    public function syncOne($feed): void
    {
        $this->export->exportToFile($feed);
    }
}
