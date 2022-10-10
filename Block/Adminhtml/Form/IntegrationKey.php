<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types = 1);

namespace Magefan\Crowdin\Block\Adminhtml\Form;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magefan\Crowdin\Model\Config;

class IntegrationKey  extends \Magento\Config\Block\System\Config\Form\Field
{
    const BUTTON_TEMPLATE = 'integration_key.phtml';

    /**
     * @var IntegrationServiceInterface
     */
    protected $integrationService;

    /**
     * @var null
     */
    private $integration = null;

    /**
     * @param Context $context
     * @param IntegrationServiceInterface $integrationService
     * @param array $data
     */
    public function __construct(
        Context $context,
        IntegrationServiceInterface $integrationService,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->integrationService = $integrationService;
    }

    /**
     * Set template to itself
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::BUTTON_TEMPLATE);
        }
        return $this;
    }

    /**
     * Render button
     *
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->_toHtml() . $this->getIntegrationKey();

    }

    /**
     * @return bool
     */
    public function isIntegrationActivated(): bool
    {
        return (bool)$this->getIntegration()->getStatus();
    }

    /**
     * @return mixed
     */
    private function getIntegration()
    {
        if (is_null($this->integration)) {
            $this->integration = $this->integrationService->get($this->integrationService->findByName(Config::INTEGRATION_NAME)->getId());
        }

        return $this->integration;
    }

    /**
     * @return string
     */
    private function getIntegrationKey(): string
    {
        $result = '';
        $integrationId = $this->integrationService->findByName(Config::INTEGRATION_NAME)->getId();
        $integration = $this->integrationService->get($integrationId);

        if ($this->isIntegrationActivated()) {
            $key = implode(
                '-',
                [
                    $integration->getData('consumer_key'),
                    $integration->getData('consumer_secret'),
                    $integration->getData('token'),
                    $integration->getData('token_secret'),
                ]
            );

            $result = '<input type="text" style="display:none;" value="' . $key . '" id="integration-key">';
        }

        return $result;
    }
}
