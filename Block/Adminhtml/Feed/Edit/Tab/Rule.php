<?php

namespace Magefast\ExportFeed\Block\Adminhtml\Feed\Edit\Tab;

use Magefast\ExportFeed\Api\ExportFeedInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;

class Rule extends Generic implements TabInterface
{
    protected $_feedStatus;

    public function __construct(
        Context      $context,
        Registry     $registry,
        FormFactory  $formFactory,
        array        $data = []
    )
    {
        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function getTabLabel()
    {
        return __('Export Feed Rules');
    }

    public function getTabTitle()
    {
        return __('Export Feed Rules');
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
            ['legend' => __('Export Feed Rules')]
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
