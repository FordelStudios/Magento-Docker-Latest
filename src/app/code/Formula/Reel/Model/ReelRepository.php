<?php
/**
 * Reel repository
 *
 * @category  Formula
 * @package   Formula\Reel
 */
namespace Formula\Reel\Model;

use Formula\Reel\Api\ReelRepositoryInterface;
use Formula\Reel\Api\Data\ReelInterface;
use Formula\Reel\Model\ResourceModel\Reel as ReelResource;
use Formula\Reel\Model\ResourceModel\Reel\CollectionFactory as ReelCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class ReelRepository implements ReelRepositoryInterface
{
    /**
     * @var ReelResource
     */
    private $resource;

    /**
     * @var ReelFactory
     */
    private $reelFactory;

    /**
     * @var ReelCollectionFactory
     */
    private $reelCollectionFactory;

    /**
     * @var SearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ReelResource $resource
     * @param ReelFactory $reelFactory
     * @param ReelCollectionFactory $reelCollectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param ProductCollectionFactory $productCollectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ReelResource $resource,
        ReelFactory $reelFactory,
        ReelCollectionFactory $reelCollectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        ProductCollectionFactory $productCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->resource = $resource;
        $this->reelFactory = $reelFactory;
        $this->reelCollectionFactory = $reelCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Save reel.
     *
     * @param ReelInterface $reel
     * @return ReelInterface
     * @throws CouldNotSaveException
     */
    public function save(ReelInterface $reel)
    {
        try {
            // Set created_at for new entities only (not having an ID yet)
            if (!$reel->getId()) {
                $reel->setCreatedAt(date('Y-m-d H:i:s'));
            }
            
            // Always update the updated_at timestamp
            $reel->setUpdatedAt(date('Y-m-d H:i:s'));

            $this->resource->save($reel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the reel post: %1', $exception->getMessage()),
                $exception
            );
        }
        return $reel;
    }

    /**
     * Get reel by ID.
     *
     * @param int $reelId
     * @return ReelInterface
     * @throws NoSuchEntityException
     */
    public function getById($reelId)
    {
        $reel = $this->reelFactory->create();
        $this->resource->load($reel, $reelId);
        if (!$reel->getId()) {
            throw new NoSuchEntityException(__('Reel post with id "%1" does not exist.', $reelId));
        }
        return $reel;
    }

    /**
     * Get product details by IDs
     *
     * @param string $productIds
     * @return array
     */
    private function getProductDetails($productIds)
    {
        if (empty($productIds)) {
            return [];
        }

        $ids = array_filter(array_map('trim', explode(',', $productIds)));
        if (empty($ids)) {
            return [];
        }

        try {
            $mediaUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

            $collection = $this->productCollectionFactory->create()
                ->addAttributeToSelect(['name', 'url_key', 'price', 'special_price', 'image', 'small_image', 'thumbnail'])
                ->addAttributeToFilter('entity_id', ['in' => $ids])
                ->addAttributeToFilter('status', 1);

            $products = [];
            foreach ($collection as $product) {
                $imageUrl = null;
                if ($product->getImage() && $product->getImage() !== 'no_selection') {
                    $imageUrl = $mediaUrl . 'catalog/product' . $product->getImage();
                }

                $products[] = [
                    'id' => (int)$product->getId(),
                    'name' => $product->getName(),
                    'url_key' => $product->getUrlKey(),
                    'price' => (float)$product->getPrice(),
                    'special_price' => $product->getSpecialPrice() ? (float)$product->getSpecialPrice() : null,
                    'image' => $imageUrl
                ];
            }

            return $products;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get reel list.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        try {
            $collection = $this->reelCollectionFactory->create();

            // Apply search criteria to the collection
            $this->collectionProcessor->process($searchCriteria, $collection);

            // Get raw items from collection
            $items = $collection->getItems();

            // Convert items to array format
            $reelItems = [];
            foreach ($items as $item) {
                $reelItems[] = [
                    'id' => $item->getId(),
                    'description' => $item->getDescription(),
                    'timer' => $item->getTimer(),
                    'video' => $item->getVideo(),
                    'thumbnail' => $item->getThumbnail(),
                    'created_at' => $item->getCreatedAt(),
                    'updated_at' => $item->getUpdatedAt(),
                    'product_ids' => $item->getProductIds(),
                    'products' => $this->getProductDetails($item->getProductIds()),
                    'category_ids' => $item->getCategoryIds(),
                    'culture' => $item->getCulture()
                ];
            }

            $searchResults = $this->searchResultsFactory->create();
            $searchResults->setSearchCriteria($searchCriteria);
            $searchResults->setItems($reelItems);
            $searchResults->setTotalCount($collection->getSize());

            return $searchResults;

        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Could not retrieve reels: %1', $e->getMessage())
            );
        }
    }

    /**
     * Delete reel.
     *
     * @param ReelInterface $reel
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(ReelInterface $reel)
    {
        try {
            $this->resource->delete($reel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete the reel post: %1', $exception->getMessage())
            );
        }
        return true;
    }

    /**
     * Delete reel by ID.
     *
     * @param int $reelId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById($reelId)
    {
        return $this->delete($this->getById($reelId));
    }

    /**
     * Update reel.
     *
     * @param int $reelId
     * @param ReelInterface $reel
     * @return ReelInterface
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     */
    public function update($reelId, ReelInterface $reel)
    {
        $existingReel = $this->getById($reelId);

        $existingReel->setDescription($reel->getDescription());
        $existingReel->setTimer($reel->getTimer());
        $existingReel->setVideo($reel->getVideo());
        $existingReel->setThumbnail($reel->getThumbnail());
        $existingReel->setProductIds($reel->getProductIds());
        $existingReel->setCategoryIds($reel->getCategoryIds());
        $existingReel->setCulture($reel->getCulture());
        $existingReel->setUpdatedAt(date('Y-m-d H:i:s'));

        // Save the updated reel
        return $this->save($existingReel);
    }
}