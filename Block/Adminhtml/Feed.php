<?php

namespace Magefast\ExportFeed\Block\Adminhtml;

class Feed extends \Magento\Backend\Block\Widget\Grid\Container
{

    protected function _construct()
    {
        $this->_controller = 'adminhtml_feed';
        $this->_blockGroup = 'Magefast_ExportFeed';
        $this->_headerText = __('Feed');
        $this->_addButtonLabel = __('Create New');
        parent::_construct();
    }

}