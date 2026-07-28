<?php
declare(strict_types=1);

namespace Formula\Shadowfax\Model;

use Formula\Shadowfax\Model\Config\OrderStatus;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

/**
 * Looks up the Magento order a ShadowFax tracking-webhook call refers to
 * (by shadowfax_awb, falling back to shadowfax_shipment_id), maps the
 * external status via StatusMapper, and applies the resulting internal
 * status/state + a status-history comment.
 *
 * Deliberately thin: no refund/inventory side effects (unlike Shiprocket's
 * handler) because at P1 scope ShadowFax cancellation/RTO refund handling is
 * out of scope for A4 — status/state sync only. Extend here, not in the
 * webhook entrypoint, if that scope grows.
 *
 * @todo P1 scope question — deferred, do NOT build yet: when ShadowFax sends
 *       CANCELLED, should we fire a real Order::cancel() (with restock +
 *       refund) or, as today, only flip the status flag? That depends on
 *       ShadowFax's actual CANCELLED semantics (is it a merchant cancel, a
 *       failed pickup, ...?) plus client intent, neither confirmed yet.
 *       Current behaviour is status-only on purpose.
 *
 * Replay / out-of-order safety: courier webhooks are at-least-once and arrive
 * out of order. Two guards protect a completed/paid order — a terminal-STATE
 * guard (never overwrite an order already complete/canceled/closed) and an
 * idempotency guard (never re-save / double-comment when the status is
 * unchanged). See handleTrackingUpdate().
 *
 * Never throws back to ShadowFax for "order not found" or "unknown status" —
 * webhooks must ack gracefully so ShadowFax doesn't retry-storm us — but both
 * cases are logged at warning level so silent data-quality issues (a typo'd
 * AWB, a new status we haven't mapped yet) are visible in the logs rather
 * than swallowed.
 */
class ShadowfaxWebhookHandler
{
    private const FIELD_AWB = 'shadowfax_awb';
    private const FIELD_SHIPMENT_ID = 'shadowfax_shipment_id';

