<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types = 1);

namespace Magefan\Crowdin\Model;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Email\Model\ResourceModel\Template\CollectionFactory as EmailCollectionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Api\CategoryAttributeRepositoryInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Api\BlockRepositoryInterface;

class GetTranslationEntitiesList
{

    const CATEGORIES = 'categories';
    const PRODUCTS = 'products';
    const EMAILS = 'emails';
    const CMS_PAGES = 'pages';
    const CMS_BLOCKS = 'blocks';
    const PRODUCT_ATTRIBUTES = 'product-attributes';

    const CATEGORY_ENTITY = 'catalog-category';
    const PRODUCT_ENTITY = 'catalog-product';
    const EMAIL_ENTITY = 'email';
    const PRODUCT_ATTRIBUTE_ENTITY = 'product-attribute';
    const CMS_PAGE_ENTITY = 'cms-page';
    const CMS_BLOCK_ENTITY = 'cms-block';

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var
     */
    protected $storeId;

    const ENTITY_TYPES = [
        self::CATEGORIES => self::CATEGORY_ENTITY,
        self::PRODUCTS   => self::PRODUCT_ENTITY,
        self::EMAILS     => self::EMAIL_ENTITY,
        self::CMS_PAGES  => self::CMS_PAGE_ENTITY,
        self::CMS_BLOCKS => self::CMS_BLOCK_ENTITY,
        self::PRODUCT_ATTRIBUTES => self::PRODUCT_ATTRIBUTE_ENTITY,
    ];

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var EmailCollectionFactory
     */
    protected $emailCollectionFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var Attribute
     */
    protected $attributeFactory;

    /**
     * @var PageRepositoryInterface
     */
    protected $pageRepository;

    /**
     * @var BlockRepositoryInterface
     */
    protected $blockRepository;

    /**
     * @var Config
     */
    private  $config;

    /**
     * @var \string[][]
     */
    private $entitiesList = [
        [
            'name' => 'Products',
            'id' => self::PRODUCTS
        ],
        [
            'name' => 'Email Templates',
            'id' => self::EMAILS
        ],
        [
            'name' => 'Product Attributes',
            'id' => self::PRODUCT_ATTRIBUTES
        ],
        [
            'name' => 'CMS Pages',
            'id' => self::CMS_PAGES
        ],
        [
            'name' => 'CMS Block',
            'id' => self::CMS_BLOCKS
        ]
    ];

    /**
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param ProductRepository $productRepository
     * @param EmailCollectionFactory $emailCollectionFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param Attribute $attributeFactory
     * @param CategoryAttributeRepositoryInterface $categoryAttributeRepository
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param PageRepositoryInterface $pageRepository
     * @param BlockRepositoryInterface $blockRepository
     * @param Config $config
     */
    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory,
        ProductRepository $productRepository,
        EmailCollectionFactory $emailCollectionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        Attribute $attributeFactory,
        CategoryAttributeRepositoryInterface $categoryAttributeRepository,
        ProductAttributeRepositoryInterface $productAttributeRepository,
        FilterGroupBuilder $filterGroupBuilder,
        PageRepositoryInterface $pageRepository,
        BlockRepositoryInterface $blockRepository,
        Config $config
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->productRepository = $productRepository;
        $this->emailCollectionFactory = $emailCollectionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->attributeFactory = $attributeFactory;
        $this->categoryAttributeRepository = $categoryAttributeRepository;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->pageRepository = $pageRepository;
        $this->blockRepository = $blockRepository;
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function execute($storeId): array
    {
        $this->storeId = $storeId;

        $this->addCategoriesEntities();
        $this->addProductsEntities();
        $this->addEmailsEntities();
        $this->addCmsPagesEntities();
        $this->addCmsBlocksEntities();
        $this->addProductAttributesEntities();

        return (array) $this->entitiesList;
    }

    private function addCategoriesEntities()
    {

        $categoryCollection = $this->categoryCollectionFactory->create()->addAttributeToSelect('name');

        if ($option = $this->config->getCatalogSynchronizationOption()){

            $categoriesIds = $this->config->getCatalogSynchronizationValues();
            $categoryCollection->addAttributeToFilter('entity_id', array($option != 1 ? 'nin' : 'in' => $categoriesIds));
        }

        foreach ($categoryCollection as $item) {
            $isRootCategory = ((int)$item->getLevel() === 0);
            $parentId = (((int)$item->getLevel()) === 1) ? self::CATEGORY_ENTITY : self::CATEGORY_ENTITY. '_' . $item->getParentId();

            $category = [
                'name' => $isRootCategory ? __('Categories') : $item->getName(),
                'id' =>   $isRootCategory ? self::CATEGORY_ENTITY : self::CATEGORY_ENTITY . '_' . $item->getId(),
                'parentId' => $parentId,
                'type' => 'html'
            ];

            if ($isRootCategory) {
                unset($category['parentId']);
            }

            $this->entitiesList[] = $category;
        }
    }

