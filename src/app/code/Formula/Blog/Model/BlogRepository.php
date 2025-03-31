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
     * @param BlogResource $resource
     * @param BlogFactory $blogFactory
     * @param BlogCollectionFactory $blogCollectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        BlogResource $resource,
        BlogFactory $blogFactory,
        BlogCollectionFactory $blogCollectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->blogFactory = $blogFactory;
        $this->blogCollectionFactory = $blogCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
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
            
            // Apply search criteria to the collection
            $this->collectionProcessor->process($searchCriteria, $collection);
            
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
                    'product_ids' => $item->getProductIds()
                ];
            }

            
            
            
            $searchResults = $this->searchResultsFactory->create();
            $searchResults->setSearchCriteria($searchCriteria);
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
        // Set the current timestamp for updated_at
        $existingBlog->setUpdatedAt(date('Y-m-d H:i:s'));
        
        // Save the updated blog
        return $this->save($existingBlog);
    }
}