<?php
namespace Formula\Reel\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class Categories implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var array
     */
    protected $categoriesTree;

    /**
     * @param CollectionFactory $categoryCollectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CollectionFactory $categoryCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Get options as tree structure for ui-select component
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->getCategoriesTree();
    }

    /**
     * Build categories tree
     *
     * @return array
     */
    protected function getCategoriesTree()
    {
        if ($this->categoriesTree === null) {
            $storeId = $this->storeManager->getStore()->getId();
            $rootCategoryId = $this->storeManager->getStore()->getRootCategoryId();

            $collection = $this->categoryCollectionFactory->create()
                ->setStoreId($storeId)
                ->addAttributeToSelect(['name', 'is_active', 'parent_id'])
                ->addAttributeToFilter('entity_id', ['neq' => \Magento\Catalog\Model\Category::TREE_ROOT_ID]);

            $categoryById = [
                \Magento\Catalog\Model\Category::TREE_ROOT_ID => [
                    'value' => \Magento\Catalog\Model\Category::TREE_ROOT_ID,
                    'optgroup' => []
                ],
            ];

            foreach ($collection as $category) {
                foreach ([$category->getId(), $category->getParentId()] as $categoryId) {
                    if (!isset($categoryById[$categoryId])) {
                        $categoryById[$categoryId] = ['value' => $categoryId, 'optgroup' => []];
                    }
                }

                $categoryById[$category->getId()]['is_active'] = $category->getIsActive();
                $categoryById[$category->getId()]['label'] = $category->getName();
                $categoryById[$category->getId()]['__disableTmpl'] = true;
                $categoryById[$category->getParentId()]['optgroup'][] = &$categoryById[$category->getId()];
            }

            $this->categoriesTree = $categoryById[$rootCategoryId]['optgroup'] ?? [];
        }

        return $this->categoriesTree;
    }
}
