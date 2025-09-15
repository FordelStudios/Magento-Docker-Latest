<?php
namespace Formula\Shiprocket\Model;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class ShiprocketShipmentWebhookHandler
{
    protected $orderRepository;
    protected $orderCollectionFactory;
    protected $logger;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        OrderCollectionFactory $orderCollectionFactory,
        LoggerInterface $logger
    ) {
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

            // Process based on shipment status
            switch (strtolower($currentStatus)) {
                case 'shipped':
                case 'pickup_scheduled':
                    return $this->handleShipmentPickupScheduled($order, $webhookData);
                    
                case 'picked':
                case 'picked_up':
                    return $this->handleShipmentPicked($order, $webhookData);
                    
                case 'in_transit':
                case 'shipped':
                    return $this->handleShipmentInTransit($order, $webhookData);
                    
                case 'out_for_delivery':
                case 'out for delivery':
                    return $this->handleShipmentOutForDelivery($order, $webhookData);
                    
                case 'delivered':
                    return $this->handleShipmentDelivered($order, $webhookData);
                    
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
        $order->setStatus('shipment_pickup_scheduled');
        
        $message = 'Shipment pickup scheduled';
        if (isset($webhookData['awb'])) {
            $message .= ' (AWB: ' . $webhookData['awb'] . ')';
            $order->setData('shiprocket_awb_number', $webhookData['awb']);
        }
        if (isset($webhookData['courier_name'])) {
            $message .= ' via ' . $webhookData['courier_name'];
            $order->setData('shiprocket_courier_name', $webhookData['courier_name']);
        }
        
        $order->addStatusHistoryComment($message, 'shipment_pickup_scheduled');
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
        $order->setStatus('shipment_picked');
        
        $message = 'Package picked up by courier';
        if (isset($webhookData['awb'])) {
            $message .= ' (AWB: ' . $webhookData['awb'] . ')';
        }
        if (isset($webhookData['pickup_date'])) {
            $message .= ' on ' . $webhookData['pickup_date'];
        }
        
        $order->addStatusHistoryComment($message, 'shipment_picked');
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
        $order->setStatus('shipment_in_transit');
        
        $message = 'Package in transit';
        if (isset($webhookData['awb'])) {
            $message .= ' (AWB: ' . $webhookData['awb'] . ')';
        }
        if (isset($webhookData['current_location'])) {
            $message .= ' - Current location: ' . $webhookData['current_location'];
        }
        
        $order->addStatusHistoryComment($message, 'shipment_in_transit');
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
        $order->setStatus('shipment_out_for_delivery');
        
        $message = 'Package out for delivery';
        if (isset($webhookData['awb'])) {
            $message .= ' (AWB: ' . $webhookData['awb'] . ')';
        }
        if (isset($webhookData['expected_delivery_date'])) {
            $message .= ' - Expected delivery: ' . $webhookData['expected_delivery_date'];
        }
        
        $order->addStatusHistoryComment($message, 'shipment_out_for_delivery');
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
        $order->setStatus('complete');
        
        $message = 'Package delivered successfully';
        if (isset($webhookData['delivered_date'])) {
            $message .= ' on ' . $webhookData['delivered_date'];
        }
        if (isset($webhookData['awb'])) {
            $message .= ' (AWB: ' . $webhookData['awb'] . ')';
        }
        
        $order->addStatusHistoryComment($message, 'complete');
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
        $order->setStatus('shipment_cancelled');
        
        $message = 'Shipment cancelled';
        if (isset($webhookData['reason'])) {
            $message .= ' (Reason: ' . $webhookData['reason'] . ')';
        }
        if (isset($webhookData['awb'])) {
            $message .= ' (AWB: ' . $webhookData['awb'] . ')';
        }
        
        $order->addStatusHistoryComment($message, 'shipment_cancelled');
        $this->orderRepository->save($order);
        
        return ['success' => true, 'message' => 'Shipment cancelled status updated'];
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
        $order->setStatus('shipment_rto');
        
        $message = 'Return to Origin (RTO) initiated';
        if (isset($webhookData['reason'])) {
            $message .= ' (Reason: ' . $webhookData['reason'] . ')';
        }
        if (isset($webhookData['awb'])) {
            $message .= ' (AWB: ' . $webhookData['awb'] . ')';
        }
        
        $order->addStatusHistoryComment($message, 'shipment_rto');
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
        $order->setStatus('shipment_rto_delivered');
        
        $message = 'Package returned to origin';
        if (isset($webhookData['delivered_date'])) {
            $message .= ' on ' . $webhookData['delivered_date'];
        }
        if (isset($webhookData['awb'])) {
            $message .= ' (AWB: ' . $webhookData['awb'] . ')';
        }
        
        // Note: May need manual intervention for refund/inventory management
        $message .= '. Manual review may be required for refund processing.';
        
        $order->addStatusHistoryComment($message, 'shipment_rto_delivered');
        $this->orderRepository->save($order);
        
        return ['success' => true, 'message' => 'RTO delivered status updated'];
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