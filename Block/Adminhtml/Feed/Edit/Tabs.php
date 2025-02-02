<?php

namespace Magefast\ExportFeed\Block\Adminhtml\Feed\Edit;

use Magento\Backend\Block\Widget\Tabs as WidgetTabs;

class Tabs extends WidgetTabs
{

    protected function _construct()
    {
        parent::_construct();
        $this->setId('feed_edit_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Feed Information'));
    }


    protected function _beforeToHtml()
    {
        $this->addTab(
            'feed_info',
            [
                'label' => __('General'),
                'title' => __('General'),
                'content' => $this->getLayout()->createBlock(
                    'Magefast\ExportFeed\Block\Adminhtml\Feed\Edit\Tab\Info'
                )->toHtml(),
                'active' => true
            ]
        );

        $this->addTab(
            'feed_rule',
            [
                'label' => __('Export Feed Rule'),
                'title' => __('Export Feed Rule'),
                'content' => $this->getLayout()->createBlock(
                    'Magefast\ExportFeed\Block\Adminhtml\Feed\Edit\Tab\Rule'
                )->toHtml(),
                'active' => true
            ]
        );

        $this->addTab(
            'feed_categories',
            [
                'label' => __('Categories to Export'),
                'title' => __('Categories to Export'),
                'content' => $this->getLayout()->createBlock(
                    \Magento\Backend\Block\Template::class
                )->setTemplate('Magefast_ExportFeed::categories.phtml')->toHtml(),
                'active' => true
            ]
        );

        $this->addTab(
            'feed_stop',
            [
                'label' => __('Stop Rule'),
                'title' => __('Stop Rule'),
                'content' => $this->getLayout()->createBlock(
                    'Magefast\ExportFeed\Block\Adminhtml\Feed\Edit\Tab\Stop'
                )->toHtml(),
                'active' => true
            ]
        );

        $id = $this->getRequest()->getParam('id', false);
        if($id) {
            $this->addTab(
                'feed_sync',
                [
                    'label' => __('Sync Feed'),
                    'content' => $this->getLayout()->createBlock(
                        \Magento\Backend\Block\Widget\Button::class
                    )->setData(
                        [
                            'label' => __('Create Feed file'),
                            'onclick' => "setLocation('" . $this->getUrl('magefast_exportfeed/feed/sync', ['id'=>$id]) . "')",
                            'class' => 'action-primary scalable',
                            'type' => 'button'
                        ]
                    )->setDataAttribute(
                        []
                    )->toHtml(),
                    'active' => true
                ]
            );
        }

        return parent::_beforeToHtml();
    }
}
