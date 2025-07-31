<?php
// app/code/Formula/CategoryBanners/Controller/Adminhtml/CategoryBanner/Subcategories.php
namespace Formula\CategoryBanners\Controller\Adminhtml\CategoryBanner;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Model\CategoryRepository;

class Subcategories extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Formula_CategoryBanners::manage';

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    
    /**
     * @var CollectionFactory
     */
    protected $categoryCollectionFactory;
    
    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CollectionFactory $categoryCollectionFactory
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CollectionFactory $categoryCollectionFactory,
        CategoryRepository $categoryRepository
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Get subcategories for a given parent category
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $categoryId = $this->getRequest()->getParam('category_id');
        $result = $this->resultJsonFactory->create();
        
        $options = [];
        
        if ($categoryId) {
            $options = $this->getNestedSubcategories($categoryId);
        }
        
        return $result->setData(['options' => $options]);
    }
    
    /**
     * Get nested subcategories with proper indentation
     *
     * @param int $categoryId
     * @return array
     */
    private function getNestedSubcategories($categoryId)
    {
        $options = [];
        $this->appendNestedCategories($options, $categoryId, 0);
        return $options;
    }
    
    /**
     * Recursively append categories with their children
     *
     * @param array &$options Reference to options array
     * @param int $parentId Parent category ID
     * @param int $level Current nesting level
     * @return void
     */
    private function appendNestedCategories(&$options, $parentId, $level)
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'is_active'])
            ->addAttributeToFilter('parent_id', $parentId)
            ->addAttributeToFilter('is_active', 1)
            ->setOrder('position', 'ASC');
            
        foreach ($collection as $category) {
            // Create indentation based on level
            $prefix = str_repeat('— ', $level) . ($level > 0 ? '' : '');
            
            $options[] = [
                'value' => $category->getId(),
                'label' => $prefix . $category->getName()
            ];
            
            // Recursively append children
            $this->appendNestedCategories($options, $category->getId(), $level + 1);
        }
    }
}