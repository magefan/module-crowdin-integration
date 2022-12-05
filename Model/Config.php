<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types = 1);

namespace Magefan\Crowdin\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;


class Config
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    const INTEGRATION_NAME = 'crowdin';

    /**
     * Extension enabled config path
     */
    const XML_PATH_EXTENSION_ENABLED = 'mf_crowdin_sync_settings/general/enabled';

    const XML_PATH_FIELDS_MAPPING = 'mf_crowdin_sync_settings/mapped/fields';

    const XML_PATH_CATALOG_SYNCHRONIZATION = 'mf_crowdin_sync_settings/catalog_synchronization/synchronize_categories_and_products';

    const XML_PATH_CATALOG_SPECIFIC_SYNCHRONIZATION = 'mf_crowdin_sync_settings/catalog_synchronization/specific_categories_and_products';

    /**
     * Config constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Retrieve true if module is enabled
     *
     * @return bool
     */
    public function isEnabled($storeId = null): bool
    {
        return (bool)$this->getConfig(self::XML_PATH_EXTENSION_ENABLED, $storeId);
    }

    /**
     * @param null $storeId
     * @return array
     */
    public function getMappedFields($storeId = null): array
    {
        return (array)json_decode((string)$this->getConfig(self::XML_PATH_FIELDS_MAPPING, $storeId), true);
    }

    public function getLocaleByStoreId($storeId = null)
    {
        return (string) $this->getConfig('general/locale/code', $storeId);
    }

    /**
     * Retrieve store config value
     * @param string $path
     * @param null $storeId
     * @return mixed
     */
    public function getConfig($path, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param null $storeId
     * @return int
     */
    public function getCatalogSynchronizationOption($storeId = null): int
    {
        return (int)$this->getConfig(self::XML_PATH_CATALOG_SYNCHRONIZATION, $storeId);
    }

    /**
     * @param null $storeId
     * @return array
     */
    public function getCatalogSynchronizationValues($storeId = null)
    {
        return explode(',', $this->getConfig(self::XML_PATH_CATALOG_SPECIFIC_SYNCHRONIZATION, $storeId));
    }
}
