<?php
/**
 * Daily Counter Model
 * Manages atomic increment of daily order counter for custom order ID format
 */

declare(strict_types=1);

namespace Formula\OrderIncrementId\Model;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class DailyCounter
{
    private const TABLE_NAME = 'formula_order_daily_counter';

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

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
     * Get and increment the daily counter atomically
     *
     * @param int $storeId
     * @return int The next counter value
     */
    public function getNextCounter(int $storeId = 1): int
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(self::TABLE_NAME);
        $today = date('Y-m-d');

        try {
            // Use INSERT ... ON DUPLICATE KEY UPDATE for atomic operation
            // This handles race conditions by using MySQL's atomic increment
            $sql = "INSERT INTO {$tableName} (store_id, order_date, daily_counter, created_at, updated_at)
                    VALUES (:store_id, :order_date, 1, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE
                        daily_counter = daily_counter + 1,
                        updated_at = NOW()";

            $connection->query($sql, [
                'store_id' => $storeId,
                'order_date' => $today
            ]);

            // Fetch the current counter value
            $select = $connection->select()
                ->from($tableName, ['daily_counter'])
                ->where('store_id = ?', $storeId)
                ->where('order_date = ?', $today);

            $counter = (int) $connection->fetchOne($select);

            $this->logger->info(
                'Formula_OrderIncrementId: Generated counter',
                [
                    'store_id' => $storeId,
                    'date' => $today,
                    'counter' => $counter
                ]
            );

            return $counter;

        } catch (\Exception $e) {
            $this->logger->error(
                'Formula_OrderIncrementId: Error generating counter',
                [
                    'store_id' => $storeId,
                    'date' => $today,
                    'error' => $e->getMessage()
                ]
            );

            // Fallback: return timestamp-based unique number to prevent order failure
            return (int) date('His');
        }
    }

    /**
     * Get current counter value without incrementing
     *
     * @param int $storeId
     * @return int
     */
    public function getCurrentCounter(int $storeId = 1): int
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(self::TABLE_NAME);
        $today = date('Y-m-d');

        $select = $connection->select()
            ->from($tableName, ['daily_counter'])
            ->where('store_id = ?', $storeId)
            ->where('order_date = ?', $today);

        $counter = $connection->fetchOne($select);

        return $counter ? (int) $counter : 0;
    }

    /**
     * Preview the next order ID without incrementing
     *
     * @param int $storeId
     * @return string
     */
    public function previewNextOrderId(int $storeId = 1): string
    {
        $nextCounter = $this->getCurrentCounter($storeId) + 1;
        $date = date('dmy');

        return sprintf('TFS-%s-%04d', $date, $nextCounter);
    }
}
