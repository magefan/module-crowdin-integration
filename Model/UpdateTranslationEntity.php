<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types = 1);

namespace Magefan\Crowdin\Model;

use Magefan\Crowdin\Model\GetTranslationEntitiesList;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Cms\Model\PageFactory;
use Magento\Cms\Model\BlockFactory;
use Magento\Store\Model\App\Emulation;
use Magento\Email\Model\ResourceModel\Template as EmailTemplateResourceModel;
use Magento\Email\Model\Template as EmailTemplate;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Catalog\Api\ProductAttributeOptionManagementInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Cms\Api\GetPageByIdentifierInterface;
use Magento\Cms\Api\GetBlockByIdentifierInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;

class UpdateTranslationEntity
{
    /**
     * @var array
     */
    private $allowedStringId = [
        GetTranslationEntitiesList::CMS_PAGE_ENTITY,
        GetTranslationEntitiesList::CMS_BLOCK_ENTITY
    ];

    /**
     * @var null
     */
    private $storeId = null;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var Emulation
     */
    private $emulation;

    /**
     * @var EmailTemplateResourceModel
     */
    private $emailTemplateResourceModel;

    /**
     * @var EmailTemplate
     */
    private $emailTemplate;

    /**
     * @var PageRepositoryInterface
     */
    private $pageRepository;

    /**
     * @var BlockRepositoryInterface
     */
    private $blockRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var GetPageByIdentifierInterface
     */
    private $getPageByIdentifier;

    /**
     * @var GetBlockByIdentifierInterface
     */
    private $getBlockByIdentifier;

    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * @var BlockFactory
     */
    private $blockFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var FilterGroupBuilder
     */
    private $filterGroupBuilder;

    /**
     * @var GetTranslationEntity
     */
    private $getTranslationEntity;

    /**
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ProductRepositoryInterface $productRepository
     * @param Emulation $emulation
     * @param EmailTemplateResourceModel $emailTemplateResourceModel
     * @param EmailTemplate $emailTemplate
     * @param PageRepositoryInterface $pageRepository
     * @param BlockRepositoryInterface $blockRepository
     * @param StoreManagerInterface $storeManager
     * @param ResourceConnection $resource
     * @param GetPageByIdentifierInterface $getPageByIdentifier
     * @param GetBlockByIdentifierInterface $getBlockByIdentifier
     * @param PageFactory $pageFactory
     * @param BlockFactory $blockFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param GetTranslationEntity $getTranslationEntity
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        ProductRepositoryInterface $productRepository,
        Emulation $emulation,
        EmailTemplateResourceModel $emailTemplateResourceModel,
        EmailTemplate $emailTemplate,
        PageRepositoryInterface $pageRepository,
        BlockRepositoryInterface $blockRepository,
        StoreManagerInterface $storeManager,
        ResourceConnection $resource,
        GetPageByIdentifierInterface $getPageByIdentifier,
        GetBlockByIdentifierInterface $getBlockByIdentifier,
        PageFactory $pageFactory,
        BlockFactory $blockFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        GetTranslationEntity $getTranslationEntity
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->emulation = $emulation;
        $this->emailTemplateResourceModel = $emailTemplateResourceModel;
        $this->emailTemplate = $emailTemplate;
        $this->pageRepository = $pageRepository;
        $this->blockRepository = $blockRepository;
        $this->storeManager = $storeManager;
        $this->resource = $resource;
        $this->getPageByIdentifier = $getPageByIdentifier;
        $this->getBlockByIdentifier = $getBlockByIdentifier;
        $this->pageFactory = $pageFactory;
        $this->blockFactory = $blockFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->getTranslationEntity = $getTranslationEntity;
    }


    /**
     * @param $id
     * @param $data
     * @return array
     */
    public function execute($id, $data)
    {
        $response = ['success' => true];
        $entityData = explode('_', $id);
        $type = $entityData[0];

        unset($entityData[0]);

        $id = implode('_', $entityData);
        $data = json_decode($data, true);

        if (trim((string)$id) === '') {
            return ['message' => 'Missed entity id!', 'success' => false];
        }

        if (!in_array($type, $this->allowedStringId) && !is_numeric($id)) {
            return ['message' => 'Entity id (' . $id . ') for this entity should be numeric!', 'success' => false];
        }

        if (!is_array($data) || !isset($data['store_id'])) {
            return ['message' => __('Missed store_id!'), 'success' => false];
        }

        $this->storeId = (int)$data['store_id'];
        unset($data['store_id']);

        $data = $this->getTranslatableData($type, $data);

        if (!$data) {
            // do not save (and do not duplicate/create CMS entities) when there is nothing to translate
            return ['message' => __('Nothing to update.'), 'success' => true];
        }

        $this->emulation->startEnvironmentEmulation($this->storeId, 'adminhtml');

        try {
            switch ($type) {
                case GetTranslationEntitiesList::CATEGORY_ENTITY:
                    $this->updateCategoryData((int)$id, $data);
                    break;
                case GetTranslationEntitiesList::PRODUCT_ENTITY:
                    $this->updateProductData((int)$id, $data);
                    break;
                case GetTranslationEntitiesList::EMAIL_ENTITY:
                    $this->updateEmailTemplateData((int)$id, $data);
                    break;
                case GetTranslationEntitiesList::CMS_PAGE_ENTITY:
                    $this->updateCmsPageData($id, $data);
                    break;
                case GetTranslationEntitiesList::CMS_BLOCK_ENTITY:
                    $this->updateCmsBlockData($id, $data);
                    break;
                case GetTranslationEntitiesList::PRODUCT_ATTRIBUTE_ENTITY:
                    $this->updateProductAttributeData((int)$id, $data);
                    break;
            }
        } finally {
            $this->emulation->stopEnvironmentEmulation();
        }

        return $response;
    }

