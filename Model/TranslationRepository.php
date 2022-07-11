<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types = 1);

namespace Magefan\Crowdin\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magefan\Crowdin\Api\TranslationRepositoryInterface;
use Magefan\Crowdin\Model\GetTranslationEntitiesList;
use Magefan\Crowdin\Model\GetTranslationEntity;
use Magefan\Crowdin\Model\UpdateTranslationEntity;
use Magefan\Crowdin\Model\Config;
use Magento\Store\Model\StoreManagerInterface;

class TranslationRepository implements TranslationRepositoryInterface
{
    /**
     * @var \Magefan\Crowdin\Model\GetTranslationEntitiesList
     */
    private $getTranslationEntitiesList;

    /**
     * @param \Magefan\Crowdin\Model\GetTranslationEntitiesList $getTranslationEntitiesList
     * @param \Magefan\Crowdin\Model\GetTranslationEntity $getTranslationEntity
     * @param \Magefan\Crowdin\Model\UpdateTranslationEntity $updateTranslationEntity
     * @param StoreManagerInterface $storeManager
     * @param \Magefan\Crowdin\Model\Config $config
     */
    public function __construct(
        GetTranslationEntitiesList $getTranslationEntitiesList,
        GetTranslationEntity $getTranslationEntity,
        UpdateTranslationEntity $updateTranslationEntity,
        StoreManagerInterface $storeManager,
        Config $config
    ) {
        $this->getTranslationEntitiesList = $getTranslationEntitiesList;
        $this->getTranslationEntity = $getTranslationEntity;
        $this->updateTranslationEntity = $updateTranslationEntity;
        $this->_storeManager = $storeManager;
        $this->config = $config;
    }

    /**
     * @param string $storeId
     * @return mixed
     */
    public function getEntitiesList($storeId)
    {
        if (!$this->config->isEnabled()) {
            return __('Extension doesn\'t enabled!');
        }
        ini_set('max_execution_time', '0');

        return $this->getTranslationEntitiesList->execute($storeId);

    }

    /**
     * @return array
     */
    public function getStoresList()
    {
        if (!$this->config->isEnabled()) {
            return __('Extension doesn\'t enabled!');
        }

        $options = [];

        foreach ($this->_storeManager->getStores() as $key => $value) {
            $options[] = [
                'id' => $value->getId(),
                'name' => $value->getName(),
                'locale' => $this->config->getLocaleByStoreId($value->getId()),
            ];
        }

        return $options;
    }

    /**
     * @param string $id
     * @return mixed
     */
    public function getEntity($id)
    {
        if (!$this->config->isEnabled()) {
            return __('Extension doesn\'t enabled!');
        }

        return $this->getTranslationEntity->execute($id);
    }

    /**
     * @param string $id
     * @param string $data
     * @return mixed
     */
    public function updateEntity(string $id, string $data)
    {
        if (!$this->config->isEnabled()) {
            return __('Extension doesn\'t enabled!');
        }

        return $this->updateTranslationEntity->execute($id, $data);
    }
}
