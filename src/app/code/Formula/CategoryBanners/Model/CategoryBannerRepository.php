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
     * @param ResourceCategoryBanner $resource
     * @param CategoryBannerFactory $categoryBannerFactory
     * @param CategoryBannerCollectionFactory $categoryBannerCollectionFactory
     * @param DateTime $dateTime
     */
    public function __construct(
        ResourceCategoryBanner $resource,
        CategoryBannerFactory $categoryBannerFactory,
        CategoryBannerCollectionFactory $categoryBannerCollectionFactory,
        DateTime $dateTime
    ) {
        $this->resource = $resource;
        $this->categoryBannerFactory = $categoryBannerFactory;
        $this->categoryBannerCollectionFactory = $categoryBannerCollectionFactory;
        $this->dateTime = $dateTime;
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
        
        return $collection->getItems();
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
}