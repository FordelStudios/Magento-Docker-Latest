<?php
namespace Formula\Shiprocket\Model;

use Formula\OrderCancellationReturn\Service\RefundProcessor;
use Formula\OrderCancellationReturn\Service\InventoryService;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class ShiprocketShipmentWebhookHandler
{
    protected $refundProcessor;
    protected $inventoryService;
    protected $orderRepository;
    protected $orderCollectionFactory;
    protected $logger;

    public function __construct(
        RefundProcessor $refundProcessor,
        InventoryService $inventoryService,
        OrderRepositoryInterface $orderRepository,
        OrderCollectionFactory $orderCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->refundProcessor = $refundProcessor;
        $this->inventoryService = $inventoryService;
        $this->orderRepository = $orderRepository;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * Handle Shiprocket shipment webhook
     *
     * @param array $webhookData
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function handleShipmentWebhook($webhookData)
    {
        try {
            $this->logger->info('Shiprocket shipment webhook received: ' . json_encode($webhookData));
            
            // Validate webhook data
            if (!isset($webhookData['order_id']) || !isset($webhookData['current_status'])) {
                throw new LocalizedException(__('Invalid webhook data: missing order_id or current_status'));
            }

            $orderIncrementId = $webhookData['order_id'];
            $currentStatus = $webhookData['current_status'];
            
            // Find the order
            $order = $this->findOrderByIncrementId($orderIncrementId);
            if (!$order) {
                throw new LocalizedException(__('Order not found: %1', $orderIncrementId));
            }

            // Idempotency: Skip if order is already in a terminal state
            $orderState = $order->getState();
            $terminalStates = [
                \Magento\Sales\Model\Order::STATE_CANCELED,
                \Magento\Sales\Model\Order::STATE_CLOSED
            ];
            if (in_array($orderState, $terminalStates)) {
                $this->logger->info('Shiprocket webhook: Order already in terminal state, skipping', [
                    'order' => $orderIncrementId,
                    'state' => $orderState,
                    'incoming_status' => $currentStatus,
                ]);
                return ['success' => true, 'message' => 'Order already in terminal state: ' . $orderState];
            }

            // Process based on shipment status
            switch (strtolower($currentStatus)) {
                case 'pickup_scheduled':
                    return $this->handleShipmentPickupScheduled($order, $webhookData);

                case 'shipped':
                case 'picked':
                case 'picked_up':
                    return $this->handleShipmentPicked($order, $webhookData);

                case 'in_transit':
                    return $this->handleShipmentInTransit($order, $webhookData);
                    
                case 'out_for_delivery':
                case 'out for delivery':
                    return $this->handleShipmentOutForDelivery($order, $webhookData);
                    
                case 'delivered':
                    return $this->handleShipmentDelivered($order, $webhookData);
                    
                case 'canceled':
                case 'cancelled':
                case 'shipment_cancelled':
                    return $this->handleShipmentCancelled($order, $webhookData);
                    
                case 'rto_initiated':
                case 'rto':
                    return $this->handleShipmentRTO($order, $webhookData);
                    
                case 'rto_delivered':
                    return $this->handleShipmentRTODelivered($order, $webhookData);
                    
                default:
                    $this->logger->warning('Unknown shipment status: ' . $currentStatus);
                    return ['success' => true, 'message' => 'Unknown status, no action taken'];
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Shiprocket shipment webhook processing failed: ' . $e->getMessage());
            throw new LocalizedException(__('Shipment webhook processing failed: %1', $e->getMessage()));
        }
    }

    /**
     * Handle shipment pickup scheduled
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param array $webhookData
     * @return array
     */
    protected function handleShipmentPickupScheduled($order, $webhookData)
    {
        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
        $order->setStatus('pickup_scheduled');

        $message = '[Shiprocket] Pickup Scheduled';
        if (isset($webhookData['awb']) && !empty($webhookData['awb'])) {
            $message .= ' | AWB: ' . $webhookData['awb'];
            $order->setData('shiprocket_awb_number', $webhookData['awb']);
        }
        if (isset($webhookData['courier_name']) && !empty($webhookData['courier_name'])) {
            $message .= ' | Courier: ' . $webhookData['courier_name'];
            $order->setData('shiprocket_courier_name', $webhookData['courier_name']);
        }

        $order->addCommentToStatusHistory($message);
        $this->orderRepository->save($order);

        return ['success' => true, 'message' => 'Shipment pickup scheduled status updated'];
    }

    /**
     * Handle shipment picked up
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param array $webhookData
     * @return array
     */
    protected function handleShipmentPicked($order, $webhookData)
    {
        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
        $order->setStatus('shipped');

        $message = '[Shiprocket] Package Picked Up';
        if (isset($webhookData['awb']) && !empty($webhookData['awb'])) {
            $message .= ' | AWB: ' . $webhookData['awb'];
        }
        if (isset($webhookData['pickup_date']) && !empty($webhookData['pickup_date'])) {
            $message .= ' | Date: ' . $webhookData['pickup_date'];
        }

        $order->addCommentToStatusHistory($message);
        $this->orderRepository->save($order);

        return ['success' => true, 'message' => 'Shipment picked status updated'];
    }

    /**
     * Handle shipment in transit
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param array $webhookData
     * @return array
     */
    protected function handleShipmentInTransit($order, $webhookData)
    {
        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
        $order->setStatus('in_transit');

        $message = '[Shiprocket] In Transit';
        if (isset($webhookData['awb']) && !empty($webhookData['awb'])) {
            $message .= ' | AWB: ' . $webhookData['awb'];
        }
        if (isset($webhookData['current_location']) && !empty($webhookData['current_location'])) {
            $message .= ' | Location: ' . $webhookData['current_location'];
        }

        $order->addCommentToStatusHistory($message);
        $this->orderRepository->save($order);

        return ['success' => true, 'message' => 'Shipment in transit status updated'];
    }

    /**
     * Handle shipment out for delivery
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param array $webhookData
     * @return array
     */
    protected function handleShipmentOutForDelivery($order, $webhookData)
    {
        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
        $order->setStatus('out_for_delivery');

        $message = '[Shiprocket] Out for Delivery';
        if (isset($webhookData['awb']) && !empty($webhookData['awb'])) {
            $message .= ' | AWB: ' . $webhookData['awb'];
        }
        if (isset($webhookData['expected_delivery_date']) && !empty($webhookData['expected_delivery_date'])) {
            $message .= ' | ETA: ' . $webhookData['expected_delivery_date'];
        }

        $order->addCommentToStatusHistory($message);
        $this->orderRepository->save($order);

        return ['success' => true, 'message' => 'Out for delivery status updated'];
    }

    /**
     * Handle shipment delivered
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param array $webhookData
     * @return array
     */
    protected function handleShipmentDelivered($order, $webhookData)
    {
        $order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE);
        $order->setStatus('delivered');

        $message = '[Shiprocket] Delivered';
        if (isset($webhookData['delivered_date']) && !empty($webhookData['delivered_date'])) {
            $message .= ' | Date: ' . $webhookData['delivered_date'];
        }
        if (isset($webhookData['awb']) && !empty($webhookData['awb'])) {
            $message .= ' | AWB: ' . $webhookData['awb'];
        }

        $order->addCommentToStatusHistory($message);
        $this->orderRepository->save($order);

        return ['success' => true, 'message' => 'Package delivered, order completed'];
    }

    /**
     * Handle shipment cancelled
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param array $webhookData
     * @return array
     */
    protected function handleShipmentCancelled($order, $webhookData)
    {
        $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
        $order->setStatus('shipment_cancelled');

        $message = '[Shiprocket] Shipment Cancelled';
        if (isset($webhookData['reason']) && !empty($webhookData['reason'])) {
            $message .= ' | Reason: ' . $webhookData['reason'];
        }
        if (isset($webhookData['awb']) && !empty($webhookData['awb'])) {
            $message .= ' | AWB: ' . $webhookData['awb'];
        }

        $order->addCommentToStatusHistory($message);
        $this->orderRepository->save($order);

        // Restore inventory
        $restoredItems = $this->inventoryService->restoreInventoryForReturn($order);
        if (!empty($restoredItems)) {
            $inventoryMessage = $this->inventoryService->createInventoryRestorationMessage($restoredItems);
            $order->addCommentToStatusHistory('[Shiprocket] Inventory restored' . $inventoryMessage);
            $this->orderRepository->save($order);
        }

        // Process refund (wallet + payment gateway)
        $refundResult = null;
        try {
            $refundResult = $this->refundProcessor->processRefund($order, 'cancel');

            $refundMessage = '[Shiprocket] Refund processed';
            if (isset($refundResult['status_message'])) {
                $refundMessage .= ': ' . $refundResult['status_message'];
            }
            $order->addCommentToStatusHistory($refundMessage);
            $this->orderRepository->save($order);

            $this->logger->info('Shiprocket cancellation refund processed', [
                'order_id' => $order->getIncrementId(),
                'refund_amount' => $refundResult['refund_amount'] ?? 0,
                'refund_method' => $refundResult['refund_method'] ?? 'unknown'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Shiprocket cancellation refund failed', [
                'order_id' => $order->getIncrementId(),
                'error' => $e->getMessage()
            ]);
            $order->addCommentToStatusHistory(
                '[Shiprocket] Refund failed: ' . $e->getMessage() . '. Manual intervention required.'
            );
            $this->orderRepository->save($order);
        }

        return ['success' => true, 'message' => 'Shipment cancelled, refund processed', 'refund_result' => $refundResult];
    }

    /**
     * Handle RTO initiated
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param array $webhookData
     * @return array
     */
    protected function handleShipmentRTO($order, $webhookData)
    {
        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
        $order->setStatus('rto_initiated');

        $message = '[Shiprocket] RTO Initiated';
        if (isset($webhookData['reason']) && !empty($webhookData['reason'])) {
            $message .= ' | Reason: ' . $webhookData['reason'];
        }
        if (isset($webhookData['awb']) && !empty($webhookData['awb'])) {
            $message .= ' | AWB: ' . $webhookData['awb'];
        }

        $order->addCommentToStatusHistory($message);
        $this->orderRepository->save($order);

        return ['success' => true, 'message' => 'RTO initiated status updated'];
    }

    /**
     * Handle RTO delivered
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param array $webhookData
     * @return array
     */
    protected function handleShipmentRTODelivered($order, $webhookData)
    {
        $order->setState(\Magento\Sales\Model\Order::STATE_CLOSED);
        $order->setStatus('rto_delivered');

        $message = '[Shiprocket] RTO Delivered - Package returned to origin';
        if (isset($webhookData['delivered_date']) && !empty($webhookData['delivered_date'])) {
            $message .= ' | Date: ' . $webhookData['delivered_date'];
        }
        if (isset($webhookData['awb']) && !empty($webhookData['awb'])) {
            $message .= ' | AWB: ' . $webhookData['awb'];
        }

        $order->addCommentToStatusHistory($message);
        $this->orderRepository->save($order);

        // Restore inventory
        $restoredItems = $this->inventoryService->restoreInventoryForReturn($order);
        if (!empty($restoredItems)) {
            $inventoryMessage = $this->inventoryService->createInventoryRestorationMessage($restoredItems);
            $order->addCommentToStatusHistory('[Shiprocket] Inventory restored' . $inventoryMessage);
            $this->orderRepository->save($order);
        }

        // Process refund (wallet + payment gateway)
        $refundResult = null;
        try {
            $refundResult = $this->refundProcessor->processRefund($order, 'cancel');

            $refundMessage = '[Shiprocket] RTO refund processed';
            if (isset($refundResult['status_message'])) {
                $refundMessage .= ': ' . $refundResult['status_message'];
            }
            $order->addCommentToStatusHistory($refundMessage);
            $this->orderRepository->save($order);

            $this->logger->info('Shiprocket RTO refund processed', [
                'order_id' => $order->getIncrementId(),
                'refund_amount' => $refundResult['refund_amount'] ?? 0,
                'refund_method' => $refundResult['refund_method'] ?? 'unknown'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Shiprocket RTO refund failed', [
                'order_id' => $order->getIncrementId(),
                'error' => $e->getMessage()
            ]);
            $order->addCommentToStatusHistory(
                '[Shiprocket] RTO refund failed: ' . $e->getMessage() . '. Manual intervention required.'
            );
            $this->orderRepository->save($order);
        }

        return ['success' => true, 'message' => 'RTO delivered, refund processed', 'refund_result' => $refundResult];
    }

    /**
     * Find order by increment ID
     *
     * @param string $incrementId
     * @return \Magento\Sales\Api\Data\OrderInterface|null
     */
    protected function findOrderByIncrementId($incrementId)
    {
        $collection = $this->orderCollectionFactory->create();
        $collection->addFieldToFilter('increment_id', $incrementId);
        $collection->setPageSize(1);
        
        return $collection->getFirstItem()->getId() ? $collection->getFirstItem() : null;
    }
}