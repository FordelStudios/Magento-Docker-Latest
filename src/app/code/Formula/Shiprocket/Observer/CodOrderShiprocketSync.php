<?php
namespace Formula\Shiprocket\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Formula\Shiprocket\Service\ShiprocketShipmentService;
use Formula\Shiprocket\Helper\Data as ShiprocketHelper;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

class CodOrderShiprocketSync implements ObserverInterface
{
    /**
     * @var ShiprocketShipmentService
     */
    private $shiprocketShipmentService;

    /**
     * @var ShiprocketHelper
     */
    private $shiprocketHelper;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Payment methods that already handle their own Shiprocket sync
     * @var array
     */
    private $excludedPaymentMethods = [
        'razorpay',
        'walletpayment'
    ];

    /**
     * @param ShiprocketShipmentService $shiprocketShipmentService
     * @param ShiprocketHelper $shiprocketHelper
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        ShiprocketShipmentService $shiprocketShipmentService,
        ShiprocketHelper $shiprocketHelper,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger
    ) {
        $this->shiprocketShipmentService = $shiprocketShipmentService;
        $this->shiprocketHelper = $shiprocketHelper;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    /**
     * Execute observer for order placement
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        if (!$order || !$order->getId()) {
            return;
        }

        try {
            // Check if Shiprocket is enabled
            if (!$this->shiprocketHelper->isEnabled()) {
                return;
            }

            // Check if this order should be synced
            if (!$this->shouldSyncToShiprocket($order)) {
                return;
            }

            $this->logger->info('CodOrderShiprocketSync: Starting Shiprocket sync for COD order ' . $order->getIncrementId());

            // Create shipment through ShiprocketShipmentService
            $shipmentResult = $this->shiprocketShipmentService->createShipment($order);

            if ($shipmentResult['success']) {
                // Store shipment data in order
                $order->setData('shiprocket_order_id', $shipmentResult['shiprocket_order_id']);
                $order->setData('shiprocket_shipment_id', $shipmentResult['shipment_id']);
                $order->setData('shiprocket_awb_number', $shipmentResult['awb_code']);
                $order->setData('shiprocket_courier_name', $shipmentResult['courier_name']);

                // Add order comment
                $comment = sprintf(
                    'Shiprocket shipment created for COD order. Shipment ID: %s, AWB: %s, Courier: %s',
                    $shipmentResult['shipment_id'],
                    $shipmentResult['awb_code'] ?: 'Pending',
                    $shipmentResult['courier_name'] ?: 'Pending'
                );
                $order->addStatusHistoryComment($comment);

                // Save order with shipment data
                $this->orderRepository->save($order);

                $this->logger->info('CodOrderShiprocketSync: Shiprocket shipment created successfully for order ' . $order->getIncrementId());
            } else {
                $this->logger->warning('CodOrderShiprocketSync: Shiprocket shipment creation returned unsuccessful for order ' . $order->getIncrementId());
            }

        } catch (\Exception $e) {
            // Log error but don't fail the order - Shiprocket sync should not block order placement
            $this->logger->error('CodOrderShiprocketSync: Exception for order ' . $order->getIncrementId() . ' - ' . $e->getMessage());
        }
    }

    /**
     * Check if order should be synced to Shiprocket
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    private function shouldSyncToShiprocket($order)
    {
        $payment = $order->getPayment();
        if (!$payment) {
            $this->logger->debug('CodOrderShiprocketSync: Skipping order ' . $order->getIncrementId() . ' - no payment info');
            return false;
        }

        $paymentMethod = $payment->getMethod();

        // Skip if payment method handles its own Shiprocket sync
        if (in_array($paymentMethod, $this->excludedPaymentMethods)) {
            $this->logger->debug('CodOrderShiprocketSync: Skipping order ' . $order->getIncrementId() . ' - payment method ' . $paymentMethod . ' handles its own sync');
            return false;
        }

        // Skip if order already has Shiprocket data (avoid duplicate sync)
        if ($order->getData('shiprocket_order_id') || $order->getData('shiprocket_shipment_id')) {
            $this->logger->debug('CodOrderShiprocketSync: Skipping order ' . $order->getIncrementId() . ' - already has Shiprocket data');
            return false;
        }

        // Skip virtual orders (no shipping needed)
        if ($order->getIsVirtual()) {
            $this->logger->debug('CodOrderShiprocketSync: Skipping order ' . $order->getIncrementId() . ' - virtual order');
            return false;
        }

        // Only process COD orders
        if ($paymentMethod !== 'cashondelivery') {
            $this->logger->debug('CodOrderShiprocketSync: Skipping order ' . $order->getIncrementId() . ' - not a COD order (method: ' . $paymentMethod . ')');
            return false;
        }

        return true;
    }
}
