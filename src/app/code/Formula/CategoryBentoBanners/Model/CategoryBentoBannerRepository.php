<?php
namespace Formula\CategoryBentoBanners\Model;

use Formula\CategoryBentoBanners\Api\CategoryBentoBannerRepositoryInterface;
use Formula\CategoryBentoBanners\Api\Data\CategoryBentoBannerInterface;
use Formula\CategoryBentoBanners\Api\Data\CategoryBentoBannerSearchResultsInterface;
use Formula\CategoryBentoBanners\Api\Data\CategoryBentoBannerSearchResultsInterfaceFactory;
use Formula\CategoryBentoBanners\Model\CategoryBentoBannerFactory;
use Formula\CategoryBentoBanners\Model\ResourceModel\CategoryBentoBanner as ResourceCategoryBentoBanner;
use Formula\CategoryBentoBanners\Model\ResourceModel\CategoryBentoBanner\CollectionFactory as CategoryBentoBannerCollectionFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Psr\Log\LoggerInterface;

class CategoryBentoBannerRepository implements CategoryBentoBannerRepositoryInterface
{
    protected $resource;
    protected $bentoBannerFactory;
    protected $bentoBannerCollectionFactory;
    protected $searchResultsFactory;
    protected $collectionProcessor;
    protected $categoryRepository;
    protected $logger;

    public function __construct(
        ResourceCategoryBentoBanner $resource,
        CategoryBentoBannerFactory $bentoBannerFactory,
        CategoryBentoBannerCollectionFactory $bentoBannerCollectionFactory,
        CategoryBentoBannerSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        CategoryRepositoryInterface $categoryRepository,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->bentoBannerFactory = $bentoBannerFactory;
        $this->bentoBannerCollectionFactory = $bentoBannerCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->categoryRepository = $categoryRepository;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function save(CategoryBentoBannerInterface $bentoBanner)
    {
        $this->logger->info('Repository Save: Starting save method', ['banner_data' => $bentoBanner->getData()]);
        try {
            $this->logger->info('Repository Save: Before resource save');
            $this->resource->save($bentoBanner);
            $this->logger->info('Repository Save: After resource save', ['banner_id' => $bentoBanner->getId()]);
        } catch (\Exception $exception) {
            $this->logger->error('Repository Save: Exception in save', ['exception' => $exception->getMessage(), 'trace' => $exception->getTraceAsString()]);
            throw new CouldNotSaveException(__(
                'Could not save the bento banner: %1',
                $exception->getMessage()
            ));
        }
        $this->logger->info('Repository Save: Returning saved banner', ['banner_id' => $bentoBanner->getId()]);
        return $bentoBanner;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($bentoBannerId)
    {
        $bentoBanner = $this->bentoBannerFactory->create();
        $this->resource->load($bentoBanner, $bentoBannerId);
        if (!$bentoBanner->getId()) {
            throw new NoSuchEntityException(__('Bento Banner with id "%1" does not exist.', $bentoBannerId));
        }
        return $bentoBanner;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(SearchCriteriaInterface $criteria)
    {
        $collection = $this->bentoBannerCollectionFactory->create();

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(CategoryBentoBannerInterface $bentoBanner)
    {
        try {
            $bentoBannerModel = $this->bentoBannerFactory->create();
            $this->resource->load($bentoBannerModel, $bentoBanner->getId());
            $this->resource->delete($bentoBannerModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the bento banner: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($bentoBannerId)
    {
        return $this->delete($this->getById($bentoBannerId));
    }

    /**
     * {@inheritdoc}
     */
    public function getByCategoryId($categoryId)
    {
        $collection = $this->bentoBannerCollectionFactory->create();
        $collection->addFieldToFilter('category_id', $categoryId);
        $collection->addFieldToFilter('is_active', 1);

        $items = [];
        foreach ($collection as $item) {
            try {
                $category = $this->categoryRepository->get($item->getCategoryId());
                $item->setCategoryName($category->getName());
            } catch (\Exception $e) {
                $item->setCategoryName('Unknown');
            }
            $items[] = $item;
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllBentoBanners()
    {
        $collection = $this->bentoBannerCollectionFactory->create();
        $collection->addFieldToFilter('is_active', 1);

        $items = [];
        foreach ($collection as $item) {
            try {
                $category = $this->categoryRepository->get($item->getCategoryId());
                $item->setCategoryName($category->getName());
            } catch (\Exception $e) {
                $item->setCategoryName('Unknown');
            }
            $items[] = $item;
        }

        return $items;
    }
}