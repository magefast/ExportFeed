<?php

namespace Magefast\ExportFeed\Controller\Adminhtml\Feed;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magefast\ExportFeed\Controller\Adminhtml\Feed;
use Magefast\ExportFeed\Model\FeedFactory;

class Edit extends Feed
{

    public $resultPageFactory;

    public function __construct(
        Context $context,
        Registry $registry,
        FeedFactory $feedFactory,
        PageFactory $resultPageFactory
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($feedFactory, $registry, $context);
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page|Redirect|Page
     */
    public function execute()
    {
        $feed = $this->initFeed();
        $duplicate = $this->getRequest()->getParam('duplicate');
        if ($duplicate) {
            $feed->setData('duplicate', true);
        }
        if (!$feed) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*');
            return $resultRedirect;
        }
        $data = $this->_session->getData('magefast_exportfeed_feed', true);


        if (!empty($data)) {
            $feed->setData($data);
        }
        $this->coreRegistry->register('magefast_exportfeed_feed', $feed);
        /** @var \Magento\Backend\Model\View\Result\Page|Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Magefast_ExportFeed::feed');
        $resultPage->getConfig()->getTitle()->set(__('Feed'));
        $title = $feed->getId() && !$feed->getDuplicate() ? $feed->getName() : __('New Feed');
        $resultPage->getConfig()->getTitle()->prepend($title);
        return $resultPage;
    }
}