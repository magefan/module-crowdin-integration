<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types = 1);

namespace Magefan\Crowdin\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Integration\Model\ConfigBasedIntegrationManager;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Integration\Model\Integration;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magefan\Crowdin\Model\Config;
use Magefan\Crowdin\Model\GetTranslationEntity;

class InstallData implements InstallDataInterface
{
    /**
     * @var ConfigBasedIntegrationManager
     */
    private $integrationManager;

    /**
     * @param ConfigBasedIntegrationManager $integrationManager
     * @param WriterInterface $configWriter
     * @param GetTranslationEntity $translationEntity
     */
    public function __construct(
        ConfigBasedIntegrationManager $integrationManager,
        WriterInterface $configWriter,
        GetTranslationEntity $translationEntity
    ) {
        $this->integrationManager = $integrationManager;
        $this->configWriter = $configWriter;
        $this->translationEntity = $translationEntity;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->createCrowdinIntegrationApiKeys();
        $this->presetTranslatableAttributes();
    }

    private function presetTranslatableAttributes()
    {
        $presetValue = [];

        foreach ($this->translationEntity->getTranslatableAttributes() as $key => $translatableAttributes) {
            foreach ($translatableAttributes as $translatableAttribute) {
                $presetValue[$key .'/'. $translatableAttribute['code']] = 1;
            }
        }

        $this->configWriter->save(Config::XML_PATH_FIELDS_MAPPING, json_encode($presetValue), $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
    }
    
    private function createCrowdinIntegrationApiKeys()
    {
        /**
         * Keep resource the same as in api.xml!!!
         */
        $integrations = [
            Config::INTEGRATION_NAME => [
                Integration::NAME => 'Crowdin Integration',
                Integration::EMAIL => '',
                Integration::ENDPOINT => '',
                Integration::IDENTITY_LINK_URL => '',
                'resource' => [
                    'Magefan_Crowdin::config_integration',
                    'Magento_Cms::save_design'
                ]
            ],
        ];

        $this->integrationManager->processConfigBasedIntegrations($integrations);
    }
}
