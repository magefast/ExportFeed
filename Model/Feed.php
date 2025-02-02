<?php

namespace Magefast\ExportFeed\Model;

class Feed extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'magefast_exportfeed_feed';

    protected $_cacheTag = 'magefast_exportfeed_feed';

    protected $_eventPrefix = 'magefast_exportfeed_feed';

    protected function _construct()
    {
        $this->_init('Magefast\ExportFeed\Model\ResourceModel\Feed');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        $values = [];

        return $values;
    }
}