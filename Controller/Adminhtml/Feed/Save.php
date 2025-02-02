<?php

namespace Magefast\ExportFeed\Controller\Adminhtml\Feed;

use Exception;
use Magefast\ExportFeed\Api\ExportFeedInterface;
use Magefast\ExportFeed\Controller\Adminhtml\Feed;
use Magefast\ExportFeed\Model\FeedFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use RuntimeException;

class Save extends Feed
{
    /**
     * @param Context $context
     * @param Registry $registry
     * @param FeedFactory $feedFactory
     */
    public function __construct(
        Context     $context,
        Registry    $registry,
        FeedFactory $feedFactory
    )
    {
        parent::__construct($feedFactory, $registry, $context);
    }

    /**
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data = $this->getRequest()->getPost('feed')) {

            $feed = $this->initFeed(false, true);
            $this->prepareData($feed, $data);
//            $this->_eventManager->dispatch(
//                'magefast_exportfeed_feed_prepare_save',
//                ['feed' => $feed, 'request' => $this->getRequest()]
//            );

            try {
                $feed->save();
                $this->messageManager->addSuccess(__('The feed has been saved.'));
                $this->_getSession()->setData('magefast_exportfeed_feed_data', false);
                if ($this->getRequest()->getParam('back')) {
                    $resultRedirect->setPath('magefast_exportfeed/*/edit', ['id' => $feed->getId(), '_current' => true]);
                } else {
                    $resultRedirect->setPath('magefast_exportfeed/*/');
                }
                return $resultRedirect;
            } catch (LocalizedException|RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the Feed.'));
            }
            $this->_getSession()->setData('magefast_exportfeed_feed_data', $data);
            $resultRedirect->setPath('magefast_exportfeed/*/edit', ['id' => $feed->getId(), '_current' => true]);
            return $resultRedirect;
        }
        $resultRedirect->setPath('magefast_exportfeed/*/');
        return $resultRedirect;
    }

    /**
     * @param $feed
     * @param $data
     * @return $this
     */
    protected function prepareData($feed, $data = [])
    {
        if (isset($data['export_rule']) && is_array($data['export_rule'])) {
            $arrayStopRules = [];
            foreach (ExportFeedInterface::EXPORT_STOP_RULES as $rule) {
                if (isset($data['export_rule'][$rule['value']])) {
                    $arrayStopRules[] = $rule['value'];
                }
            }
            $data['export_rules'] = json_encode($arrayStopRules);
        } else {
            $data['export_rules'] = '';
        }

        $formData = $this->getRequest()->getPost();
        $cats = $this->getRequest()->getPost('cats', false);

        /**
         * categories
         */
        $data['categories'] = '';
        if ($cats) {
            $data['categories'] = json_encode($cats);
        }
        /**
         * categories
         */


        /**
         * brand_filter_category
         */
        if ($cats) {
            $brandFilterArray = [];
            foreach ($cats as $c) {
                if (isset($formData['brand-filter-id' . $c])) {
                    $brandFilterArray[$c] = $formData['brand-filter-id' . $c];
                }
            }
            $data['brand_filter_category'] = json_encode($brandFilterArray);
        } else {
            $data['brand_filter_category'] = json_encode(array());
        }
        /**
         * brand_filter_category
         */


        /**
         * price_settings_category
         */
        if ($cats) {
            $priceSettingsFilterArray = [];
            foreach ($cats as $c) {
                if (isset($formData['price-settings-id' . $c])) {
                    if ($formData['price-settings-id' . $c] != '' && $formData['price-settings-id' . $c] != 0) {
                        if ($formData['price-settings-id' . $c] != 0 || $formData['price-settings-id' . $c] != 1) {
                            $priceSettingsFilterArray[$c] = $formData['price-settings-id' . $c];
                        }
                    }
                }
            }
            $data['price_settings_category'] = json_encode($priceSettingsFilterArray);
        } else {
            $data['price_settings_category'] = json_encode([]);
        }
        /**
         * price_settings_category
         */

        $feed->addData($data);

        return $this;
    }

}



/** Set specify field data */
//        $timezone = $this->_objectManager->create('Magento\Framework\Stdlib\DateTime\TimezoneInterface');
//        $data['publish_date'] .= ' ' . $data['publish_time'][0]
//            . ':' . $data['publish_time'][1] . ':' . $data['publish_time'][2];
//        $data['publish_date'] = $timezone->convertConfigTimeToUtc(isset($data['publish_date'])
//            ? $data['publish_date'] : null);
//        $data['modifier_id'] = $this->_auth->getUser()->getId();
//        $data['categories_ids'] = (isset($data['categories_ids']) && $data['categories_ids']) ? explode(
//            ',',
//            $data['categories_ids']
//        ) : [];
//        $data['tags_ids'] = (isset($data['tags_ids']) && $data['tags_ids'])
//            ? explode(',', $data['tags_ids']) : [];
//        $data['topics_ids'] = (isset($data['topics_ids']) && $data['topics_ids']) ? explode(
//            ',',
//            $data['topics_ids']
//        ) : [];
//        if ($post->getCreatedAt() == null) {
//            $data['created_at'] = $this->date->date();
//        }
//$data['updated_at'] = $this->date->date();
