<?php
/**
 * Products source model for admin forms
 * Returns products in format: "Product Name (ID) (comma separated categories)"
 *
 * @category  Formula
 * @package   Formula\Blog
 */
namespace Formula\Blog\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Products implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @param CollectionFactory $productCollectionFactory
     * @param CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        CollectionFactory $productCollectionFactory,
        CategoryRepositoryInterface $categoryRepository
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Get options
     * Format: "Product Name (ID) (category1, category2, category3)"
     *
     * @return array
     */
    public function toOptionArray()
    {
        $collection = $this->productCollectionFactory->create()
            ->addAttributeToSelect(['name'])
            ->addAttributeToFilter('status', 1)
            ->setOrder('name', 'ASC');
        
        $options = [];
        foreach ($collection as $product) {
            $productId = $product->getId();
            $productName = $product->getName();
            
            // Get product categories
            $categoryNames = [];
            $categoryIds = $product->getCategoryIds();
            
            if (!empty($categoryIds)) {
                foreach ($categoryIds as $categoryId) {
                    try {
                        $category = $this->categoryRepository->get($categoryId);
                        // Skip root categories (level 0 and 1)
                        if ($category->getLevel() > 1) {
                            $categoryNames[] = $category->getName();
                        }
                    } catch (NoSuchEntityException $e) {
                        // Skip if category doesn't exist
                        continue;
                    }
                }
            }
            
            // Format: "Product Name (ID) (category1, category2)"
            $categoriesString = !empty($categoryNames) ? '(' . implode(', ', $categoryNames) . ')' : '(No categories)';
            $label = $productName .' '. $categoriesString;
            
            $options[] = [
                'value' => $productId,
                'label' => $label
            ];
        }
        
        return $options;
    }
}