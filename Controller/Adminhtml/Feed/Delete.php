<?php

namespace Magefast\ExportFeed\Controller\Adminhtml\Feed;

use Exception;
use Magento\Framework\Controller\Result\Redirect;
use Magefast\ExportFeed\Controller\Adminhtml\Feed;

class Delete extends Feed
{

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id = $this->getRequest()->getParam('id')) {
            try {
                $this->feedFactory->create()
                    ->load($id)
                    ->delete();
                $this->messageManager->addSuccess(__('The Feed has been deleted.'));
            } catch (Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $resultRedirect->setPath('magefast_exportfeed/*/edit', ['id' => $id]);
                return $resultRedirect;
            }
        } else {
            $this->messageManager->addError(__('Feed to delete was not found.'));
        }
        $resultRedirect->setPath('magefast_exportfeed/*/');
        return $resultRedirect;
    }
}