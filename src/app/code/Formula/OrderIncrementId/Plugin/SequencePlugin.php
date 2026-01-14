<?php
/**
 * Sequence Plugin
 * Intercepts Magento's order sequence generation to apply custom format
 * Format: TFS-DDMMYY-NNNN (e.g., TFS-140126-0015)
 */

declare(strict_types=1);

namespace Formula\OrderIncrementId\Plugin;

use Formula\OrderIncrementId\Model\DailyCounter;
use Magento\SalesSequence\Model\Sequence;
use Psr\Log\LoggerInterface;

class SequencePlugin
{
    /**
     * Order ID prefix
     */
    private const PREFIX = 'TFS';

    /**
     * @var DailyCounter
     */
    private DailyCounter $dailyCounter;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param DailyCounter $dailyCounter
     * @param LoggerInterface $logger
     */
    public function __construct(
        DailyCounter $dailyCounter,
        LoggerInterface $logger
    ) {
        $this->dailyCounter = $dailyCounter;
        $this->logger = $logger;
    }

    /**
     * Intercept getNextValue to generate custom order ID format
     *
     * @param Sequence $subject
     * @param callable $proceed
     * @return string
     */
    public function aroundGetNextValue(
        Sequence $subject,
        callable $proceed
    ): string {
        try {
            // Access private $meta property via Reflection (no public getter exists)
            // Must reflect on parent Sequence class, not the generated Interceptor
            $reflection = new \ReflectionClass(\Magento\SalesSequence\Model\Sequence::class);
            $metaProperty = $reflection->getProperty('meta');
            $metaProperty->setAccessible(true);
            $meta = $metaProperty->getValue($subject);

            $entityType = $meta->getEntityType();

            // Only customize ORDER increment IDs
            // Let invoice, shipment, creditmemo use default Magento format
            if ($entityType !== 'order') {
                return $proceed();
            }

            $storeId = (int) $meta->getStoreId();

            // Get date in DDMMYY format
            $date = date('dmy');

            // Get next counter (atomic increment)
            $counter = $this->dailyCounter->getNextCounter($storeId);

            // Format: TFS-DDMMYY-NNNN
            $customOrderId = sprintf('%s-%s-%04d', self::PREFIX, $date, $counter);

            $this->logger->info(
                'Formula_OrderIncrementId: Generated custom order ID',
                [
                    'order_id' => $customOrderId,
                    'store_id' => $storeId,
                    'entity_type' => $entityType
                ]
            );

            return $customOrderId;

        } catch (\Exception $e) {
            $this->logger->error(
                'Formula_OrderIncrementId: Error generating custom order ID, falling back to default',
                [
                    'error' => $e->getMessage()
                ]
            );

            // Fallback to default Magento behavior on error
            return $proceed();
        }
    }
}
