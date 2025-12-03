<?php
declare(strict_types=1);

namespace Formula\Review\Cron;

use Formula\Review\Service\ReviewAggregationSync;
use Psr\Log\LoggerInterface;

/**
 * Cron job to sync review aggregation data for all products
 */
class SyncReviewAggregation
{
    /**
     * @var ReviewAggregationSync
     */
    private $reviewAggregationSync;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ReviewAggregationSync $reviewAggregationSync
     * @param LoggerInterface $logger
     */
    public function __construct(
        ReviewAggregationSync $reviewAggregationSync,
        LoggerInterface $logger
    ) {
        $this->reviewAggregationSync = $reviewAggregationSync;
        $this->logger = $logger;
    }

    /**
     * Execute cron job
     *
     * @return void
     */
    public function execute(): void
    {
        $this->logger->info('Starting review aggregation sync cron job');

        $count = $this->reviewAggregationSync->syncAllProducts();

        $this->logger->info('Review aggregation sync completed. Products synced: ' . $count);
    }
}