    /**
     * Only fields that are sent to Crowdin, with scalar values.
     * Product attribute data is keyed by option labels, options are matched in updateProductAttributeData()
     *
     * @param string $type
     * @param array $data
     * @return array
     */
    private function getTranslatableData(string $type, array $data): array
    {
        if ($type !== GetTranslationEntitiesList::PRODUCT_ATTRIBUTE_ENTITY) {
            $allowedCodes = array_column($this->getTranslationEntity->getAllowedAttributes($type), 'code');
            $data = array_intersect_key($data, array_flip($allowedCodes));
        }

        $allowNull = $type !== GetTranslationEntitiesList::PRODUCT_ATTRIBUTE_ENTITY;

        return array_filter($data, function ($value) use ($allowNull) {
            return is_scalar($value) || ($allowNull && $value === null);
        });
    }

    /**
     * @param int $id
     * @param $data
     */
    private function updateCategoryData(int $id, $data)
    {
        $category = $this->categoryRepository->get($id, $this->storeId);

        foreach ($data as $attributeCode => $attributeValue) {
            $category->setData($attributeCode, $attributeValue);

            /**
             * https://github.com/magento/magento2/issues/31083
             */
            $category->getResource()->saveAttribute($category, $attributeCode);
        }

        //$this->categoryRepository->save($category);
    }

    /**
     * @param int $id
     * @param array $data
     */
    private function updateProductData(int $id, array $data)
    {
        $product = $this->productRepository->getById($id, false, $this->storeId);

        foreach ($data as $attributeCode => $attributeValue) {
            $product->setData($attributeCode, $attributeValue);

            /**
             * https://github.com/magento/magento2/issues/31083
             */
            $product->getResource()->saveAttribute($product, $attributeCode);
        }

        //$this->productRepository->save($product);
    }

