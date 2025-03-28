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
     * @param ReviewFactory $reviewFactory
     * @param ReviewResource $reviewResource
     * @param ReviewCollectionFactory $reviewCollectionFactory
     * @param ReviewInterfaceFactory $reviewDataFactory
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     * @param DataObjectHelper $dataObjectHelper
     * @param VoteFactory $voteFactory
     * @param VoteCollectionFactory $voteCollectionFactory
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
        VoteCollectionFactory $voteCollectionFactory
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
     * Get a simple average rating for a review (fallback method)
     *
     * @param int $reviewId
     * @return array
     */
    protected function getSimpleReviewRating($reviewId)
    {
        try {
            $votes = $this->voteCollectionFactory->create()
                ->setReviewFilter($reviewId)
                ->addRatingInfo($this->storeManager->getStore()->getId())
                ->load();

            if ($votes->getSize() == 0) {
                return ['average' => 0];
            }

            $totalRating = 0;
            $count = 0;
            
            foreach ($votes as $vote) {
                // Make sure we have a valid value
                if ($vote->getValue()) {
                    $totalRating += $vote->getValue();
                    $count++;
                }
            }
            
            return ['average' => $count > 0 ? round($totalRating / $count) : 0];
        } catch (\Exception $e) {
            // Log the error but don't fail the whole request
            return ['average' => 0];
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
                
                // Populate basic data
                $data = [
                    'id' => $reviewModel->getId(),
                    'title' => $reviewModel->getTitle(),
                    'nickname' => $reviewModel->getNickname(),
                    'detail' => $reviewModel->getDetail(),
                    'product_sku' => $sku,
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
        
        // Populate basic data
        $data = [
            'id' => $reviewModel->getId(),
            'title' => $reviewModel->getTitle(),
            'nickname' => $reviewModel->getNickname(),
            'detail' => $reviewModel->getDetail(),
            'product_sku' => $product->getSku(),
            'ratings' => $ratings['average'],
            'status' => $statusText,
            'created_at' => $reviewModel->getCreatedAt(),
            'updated_at' => $reviewModel->getData('updated_at') ?: $reviewModel->getCreatedAt()
        ];

        $this->dataObjectHelper->populateWithArray($reviewData, $data, ReviewInterface::class);
        
        // Set ratings_details separately to avoid type issues
        $reviewData->setRatingsDetails($ratings['detailed']);
        
        return $reviewData;
    }

    /**
     * {@inheritdoc}
     */
    public function create(ReviewInterface $review)
    {
        try {
            $product = $this->productRepository->get($review->getProductSku());
            
            $reviewModel = $this->reviewFactory->create();
            
            // Default status is pending
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
            
            $reviewModel->setData([
                'nickname' => $review->getNickname(),
                'title' => $review->getTitle(),
                'detail' => $review->getDetail(),
                'entity_id' => 1, // 1 = product
                'entity_pk_value' => $product->getId(),
                'status_id' => $statusId,
                'store_id' => $this->storeManager->getStore()->getId(),
                'stores' => [$this->storeManager->getStore()->getId()]
            ]);

            $this->reviewResource->save($reviewModel);
            
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

            $reviewModel->setStatusId($statusId);
            
            // Set updated_at timestamp
            $now = new \DateTime();
            $reviewModel->setData('updated_at', $now->format('Y-m-d H:i:s'));

            // Save the updated review
            $this->reviewResource->save($reviewModel);

            // Update ratings if the ratings field is present in the request
            // We check if the field is set rather than just checking if > 0
            // This allows updating to a rating of 0
            $ratingValue = null;
            $updatedRatings = [];
            
            // Check if ratings was explicitly set in the request
            // We use property_exists to check if the field is present, not just if it has a value
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
            throw new \Magento\Framework\Exception\CouldNotSaveException(
                __('Could not update review: %1', $e->getMessage()),
                $e
            );
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

            // Delete related votes first
            $connection = $this->reviewResource->getConnection();
            $voteTable = $connection->getTableName('rating_option_vote');
            $connection->delete($voteTable, ['review_id = ?' => $id]);

            // Now delete the review
            $this->reviewResource->delete($reviewModel);
            return true;
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
}