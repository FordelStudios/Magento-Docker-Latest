<?php
namespace Formula\Review\Model;

use Formula\Review\Api\ProductReviewRepositoryInterface;
use Formula\Review\Api\Data\ReviewInterface;
use Formula\Review\Api\Data\ReviewInterfaceFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Review\Model\ReviewFactory;
use Magento\Review\Model\ResourceModel\Review\CollectionFactory as ReviewCollectionFactory;
use Magento\Review\Model\ResourceModel\Review as ReviewResource;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Review\Model\Rating\Option\VoteFactory;
use Magento\Review\Model\ResourceModel\Rating\Option\Vote\CollectionFactory as VoteCollectionFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Webapi\Rest\Request\Authentication as TokenAuth;

class ProductReviewRepository implements ProductReviewRepositoryInterface
{
    /**
     * @var ReviewFactory
     */
    protected $reviewFactory;

    /**
     * @var ReviewResource
     */
    protected $reviewResource;

    /**
     * @var ReviewCollectionFactory
     */
    protected $reviewCollectionFactory;

    /**
     * @var ReviewInterfaceFactory
     */
    protected $reviewDataFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var VoteFactory
     */
    protected $voteFactory;

    /**
     * @var VoteCollectionFactory
     */
    protected $voteCollectionFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Formula\Review\Api\Data\CustomerReviewStatusInterfaceFactory
     */
    protected $customerReviewStatusFactory;

    /**
     * @var \Formula\Review\Api\Data\CustomerPurchaseVerificationInterfaceFactory
     */
    protected $purchaseVerificationFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory
     */
    protected $orderItemCollectionFactory;

    /**
     * @param ReviewFactory $reviewFactory
     * @param ReviewResource $reviewResource
     * @param ReviewCollectionFactory $reviewCollectionFactory
     * @param ReviewInterfaceFactory $reviewDataFactory
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     * @param DataObjectHelper $dataObjectHelper
     * @param VoteFactory $voteFactory
     * @param VoteCollectionFactory $voteCollectionFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Formula\Review\Api\Data\CustomerReviewStatusInterfaceFactory $customerReviewStatusFactory
     */
    public function __construct(
        ReviewFactory $reviewFactory,
        ReviewResource $reviewResource,
        ReviewCollectionFactory $reviewCollectionFactory,
        ReviewInterfaceFactory $reviewDataFactory,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        DataObjectHelper $dataObjectHelper,
        VoteFactory $voteFactory,
        VoteCollectionFactory $voteCollectionFactory,
        CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Formula\Review\Api\Data\CustomerReviewStatusInterfaceFactory $customerReviewStatusFactory,
        \Formula\Review\Api\Data\CustomerPurchaseVerificationInterfaceFactory $purchaseVerificationFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemCollectionFactory
    ) {
        $this->reviewFactory = $reviewFactory;
        $this->reviewResource = $reviewResource;
        $this->reviewCollectionFactory = $reviewCollectionFactory;
        $this->reviewDataFactory = $reviewDataFactory;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->voteFactory = $voteFactory;
        $this->voteCollectionFactory = $voteCollectionFactory;
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->customerReviewStatusFactory = $customerReviewStatusFactory;
        $this->purchaseVerificationFactory = $purchaseVerificationFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
    }

