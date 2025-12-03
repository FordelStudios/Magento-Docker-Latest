<?php
declare(strict_types=1);

namespace Formula\Review\Service;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Service to sync review aggregation data to product EAV attributes
 */
class ReviewAggregationSync
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ProductRepositoryInterface $productRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    /**
     * Sync rating data for a single product by product ID
     *
     * @param int $productId
     * @return void
     */
    public function syncProductById(int $productId): void
    {
        try {
            $aggregationData = $this->getAggregationDataForProducts([$productId]);
            $data = $aggregationData[$productId] ?? [
                'rating_summary' => 0,
                'reviews_count' => 0
            ];

            $this->updateProductAttributes($productId, $data);
        } catch (\Exception $e) {
            $this->logger->error('Error syncing review aggregation for product ' . $productId . ': ' . $e->getMessage());
        }
    }

    /**
     * Sync rating data for all products that have reviews
     *
     * @return int Number of products synced
     */
    public function syncAllProducts(): int
    {
        try {
            $connection = $this->resourceConnection->getConnection();

            // Get all product IDs that have reviews
            $reviewTable = $this->resourceConnection->getTableName('review');
            $select = $connection->select()
                ->from($reviewTable, ['entity_pk_value'])
                ->where('entity_id = ?', 1) // product entity
                ->distinct(true);

            $productIds = $connection->fetchCol($select);

            if (empty($productIds)) {
                return 0;
            }

            // Get aggregation data for all products
            $aggregationData = $this->getAggregationDataForProducts($productIds);

            // Update each product
            $count = 0;
            foreach ($productIds as $productId) {
                $data = $aggregationData[$productId] ?? [
                    'rating_summary' => 0,
                    'reviews_count' => 0
                ];

                $this->updateProductAttributes((int)$productId, $data);
                $count++;
            }

            $this->logger->info('Synced review aggregation for ' . $count . ' products');
            return $count;
        } catch (\Exception $e) {
            $this->logger->error('Error syncing all product review aggregations: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get aggregation data for multiple products
     *
     * @param array $productIds
     * @return array
     */
    private function getAggregationDataForProducts(array $productIds): array
    {
        $connection = $this->resourceConnection->getConnection();
        $reviewTable = $this->resourceConnection->getTableName('review');
        $voteTable = $this->resourceConnection->getTableName('rating_option_vote');

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
            ->where('r.entity_id = ?', 1)
            ->group('r.entity_pk_value');

        $results = $connection->fetchAll($select);

        $data = [];
        foreach ($results as $row) {
            $data[$row['entity_pk_value']] = [
                'rating_summary' => $row['rating_summary'] !== null ? (float)$row['rating_summary'] : 0.0,
                'reviews_count' => (int)$row['reviews_count']
            ];
        }

        return $data;
    }

    /**
     * Update product EAV attributes directly via SQL for performance
     *
     * @param int $productId
     * @param array $data
     * @return void
     */
    private function updateProductAttributes(int $productId, array $data): void
    {
        $connection = $this->resourceConnection->getConnection();

        // Get attribute IDs
        $eavAttributeTable = $this->resourceConnection->getTableName('eav_attribute');
        $entityTypeTable = $this->resourceConnection->getTableName('eav_entity_type');

        // Get product entity type ID
        $entityTypeId = $connection->fetchOne(
            $connection->select()
                ->from($entityTypeTable, ['entity_type_id'])
                ->where('entity_type_code = ?', 'catalog_product')
        );

        // Get attribute IDs for rating_summary and reviews_count
        $ratingSummaryAttrId = $connection->fetchOne(
            $connection->select()
                ->from($eavAttributeTable, ['attribute_id'])
                ->where('attribute_code = ?', 'rating_summary')
                ->where('entity_type_id = ?', $entityTypeId)
        );

        $reviewsCountAttrId = $connection->fetchOne(
            $connection->select()
                ->from($eavAttributeTable, ['attribute_id'])
                ->where('attribute_code = ?', 'reviews_count')
                ->where('entity_type_id = ?', $entityTypeId)
        );

        if (!$ratingSummaryAttrId || !$reviewsCountAttrId) {
            $this->logger->warning('Review aggregation attributes not found. Run setup:upgrade first.');
            return;
        }

        // Update rating_summary (decimal)
        $decimalTable = $this->resourceConnection->getTableName('catalog_product_entity_decimal');
        $connection->insertOnDuplicate(
            $decimalTable,
            [
                'attribute_id' => $ratingSummaryAttrId,
                'store_id' => 0,
                'entity_id' => $productId,
                'value' => $data['rating_summary']
            ],
            ['value']
        );

        // Update reviews_count (int)
        $intTable = $this->resourceConnection->getTableName('catalog_product_entity_int');
        $connection->insertOnDuplicate(
            $intTable,
            [
                'attribute_id' => $reviewsCountAttrId,
                'store_id' => 0,
                'entity_id' => $productId,
                'value' => $data['reviews_count']
            ],
            ['value']
        );
    }
}