    /**
     * @param string $identifier
     * @param array $data
     */
    private function updateCmsPageData(string $identifier, array $data)
    {
        $page = $this->getCmsEntityByIdentifier($identifier, $this->pageRepository, $this->pageFactory);

        // if page saved on all store views
        if (in_array(0, $page->getStoreId())) {
            // create new page
            $page = $this->duplicateCmsPage($page, $this->storeId);
        }

        foreach ($data as $attributeCode => $attributeValue) {
            $page->setData($attributeCode, $attributeValue);
        }

        $this->pageRepository->save($page);
    }

    /**
     * @param $page
     * @param $storeId
     * @return mixed
     */
    private function duplicateCmsPage($page, $storeId)
    {
        $storeIds = [];

        foreach ($this->storeManager->getStores() as $store) {
            $storeIds[$store->getId()] = $store->getId();
        }

        unset($storeIds[$storeId]);

        $this->pageRepository->save($this->setCmsPageStores($page, $storeIds));

        $newPage = $this->pageFactory
            ->create(['data' => $page->getData()])
            ->setId(null)
            //->setIsActive(0)
            ->setCreationTime(null)
            ->setUpdateTime(null);

        return $this->pageRepository->save($this->setCmsPageStores($newPage, [$storeId]));
    }

    /**
     * Page store assignment: cms_page_store is saved from "stores", URL rewrites are generated from "store_id"
     *
     * @param $page
     * @param array $storeIds
     * @return mixed
     */
    private function setCmsPageStores($page, array $storeIds)
    {
        return $page->setStoreId($storeIds)->setStores($storeIds);
    }

    /**
     * @param string $identifier
     * @param array $data
     */
    private function updateCmsBlockData(string $identifier, array $data)
    {
        $block = $this->getCmsEntityByIdentifier($identifier, $this->blockRepository, $this->blockFactory);

        // if block saved on all store views
        if (in_array(0, $block->getStoreId())) {
            // create new page
            $block = $this->duplicateCmsBlock($block, $this->storeId);
        }

        foreach ($data as $attributeCode => $attributeValue) {
            $block->setData($attributeCode, $attributeValue);
        }

        $this->blockRepository->save($block);
    }

    /**
     * @param $block
     * @param $storeId
     * @return mixed
     */
    private function duplicateCmsBlock($block, $storeId)
    {
        $storeIds = [];

        foreach ($this->storeManager->getStores() as $store) {
            $storeIds[$store->getId()] = $store->getId();
        }

        unset($storeIds[$storeId]);
        $block->setStores($storeIds);
        $this->blockRepository->save($block);

        $newBlock = $this->blockFactory
            ->create(['data' => $block->getData()])
            ->setId(null)
            //->setIsActive(0)
            ->setCreationTime(null)
            ->setUpdateTime(null)
            ->setStores([$storeId]);

        return $this->blockRepository->save($newBlock);
    }

    /**
     * @param $identifier
     * @param $repository
     * @return false|mixed
     */
    private function getCmsEntityByIdentifier($identifier, $repository, $factory)
    {
        $storeFilter = $this->filterBuilder
            ->setField('store_id')
            ->setConditionType('eq')
            ->setValue($this->storeId)
            ->create();

        $identifierFilter = $this->filterBuilder
            ->setField('identifier')
            ->setConditionType('eq')
            ->setValue($identifier)
            ->create();

        $storeGroup = $this->filterGroupBuilder->addFilter($storeFilter)->create();
        $identifierGroup = $this->filterGroupBuilder->addFilter($identifierFilter)->create();

        $searchCriteria = $this->searchCriteriaBuilder->setFilterGroups([$storeGroup, $identifierGroup])->create();
        $cmsEntity = $repository->getList($searchCriteria)->getItems();

        if (!$cmsEntity) {
            // in case if entity does not exist on specific store, need to check if it assigned to all stores
            $storeFilter = $this->filterBuilder
                ->setField('store_id')
                ->setConditionType('eq')
                ->setValue(0)
                ->create();

            $storeGroup = $this->filterGroupBuilder->addFilter($storeFilter)->create();
            $searchCriteria = $this->searchCriteriaBuilder->setFilterGroups([$storeGroup, $identifierGroup])->create();
            $cmsEntity = $repository->getList($searchCriteria)->getItems();
        }

        if (!$cmsEntity) {
            $cmsEntity = $factory->create(
                    ['data' =>
                        [
                            'title' => $identifier,
                            'identifier' => $identifier
                        ]
                    ]
            )
            ->setId(null)
            ->setCreationTime(null)
            ->setUpdateTime(null)
            ->setStoreId([$this->storeId]);

            return $repository->save($cmsEntity);
        }

        return reset($cmsEntity);
    }

