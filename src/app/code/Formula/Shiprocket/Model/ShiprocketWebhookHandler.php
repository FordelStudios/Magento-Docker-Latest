<?php
namespace Formula\Shiprocket\Model;

use Formula\OrderCancellationReturn\Service\InventoryService;
use Formula\OrderCancellationReturn\Service\RefundProcessor;
use Formula\OrderCancellationReturn\Service\ReturnEmailSender;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class ShiprocketWebhookHandler
{
    protected $inventoryService;
    protected $refundProcessor;
    protected $returnEmailSender;
    protected $orderRepository;
    protected $orderCollectionFactory;
    protected $logger;

    public function __construct(
        InventoryService $inventoryService,
        RefundProcessor $refundProcessor,
        ReturnEmailSender $returnEmailSender,
        OrderRepositoryInterface $orderRepository,
        OrderCollectionFactory $orderCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->inventoryService = $inventoryService;
        $this->refundProcessor = $refundProcessor;
        $this->returnEmailSender = $returnEmailSender;
        $this->orderRepository = $orderRepository;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * Handle Shiprocket return webhook
     *
     * @param array $webhookData
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function handleReturnWebhook($webhookData)
    {
        try {
            $this->logger->info('Shiprocket return webhook received: ' . json_encode($webhookData));
            
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

            // Process based on return status
            switch (strtolower($currentStatus)) {
                case 'return_pickup_scheduled':
                case 'return_scheduled':
                    return $this->handleReturnPickupScheduled($order, $webhookData);
                    
                case 'return_picked':
                case 'return_in_transit':
                    return $this->handleReturnInTransit($order, $webhookData);
                    
                case 'return_delivered':
                case 'return_received':
                    return $this->handleReturnReceived($order, $webhookData);
                    
                case 'return_cancelled':
                case 'return_failed':
                    return $this->handleReturnCancelled($order, $webhookData);
                    
                default:
                    $this->logger->warning('Unknown return status: ' . $currentStatus);
                    return ['success' => true, 'message' => 'Unknown status, no action taken'];
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Shiprocket webhook processing failed: ' . $e->getMessage());
            throw new LocalizedException(__('Webhook processing failed: %1', $e->getMessage()));
        }
    }

    /**
     * Handle return pickup scheduled
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param array $webhookData
     * @return array
     */
    protected function handleReturnPickupScheduled($order, $webhookData)
    {
        $order->setStatus('return_pickup_scheduled');

        $pickupDetails = [];
        $message = 'Return pickup scheduled by Shiprocket';
        if (isset($webhookData['pickup_date'])) {
            $message .= ' (Pickup Date: ' . $webhookData['pickup_date'] . ')';
            $pickupDetails[] = 'Pickup Date: ' . $webhookData['pickup_date'];
        }
        if (isset($webhookData['awb'])) {
            $message .= ' (AWB: ' . $webhookData['awb'] . ')';
            $pickupDetails[] = 'AWB: ' . $webhookData['awb'];
        }

        $order->addStatusHistoryComment($message, 'return_pickup_scheduled');
        $this->orderRepository->save($order);

        // Send return approved email with pickup details
        $pickupDetailsString = !empty($pickupDetails) ? implode(', ', $pickupDetails) : 'Our courier partner will contact you shortly.';
        $this->returnEmailSender->sendReturnApprovedEmail($order, $pickupDetailsString);

        return ['success' => true, 'message' => 'Return pickup scheduled status updated'];
    }

    /**
     * Handle return in transit
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param array $webhookData
     * @return array
     */
    protected function handleReturnInTransit($order, $webhookData)
    {
        $order->setStatus('return_in_transit');
        
        $message = 'Return package picked up and in transit';
        if (isset($webhookData['awb'])) {
            $message .= ' (AWB: ' . $webhookData['awb'] . ')';
        }
        if (isset($webhookData['tracking_url'])) {
            $message .= ' (Track: ' . $webhookData['tracking_url'] . ')';
        }
        
        $order->addStatusHistoryComment($message, 'return_in_transit');
        $this->orderRepository->save($order);
        
        return ['success' => true, 'message' => 'Return in transit status updated'];
    }

    /**
     * Handle return received (product delivered back to warehouse)
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param array $webhookData
     * @return array
     */
    protected function handleReturnReceived($order, $webhookData)
    {
        // Update order status
        $order->setStatus('return_received');

        // Restore inventory now that product is physically received
        $restoredItems = $this->inventoryService->restoreInventoryForReturn($order);

        $message = 'Return package received at warehouse';
        if (isset($webhookData['delivered_date'])) {
            $message .= ' (Delivered: ' . $webhookData['delivered_date'] . ')';
        }

        // Add inventory restoration info
        $inventoryMessage = $this->inventoryService->createInventoryRestorationMessage($restoredItems);
        $message .= $inventoryMessage;

        $order->addStatusHistoryComment($message, 'return_received');
        $this->orderRepository->save($order);

        // Process refund now that product is received
        $refundResult = null;
        try {
            $order->setStatus('refund_initiated');
            $order->addStatusHistoryComment('Processing refund for returned order', 'refund_initiated');
            $this->orderRepository->save($order);

            $refundResult = $this->refundProcessor->processRefund($order, 'return');

            // Update status based on refund result
            $order->setStatus('refund_completed');
            $refundMessage = 'Refund processed successfully';
            if (isset($refundResult['status_message'])) {
                $refundMessage .= '. ' . $refundResult['status_message'];
            }
            if (isset($refundResult['transaction_id'])) {
                $refundMessage .= ' (Transaction: ' . $refundResult['transaction_id'] . ')';
            }
            $order->addStatusHistoryComment($refundMessage, 'refund_completed');

            // Send refund completion email
            $refundAmount = $refundResult['refund_amount'] ?? $order->getGrandTotal();
            $refundMethod = $refundResult['refund_method'] ?? 'wallet';
            $this->returnEmailSender->sendRefundCompletedEmail($order, (float)$refundAmount, $refundMethod);

            $this->logger->info('Return refund processed', [
                'order_id' => $order->getIncrementId(),
                'refund_amount' => $refundAmount,
                'refund_method' => $refundMethod
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Return refund failed', [
                'order_id' => $order->getIncrementId(),
                'error' => $e->getMessage()
            ]);

            $order->setStatus('return_received');
            $order->addStatusHistoryComment(
                'Refund processing failed: ' . $e->getMessage() . '. Manual intervention required.',
                'return_received'
            );
        }

        $this->orderRepository->save($order);

        return [
            'success' => true,
            'message' => 'Return received, inventory restored, and refund processed',
            'refund_result' => $refundResult
        ];
    }

    /**
     * Handle return cancelled/failed
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param array $webhookData
     * @return array
     */
    protected function handleReturnCancelled($order, $webhookData)
    {
        $order->setStatus('return_cancelled');

        $message = 'Return pickup cancelled/failed';
        if (isset($webhookData['reason'])) {
            $message .= ' (Reason: ' . $webhookData['reason'] . ')';
        }

        // No refund was processed since we only refund when return is received
        $message .= '. No refund was processed - customer may retry return request.';

        $order->addStatusHistoryComment($message, 'return_cancelled');

        // Send return rejected email to notify customer
        $rejectionReason = $webhookData['reason'] ?? 'Return pickup could not be completed';
        $this->returnEmailSender->sendReturnRejectedEmail($order, $rejectionReason);

        $this->orderRepository->save($order);

        return ['success' => true, 'message' => 'Return cancelled status updated'];
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

    /**
     * Verify webhook signature (implement based on Shiprocket documentation)
     *
     * @param array $webhookData
     * @param string $signature
     * @return bool
     */
    public function verifyWebhookSignature($webhookData, $signature)
    {
        // TODO: Implement signature verification based on Shiprocket's webhook security
        // This should validate the webhook is genuinely from Shiprocket
        
        // For now, return true - implement proper verification in production
        return true;
    }
}