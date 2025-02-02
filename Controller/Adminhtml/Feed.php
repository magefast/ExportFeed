<?php

namespace Magefast\ExportFeed\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magefast\ExportFeed\Model\FeedFactory;

abstract class Feed extends Action
{
    const ADMIN_RESOURCE = 'Magefast_ExportFeed::feed';

    public $feedFactory;
    public $coreRegistry;

    public function __construct(
        FeedFactory $feedFactory,
        Registry $coreRegistry,
        Context $context
    )
    {
        $this->feedFactory = $feedFactory;
        $this->coreRegistry = $coreRegistry;
        parent::__construct($context);
    }


    protected function initFeed($register = false, $isSave = false)
    {
        $feedId = (int)$this->getRequest()->getParam('id');

        if ($this->getRequest()->getParam('exportfeed_id')) {
            $feedId = $this->getRequest()->getParam('exportfeed_id');
        }


        $duplicate = $this->getRequest()->getParam('duplicate');
        $feed = $this->feedFactory->create();
        if ($feedId) {
            if (!$isSave || !$duplicate) {
                $feed->load($feedId);
                if (!$feed->getId()) {
                    $this->messageManager->addErrorMessage(__('This feed no longer exists.'));
                    return false;
                }
            }
        }

        if ($register) {
            $this->coreRegistry->register('magefast_exportfeed_feed', $feed);
        }
        return $feed;
    }

}