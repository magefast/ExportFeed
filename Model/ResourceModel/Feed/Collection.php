<?php

namespace Magefast\ExportFeed\Model\ResourceModel\Feed;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'exportfeed_id';
    protected $_eventPrefix = 'magefast_exportfeed_feed_collection';
    protected $_eventObject = 'feed_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Magefast\ExportFeed\Model\Feed', 'Magefast\ExportFeed\Model\ResourceModel\Feed');
    }

}