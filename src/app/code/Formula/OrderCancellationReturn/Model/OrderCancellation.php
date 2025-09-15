<?php
namespace Formula\OrderCancellationReturn\Model;

use Formula\OrderCancellationReturn\Api\OrderCancellationInterface;
use Formula\OrderCancellationReturn\Api\Data\RefundResponseInterface;
use Formula\OrderCancellationReturn\Api\Data\RefundResponseInterfaceFactory;
use Formula\OrderCancellationReturn\Service\OrderValidator;
use Formula\OrderCancellationReturn\Service\RefundProcessor;
use Formula\OrderCancellationReturn\Service\InventoryService;
use Formula\Shiprocket\Service\ShiprocketShipmentService;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class OrderCancellation implements OrderCancellationInterface
{
    protected $orderValidator;
    protected $refundProcessor;
    protected $inventoryService;
    protected $shiprocketShipmentService;
    protected $orderRepository;
    protected $refundResponseFactory;
    protected $logger;

    public function __construct(
        OrderValidator $orderValidator,
        RefundProcessor $refundProcessor,
        InventoryService $inventoryService,
        ShiprocketShipmentService $shiprocketShipmentService,
        OrderRepositoryInterface $orderRepository,
        RefundResponseInterfaceFactory $refundResponseFactory,
        LoggerInterface $logger
    ) {
        $this->orderValidator = $orderValidator;
        $this->refundProcessor = $refundProcessor;
        $this->inventoryService = $inventoryService;
        $this->shiprocketShipmentService = $shiprocketShipmentService;
        $this->orderRepository = $orderRepository;
        $this->refundResponseFactory = $refundResponseFactory;
        $this->logger = $logger;
    }

    /**
     * Cancel an order for a customer
     *
     * @param int $customerId Customer ID
     * @param int $orderId Order ID to cancel
     * @param string|null $reason Optional reason for cancellation
     * @return \Formula\OrderCancellationReturn\Api\Data\RefundResponseInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function cancelOrder($customerId, $orderId, $reason = null)
    {
        $response = $this->refundResponseFactory->create();

        try {
            // Validate order for cancellation
            $order = $this->orderValidator->validateCancellation($customerId, $orderId);

            // Process refund if needed
            $refundResult = $this->refundProcessor->processRefund($order, 'cancel');

            // Restore inventory for cancelled order (not delivered yet)
            $restoredItems = $this->inventoryService->restoreInventoryForCancellation($order);

            // Cancel shipment if exists
            $shipmentCancellationResult = $this->cancelShipmentIfExists($order);

            // Update order status to cancelled
            $order->setState(Order::STATE_CANCELED);
            $order->setStatus(Order::STATE_CANCELED);
            
            $cancelReason = $reason ? ': ' . $reason : '';
            $statusMessage = 'Order cancelled by customer' . $cancelReason . '. ';
            
            // Add detailed refund information
            if (isset($refundResult['status_message'])) {
                $statusMessage .= $refundResult['status_message'];
            } else {
                // Fallback for legacy format
                $statusMessage .= 'Refund method: ' . $refundResult['refund_method'] . '. Refund amount: ' . $refundResult['refund_amount'];
            }
            
            // Add inventory restoration information
            $inventoryMessage = $this->inventoryService->createInventoryRestorationMessage($restoredItems);
            $statusMessage .= $inventoryMessage;
            
            // Add shipment cancellation information
            if ($shipmentCancellationResult['attempted']) {
                $statusMessage .= ' ' . $shipmentCancellationResult['message'];
            }
            
            $order->addStatusHistoryComment($statusMessage, Order::STATE_CANCELED);

            $this->orderRepository->save($order);

            // Build successful response
            $response->setSuccess(true);
            $response->setError(false);
            $response->setOrderId($order->getId());
            $response->setIncrementId($order->getIncrementId());
            $response->setRefundAmount($refundResult['refund_amount']);
            $response->setRefundMethod($refundResult['refund_method']);
            $response->setTransactionId($refundResult['transaction_id']);
            $response->setMessage('Order cancelled successfully and refund processed.');

        } catch (\Exception $e) {
            $this->logger->error('Order cancellation failed: ' . $e->getMessage());
            
            $response->setSuccess(false);
            $response->setError(true);
            $response->setMessage($e->getMessage());
        }

        return $response;
    }

    /**
     * Cancel shipment if it exists for the order
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return array
     */
    private function cancelShipmentIfExists($order)
    {
        try {
            $shipmentId = $order->getData('shiprocket_shipment_id');
            $awbNumber = $order->getData('shiprocket_awb_number');
            
            if (!$shipmentId) {
                return [
                    'attempted' => false,
                    'success' => false,
                    'message' => 'No shipment to cancel.'
                ];
            }
            
            // Attempt to cancel the shipment
            $cancellationResult = $this->shiprocketShipmentService->cancelShipment($shipmentId);
            
            if ($cancellationResult['success']) {
                return [
                    'attempted' => true,
                    'success' => true,
                    'message' => sprintf('Shiprocket shipment cancelled (ID: %s, AWB: %s).', $shipmentId, $awbNumber ?: 'N/A')
                ];
            } else {
                return [
                    'attempted' => true,
                    'success' => false,
                    'message' => sprintf('Failed to cancel Shiprocket shipment (ID: %s): %s', $shipmentId, $cancellationResult['message'] ?? 'Unknown error')
                ];
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Exception during shipment cancellation: ' . $e->getMessage());
            return [
                'attempted' => true,
                'success' => false,
                'message' => sprintf('Shipment cancellation failed: %s', $e->getMessage())
            ];
        }
    }
}