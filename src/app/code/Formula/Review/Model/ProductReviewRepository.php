<?php
namespace Formula\Review\Model;

use Formula\Review\Api\ProductReviewRepositoryInterface;
use Formula\Review\Api\Data\ReviewInterface;
use Formula\Review\Api\Data\ReviewInterfaceFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Review\Model\ReviewFactory;
use Magento\Review\Model\ResourceModel\Review\CollectionFactory as ReviewCollectionFactory;
use Magento\Review\Model\ResourceModel\Review as ReviewResource;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Api\DataObjectHelper;

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
     * @param ReviewFactory $reviewFactory
     * @param ReviewResource $reviewResource
     * @param ReviewCollectionFactory $reviewCollectionFactory
     * @param ReviewInterfaceFactory $reviewDataFactory
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     * @param DataObjectHelper $dataObjectHelper
     */
    public function __construct(
        ReviewFactory $reviewFactory,
        ReviewResource $reviewResource,
        ReviewCollectionFactory $reviewCollectionFactory,
        ReviewInterfaceFactory $reviewDataFactory,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        DataObjectHelper $dataObjectHelper
    ) {
        $this->reviewFactory = $reviewFactory;
        $this->reviewResource = $reviewResource;
        $this->reviewCollectionFactory = $reviewCollectionFactory;
        $this->reviewDataFactory = $reviewDataFactory;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->dataObjectHelper = $dataObjectHelper;
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
                ->addStatusFilter(\Magento\Review\Model\Review::STATUS_APPROVED)
                ->addStoreFilter($this->storeManager->getStore()->getId())
                ->setDateOrder();

            $reviews = [];
            $storeId = $this->storeManager->getStore()->getId(); // Get current store ID
            
            foreach ($collection as $reviewModel) {
                $reviewData = $this->reviewDataFactory->create();
                
                // Pass the store ID to getEntitySummary()
                $reviewModel->getEntitySummary($reviewModel, $storeId);
                
                $data = [
                    'id' => $reviewModel->getId(),
                    'title' => $reviewModel->getTitle(),
                    'nickname' => $reviewModel->getNickname(),
                    'detail' => $reviewModel->getDetail(),
                    'product_sku' => $sku,
                    'ratings' => 0
                ];

                $this->dataObjectHelper->populateWithArray($reviewData, $data, ReviewInterface::class);
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
        
        $data = [
            'id' => $reviewModel->getId(),
            'title' => $reviewModel->getTitle(),
            'nickname' => $reviewModel->getNickname(),
            'detail' => $reviewModel->getDetail(),
            'product_sku' => $product->getSku(),
            // Simplified ratings
            'ratings' => 0
        ];

        $this->dataObjectHelper->populateWithArray($reviewData, $data, ReviewInterface::class);
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
            $reviewModel->setData([
                'nickname' => $review->getNickname(),
                'title' => $review->getTitle(),
                'detail' => $review->getDetail(),
                'entity_id' => 1, // 1 = product
                'entity_pk_value' => $product->getId(),
                'status_id' => \Magento\Review\Model\Review::STATUS_PENDING, // Pending approval
                'store_id' => $this->storeManager->getStore()->getId(),
                'stores' => [$this->storeManager->getStore()->getId()]
            ]);

            $this->reviewResource->save($reviewModel);
            
            // Handle ratings if needed - this is simplified
            // You'd need to implement rating logic based on your store's configured rating options
            
            $review->setId($reviewModel->getId());
            return $review;
            
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save review: %1', $e->getMessage()), $e);
        }
    }
}