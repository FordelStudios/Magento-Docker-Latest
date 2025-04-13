<?php
// app/code/Formula/CategoryBanners/Model/Config/Source/Subcategories.php
namespace Formula\CategoryBanners\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\App\RequestInterface;

class Subcategories implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;
    
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param CollectionFactory $categoryCollectionFactory
     * @param RequestInterface $request
     */
    public function __construct(
        CollectionFactory $categoryCollectionFactory,
        RequestInterface $request
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->request = $request;
    }

    /**
     * Get options for subcategories based on selected parent category
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        $categoryId = null;
        
        // Try to get the category_id from POST data or from the existing entity
        $postParams = $this->request->getParams();
        if (isset($postParams['category_id'])) {
            $categoryId = $postParams['category_id'];
        } else if (isset($postParams['id'])) {
            // If editing existing entity, try to get its category_id
            $id = $postParams['id'];
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $bannerRepository = $objectManager->get(\Formula\CategoryBanners\Model\CategoryBannerRepository::class);
            try {
                $banner = $bannerRepository->getById($id);
                $categoryId = $banner->getCategoryId();
            } catch (\Exception $e) {
                // Category not found, return empty options
            }
        }
        
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
        
        return $options;
    }
}