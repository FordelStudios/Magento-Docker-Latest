<?php
namespace Formula\Wishlist\Model;

use Formula\Wishlist\Api\Data\WishlistItemInterface;
use Formula\Wishlist\Api\WishlistRepositoryInterface;
use Formula\Wishlist\Model\ResourceModel\WishlistItem as ResourceWishlistItem;
use Formula\Wishlist\Model\ResourceModel\WishlistItem\CollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class WishlistRepository implements WishlistRepositoryInterface
{
    /**
     * @var ResourceWishlistItem
     */
    private $resource;

    /**
     * @var WishlistItemFactory
     */
    private $wishlistItemFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var SearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

   

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Magento\Framework\Webapi\ServiceInputProcessor
     */
    private $serviceInputProcessor;

     /**
     * @var WishlistItemHydrator
     */
    private $wishlistItemHydrator;

    /**
     * @param ResourceWishlistItem $resource
     * @param WishlistItemFactory $wishlistItemFactory
     * @param CollectionFactory $collectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Webapi\ServiceInputProcessor $serviceInputProcessor
     * @param WishlistItemHydrator $wishlistItemHydrator
     */
    public function __construct(
        ResourceWishlistItem $resource,
        WishlistItemFactory $wishlistItemFactory,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Webapi\ServiceInputProcessor $serviceInputProcessor = null,
        WishlistItemHydrator $wishlistItemHydrator = null
    ) {
        $this->resource = $resource;
        $this->wishlistItemFactory = $wishlistItemFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->serviceInputProcessor = $serviceInputProcessor ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Webapi\ServiceInputProcessor::class);
        $this->wishlistItemHydrator = $wishlistItemHydrator ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(WishlistItemHydrator::class);        
    }

    /**
     * Save wishlist item
     *
     * @param WishlistItemInterface $wishlistItem
     * @return WishlistItemInterface
     * @throws CouldNotSaveException
     */
    public function save(WishlistItemInterface $wishlistItem)
    {
        try {
            // For REST API with token, the customerId comes from the token context
            $customerId = 0;
            
            if ($this->customerSession->isLoggedIn()) {
                // Customer is logged in through session
                $customerId = $this->customerSession->getCustomerId();
            } else {
                // Check if we have a valid token-based customer context
                $currentCustomerId = 0;
                
                try {
                    // This gets the customer ID from the API authentication token context
                    $context = \Magento\Framework\App\ObjectManager::getInstance()
                        ->get(\Magento\Authorization\Model\UserContextInterface::class);
                    
                    if ($context->getUserType() == \Magento\Authorization\Model\UserContextInterface::USER_TYPE_CUSTOMER) {
                        $currentCustomerId = $context->getUserId();
                    }
                } catch (\Exception $e) {
                    // Context not available or not a customer context
                }
                
                if ($currentCustomerId) {
                    $customerId = $currentCustomerId;
                }
            }
            
            // Set the customer ID if it's available
            if ($customerId) {
                $wishlistItem->setCustomerId($customerId);
            }
            
            // Make sure customer ID is set
            if (!$wishlistItem->getCustomerId()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Customer must be logged in to save wishlist items')
                );
            }

            // Check if this product is already in the customer's wishlist
            if (!$wishlistItem->getId()) {
                $collection = $this->collectionFactory->create();
                $collection->addCustomerFilter($wishlistItem->getCustomerId())
                        ->addFieldToFilter('product_id', $wishlistItem->getProductId());
                
                if ($collection->getSize() > 0) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('This product is already in your wishlist')
                    );
                }
            }
            
            $this->resource->save($wishlistItem);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $wishlistItem;
    }

    /**
     * Get wishlist item by ID
     *
     * @param int $wishlistItemId
     * @return WishlistItemInterface
     * @throws NoSuchEntityException
     */
    public function getById($wishlistItemId)
    {
        $wishlistItem = $this->wishlistItemFactory->create();
        $this->resource->load($wishlistItem, $wishlistItemId);
        if (!$wishlistItem->getId()) {
            throw new NoSuchEntityException(__('Wishlist item with id "%1" does not exist.', $wishlistItemId));
        }
        // Hydrate with product data
        return $this->wishlistItemHydrator->hydrate($wishlistItem);
    }

    /**
     * Retrieve wishlist items matching the specified criteria
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);
        
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        
        $items = $collection->getItems();
        
        // Hydrate all items with product data
        foreach ($items as $key => $item) {
            $items[$key] = $this->wishlistItemHydrator->hydrate($item);
        }
        
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        
        return $searchResults;
    }

    /**
     * Get wishlist items by customer ID
     *
     * @param int $customerId
     * @return WishlistItemInterface[]
     */
    public function getByCustomerId($customerId)
    {
        $collection = $this->collectionFactory->create();
        $collection->addCustomerFilter($customerId);
        $items = $collection->getItems();
        
        // Hydrate all items with product data
        foreach ($items as $key => $item) {
            $items[$key] = $this->wishlistItemHydrator->hydrate($item);
        }
        
        return $items;
    }

    /**
     * Delete wishlist item
     *
     * @param WishlistItemInterface $wishlistItem
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(WishlistItemInterface $wishlistItem)
    {
        try {
            $this->resource->delete($wishlistItem);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * Delete wishlist item by ID
     *
     * @param int $wishlistItemId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById($wishlistItemId)
    {
        return $this->delete($this->getById($wishlistItemId));
    }
}