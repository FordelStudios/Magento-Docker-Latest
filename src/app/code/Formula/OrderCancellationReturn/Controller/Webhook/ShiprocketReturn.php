<?php
/**
 * Shiprocket Return Webhook Controller
 *
 * Processes webhook notifications from Shiprocket when a return is delivered.
 * This triggers the deferred refund that was held when the return was requested.
 *
 * SECURITY: Refunds are only processed after products are physically received,
 * preventing customers from getting refunds without returning products.
 */
namespace Formula\OrderCancellationReturn\Controller\Webhook;

use Formula\OrderCancellationReturn\Service\RefundProcessor;
use Formula\OrderCancellationReturn\Service\InventoryService;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Psr\Log\LoggerInterface;

class ShiprocketReturn implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var RefundProcessor
     */
    private $refundProcessor;

    /**
     * @var InventoryService
     */
    private $inventoryService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Valid return delivery statuses from Shiprocket
     */
    private const VALID_DELIVERY_STATUSES = [
        'return_delivered',
        'return_received',
        'rto_delivered',  // Return to Origin delivered
        'pickup_complete'
    ];

    /**
     * @param RequestInterface $request
     * @param JsonFactory $jsonFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RefundProcessor $refundProcessor
     * @param InventoryService $inventoryService
     * @param LoggerInterface $logger
     */
    public function __construct(
        RequestInterface $request,
        JsonFactory $jsonFactory,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RefundProcessor $refundProcessor,
        InventoryService $inventoryService,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->refundProcessor = $refundProcessor;
        $this->inventoryService = $inventoryService;
        $this->logger = $logger;
    }

    /**
     * Process Shiprocket return webhook
     *
     * Expected payload format:
     * {
     *     "order_id": "SR12345",
     *     "awb": "AWB123456",
     *     "current_status": "return_delivered",
     *     "current_status_id": 16,
     *     "shipment_status": "Return Delivered",
     *     "shipment_status_id": 16,
     *     "etd": "2024-01-15",
     *     "scans": [...]
     * }
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            // Parse webhook payload
            $payload = $this->getPayload();

            if (empty($payload)) {
                $this->logger->error('Shiprocket return webhook: Empty payload received');
                return $result->setData([
                    'success' => false,
                    'message' => 'Empty payload'
                ]);
            }

            $this->logger->info('Shiprocket return webhook received', [
                'payload' => json_encode($payload)
            ]);

            // Validate status - only process delivery confirmations
            $status = $payload['current_status'] ?? '';
            if (!$this->isValidDeliveryStatus($status)) {
                $this->logger->info('Shiprocket return webhook: Ignoring non-delivery status', [
                    'status' => $status
                ]);
                return $result->setData([
                    'success' => true,
                    'message' => 'Status ignored: ' . $status
                ]);
            }

            // Find the order by Shiprocket return ID or AWB
            $order = $this->findOrderByShiprocketData($payload);

            if (!$order) {
                $this->logger->warning('Shiprocket return webhook: Order not found', [
                    'shiprocket_order_id' => $payload['order_id'] ?? 'N/A',
                    'awb' => $payload['awb'] ?? 'N/A'
                ]);
                return $result->setData([
                    'success' => false,
                    'message' => 'Order not found'
                ]);
            }

            // Validate order is in return status
            $allowedStatuses = ['return_requested', 'return_pickup_scheduled', 'return_in_transit'];
            if (!in_array($order->getStatus(), $allowedStatuses)) {
                $this->logger->warning('Shiprocket return webhook: Invalid order status', [
                    'order_id' => $order->getId(),
                    'status' => $order->getStatus()
                ]);
                return $result->setData([
                    'success' => false,
                    'message' => 'Invalid order status: ' . $order->getStatus()
                ]);
            }

            // Process the return completion
            $refundResult = $this->processReturnCompletion($order, $payload);

            return $result->setData([
                'success' => true,
                'message' => 'Return processed successfully',
                'order_id' => $order->getIncrementId(),
                'refund_amount' => $refundResult['refund_amount'] ?? 0,
                'refund_method' => $refundResult['refund_method'] ?? 'unknown'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Shiprocket return webhook error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return $result->setData([
                'success' => false,
                'message' => 'Internal error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Process return completion - refund and inventory restoration
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param array $payload
     * @return array
     */
    private function processReturnCompletion($order, array $payload): array
    {
        // Get pending refund details
        $pendingRefundAmount = $order->getData('pending_return_refund_amount');

        if (!$pendingRefundAmount || $pendingRefundAmount <= 0) {
            $this->logger->warning('No pending refund amount found for return', [
                'order_id' => $order->getId()
            ]);
            // Still update status but note no refund was pending
            $order->setStatus('return_completed');
            $order->addStatusHistoryComment(
                'Return received (Shiprocket webhook). No pending refund found.',
                'return_completed'
            );
            $this->orderRepository->save($order);

            return ['refund_amount' => 0, 'refund_method' => 'none'];
        }

        // Process the deferred refund
        $refundResult = $this->refundProcessor->processRefund($order, 'return');

        // Restore inventory
        $restoredItems = $this->inventoryService->restoreInventoryForReturn($order);
        $inventoryMessage = $this->inventoryService->createInventoryRestorationMessage($restoredItems);

        // Update order status
        $order->setStatus('return_completed');

        // Build status message
        $statusMessage = sprintf(
            'Return received and verified (Shiprocket webhook: %s). Refund processed: %s.%s',
            $payload['current_status'] ?? 'unknown',
            $refundResult['status_message'] ?? $refundResult['refund_amount'],
            $inventoryMessage
        );

        $order->addStatusHistoryComment($statusMessage, 'return_completed');

        // Clear pending refund data
        $order->setData('pending_return_refund_amount', null);
        $order->setData('pending_return_refund_method', null);
        $order->setData('pending_return_wallet_amount', null);

        $this->orderRepository->save($order);

        $this->logger->info('Return refund processed via webhook', [
            'order_id' => $order->getId(),
            'increment_id' => $order->getIncrementId(),
            'refund_amount' => $refundResult['refund_amount'],
            'refund_method' => $refundResult['refund_method']
        ]);

        return $refundResult;
    }

    /**
     * Find order by Shiprocket data
     *
     * @param array $payload
     * @return \Magento\Sales\Api\Data\OrderInterface|null
     */
    private function findOrderByShiprocketData(array $payload)
    {
        $shiprocketOrderId = $payload['order_id'] ?? null;
        $awb = $payload['awb'] ?? null;

        // Try to find by return ID first
        if ($shiprocketOrderId) {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('shiprocket_return_id', $shiprocketOrderId)
                ->create();

            $orders = $this->orderRepository->getList($searchCriteria);
            if ($orders->getTotalCount() > 0) {
                $items = $orders->getItems();
                return reset($items);
            }
        }

        // Try by AWB
        if ($awb) {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('shiprocket_awb_number', $awb)
                ->create();

            $orders = $this->orderRepository->getList($searchCriteria);
            if ($orders->getTotalCount() > 0) {
                $items = $orders->getItems();
                return reset($items);
            }
        }

        return null;
    }

    /**
     * Check if status indicates delivery completion
     *
     * @param string $status
     * @return bool
     */
    private function isValidDeliveryStatus(string $status): bool
    {
        return in_array(strtolower($status), self::VALID_DELIVERY_STATUSES);
    }

    /**
     * Get parsed payload from request
     *
     * @return array
     */
    private function getPayload(): array
    {
        $content = $this->request->getContent();

        if (empty($content)) {
            return [];
        }

        $payload = json_decode($content, true);

        return is_array($payload) ? $payload : [];
    }

    /**
     * @inheritdoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritdoc
     * Disable CSRF validation for webhook endpoint
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