    /**
     * Get authenticated customer ID from session or API token context
     *
     * @return int
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    protected function getAuthenticatedCustomerId()
    {
        $customerId = 0;
        
        if ($this->customerSession->isLoggedIn()) {
            // Customer is logged in through session
            $customerId = $this->customerSession->getCustomerId();
        } else {
            // Check if we have a valid token-based customer context
            try {
                // This gets the customer ID from the API authentication token context
                $context = \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Magento\Authorization\Model\UserContextInterface::class);
                
                if ($context->getUserType() == \Magento\Authorization\Model\UserContextInterface::USER_TYPE_CUSTOMER) {
                    $customerId = $context->getUserId();
                }
            } catch (\Exception $e) {
                // Context not available or not a customer context
            }
        }

        if (!$customerId) {
            throw new \Magento\Framework\Exception\AuthorizationException(__('Customer must be logged in.'));
        }

        return $customerId;
    }


    /**
     * Check if customer already has a review for this product
     *
     * @param int $customerId
     * @param int $productId
     * @return int|null Returns review ID if exists, null otherwise
     */
    protected function getExistingReviewId($customerId, $productId)
    {
        try {
            $collection = $this->reviewCollectionFactory->create()
                ->addFieldToFilter('customer_id', $customerId)
                ->addFieldToFilter('entity_pk_value', $productId)
                ->addFieldToFilter('entity_id', 1) // 1 = product entity
                ->setPageSize(1)
                ->setCurPage(1);

            $review = $collection->getFirstItem();
            
            if ($review && $review->getId()) {
                return $review->getId();
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the rating information for a review
     *
     * @param int $reviewId
     * @return array
     */
    protected function getReviewRatings($reviewId)
    {
        try {
            $connection = $this->reviewResource->getConnection();
            
            // Query to join rating_option_vote with rating to get all the ratings for this review
            $select = $connection->select()
                ->from(
                    ['vote' => $connection->getTableName('rating_option_vote')],
                    ['rating_id', 'value', 'percent']
                )
                ->join(
                    ['rating' => $connection->getTableName('rating')],
                    'vote.rating_id = rating.rating_id',
                    ['rating_code']
                )
                ->where('vote.review_id = ?', $reviewId);
            
            $ratings = $connection->fetchAll($select);
            
            // If we have detailed ratings, calculate average and format for API
            if (!empty($ratings)) {
                $totalValue = 0;
                $detailedOutput = [];
                
                foreach ($ratings as $rating) {
                    $totalValue += (int)$rating['value'];
                    $detailedOutput[] = [
                        'rating_code' => $rating['rating_code'],
                        'value' => (int)$rating['value'],
                        'percent' => (int)$rating['percent']
                    ];
                }
                
                $averageRating = count($ratings) > 0 ? round($totalValue / count($ratings)) : 0;
                
                return [
                    'average' => $averageRating,
                    'detailed' => $detailedOutput
                ];
            }
            
            // Return a default structure if no ratings found
            return [
                'average' => 0,
                'detailed' => []
            ];
        } catch (\Exception $e) {
            // Return a default structure if there's an error
            return [
                'average' => 0,
                'detailed' => []
            ];
        }
    }


    /**
     * Check customer's purchase history for a specific product
     *
     * @param int $customerId
     * @param int $productId
     * @return array
     */
    protected function checkCustomerPurchaseHistory($customerId, $productId)
    {
        try {
            // Get all completed orders for this customer
            $orderCollection = $this->orderCollectionFactory->create()
                ->addFieldToFilter('customer_id', $customerId)
                ->addFieldToFilter('status', ['in' => ['complete', 'closed']]) // Only completed orders
                ->setOrder('created_at', 'DESC');

            $orderIds = [];
            $purchaseCount = 0;
            $lastPurchaseDate = null;

            foreach ($orderCollection as $order) {
                // Check if this order contains the specified product
                $orderItemCollection = $this->orderItemCollectionFactory->create()
                    ->addFieldToFilter('order_id', $order->getId())
                    ->addFieldToFilter('product_id', $productId);

                if ($orderItemCollection->getSize() > 0) {
                    $orderIds[] = (int)$order->getId();
                    
                    // Count the total quantity purchased across all matching order items
                    foreach ($orderItemCollection as $item) {
                        $purchaseCount += (int)$item->getQtyOrdered();
                    }
                    
                    // Set the last purchase date (from the most recent order)
                    if (!$lastPurchaseDate) {
                        $lastPurchaseDate = $order->getCreatedAt();
                    }
                }
            }

            return [
                'has_purchased' => !empty($orderIds),
                'purchase_count' => $purchaseCount,
                'last_purchase_date' => $lastPurchaseDate,
                'order_ids' => $orderIds
            ];

        } catch (\Exception $e) {
            // Log error but return empty result
            return [
                'has_purchased' => false,
                'purchase_count' => 0,
                'last_purchase_date' => null,
                'order_ids' => []
            ];
        }
    }


    /**
     * {@inheritdoc}
     */
    public function verifyCustomerPurchase($sku = null, $productId = null)
    {
        try {
            // Validate input parameters
            if (!$sku && !$productId) {
                throw new \Magento\Framework\Exception\InvalidArgumentException(
                    __('Either product SKU or product ID must be provided.')
                );
            }

            // Get authenticated customer ID
            $customerId = $this->getAuthenticatedCustomerId();
            
            // Get product information
            $product = null;
            if ($sku) {
                $product = $this->productRepository->get($sku);
                $productId = $product->getId();
                $productSku = $sku;
            } else {
                $product = $this->productRepository->getById($productId);
                $productSku = $product->getSku();
            }

            // Create the response object
            $verification = $this->purchaseVerificationFactory->create();
            $verification->setCustomerId($customerId);
            $verification->setProductSku($productSku);
            $verification->setProductId($productId);

            // Check for completed orders containing this product
            $purchaseData = $this->checkCustomerPurchaseHistory($customerId, $productId);
            
            $verification->setHasPurchased($purchaseData['has_purchased']);
            $verification->setPurchaseCount($purchaseData['purchase_count']);
            $verification->setLastPurchaseDate($purchaseData['last_purchase_date']);
            $verification->setOrderIds($purchaseData['order_ids']);

            return $verification;
            
        } catch (\Magento\Framework\Exception\AuthorizationException $e) {
            throw $e;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            if ($sku) {
                throw new \Magento\Framework\Exception\NoSuchEntityException(__('Product with SKU "%1" does not exist.', $sku));
            } else {
                throw new \Magento\Framework\Exception\NoSuchEntityException(__('Product with ID "%1" does not exist.', $productId));
            }
        } catch (\Magento\Framework\Exception\InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Could not verify customer purchase: %1', $e->getMessage()));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerExistingReview($sku)
    {
        try {
            // Get authenticated customer ID
            $customerId = $this->getAuthenticatedCustomerId();
            
            // Get product by SKU
            $product = $this->productRepository->get($sku);
            
            // Check if customer has existing review
            $reviewId = $this->getExistingReviewId($customerId, $product->getId());

            $customerReviewStatus = $this->customerReviewStatusFactory->create();
            $customerReviewStatus->setCustomerId($customerId);
            $customerReviewStatus->setProductSku($sku);
            
            if ($reviewId) {
                $customerReviewStatus->setHasReview(true);
                $customerReviewStatus->setReviewId($reviewId);
            } else {
                $customerReviewStatus->setHasReview(false);
                $customerReviewStatus->setReviewId(null);
            }

            return $customerReviewStatus;

            
        } catch (\Magento\Framework\Exception\AuthorizationException $e) {
            throw $e;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            throw new NoSuchEntityException(__('Product with SKU "%1" does not exist.', $sku));
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Could not check existing review: %1', $e->getMessage()));
        }
    }


    /**
     * {@inheritdoc}
     */
    public function getList($sku)
    {
        try {
            $product = $this->productRepository->get($sku);
            
            $collection = $this->reviewCollectionFactory->create()
                ->addEntityFilter('product', $product->getId())
                ->addStoreFilter($this->storeManager->getStore()->getId())
                ->setDateOrder();
            // No status filter to get all reviews

            $reviews = [];
            $storeId = $this->storeManager->getStore()->getId();
            
            foreach ($collection as $reviewModel) {
                $reviewData = $this->reviewDataFactory->create();
                $reviewModel->getEntitySummary($reviewModel, $storeId);
                
                // Get the status text from the database
                $statusText = $this->getStatusText($reviewModel->getStatusId());
                
                // Get ratings
                $ratings = $this->getReviewRatings($reviewModel->getId());

                $isRecommended = (bool)$reviewModel->getData('is_recommended');
            
                // Get images for this review
                $images = $this->getReviewImages($reviewModel->getId());
                
                // Populate basic data
                $data = [
                    'id' => $reviewModel->getId(),
                    'title' => $reviewModel->getTitle(),
                    'nickname' => $reviewModel->getNickname(),
                    'detail' => $reviewModel->getDetail(),
                    'product_sku' => $sku,
                    'ratings' => $ratings['average'],
                    'status' => $statusText,
                    'is_recommended' => $isRecommended,
                    'created_at' => $reviewModel->getCreatedAt(),
                    'updated_at' => $reviewModel->getData('updated_at') ?: $reviewModel->getCreatedAt()
                ];

                $this->dataObjectHelper->populateWithArray($reviewData, $data, ReviewInterface::class);
                
                // Set ratings_details separately to avoid type issues
                $reviewData->setRatingsDetails($ratings['detailed']);

                if (!empty($images)) {
                    $reviewData->setImages($images);
                }
                
                $reviews[] = $reviewData;
            }

            return $reviews;
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Could not retrieve reviews: %1', $e->getMessage()));
        }
    }

   /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        $reviewModel = $this->reviewFactory->create();
        $this->reviewResource->load($reviewModel, $id);

        if (!$reviewModel->getId()) {
            throw new NoSuchEntityException(__('Review with ID "%1" does not exist.', $id));
        }

        $reviewData = $this->reviewDataFactory->create();
        $product = $this->productRepository->getById($reviewModel->getEntityPkValue());
        
        // Get the status text from the database
        $statusText = $this->getStatusText($reviewModel->getStatusId());
        
        // Get ratings
        $ratings = $this->getReviewRatings($reviewModel->getId());

        // Get isRecommended value
        $isRecommended = (bool)$reviewModel->getData('is_recommended');
        
        // Get images for this review
        $images = $this->getReviewImages($reviewModel->getId());
        
        // Populate basic data
        $data = [
            'id' => $reviewModel->getId(),
            'title' => $reviewModel->getTitle(),
            'nickname' => $reviewModel->getNickname(),
            'detail' => $reviewModel->getDetail(),
            'product_sku' => $product->getSku(),
            'ratings' => $ratings['average'],
            'status' => $statusText,
            'is_recommended' => $isRecommended,
            'created_at' => $reviewModel->getCreatedAt(),
            'updated_at' => $reviewModel->getData('updated_at') ?: $reviewModel->getCreatedAt()
        ];

        $this->dataObjectHelper->populateWithArray($reviewData, $data, ReviewInterface::class);
        
        // Set ratings_details separately to avoid type issues
        $reviewData->setRatingsDetails($ratings['detailed']);

        // Set images if there are any
        if (!empty($images)) {
            $reviewData->setImages($images);
        }
        
        return $reviewData;
    }

    /**
     * {@inheritdoc}
     */
    public function create(ReviewInterface $review)
    {
        try {
            // For REST API with token, the customerId comes from the token context
            // Get authenticated customer ID
            $customerId = $this->getAuthenticatedCustomerId();

            $customer = $this->customerRepository->getById($customerId);
            $product = $this->productRepository->get($review->getProductSku());

            // Check if customer already has a review for this product
            $existingReviewId = $this->getExistingReviewId($customerId, $product->getId());
            
            if ($existingReviewId) {
                // Update the existing review instead of creating a new one
                return $this->update($existingReviewId, $review);
            }

            $nickname = $customer->getFirstname() . ' ' . $customer->getLastname();

            $reviewModel = $this->reviewFactory->create();
    
            $statusId = \Magento\Review\Model\Review::STATUS_PENDING;
    
            // If status was provided and valid, use it
            if ($review->getStatus()) {
                switch ($review->getStatus()) {
                    case 'approved':
                        $statusId = \Magento\Review\Model\Review::STATUS_APPROVED;
                        break;
                    case 'not_approved':
                        $statusId = \Magento\Review\Model\Review::STATUS_NOT_APPROVED;
                        break;
                    // For any other value, keep it as pending
                }
            }

            $isRecommended = $review->getIsRecommended() !== null ? (bool)$review->getIsRecommended() : null;
            
            $reviewModel->setData([
                'customer_id' => $customerId,
                'nickname' => $nickname,
                'title' => $review->getTitle(),
                'detail' => $review->getDetail(),
                'entity_id' => 1, // product
                'entity_pk_value' => $product->getId(),
                'status_id' => $statusId,
                'store_id' => $this->storeManager->getStore()->getId(),
                'stores' => [$this->storeManager->getStore()->getId()]
            ]);

            // Add is_recommended if it was provided
            if ($isRecommended !== null) {
                $reviewModel->setData('is_recommended', $isRecommended ? 1 : 0);
            }

            $this->reviewResource->save($reviewModel);

            // Save images if provided
            $images = $review->getImages();
            if (!empty($images) && is_array($images)) {
                $this->saveReviewImages($reviewModel->getId(), $images);
            }
            
            // Array to store created votes for later inclusion in response
            $createdRatings = [];
            
            // Handle ratings if provided
            $ratingValue = (int)$review->getRatings();
            if ($ratingValue > 0) {
                try {
                    $connection = $this->reviewResource->getConnection();
                    
                    // Get all available ratings
                    $ratingSelect = $connection->select()
                        ->from($connection->getTableName('rating'), ['rating_id', 'rating_code'])
                        ->where('entity_id = ?', 1) // 1 = product entity
                        ->order('position ASC');
                    
                    $ratings = $connection->fetchAll($ratingSelect);
                    
                    if (!empty($ratings)) {
                        foreach ($ratings as $rating) {
                            $ratingId = $rating['rating_id'];
                            $ratingCode = $rating['rating_code'];
                            
                            // For each rating, get the option that matches the provided rating value
                            $optionSelect = $connection->select()
                                ->from($connection->getTableName('rating_option'), ['option_id'])
                                ->where('rating_id = ?', $ratingId)
                                ->where('value = ?', $ratingValue)
                                ->limit(1);
                            
                            $optionId = $connection->fetchOne($optionSelect);
                            
                            // If we couldn't find an exact match, get the closest option
                            if (!$optionId) {
                                $optionSelect = $connection->select()
                                    ->from($connection->getTableName('rating_option'), ['option_id', 'value'])
                                    ->where('rating_id = ?', $ratingId)
                                    ->order('ABS(value - ' . $ratingValue . ')')
                                    ->limit(1);
                                
                                $option = $connection->fetchRow($optionSelect);
                                if ($option) {
                                    $optionId = $option['option_id'];
                                }
                            }
                            
                            // If we have an option ID, create the vote
                            if ($optionId) {
                                $vote = $this->voteFactory->create();
                                $percentValue = $ratingValue * 20; // Convert 1-5 to percentage (20%, 40%, etc.)
                                
                                $vote->setRatingId($ratingId)
                                    ->setReviewId($reviewModel->getId())
                                    ->setOptionId($optionId)
                                    ->setEntityPkValue($product->getId())
                                    ->setValue($ratingValue)
                                    ->setPercent($percentValue)
                                    ->save();
                                
                                // Store rating details for response
                                $createdRatings[] = [
                                    'rating_code' => $ratingCode,
                                    'value' => $ratingValue, 
                                    'percent' => $percentValue
                                ];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // If there's an error with ratings, log it but don't fail the whole review creation
                    // We could log the error here if you have a logger injected
                }
            }
            
            // Set the data for the returned object
            $review->setId($reviewModel->getId());
            $review->setStatus($this->getStatusText($statusId));
            $review->setCreatedAt($reviewModel->getCreatedAt());
            
            // Also set the ratings back
            $review->setRatings($ratingValue);
            
            // Include the rating details in the response
            $review->setRatingsDetails($createdRatings);

            // Include the is_recommended value in the response
            if ($isRecommended !== null) {
                $review->setIsRecommended($isRecommended);
            }
            
            // Include images in the response
            if (!empty($images)) {
                $review->setImages($images);
            }
            
            return $review;
            
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save review: %1', $e->getMessage()), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function update($id, ReviewInterface $review)
    {
        try {
            // Load the existing review
            $reviewModel = $this->reviewFactory->create();
            $this->reviewResource->load($reviewModel, $id);

            if (!$reviewModel->getId()) {
                throw new NoSuchEntityException(__('Review with ID "%1" does not exist.', $id));
            }

            $customerId = $this->getAuthenticatedCustomerId();

            if ($reviewModel->getCustomerId() != $customerId) {
                throw new \Magento\Framework\Exception\AuthorizationException(__('You are not authorized to access this review.'));
            }

            // Get the product
            $productId = $reviewModel->getEntityPkValue();
            try {
                $product = $this->productRepository->getById($productId);
                $productSku = $product->getSku();
            } catch (\Exception $e) {
                $productSku = $review->getProductSku() ?: 'unknown';
            }

            // Update review status if provided
            $statusId = $reviewModel->getStatusId();
            if ($review->getStatus()) {
                switch ($review->getStatus()) {
                    case 'approved':
                        $statusId = \Magento\Review\Model\Review::STATUS_APPROVED;
                        break;
                    case 'pending':
                        $statusId = \Magento\Review\Model\Review::STATUS_PENDING;
                        break;
                    case 'not_approved':
                        $statusId = \Magento\Review\Model\Review::STATUS_NOT_APPROVED;
                        break;
                }
            }

            // Update basic fields if provided
            if ($review->getNickname()) {
                $reviewModel->setNickname($review->getNickname());
            }
            
            if ($review->getTitle()) {
                $reviewModel->setTitle($review->getTitle());
            }
            
            if ($review->getDetail()) {
                $reviewModel->setDetail($review->getDetail());
            }

            // Update is_recommended if provided
            if ($review->getIsRecommended() !== null) {
                $reviewModel->setData('is_recommended', (bool)$review->getIsRecommended() ? 1 : 0);
            }

            $reviewModel->setStatusId($statusId);
            
            // Set updated_at timestamp
            $now = new \DateTime();
            $reviewModel->setData('updated_at', $now->format('Y-m-d H:i:s'));

            // Save the updated review
            $this->reviewResource->save($reviewModel);

            // Update images if provided
            $images = $review->getImages();
            if ($images !== null) {
                // Delete existing images first
                $this->deleteReviewImages($reviewModel->getId());
                
                // Then save new images if any were provided
                if (!empty($images) && is_array($images)) {
                    $this->saveReviewImages($reviewModel->getId(), $images);
                }
            }

            // Update ratings if the ratings field is present in the request
            $ratingValue = null;
            $updatedRatings = [];
            
            if ($review->getRatings() !== null) {
                $ratingValue = (int)$review->getRatings();
                
                try {
                    $connection = $this->reviewResource->getConnection();
                    
                    // First delete existing votes
                    $voteTable = $connection->getTableName('rating_option_vote');
                    $connection->delete($voteTable, ['review_id = ?' => $id]);
                    
                    // Only create new votes if rating value is greater than 0
                    if ($ratingValue > 0) {
                        // Get all available ratings
                        $ratingSelect = $connection->select()
                            ->from($connection->getTableName('rating'), ['rating_id', 'rating_code'])
                            ->where('entity_id = ?', 1) // 1 = product entity
                            ->order('position ASC');
                        
                        $ratings = $connection->fetchAll($ratingSelect);
                        
                        if (!empty($ratings)) {
                            foreach ($ratings as $rating) {
                                $ratingId = $rating['rating_id'];
                                $ratingCode = $rating['rating_code'];
                                
                                // For each rating, get the option that matches the provided rating value
                                $optionSelect = $connection->select()
                                    ->from($connection->getTableName('rating_option'), ['option_id'])
                                    ->where('rating_id = ?', $ratingId)
                                    ->where('value = ?', $ratingValue)
                                    ->limit(1);
                                
                                $optionId = $connection->fetchOne($optionSelect);
                                
                                // If we couldn't find an exact match, get the closest option
                                if (!$optionId) {
                                    $optionSelect = $connection->select()
                                        ->from($connection->getTableName('rating_option'), ['option_id', 'value'])
                                        ->where('rating_id = ?', $ratingId)
                                        ->order('ABS(value - ' . $ratingValue . ')')
                                        ->limit(1);
                                    
                                    $option = $connection->fetchRow($optionSelect);
                                    if ($option) {
                                        $optionId = $option['option_id'];
                                    }
                                }
                                
                                // If we have an option ID, create the vote
                                if ($optionId) {
                                    $vote = $this->voteFactory->create();
                                    $percentValue = $ratingValue * 20; // Convert 1-5 to percentage (20%, 40%, etc.)
                                    
                                    $vote->setRatingId($ratingId)
                                        ->setReviewId($reviewModel->getId())
                                        ->setOptionId($optionId)
                                        ->setEntityPkValue($productId)
                                        ->setValue($ratingValue)
                                        ->setPercent($percentValue)
                                        ->save();
                                    
                                    // Store rating details for response
                                    $updatedRatings[] = [
                                        'rating_code' => $ratingCode,
                                        'value' => $ratingValue, 
                                        'percent' => $percentValue
                                    ];
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // Log error but continue
                }
            }

            // Prepare response
            $responseReview = $this->reviewDataFactory->create();
            
            $data = [
                'id' => $reviewModel->getId(),
                'title' => $reviewModel->getTitle(),
                'nickname' => $reviewModel->getNickname(),
                'detail' => $reviewModel->getDetail(),
                'product_sku' => $productSku,
                'status' => $this->getStatusText($statusId),
                'created_at' => $reviewModel->getCreatedAt(),
                'updated_at' => $reviewModel->getData('updated_at')
            ];
            
            // Only set ratings if it was provided in the request
            if ($ratingValue !== null) {
                $data['ratings'] = $ratingValue;
            } else {
                $data['ratings'] = $this->getReviewRatings($reviewModel->getId())['average'];
            }

            // Set isRecommended
            $data['is_recommended'] = (bool)$reviewModel->getData('is_recommended');
            
            // Get images for the review
            $reviewImages = $this->getReviewImages($reviewModel->getId());
            if (!empty($reviewImages)) {
                $data['images'] = $reviewImages;
            }

            $this->dataObjectHelper->populateWithArray($responseReview, $data, ReviewInterface::class);
            
            // Set ratings_details - use updated ratings if available
            if ($ratingValue !== null) {
                $responseReview->setRatingsDetails($ratingValue > 0 ? $updatedRatings : []);
            } else {
                $responseReview->setRatingsDetails($this->getReviewRatings($reviewModel->getId())['detailed']);
            }
            
            return $responseReview;
        } catch (NoSuchEntityException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete review: %1', $e->getMessage()), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAllReviews()
    {
        try {
            $collection = $this->reviewCollectionFactory->create()
                ->setDateOrder();
            // No status filter here, so we get all reviews regardless of status

            $reviews = [];
            $storeId = $this->storeManager->getStore()->getId();
            
            foreach ($collection as $reviewModel) {
                $reviewData = $this->reviewDataFactory->create();
                $reviewModel->getEntitySummary($reviewModel, $storeId);
                
                try {
                    $product = $this->productRepository->getById($reviewModel->getEntityPkValue());
                    $productSku = $product->getSku();
                } catch (\Exception $e) {
                    // If product doesn't exist anymore, set a placeholder SKU
                    $productSku = 'unknown';
                }
                
                // Get the status text from the database
                $statusText = $this->getStatusText($reviewModel->getStatusId());
                
                // Get ratings
                $ratings = $this->getReviewRatings($reviewModel->getId());
                
                // Populate basic data
                $data = [
                    'id' => $reviewModel->getId(),
                    'title' => $reviewModel->getTitle(),
                    'nickname' => $reviewModel->getNickname(),
                    'detail' => $reviewModel->getDetail(),
                    'product_sku' => $productSku,
                    'ratings' => $ratings['average'],
                    'status' => $statusText,
                    'created_at' => $reviewModel->getCreatedAt(),
                    'updated_at' => $reviewModel->getData('updated_at') ?: $reviewModel->getCreatedAt()
                ];

                $this->dataObjectHelper->populateWithArray($reviewData, $data, ReviewInterface::class);
                
                // Set ratings_details separately to avoid type issues
                $reviewData->setRatingsDetails($ratings['detailed']);
                
                $reviews[] = $reviewData;
            }

            return $reviews;
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Could not retrieve reviews: %1', $e->getMessage()));
        }
    }

    /**
     * Get status text from database by status ID
     *
     * @param int $statusId
     * @return string
     */
    private function getStatusText($statusId)
    {
        try {
            $connection = $this->reviewResource->getConnection();
            $select = $connection->select()
                ->from($connection->getTableName('review_status'), ['status_code'])
                ->where('status_id = ?', $statusId);
            
            $statusCode = $connection->fetchOne($select);
            
            return $statusCode ?: 'unknown';
        } catch (\Exception $e) {
            // If there's any issue accessing the database, fall back to the hardcoded values
            switch ($statusId) {
                case \Magento\Review\Model\Review::STATUS_APPROVED:
                    return 'approved';
                case \Magento\Review\Model\Review::STATUS_PENDING:
                    return 'pending';
                case \Magento\Review\Model\Review::STATUS_NOT_APPROVED:
                    return 'not_approved';
                default:
                    return 'unknown';
            }
        }
    }

    /**
     * Save images for a review
     *
     * @param int $reviewId
     * @param array $images
     * @return void
     */
    protected function saveReviewImages($reviewId, array $images)
    {
        try {
            $connection = $this->reviewResource->getConnection();
            $tableName = $connection->getTableName('review_images'); // You'll need to create this table
            
            foreach ($images as $imagePath) {
                // Skip empty strings
                if (empty($imagePath)) {
                    continue;
                }
                
                $connection->insert(
                    $tableName,
                    [
                        'review_id' => $reviewId,
                        'image_path' => $imagePath
                    ]
                );
            }
        } catch (\Exception $e) {
            // Log error but continue
            // We could log the error here if you have a logger injected
        }
    }

    /**
     * Get images for a review
     *
     * @param int $reviewId
     * @return array
     */
    protected function getReviewImages($reviewId)
    {
        try {
            $connection = $this->reviewResource->getConnection();
            $tableName = $connection->getTableName('review_images');
            
            $select = $connection->select()
                ->from($tableName, ['image_path'])
                ->where('review_id = ?', $reviewId);
            
            $results = $connection->fetchCol($select);
            
            return $results ?: [];
        } catch (\Exception $e) {
            // Log error but return empty array
            return [];
        }
    }

    /**
     * Delete images for a review
     *
     * @param int $reviewId
     * @return void
     */
    protected function deleteReviewImages($reviewId)
    {
        try {
            $connection = $this->reviewResource->getConnection();
            $tableName = $connection->getTableName('review_images');
            
            $connection->delete($tableName, ['review_id = ?' => $reviewId]);
        } catch (\Exception $e) {
            // Log error but continue
            // We could log the error here if you have a logger injected
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($id)
    {
        try {
            $reviewModel = $this->reviewFactory->create();
            $this->reviewResource->load($reviewModel, $id);

            if (!$reviewModel->getId()) {
                throw new NoSuchEntityException(__('Review with ID "%1" does not exist.', $id));
            }

            $customerId = $this->getAuthenticatedCustomerId();

            if ($reviewModel->getCustomerId() != $customerId) {
                throw new \Magento\Framework\Exception\AuthorizationException(__('You are not authorized to access this review.'));
            }

            // Delete related votes first
            $connection = $this->reviewResource->getConnection();
            $voteTable = $connection->getTableName('rating_option_vote');
            $connection->delete($voteTable, ['review_id = ?' => $id]);

            $this->deleteReviewImages($id);

            // Now delete the review
            $this->reviewResource->delete($reviewModel);
            return true;
        } catch (NoSuchEntityException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\CouldNotSaveException(
                __('Could not update review: %1', $e->getMessage()),
                $e
            );
        }
    }
}

    