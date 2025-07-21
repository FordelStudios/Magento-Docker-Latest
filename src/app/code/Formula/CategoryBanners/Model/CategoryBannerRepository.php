<?php
// app/code/Formula/CategoryBanners/Model/CategoryBannerRepository.php
namespace Formula\CategoryBanners\Model;

use Formula\CategoryBanners\Api\CategoryBannerRepositoryInterface;
use Formula\CategoryBanners\Api\Data\CategoryBannerInterface;
use Formula\CategoryBanners\Model\ResourceModel\CategoryBanner as ResourceCategoryBanner;
use Formula\CategoryBanners\Model\ResourceModel\CategoryBanner\CollectionFactory as CategoryBannerCollectionFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Formula\CategoryBanners\Model\SubcategoryFactory;

class CategoryBannerRepository implements CategoryBannerRepositoryInterface
{
    /**
     * @var ResourceCategoryBanner
     */
    private $resource;

    /**
     * @var CategoryBannerFactory
     */
    private $categoryBannerFactory;

    /**
     * @var CategoryBannerCollectionFactory
     */
    private $categoryBannerCollectionFactory;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var SubcategoryFactory
     */
    private $subcategoryFactory;

    /**
     * @param ResourceCategoryBanner $resource
     * @param CategoryBannerFactory $categoryBannerFactory
     * @param CategoryBannerCollectionFactory $categoryBannerCollectionFactory
     * @param DateTime $dateTime
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param SubcategoryFactory $subcategoryFactory
     */
    public function __construct(
        ResourceCategoryBanner $resource,
        CategoryBannerFactory $categoryBannerFactory,
        CategoryBannerCollectionFactory $categoryBannerCollectionFactory,
        DateTime $dateTime,
        CategoryCollectionFactory $categoryCollectionFactory,
        SubcategoryFactory $subcategoryFactory
    ) {
        $this->resource = $resource;
        $this->categoryBannerFactory = $categoryBannerFactory;
        $this->categoryBannerCollectionFactory = $categoryBannerCollectionFactory;
        $this->dateTime = $dateTime;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->subcategoryFactory = $subcategoryFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function save(CategoryBannerInterface $banner)
    {
        try {
            // Ensure timestamps are set correctly
            if ($banner->getId()) {
                $banner->setUpdatedAt($this->dateTime->gmtDate());
            } else {
                $now = $this->dateTime->gmtDate();
                $banner->setCreatedAt($now);
                $banner->setUpdatedAt($now);
            }
            
            $this->resource->save($banner);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $banner;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($id)
    {
        $banner = $this->categoryBannerFactory->create();
        $this->resource->load($banner, $id);
        if (!$banner->getId()) {
            throw new NoSuchEntityException(__('The category banner with the "%1" ID doesn\'t exist.', $id));
        }
        
        // Load subcategory names
        $this->loadSubcategoryNames($banner);
        
        return $banner;
    }

    /**
     * {@inheritdoc}
     */
    public function getByCategoryId($categoryId)
    {
        $collection = $this->categoryBannerCollectionFactory->create();
        $collection->addFieldToFilter('category_id', $categoryId);
        $collection->addFieldToFilter('is_active', 1);
        
        $banners = $collection->getItems();
        
        // Load subcategory names for each banner
        foreach ($banners as $banner) {
            $this->loadSubcategoryNames($banner);
        }
        
        return $banners;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(CategoryBannerInterface $banner)
    {
        try {
            $this->resource->delete($banner);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($id)
    {
        return $this->delete($this->getById($id));
    }

    /**
     * Load subcategory names for a banner
     *
     * @param CategoryBannerInterface $banner
     * @return void
     */
    private function loadSubcategoryNames(CategoryBannerInterface $banner)
    {
        $subcategories = $banner->getSubcategories();
        if (!empty($subcategories)) {
            $subcategoryIds = is_string($subcategories) ? explode(',', $subcategories) : $subcategories;
            $subcategoryIds = array_filter(array_map('trim', $subcategoryIds));
            
            if (!empty($subcategoryIds)) {
                $categoryCollection = $this->categoryCollectionFactory->create();
                $categoryCollection->addAttributeToSelect('name')
                    ->addFieldToFilter('entity_id', ['in' => $subcategoryIds]);
                
                $subcategoryNames = [];
                foreach ($categoryCollection as $category) {
                    $subcategoryObject = $this->subcategoryFactory->create();
                    $subcategoryObject->setId($category->getId());
                    $subcategoryObject->setName($category->getName());
                    $subcategoryNames[] = $subcategoryObject;
                }
                
                $banner->setSubcategoryNames($subcategoryNames);
            }
        }
    }
}