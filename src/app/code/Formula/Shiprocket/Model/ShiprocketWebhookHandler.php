<?php
namespace Formula\Shiprocket\Model;

use Formula\OrderCancellationReturn\Service\InventoryService;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class ShiprocketWebhookHandler
{
    protected $inventoryService;
    protected $orderRepository;
    protected $orderCollectionFactory;
    protected $logger;

    public function __construct(
        InventoryService $inventoryService,
        OrderRepositoryInterface $orderRepository,
        OrderCollectionFactory $orderCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->inventoryService = $inventoryService;
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
        
        $message = 'Return pickup scheduled by Shiprocket';
        if (isset($webhookData['pickup_date'])) {
            $message .= ' (Pickup Date: ' . $webhookData['pickup_date'] . ')';
        }
        if (isset($webhookData['awb'])) {
            $message .= ' (AWB: ' . $webhookData['awb'] . ')';
        }
        
        $order->addStatusHistoryComment($message, 'return_pickup_scheduled');
        $this->orderRepository->save($order);
        
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
        
        // Mark return as completed
        $order->setStatus('return_completed');
        $order->addStatusHistoryComment('Return process completed successfully', 'return_completed');
        
        $this->orderRepository->save($order);
        
        return ['success' => true, 'message' => 'Return received and inventory restored'];
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
        
        // Note: Refund was already processed, so we don't reverse it
        // Customer service may need to handle this manually
        $message .= '. Refund was already processed - manual review may be required.';
        
        $order->addStatusHistoryComment($message, 'return_cancelled');
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