<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types = 1);

namespace Magefan\Crowdin\Block\Adminhtml\System\Config\Form;


class Info extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $moduleList;

    /**
     * Info constructor.
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->moduleList = $moduleList;
    }

    /**
     * Return info block html
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $useUrl = true;
        $m = $this->moduleList->getOne($this->getModuleName());
        $html = '<div style="padding:10px;background-color:#f8f8f8;border:1px solid #ddd;margin-bottom:7px;">
            ' . $this->escapeHtml($this->getModuleTitle()) . ' v' . $this->escapeHtml($m['setup_version']) . ' was developed by ';
        if ($useUrl) {
            $html .= '<a href="' . $this->escapeHtml($this->getModuleUrl()) . '" target="_blank">Magefan</a>';
        } else {
            $html .= '<strong>Magefan</strong>';
        }

        $html .= '<br/><br/>';
        $html .= '<a href="https://crowdin.com?utm_source=http://magefan.com&utm_medium=referral&utm_campaign=magefan" target="_blank">Crowdin</a>';
        $html .= ' is a cloud-based localization management software with 2M+ user accounts and 130K+ localization projects that helps teams and companies reach new markets. People from over 160 countries use Crowdin to translate & localize their software, mobile and desktop apps, games, websites, help articles, marketing communication, and other content.';
        $html .= '</div>';

        return $html;
    }

    /**
     * Return extension url
     * @return string
     */
    protected function getModuleUrl()
    {
        return 'https://mage' . 'fan.com/magento2-extensions?utm_source=m2admin_crowdin_config&utm_medium=link&utm_campaign=regular';
    }

    /**
     * Return extension title
     * @return string
     */
    protected function getModuleTitle()
    {
        return 'Crowdin Integration Extension';
    }
}