    /**
     * @param $id
     * @param array $data
     */
    private function updateEmailTemplateData($id, array $data)
    {
        $emailTemplate = $this->emailTemplate->load($id);

        foreach ($data as $attributeCode => $attributeValue) {
            $emailTemplate->setData($attributeCode, $attributeValue);
        }

        $this->emailTemplateResourceModel->save($emailTemplate);
    }

    /**
     * Options by their label, then by the key the label gets in Crowdin; a Crowdin key that several options share is found only by an exact label
     *
     * @param array $options
     * @return array
     */
    private function getOptionsByLabelInCrowdin(array $options): array
    {
        $result = [];
        $shared = [];

        foreach ($options as $label => $option) {
            $labelInCrowdin = $this->getTranslationEntity->getLabelInCrowdin((string)$label);

            if (isset($result[$labelInCrowdin])) {
                $shared[$labelInCrowdin] = true;
            }

            $result[$labelInCrowdin] = $option;
        }

        return $options + array_diff_key($result, $shared);
    }

    /**
     * @param $attributeId
     * @param array $data
     */
    private function updateProductAttributeData($attributeId, array $data)
    {
        $connection = $this->resource->getConnection();

        /**
         *   [Black] => Array
         *       (
         *           [value] => Black
         *           [store_id] => 0
         *           [option_id] => 49
         *           [attribute_id] => 93
         *       )
         *   [Blue] => Array
         *       (
         *           [value] => Blue
         *           [store_id] => 0
         *           [option_id] => 50
         *           [attribute_id] => 93
         *       )
         */
        $selectAttributesData = $connection->select()
            ->from(
                ['option' => $this->resource->getTableName('eav_attribute_option')],
                [
                    'value' => 'option_value.value',
                    'store_id' => 'option_value.store_id',
                    'option_id' => 'option.option_id',
                    'attribute_id' => 'option.attribute_id',
                ]
            )
            ->joinLeft(
                ['option_value' => $this->resource->getTableName('eav_attribute_option_value')],
                'option.option_id = option_value.option_id',
                []
            )->where('option.attribute_id = ?', $attributeId);

        $attributeOriginalValues = $this->getOptionsByLabelInCrowdin($connection->fetchAssoc(
            $selectAttributesData->where('option_value.store_id = ?', Store::DEFAULT_STORE_ID)
        ));

        foreach ($data as $originalAttributeVal => $translatedAttributeVal) {
            if (isset($attributeOriginalValues[$originalAttributeVal])) {
                $optionId = $attributeOriginalValues[$originalAttributeVal]['option_id'];

                $valueId = $connection->fetchOne(
                    $connection->select()
                        ->from(
                            [$connection->getTableName('eav_attribute_option_value')],
                            ['value_id'])
                        ->where('store_id = ?', $this->storeId)
                        ->where('option_id = ?', $optionId)
                );

                $attributeData =  [
                    'value_id' => (int)$valueId,
                    'option_id' => $optionId,
                    'store_id' =>  $this->storeId,
                    'value' => $translatedAttributeVal
                ];

                if (!$valueId) {
                    unset($attributeData['value_id']);
                }

                $connection->insertOnDuplicate($this->resource->getTableName('eav_attribute_option_value'), $attributeData);
            }
        }
    }
}
