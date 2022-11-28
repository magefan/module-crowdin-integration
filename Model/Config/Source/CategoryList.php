<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\Crowdin\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;

class CategoryList implements OptionSourceInterface
{
    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var CollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * CategoryList constructor.
     *
     * @param CategoryFactory $categoryFactory
     * @param CollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        CategoryFactory   $categoryFactory,
        CollectionFactory $categoryCollectionFactory
    )
    {
        $this->categoryFactory = $categoryFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function toOptionArray()
    {
        $preparedDataArray = $this->toArray();
        $options = [];
        foreach ($preparedDataArray as $key => $value) {
            $options[] = [
                'value' => $key,
                'label' => $value
            ];
        }

        return $options;
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function toArray()
    {
        $categories = $this->getCategoryCollection();

        $categoryList = [];
        foreach ($categories as $category) {
            $categoryList[$category->getEntityId()] = [
                'name' => $category->getName(),
                'path' => $category->getPath(),
                'cat_id' => $category->getId()
            ];
        }

        $categoryArray = [];
        foreach ($categoryList as $key => $value) {
            if ($path = $this->getCategoryPath($value['path'], $categoryList)) {
                $categoryArray[$key] = $path . ' (ID: ' . $value['cat_id'] . ')';
            }
        }

        asort($categoryArray);

        return $categoryArray;
    }

    /**
     * @return Collection
     * @throws LocalizedException
     */
    public function getCategoryCollection()
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect(['path', 'name']);

        return $collection;
    }

    /**
     * @param $path
     * @param $categoryList
     *
     * @return string
     */
    public function getCategoryPath($path, $categoryList)
    {
        $categoryPath = [];
        $rootCategories = [1, 2];
        $path = explode('/', $path);

        if ($path) {
            foreach ($path as $categoryId) {
                if (!in_array($categoryId, $rootCategories)) {
                    if (!empty($categoryList[$categoryId]['name'])) {
                        $categoryPath[] = $categoryList[$categoryId]['name'];
                    }
                }
            }
        }

        if (!empty($categoryPath)) {
            return implode(' » ', $categoryPath);
        }

        return false;
    }
}
