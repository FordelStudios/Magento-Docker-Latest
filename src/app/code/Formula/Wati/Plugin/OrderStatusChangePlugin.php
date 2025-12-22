<?php
declare(strict_types=1);

namespace Formula\Wati\Plugin;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Formula\Wati\Service\WatiApiService;
use Formula\Wati\Helper\Data as WatiHelper;
use Psr\Log\LoggerInterface;

/**
 * Plugin to detect order status changes and send WhatsApp notifications
 */
class OrderStatusChangePlugin
{
    /**
     * @var WatiApiService
     */
    protected $watiApiService;

    /**
     * @var WatiHelper
     */
    protected $watiHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Track previous status to avoid duplicate notifications
     *
     * @var array
     */
    protected $previousStatuses = [];

    /**
     * Statuses that should trigger notifications
     *
     * @var array
     */
    protected $notifiableStatuses = [
        'processing',
        'shipped',
        'in_transit',
        'out_for_delivery',
        'delivered',
        'complete',
        'canceled',
        'cancelled',
        'closed',
        'refunded'
    ];

    /**
     * @param WatiApiService $watiApiService
     * @param WatiHelper $watiHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        WatiApiService $watiApiService,
        WatiHelper $watiHelper,
        LoggerInterface $logger
    ) {
        $this->watiApiService = $watiApiService;
        $this->watiHelper = $watiHelper;
        $this->logger = $logger;
    }

    /**
     * Before save - capture original status
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $order
     * @return array
     */
    public function beforeSave(
        OrderRepositoryInterface $subject,
        OrderInterface $order
    ): array {
        if ($order->getId()) {
            // Get the original status before the save
            $this->previousStatuses[$order->getId()] = $order->getOrigData('status');
        }
        return [$order];
    }

    /**
     * After save - check if status changed and send notification
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $order
     * @return OrderInterface
     */
    public function afterSave(
        OrderRepositoryInterface $subject,
        OrderInterface $order
    ): OrderInterface {
        if (!$this->watiHelper->isEnabled()) {
            return $order;
        }

        $orderId = $order->getId();
        $newStatus = $order->getStatus();
        $previousStatus = $this->previousStatuses[$orderId] ?? null;

        // Clear stored status
        unset($this->previousStatuses[$orderId]);

        // Skip if status hasn't changed
        if ($previousStatus === $newStatus) {
            return $order;
        }

        // Skip if new status is 'pending' (handled by OrderPlaceAfter observer)
        if (in_array($newStatus, ['pending', 'pending_payment'])) {
            return $order;
        }

        // Check if this status should trigger a notification
        if (!in_array(strtolower($newStatus), $this->notifiableStatuses)) {
            return $order;
        }

        // Skip virtual orders
        if ($order->getIsVirtual()) {
            return $order;
        }

        try {
            $this->logger->info('Wati: Order status changed', [
                'order_id' => $order->getIncrementId(),
                'previous_status' => $previousStatus,
                'new_status' => $newStatus
            ]);

            $result = $this->watiApiService->sendOrderStatusNotification($order, $newStatus);

            if (!$result['success']) {
                $this->logger->warning('Wati: Status change notification failed', [
                    'order_id' => $order->getIncrementId(),
                    'status' => $newStatus,
                    'error' => $result['error'] ?? 'Unknown'
                ]);
            }

        } catch (\Exception $e) {
            // Log but don't fail - notifications should never break order processing
            $this->logger->error('Wati: Exception in status change plugin', [
                'order_id' => $order->getIncrementId(),
                'status' => $newStatus,
                'exception' => $e->getMessage()
            ]);
        }

        return $order;
    }
}
