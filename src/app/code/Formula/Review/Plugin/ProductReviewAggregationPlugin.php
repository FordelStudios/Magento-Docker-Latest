<?php
declare(strict_types=1);

namespace Formula\Review\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Plugin to add review aggregation data (rating_summary, reviews_count)
 * to product extension attributes in the Product API response.
 */
class ProductReviewAggregationPlugin
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array In-memory cache for batch-loaded review data
     */
    private $reviewDataCache = [];

    /**
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Add review aggregation data to a single product
     *
     * @param ProductRepositoryInterface $subject
     * @param ProductInterface $product
     * @return ProductInterface
     */
    public function afterGet(
        ProductRepositoryInterface $subject,
        ProductInterface $product
    ): ProductInterface {
        $this->addReviewAggregationData($product);
        return $product;
    }

    /**
     * Add review aggregation data to product list (batch optimized)
     *
     * @param ProductRepositoryInterface $subject
     * @param ProductSearchResultsInterface $searchResults
     * @return ProductSearchResultsInterface
     */
    public function afterGetList(
        ProductRepositoryInterface $subject,
        ProductSearchResultsInterface $searchResults
    ): ProductSearchResultsInterface {
        $products = $searchResults->getItems();

        if (empty($products)) {
            return $searchResults;
        }

        // Collect all product IDs for batch query
        $productIds = [];
        foreach ($products as $product) {
            $productIds[] = $product->getId();
        }

        // Batch load review data for all products in a single query
        $this->batchLoadReviewData($productIds);

        // Apply data to each product
        foreach ($products as $product) {
            $this->addReviewAggregationData($product);
        }

        return $searchResults;
    }

    /**
     * Batch load review aggregation data for multiple products
     *
     * Executes a single optimized query to fetch both reviews_count
     * and rating_summary for all provided product IDs.
     *
     * @param array $productIds
     * @return void
     */
    private function batchLoadReviewData(array $productIds): void
    {
        if (empty($productIds)) {
            return;
        }

        try {
            $connection = $this->resourceConnection->getConnection();
            $reviewTable = $this->resourceConnection->getTableName('review');
            $voteTable = $this->resourceConnection->getTableName('rating_option_vote');

            // Single query to get both review count and average rating
            // Only count APPROVED reviews (status_id = 1)
            $select = $connection->select()
                ->from(
                    ['r' => $reviewTable],
                    [
                        'entity_pk_value',
                        'reviews_count' => new \Zend_Db_Expr('COUNT(DISTINCT r.review_id)')
                    ]
                )
                ->joinLeft(
                    ['v' => $voteTable],
                    'r.review_id = v.review_id',
                    [
                        'rating_summary' => new \Zend_Db_Expr('ROUND(AVG(v.value), 2)')
                    ]
                )
                ->where('r.entity_pk_value IN (?)', $productIds)
                ->where('r.entity_id = ?', 1) // 1 = product entity
                ->where('r.status_id = ?', 1) // 1 = approved
                ->group('r.entity_pk_value');

            $results = $connection->fetchAll($select);

            // Index by product ID for O(1) lookup
            foreach ($results as $row) {
                $this->reviewDataCache[$row['entity_pk_value']] = [
                    'rating_summary' => $row['rating_summary'] !== null ? (float)$row['rating_summary'] : 0.0,
                    'reviews_count' => (int)$row['reviews_count']
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('Error loading review aggregation data: ' . $e->getMessage());
        }
    }

    /**
     * Add review aggregation data to a single product's extension attributes
     *
     * @param ProductInterface $product
     * @return void
     */
    private function addReviewAggregationData(ProductInterface $product): void
    {
        try {
            $productId = $product->getId();

            // Check cache first, load if not present
            if (!isset($this->reviewDataCache[$productId])) {
                $this->batchLoadReviewData([$productId]);
            }

            $extensionAttributes = $product->getExtensionAttributes();

            if ($extensionAttributes) {
                // Get cached data or default values for products with no reviews
                $data = $this->reviewDataCache[$productId] ?? [
                    'rating_summary' => 0.0,
                    'reviews_count' => 0
                ];

                $extensionAttributes->setRatingSummary($data['rating_summary']);
                $extensionAttributes->setReviewsCount($data['reviews_count']);
                $product->setExtensionAttributes($extensionAttributes);
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'Error adding review aggregation to product: ' . $product->getSku(),
                ['exception' => $e->getMessage()]
            );
        }
    }
}
