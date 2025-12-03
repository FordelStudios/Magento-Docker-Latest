<?php
declare(strict_types=1);

namespace Formula\Review\Observer;

use Formula\Review\Service\ReviewAggregationSync;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

/**
 * Observer to sync product review aggregation after a review is deleted
 */
class ReviewDeleteAfter implements ObserverInterface
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
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $review = $observer->getEvent()->getObject();

            if ($review && $review->getEntityPkValue()) {
                $productId = (int)$review->getEntityPkValue();
                $this->reviewAggregationSync->syncProductById($productId);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error in ReviewDeleteAfter observer: ' . $e->getMessage());
        }
    }
}
