<?php
// app/code/Formula/CategoryBanners/Controller/Adminhtml/CategoryBanner/Subcategories.php
namespace Formula\CategoryBanners\Controller\Adminhtml\CategoryBanner;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;

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
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CollectionFactory $categoryCollectionFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
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
            $collection = $this->categoryCollectionFactory->create();
            $collection->addAttributeToSelect('name')
                ->addAttributeToFilter('parent_id', $categoryId)
                ->addAttributeToFilter('is_active', 1)
                ->setOrder('position', 'ASC');
                
            foreach ($collection as $category) {
                $options[] = [
                    'value' => $category->getId(),
                    'label' => $category->getName()
                ];
            }
        }
        
        return $result->setData(['options' => $options]);
    }
}