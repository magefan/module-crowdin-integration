<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types = 1);

namespace Magefan\Crowdin\Block\Adminhtml\Form\Field;

/**
 * Class Mapped return field mapping
 */
class Mapped extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * @var \Magento\Framework\View\Element\BlockInterface
     */
    protected $_groupRenderer;

    /**
     * @var \Magento\Framework\View\Element\BlockInterface
     */
    protected $magentoFieldsRenderer;

    /**
     * @return \Magento\Framework\View\Element\BlockInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getYesNoFields()
    {
        if (!$this->_groupRenderer) {
            $this->_groupRenderer = $this->createBlock(\Magefan\Crowdin\Block\Adminhtml\Form\Field\YesNo::class);
            $this->_groupRenderer->setClass('yesno_fields_select');
        }

        return $this->_groupRenderer;
    }

    /**
     * @return \Magento\Framework\View\Element\BlockInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getTranslatableFiels()
    {
        if (!$this->magentoFieldsRenderer) {
            $this->magentoFieldsRenderer = $this->createBlock(\Magefan\Crowdin\Block\Adminhtml\Form\Field\Magento::class);
            $this->magentoFieldsRenderer->setClass('translatable_fields_select');
        }
        return $this->magentoFieldsRenderer;
    }

    /**
     * @param $object
     * @return \Magento\Framework\View\Element\BlockInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function createBlock($object)
    {
        return $this->getLayout()->createBlock(
            $object,
            '',
            ['data' =>
                ['is_render_to_js_template' => true]
            ]);
    }

    /**
     * Prepare to render
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareToRender()
    {

        $this->addColumn(
            'magento_translatable_fields',
            [
                'label' => __('Translatable Field'),
                'renderer' => $this->_getTranslatableFiels()
            ]
        );
        $this->addColumn(
            'is_crowdin_sync_enabled',
            [
                'label' => __('Enable Sync'),
                'renderer' => $this->_getYesNoFields()
            ]
        );

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return bool
     */
    protected function _isInheritCheckboxRequired(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return false;
    }

    /**
     * Prepare existing row data object
     *
     * @param \Magento\Framework\DataObject $row
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $optionExtraAttr = [];

        $optionExtraAttr['option_' . $this->_getTranslatableFiels()->calcOptionHash($row->getData('magento_translatable_fields'))] = 'selected="selected"';
        $optionExtraAttr['option_' . $this->_getYesNoFields()->calcOptionHash($row->getData('is_crowdin_sync_enabled'))] = 'selected="selected"';

        $row->setData('option_extra_attrs', $optionExtraAttr);
    }

    /**
     * @return \Magento\Framework\Phrase|string
     * @throws \Exception
     */
    protected function _toHtml()
    {
        try {
            return parent::_toHtml();
        } catch (\Mautic\Exception\RequiredParameterMissingException $e) {
            return __(
                '<strong>Cannot Display Translatable Fields</strong>.',
                $this->escapeHtml($e->getMessage())
            );
        }
    }
}
