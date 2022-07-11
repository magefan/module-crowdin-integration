<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types = 1);

namespace Magefan\Crowdin\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Context;

class YesNo extends \Magento\Framework\View\Element\Html\Select
{

    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    )
    {
        parent::__construct($context, $data);
    }

    /**
     * @return mixed
     */
    public function getCustomField()
    {
        return [];
    }

    /**
     * @return array|null
     * @throws \Mautic\Exception\RequiredParameterMissingException
     */
    protected function _getYesNoFields()
    {
        return [
             1 => 'yes',
             0 => 'no'
        ];
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * @return string
     * @throws \Mautic\Exception\RequiredParameterMissingException
     */
    protected function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->_getYesNoFields() as $groupId => $groupLabel) {
                $this->addOption($groupId, addslashes($groupLabel));
            }
        }

        return parent::_toHtml();
    }
}