    private function addProductsEntities()
    {
        $filters = [];
        $filters[] = $this->filterBuilder
            ->setField('store_id')
            ->setConditionType('eq')
            ->setValue(0)
            ->create();

        if ($option = $this->config->getCatalogSynchronizationOption()){

            $categoriesIds = $this->config->getCatalogSynchronizationValues();
            $categoryFilter = $this->filterBuilder->setField('category_id')
                ->setValue($categoriesIds)
                ->setConditionType($option != 1 ? 'nin' : 'in')
                ->create();
            $filters[] = $categoryFilter;
        }

        $searchCriteria = $this->searchCriteriaBuilder->addFilters($filters)->create();

        $this->addEntityToResponse(self::PRODUCTS, $this->productRepository->getList($searchCriteria)->getItems());
    }

    /**
     * @param string $type
     * @param $collection
     */
    private function addEntityToResponse(string $type, $collection)
    {
        foreach ($collection as $item) {
            $itemId = self::ENTITY_TYPES[$type] . '_' . $item->getId();
            $this->entitiesList[] = [
                'name' => $item->getName(),
                'id' => $itemId,
                'parentId' => $type,
                'type' => 'html',
            ];
        }
    }

    private function addEmailsEntities()
    {
        $emailCollection = $this->emailCollectionFactory->create();

        foreach ($emailCollection->getItems() as $templates) {
            $this->entitiesList[] = [
                'name' => $templates->getTemplateCode(),
                'id' => self::EMAIL_ENTITY . '_' . $templates->getTemplateId(),
                'parentId' => self::EMAILS,
                'type' => 'html'
            ];
        }
    }

    private function addCmsPagesEntities()
    {
        $this->addCmsEntity($this->pageRepository, self::CMS_PAGE_ENTITY, self::CMS_PAGES);
    }

    private function addCmsBlocksEntities()
    {
        $this->addCmsEntity($this->blockRepository, self::CMS_BLOCK_ENTITY, self::CMS_BLOCKS);
    }

    private function addCmsEntity($repository, $cmsEntityType, $parentId)
    {
        $cmsEntitiesForAllStores = $this->getCmsEntitiesByStore($repository, 0);
        $cmsEntitiesForCurrentStore = $this->getCmsEntitiesByStore($repository, $this->storeId);
        $cmsEntities = [];

        foreach (array_merge($cmsEntitiesForAllStores, $cmsEntitiesForCurrentStore) as $cmsEntity) {
            $cmsEntities[$cmsEntity->getIdentifier()] = [
                'name' => $cmsEntity->getTitle(),
                'id' => $cmsEntityType . '_' . $cmsEntity->getIdentifier(),
                'parentId' => $parentId,
                'type' => 'html'
            ];
        }

        foreach ($cmsEntities as $cmsEntity) {
            $this->entitiesList[] = $cmsEntity;
        }
    }

    /**
     * @param $repository
     * @param $storeId
     * @return mixed
     */
    private function getCmsEntitiesByStore($repository, $storeId)
    {
        $this->searchCriteriaBuilder->addFilters(
            [
                $this->filterBuilder
                    ->setField('store_id')
                    ->setConditionType('eq')
                    ->setValue($storeId)
                    ->create()
            ]
        );

        return $repository->getList($this->searchCriteriaBuilder->create())->getItems();
    }

    private function addProductAttributesEntities()
    {
        //$productAttributesList = $this->productAttributeRepository->getList($this->searchCriteriaBuilder->create())->getItems();
        $this->getProductAttributes($this->productAttributeRepository);
    }

    /**
     * Return attributes that can be translated and not already in product entity
     * @param $repository
     */
    public function getProductAttributes($repository)
    {
        $inputFilter = $this->filterBuilder
            ->setField('frontend_input')
            ->setConditionType('in')
            ->setValue(['multiselect', 'select'])
            ->create();

        $isUserDefinedFilter = $this->filterBuilder
            ->setField('is_user_defined')
            ->setConditionType('eq')
            ->setValue(1)
            ->create();

        $inputGroup = $this->filterGroupBuilder->addFilter($inputFilter)->create();
        $isUserDefinedGroup = $this->filterGroupBuilder->addFilter($isUserDefinedFilter)->create();

        $searchCriteria = $this->searchCriteriaBuilder->setFilterGroups([$inputGroup, $isUserDefinedGroup])->create();
        $attributesList = $repository->getList($searchCriteria)->getItems();

        foreach ($attributesList as $attribute) {
            $this->entitiesList[] = [
                'name' => $attribute->getDefaultFrontendLabel() ?: $attribute->getAttributeCode() . ' [label not set]',
                'id' => self::PRODUCT_ATTRIBUTE_ENTITY . '_' . $attribute->getId(),
                'parentId' => self::PRODUCT_ATTRIBUTES,
                'type' => 'html'
            ];
        }
    }
}