    /**
     * Magento order states from which a delivery webhook must never move an
     * order. Keyed on STATE (not our status codes) so natively-terminated
     * orders are protected too.
     */
    private const TERMINAL_STATES = [
        Order::STATE_COMPLETE,
        Order::STATE_CANCELED,
        Order::STATE_CLOSED,
    ];

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var StatusMapper
     */
    private $statusMapper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param StatusMapper $statusMapper
     * @param LoggerInterface $logger
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        StatusMapper $statusMapper,
        LoggerInterface $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->statusMapper = $statusMapper;
        $this->logger = $logger;
    }

    /**
     * @param string|null $awb
     * @param string|null $shipmentId
     * @param string|null $externalStatus
     * @return array{success: bool, message: string}
     */
    public function handleTrackingUpdate(?string $awb, ?string $shipmentId, ?string $externalStatus): array
    {
        if ($externalStatus === null || trim($externalStatus) === '') {
            $this->logger->warning('Shadowfax webhook: payload missing external status', [
                'awb' => $awb,
                'shipment_id' => $shipmentId,
            ]);
            return ['success' => true, 'message' => 'Missing status in payload, no action taken'];
        }

        $order = $this->findOrder($awb, $shipmentId);
        if ($order === null) {
            $this->logger->warning('Shadowfax webhook: no order found for AWB/shipment ID', [
                'awb' => $awb,
                'shipment_id' => $shipmentId,
                'external_status' => $externalStatus,
            ]);
            return ['success' => true, 'message' => 'Order not found, no action taken'];
        }

        $internalStatus = $this->statusMapper->mapExternalStatus($externalStatus);
        if ($internalStatus === null) {
            $this->logger->warning('Shadowfax webhook: unrecognised external status, not in assumed vocabulary', [
                'order' => $order->getIncrementId(),
                'external_status' => $externalStatus,
            ]);
            return ['success' => true, 'message' => 'Unknown status, no action taken'];
        }

        // Terminal-state guard. Courier webhooks are at-least-once and arrive
        // OUT OF ORDER, so a stale IN_TRANSIT can land after DELIVERED and a
        // replayed CANCELLED can land after a completed/paid order. If the
        // order is already in a terminal Magento STATE (not just one of OUR
        // status codes — using the state protects natively-terminated orders
        // too), never overwrite it from a delivery webhook: log and ack.
        $currentState = (string) $order->getState();
        if (in_array($currentState, self::TERMINAL_STATES, true)) {
            $this->logger->info('Shadowfax webhook: ignoring status for order already in terminal state', [
                'order' => $order->getIncrementId(),
                'external_status' => $externalStatus,
                'internal_status' => $internalStatus,
                'current_state' => $currentState,
            ]);
            return ['success' => true, 'message' => 'Order already in terminal state, no action taken'];
        }

        // Idempotency guard. A duplicate delivery of the same status must not
        // re-save the order or append a second identical status-history
        // comment. Only mutate when the status actually changes.
        if ((string) $order->getStatus() === $internalStatus) {
            $this->logger->info('Shadowfax webhook: status unchanged, skipping duplicate update', [
                'order' => $order->getIncrementId(),
                'internal_status' => $internalStatus,
                'external_status' => $externalStatus,
            ]);
            return ['success' => true, 'message' => 'Status unchanged, no action taken'];
        }

        $this->applyStatus($order, $internalStatus, $externalStatus);

        $this->logger->info('Shadowfax webhook: order status updated', [
            'order' => $order->getIncrementId(),
            'internal_status' => $internalStatus,
            'external_status' => $externalStatus,
        ]);

        return ['success' => true, 'message' => 'Order status updated to ' . $internalStatus];
    }

    /**
     * @param OrderInterface $order
     * @param string $internalStatus
     * @param string $externalStatus
     * @return void
     */
    private function applyStatus(OrderInterface $order, string $internalStatus, string $externalStatus): void
    {
        $statusMap = OrderStatus::getStatusMap();
        $state = $statusMap[$internalStatus]['state'] ?? Order::STATE_PROCESSING;
        $label = $statusMap[$internalStatus]['label'] ?? $internalStatus;

        $order->setState($state);
        $order->setStatus($internalStatus);

        // addCommentToStatusHistory() is a Magento\Sales\Model\Order method, not
        // declared on OrderInterface itself — same loose-typing pattern
        // Formula_Shiprocket and Model\Request\ManifestPayloadBuilder use.
        if (method_exists($order, 'addCommentToStatusHistory')) {
            $order->addCommentToStatusHistory(sprintf(
                '[ShadowFax] %s (external status: %s)',
                $label,
                $externalStatus
            ));
        }

        $this->orderRepository->save($order);
    }

    /**
     * @param string|null $awb
     * @param string|null $shipmentId
     * @return OrderInterface|null
     */
    private function findOrder(?string $awb, ?string $shipmentId): ?OrderInterface
    {
        if ($awb !== null && trim($awb) !== '') {
            $order = $this->findOrderByField(self::FIELD_AWB, $awb);
            if ($order !== null) {
                return $order;
            }
        }

        if ($shipmentId !== null && trim($shipmentId) !== '') {
            return $this->findOrderByField(self::FIELD_SHIPMENT_ID, $shipmentId);
        }

        return null;
    }

    /**
     * @param string $field
     * @param string $value
     * @return OrderInterface|null
     */
    private function findOrderByField(string $field, string $value): ?OrderInterface
    {
        $filter = $this->filterBuilder
            ->setField($field)
            ->setValue($value)
            ->setConditionType('eq')
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilters([$filter])
            ->setPageSize(1)
            ->create();

        $items = $this->orderRepository->getList($searchCriteria)->getItems();

        return !empty($items) ? array_shift($items) : null;
    }
}
