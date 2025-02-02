<?php

namespace Magefast\ExportFeed\Block\Adminhtml\Feed\Edit\Tab;

use Magefast\ExportFeed\Api\ExportFeedInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;

class Stop extends Generic implements TabInterface
{
    public function __construct(
        Context     $context,
        Registry    $registry,
        FormFactory $formFactory,
        array       $data = []
    )
    {
        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function getTabLabel()
    {
        return __('Stop Rules');
    }

    public function getTabTitle()
    {
        return __('Stop Rules');
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
        $data = $model->getData();

        /** @var Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('feed_');
        $form->setFieldNameSuffix('feed');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Stop Rules')]
        );

        $fieldset->addField(
            'stop_sku',
            'textarea',
            [
                'name' => 'stop_sku',
                'label' => __('Stop SKUs'),
                'note' => __('comma delimeter'),
                'required' => false,
                'style' => 'height: 15em;'
            ]
        );

        $fieldset->addField(
            'stop_manufacturers',
            'textarea',
            [
                'name' => 'stop_manufacturers',
                'label' => __('Stop Manufacturers'),
                'note' => __('comma delimeter'),
                'required' => false,
                'style' => 'height: 15em;'
            ]
        );

        $rulesActive = isset($data['export_rules']) && json_decode($data['export_rules'], true) ?? [];
        foreach (ExportFeedInterface::EXPORT_STOP_RULES as $rule) {
            $fieldset->addField(
                'export_rule_' . $rule['value'],
                'checkbox',
                [
                    'name' => 'export_rule[' . $rule['value'] . ']',
                    'label' => __($rule['name']),
                    'value' => 1,
                    'checked' => is_array($rule['value']) && in_array($rule['value'], $rulesActive),
                    'note' => __($rule['label'])
                ]
            );
        }

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
