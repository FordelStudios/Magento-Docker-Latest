<?php
/**
 * Blog repository
 *
 * @category  Formula
 * @package   Formula\Blog
 */
namespace Formula\Blog\Model;

use Formula\Blog\Api\BlogRepositoryInterface;
use Formula\Blog\Api\Data\BlogInterface;
use Formula\Blog\Model\ResourceModel\Blog as BlogResource;
use Formula\Blog\Model\ResourceModel\Blog\CollectionFactory as BlogCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;

class BlogRepository implements BlogRepositoryInterface
{
    /**
     * @var BlogResource
     */
    private $resource;

    /**
     * @var BlogFactory
     */
    private $blogFactory;

    /**
     * @var BlogCollectionFactory
     */
    private $blogCollectionFactory;

    /**
     * @var SearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @param BlogResource $resource
     * @param BlogFactory $blogFactory
     * @param BlogCollectionFactory $blogCollectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param RequestInterface $request
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     */
    public function __construct(
        BlogResource $resource,
        BlogFactory $blogFactory,
        BlogCollectionFactory $blogCollectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        RequestInterface $request,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder
    ) {
        $this->resource = $resource;
        $this->blogFactory = $blogFactory;
        $this->blogCollectionFactory = $blogCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->request = $request;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
    }

    /**
     * Save blog.
     *
     * @param BlogInterface $blog
     * @return BlogInterface
     * @throws CouldNotSaveException
     */
    public function save(BlogInterface $blog)
    {
        try {
            // Set created_at for new entities only (not having an ID yet)
            if (!$blog->getId()) {
                $blog->setCreatedAt(date('Y-m-d H:i:s'));
            }
            
            // Always update the updated_at timestamp
            $blog->setUpdatedAt(date('Y-m-d H:i:s'));

            $this->resource->save($blog);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the blog post: %1', $exception->getMessage()),
                $exception
            );
        }
        return $blog;
    }

    /**
     * Get blog by ID.
     *
     * @param int $blogId
     * @return BlogInterface
     * @throws NoSuchEntityException
     */
    public function getById($blogId)
    {
        $blog = $this->blogFactory->create();
        $this->resource->load($blog, $blogId);
        if (!$blog->getId()) {
            throw new NoSuchEntityException(__('Blog post with id "%1" does not exist.', $blogId));
        }
        return $blog;
    }

    /**
     * Get blog list.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        try {
            $collection = $this->blogCollectionFactory->create();
            
            // Check if search criteria is empty (Web API parsing failed)
            // If so, manually parse from request parameters
            $finalSearchCriteria = $searchCriteria;
            if (empty($searchCriteria->getFilterGroups()) && $this->request) {
                $finalSearchCriteria = $this->parseSearchCriteriaFromRequest();
            }
            
            // Use our custom collection processor to handle all search criteria including category filtering
            $this->collectionProcessor->process($finalSearchCriteria, $collection);
            
            // Get raw items from collection
            $items = $collection->getItems();

            
            // Convert items to array format
            $blogItems = [];
            foreach ($items as $item) {
                $blogItems[] = [
                    'id' => $item->getId(),
                    'title' => $item->getTitle(),
                    'content' => $item->getContent(),
                    'image' => $item->getImage(),
                    'author' => $item->getAuthor(),
                    'created_at' => $item->getCreatedAt(),
                    'updated_at' => $item->getUpdatedAt(),
                    'isPublished' => $item->getIsPublished(),
                    'product_ids' => $item->getProductIds(),
                    'tags' => $item->getTags(),
                    'category_ids' => $item->getCategoryIds(),
                ];
            }

            
            
            
            $searchResults = $this->searchResultsFactory->create();
            $searchResults->setSearchCriteria($finalSearchCriteria);
            $searchResults->setItems($blogItems);
            $searchResults->setTotalCount($collection->getSize());
            
            return $searchResults;
            
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Could not retrieve blogs: %1', $e->getMessage())
            );
        }
    }

    /**
     * Delete blog.
     *
     * @param BlogInterface $blog
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(BlogInterface $blog)
    {
        try {
            $this->resource->delete($blog);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete the blog post: %1', $exception->getMessage())
            );
        }
        return true;
    }

    /**
     * Delete blog by ID.
     *
     * @param int $blogId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById($blogId)
    {
        return $this->delete($this->getById($blogId));
    }

    /**
     * Update blog.
     *
     * @param int $blogId
     * @param BlogInterface $blog
     * @return BlogInterface
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     */
    public function update($blogId, BlogInterface $blog)
    {
        $existingBlog = $this->getById($blogId);
        
        // Transfer data from the new blog to the existing one
        $existingBlog->setTitle($blog->getTitle());
        $existingBlog->setContent($blog->getContent());
        $existingBlog->setImage($blog->getImage());
        $existingBlog->setAuthor($blog->getAuthor());
        $existingBlog->setIsPublished($blog->getIsPublished());
        $existingBlog->setProductIds($blog->getProductIds());
        $existingBlog->setTags($blog->getTags());
        $existingBlog->setCategoryIds($blog->getCategoryIds());
        // Set the current timestamp for updated_at
        $existingBlog->setUpdatedAt(date('Y-m-d H:i:s'));
        
        // Save the updated blog
        return $this->save($existingBlog);
    }

    /**
     * Manually parse search criteria from request parameters
     * This is a fallback when Magento's Web API doesn't parse search criteria properly
     *
     * @return SearchCriteriaInterface
     */
    private function parseSearchCriteriaFromRequest()
    {
        $requestParams = $this->request->getParams();
        $searchCriteriaBuilder = $this->searchCriteriaBuilder;
        
        // Debug logging
        error_log("BlogRepository: Manually parsing search criteria from request params: " . json_encode($requestParams));
        
        // Parse page size
        if (isset($requestParams['searchCriteria']['pageSize'])) {
            $searchCriteriaBuilder->setPageSize((int)$requestParams['searchCriteria']['pageSize']);
        }
        
        // Parse current page
        if (isset($requestParams['searchCriteria']['currentPage'])) {
            $searchCriteriaBuilder->setCurrentPage((int)$requestParams['searchCriteria']['currentPage']);
        }
        
        // Parse filter groups
        if (isset($requestParams['searchCriteria']['filterGroups']) && is_array($requestParams['searchCriteria']['filterGroups'])) {
            foreach ($requestParams['searchCriteria']['filterGroups'] as $filterGroup) {
                if (isset($filterGroup['filters']) && is_array($filterGroup['filters'])) {
                    foreach ($filterGroup['filters'] as $filterData) {
                        if (isset($filterData['field']) && isset($filterData['value'])) {
                            $field = $filterData['field'];
                            $value = $filterData['value'];
                            $conditionType = $filterData['conditionType'] ?? 'eq';
                            
                            // Debug logging
                            error_log("BlogRepository: Adding filter - field: $field, value: $value, condition: $conditionType");
                            
                            $searchCriteriaBuilder->addFilter($field, $value, $conditionType);
                        }
                    }
                }
            }
        }
        
        $searchCriteria = $searchCriteriaBuilder->create();
        
        // Debug logging
        error_log("BlogRepository: Created search criteria with " . count($searchCriteria->getFilterGroups()) . " filter groups");
        
        return $searchCriteria;
    }


}