<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types = 1);

namespace Magefan\Crowdin\Model;

use Magefan\Crowdin\Model\GetTranslationEntitiesList;
use Magefan\Crowdin\Model\Config;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Api\CategoryAttributeRepositoryInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Email\Model\Template as EmailTemplate;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Catalog\Api\ProductAttributeOptionManagementInterface;
use Magento\Store\Model\App\Emulation;

class GetTranslationEntity
{
    /**
     * @var array
     */
    private $allowedStringId = [
        GetTranslationEntitiesList::CMS_PAGE_ENTITY,
        GetTranslationEntitiesList::CMS_BLOCK_ENTITY
    ];

    /**
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param EmailTemplate $emailTemplate
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param Attribute $attributeFactory
     * @param CategoryAttributeRepositoryInterface $categoryAttributeRepository
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     * @param StoreInterface $store
     * @param \Magefan\Crowdin\Model\Config $config
     * @param PageRepositoryInterface $pageRepository
     * @param BlockRepositoryInterface $blockRepository
     * @param ProductAttributeOptionManagementInterface $attributeOptionManagement
     * @param Emulation $emulation
     */
    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory,
        EmailTemplate $emailTemplate,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        Attribute $attributeFactory,
        CategoryAttributeRepositoryInterface $categoryAttributeRepository,
        ProductAttributeRepositoryInterface $productAttributeRepository,
        FilterGroupBuilder $filterGroupBuilder,
        CategoryRepositoryInterface $categoryRepository,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        StoreInterface $store,
        Config $config,
        PageRepositoryInterface $pageRepository,
        BlockRepositoryInterface $blockRepository,
        ProductAttributeOptionManagementInterface $attributeOptionManagement,
        Emulation $emulation
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->attributeFactory = $attributeFactory;
        $this->categoryAttributeRepository = $categoryAttributeRepository;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->store = $store;
        $this->config = $config;
        $this->emailTemplate = $emailTemplate;
        $this->pageRepository = $pageRepository;
        $this->blockRepository = $blockRepository;
        $this->attributeOptionManagement = $attributeOptionManagement;
        $this->emulation = $emulation;
    }
    
    /**
     * @param $id
     * @return array|void
     */
    public function execute($id)
    {
        $response = ['success' => true];
        $entityData = explode('_', $id);
        $type = $entityData[0];

        unset($entityData[0]);

        $id = implode('_', $entityData);

        if (trim((string)$id) === '') {
            return ['message' => 'Missed entity id!', 'success' => false];
        }

        if (!in_array($type, $this->allowedStringId) && !is_numeric($id)) {
            return ['message' => 'Entity id (' . $id . ') for this entity should be numeric!', 'success' => false];
        }

        switch ($type) {
            case GetTranslationEntitiesList::CATEGORY_ENTITY:
                $response = $this->getCategoryData((int)$id);
                break;
            case GetTranslationEntitiesList::PRODUCT_ENTITY:
                $response = $this->getProductData((int)$id);
                break;
            case GetTranslationEntitiesList::EMAIL_ENTITY:
                $response = $this->getEmailTemplateData((int)$id);
                break;
            case GetTranslationEntitiesList::CMS_PAGE_ENTITY:
                $response = $this->getCmsPageData($id);
                break;
            case GetTranslationEntitiesList::CMS_BLOCK_ENTITY:
                $response = $this->getCmsBlockData($id);
                break;
            case GetTranslationEntitiesList::PRODUCT_ATTRIBUTE_ENTITY:
                $response = $this->getProductAttributeData((int)$id);
                break;
        }

        return $response;
    }

    /**
     * @param int $id
     * @return array
     */
    private function getCategoryData(int $id): array
    {
        $data = [];

        $attributes = $this->removeNotAllowedFields($this->getCatalogAttributes($this->categoryAttributeRepository), 'category');

        foreach ($this->storeManager->getStores() as $store) {
            if (!$store->getIsActive()) {
                continue;
            }

            $storeId = $store->getId();
            $category = $this->categoryRepository->get($id, $store->getId());

            foreach ($attributes as $attribute) {
                $data[$storeId][$attribute['code']] = $category->getData($attribute['code']);
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getTranslatableAttributes(): array
    {
        $attributes = [];
        $attributes['category'] = $this->getCatalogAttributes($this->categoryAttributeRepository);
        $attributes['product'] = $this->getCatalogAttributes($this->productAttributeRepository);
        $attributes['email'] = $this->getEmailAttributes();
        $attributes['page'] = $this->getCmsAttributesByType('page');
        $attributes['block'] = $this->getCmsAttributesByType('block');

        return $attributes;
    }

    /**
     * @param int $id
     * @return array
     */
    private function getProductData(int $id): array
    {
        $data = [];

        $attributes = $this->removeNotAllowedFields($this->getCatalogAttributes($this->productAttributeRepository), 'product');

        foreach ($this->storeManager->getStores() as $store) {
            if (!$store->getIsActive()) {
                continue;
            }

            $storeId = $store->getId();
            $product = $this->productRepository->getById($id, false, $storeId);

            foreach ($attributes as $attribute) {
                $data[$storeId][$attribute['code']] = $product->getData($attribute['code']);
            }
        }

        return $data;
    }

    /**
     * @param int $id
     * @return array
     */
    private function getEmailTemplateData(int $id): array
    {
        $data = [];
        $attributes = $this->removeNotAllowedFields($this->getEmailAttributes(), 'email');

        $emailTemplate = $this->emailTemplate->load($id);

        if (!$emailTemplate->getId()) {
            return ['message' => __('Entity with id %1 does not exist!', $id), 'success' => false];
        }

        foreach ($this->storeManager->getStores() as $store) {
            if (!$store->getIsActive()) {
                continue;
            }

            $storeId = $store->getId();

            foreach ($attributes as $attribute) {
                $data[$storeId][$attribute['code']] = $emailTemplate->getData($attribute['code']);
            }
        }

        return $data;
    }

    /**
     * @param string $identifier
     * @return array
     */
    private function getCmsPageData(string $identifier): array
    {
        return $this->getCmsEntityData($this->pageRepository, 'page', $identifier);
    }

    /**
     * @param string $identifier
     * @return array
     */
    private function getCmsBlockData(string $identifier): array
    {
        return $this->getCmsEntityData($this->blockRepository, 'block', $identifier);
    }

    /**
     * @param $repository
     * @param string $entityType
     * @param string $identifier
     * @return array
     */
    private function getCmsEntityData($repository, string $entityType, string $identifier): array
    {

        $stores = [0];
        $data = [];
        $attributes = $this->removeNotAllowedFields($this->getCmsAttributesByType($entityType), $entityType);

        foreach ($this->storeManager->getStores() as $store) {
            //if ($store->getIsActive())
            $stores[$store->getId()] = $store->getId();
        }

        foreach ($stores as $storeId) {
            $cmsEntity = $this->getCmsEntityByIdentifier($identifier, $repository, $storeId);

            if (!$cmsEntity) {
                if (isset($data[0])) {
                    $data[$storeId] = $data[0];
                }

                continue;
            }

            foreach ($attributes as $attribute) {
                $data[$storeId][$attribute['code']] = $cmsEntity->getData($attribute['code']);
            }
        }

        return $data;
    }

    /**
     * @param $identifier
     * @param $repository
     * @param $storeId
     * @return false|mixed
     */
    private function getCmsEntityByIdentifier($identifier, $repository, $storeId)
    {
        $storeFilter = $this->filterBuilder
            ->setField('store_id')
            ->setConditionType('eq')
            ->setValue($storeId)
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

        return reset($cmsEntity);
    }

    /**
     * @param int $id
     * @return array
     */
    private function getProductAttributeData(int $id): array
    {
        $baseOptionLabel = $this->getBaseAttributesOptionLabels($id);

        $data = [];

        foreach ($this->storeManager->getStores() as $store) {
            if (!$store->getIsActive() || !$store->getId()) {
                continue;
            }

            $storeId = $store->getId();

            $this->emulation->startEnvironmentEmulation($storeId);

            $attributeCode = $this->productAttributeRepository->get($id)->getAttributeCode();
            $attributeOptions = $this->attributeOptionManagement->getItems($attributeCode);

            foreach ($attributeOptions as $option) {

                if (!$option->getValue()) {
                    continue;
                }

                $labelInCrowdinForOriginalText = $baseOptionLabel[$option->getValue()];
                $labelInCrowdinForOriginalText = str_replace('&', 'and', $labelInCrowdinForOriginalText);
                $labelInCrowdinForOriginalText = preg_replace("/[^A-Za-z0-9]/", '_', $labelInCrowdinForOriginalText);


                $data[$storeId][$labelInCrowdinForOriginalText] = (string)$option->getLabel();
            }

            $this->emulation->stopEnvironmentEmulation();
        }

        return $data;
    }

    /**
     * @param int $attributeId
     * @return array
     */
    private function getBaseAttributesOptionLabels(int $attributeId): array
    {
        $this->emulation->startEnvironmentEmulation(0);
        $attributeCode = $this->productAttributeRepository->get($attributeId)->getAttributeCode();


        $attributeOptions = $this->attributeOptionManagement->getItems($attributeCode);
        $baseOptionLabel = [];

        foreach ($attributeOptions as $option) {
            if (!$option->getValue()) {
                continue;
            }

            $baseOptionLabel[$option->getValue()] = (string)$option->getLabel();
        }
        $this->emulation->stopEnvironmentEmulation();

        return $baseOptionLabel;
    }

    /**
     * @param $entityType
     * @return array[]
     */
    private function getCmsAttributesByType($entityType): array
    {
        $cmsAttributes = [
            'page' => [
                ['label' => __('Title'), 'code' => 'title', 'type' => 'text'],
                ['label' => __('Content Heading'), 'code' => 'content_heading', 'type' => 'text'],
                ['label' => __('Content'), 'code' => 'content', 'type' => 'html'],
                ['label' => __('Meta Title'), 'code' => 'meta_title', 'type' => 'html'],
                ['label' => __('Meta Description'), 'code' => 'meta_description', 'type' => 'html'],
                ['label' => __('Meta Keywords'), 'code' => 'meta_keywords', 'type' => 'html']
            ],
            'block' => [
                ['label' => __('Title'), 'code' => 'title', 'type' => 'text'],
                ['label' => __('Content'), 'code' => 'content', 'type' => 'html']
            ]
        ];

        return $cmsAttributes[$entityType];
    }

    /**
     * @return array[]
     */
    private function getEmailAttributes(): array
    {
        return  [
            ['label' => __('Template Name'),    'code' => 'template_code',    'type' => 'text'],
            ['label' => __('Template Subject'), 'code' => 'template_subject', 'type' => 'text'],
            ['label' => __('Template Text'),    'code' => 'template_text',    'type' => 'html']
        ];
    }

    /**
     * @param $repository
     * @return array
     */
    private function getCatalogAttributes($repository): array
    {
        $inputFilter = $this->filterBuilder
            ->setField('frontend_input')
            ->setConditionType('in')
            ->setValue(['text', 'textarea'])
            ->create();

        $typeFilter = $this->filterBuilder
            ->setField('backend_type')
            ->setConditionType('in')
            ->setValue(['varchar', 'text'])
            ->create();

        $visibilityFilter = $this->filterBuilder
            ->setField('is_visible')
            ->setConditionType('eq')
            ->setValue(1)
            ->create();

        $inputGroup = $this->filterGroupBuilder->addFilter($inputFilter)->create();
        $typeGroup = $this->filterGroupBuilder->addFilter($typeFilter)->create();
        $visibilityGroup = $this->filterGroupBuilder->addFilter($visibilityFilter)->create();

        $searchCriteria = $this->searchCriteriaBuilder->setFilterGroups([$inputGroup, $typeGroup, $visibilityGroup])->create();
        $attributesList = $repository->getList($searchCriteria)->getItems();

        $attributes = [];

        foreach ($attributesList as $attribute) {
            $type = ($attribute->getFrontendInput() == 'textarea') ? 'html' : 'text';

            $attributes[] = [
                'label' => $attribute->getDefaultFrontendLabel(),
                'code' => $attribute->getAttributeCode() ,
                'type' => $type,
            ];
        }

        return $attributes;
    }

    /**
     * @param array $fields
     * @param string $entityType
     * @return array
     */
    private function removeNotAllowedFields(array $fields, string $entityType): array
    {
        $mappedFields = $this->config->getMappedFields();

        foreach ($fields as $key => $attribute) {

            $attributeKey = $entityType . '/' . $attribute['code'];

            if (isset($mappedFields[$attributeKey]) && $mappedFields[$attributeKey] === '0') {
                unset($fields[$key]);
            }
        }

        return $fields;
    }
}
