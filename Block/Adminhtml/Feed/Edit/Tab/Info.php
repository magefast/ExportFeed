<?php

namespace Magefast\ExportFeed\Block\Adminhtml\Feed\Edit\Tab;

use Magefast\ExportFeed\Api\ExportFeedInterface;
use Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Store\Model\System\Store;

class Info extends Generic implements TabInterface
{
    private Store $store;

    public function __construct(
        Context                           $context,
        Registry                          $registry,
        FormFactory                       $formFactory,
        Store $store,
        array                             $data = []
    )
    {
        $this->store = $store;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function getTabLabel()
    {
        return __('Feed Info');
    }

    public function getTabTitle()
    {
        return __('Feed Info');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }

    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('magefast_exportfeed_feed');

        /** @var Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('feed_');
        $form->setFieldNameSuffix('feed');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('General')]
        );


        if ($model->getExportfeedId()) {
            $fieldset->addField(
                'exportfeed_id',
                'hidden',
                ['name' => 'exportfeed_id']
            );
        }

        $fieldset->addField(
            'name',
            'text',
            [
                'name' => 'name',
                'label' => __('Name'),
                'required' => true
            ]
        );

        $fieldset->addField(
            'status',
            'select',
            [
                'name' => 'status',
                'label' => __('Status'),
                'options' => [0 => __('Disabled'), 1 => __('Enabled')],
                'required' => true
            ]
        );

        if (!$this->_storeManager->isSingleStoreMode()) {
            $field = $fieldset->addField(
                'store_id',
                'select',
                [
                    'name' => 'store_id',
                    'label' => __('Store'),
                    'title' => __('Store'),
                    'required' => true,
                    'values' => $this->store->getStoreValuesForForm(false, false)
                ]
            );
            $renderer = $this->getLayout()->createBlock(
                Element::class
            );
            $field->setRenderer($renderer);
        }

        $fieldset->addField(
            'filename',
            'text',
            [
                'name' => 'filename',
                'label' => __('Filename'),
                'required' => true,
                'note' => __('Without file type (ex. file_name)')
            ]
        );

//        $fieldset->addField(
//            'customer_group',
//            'text',
//            [
//                'name' => 'customer_group',
//                'label' => __('Customer Group'),
//                'required' => false
//            ]
//        );

        $fieldset->addField(
            'type',
            'select',
            [
                'name' => 'type',
                'label' => __('Type'),
                'title' => __('Type'),
                'required' => true,
                'values' => $this->getTypeList(__('-- Please Select --'))
            ]
        );

        $fieldset->addField(
            'additional_settings_json',
            'textarea',
            [
                'name' => 'additional_settings_json',
                'label' => __('Additional Settings JSON'),
                'note' => __('JSON format'),
                'required' => false,
                'style' => 'height: 15em;'
            ]
        );

        $fieldset->addField(
            'comment',
            'textarea',
            [
                'name' => 'comment',
                'label' => __('Comment'),
                'required' => false
            ]
        );

        $data = $model->getData();
        $form->setValues($data);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    private function getTypeList($label = false)
    {
        $options = ExportFeedInterface::EXPORT_TYPE;
        if ($label) {
            array_unshift($options, ['value' => '', 'label' => $label]);
        }
        return $options;
    }
}
