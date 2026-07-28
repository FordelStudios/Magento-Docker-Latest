<?php
namespace Formula\Shiprocket\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Formula\Shiprocket\Service\ShiprocketShipmentService;
use Formula\Shiprocket\Helper\Data as ShiprocketHelper;
use Formula\DeliveryPartner\Model\DeliveryPartnerResolver;
use Formula\DeliveryPartner\Model\PartnerCode;
use Formula\DeliveryPartner\Model\ShipmentResultPersister;
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
     * @var DeliveryPartnerResolver
     */
    private $deliveryPartnerResolver;

    /**
     * @var ShipmentResultPersister
     */
    private $shipmentResultPersister;

    /**
     * Payment methods that already handle their own Shiprocket sync.
     * Razorpay orders sync via OrderManagement.php after payment capture.
     * Wallet-only orders sync here (previously excluded — bug fix).
     * @var array
     */
    private $excludedPaymentMethods = [
        'razorpay'
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
        LoggerInterface $logger,
        DeliveryPartnerResolver $deliveryPartnerResolver,
        ShipmentResultPersister $shipmentResultPersister
    ) {
        $this->shiprocketShipmentService = $shiprocketShipmentService;
        $this->shiprocketHelper = $shiprocketHelper;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->deliveryPartnerResolver = $deliveryPartnerResolver;
        $this->shipmentResultPersister = $shipmentResultPersister;
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

        // Visibility probe: prove whether sales_order_place_after actually reaches this observer.
        // Logged at info (not debug) so it survives prod where debug_mode=0, BEFORE any shouldSync gating.
        $payment = $order->getPayment();
        $this->logger->info(sprintf(
            'CodOrderShiprocketSync: place_after fired for order %s (method=%s)',
            $order->getIncrementId(),
            $payment ? $payment->getMethod() : 'unknown'
        ));

        try {
            // Check if Shiprocket is enabled
            if (!$this->shiprocketHelper->isEnabled()) {
                return;
            }

            // Check if this order should be synced
            if (!$this->shouldSyncToShiprocket($order)) {
                return;
            }

            // Route to the order's active delivery partner. Shiprocket (the default) keeps
            // its existing, unchanged code path; any other partner goes through the neutral
            // Formula_DeliveryPartner abstraction. This observer depends ONLY on
            // Formula_DeliveryPartner — never directly on any other concrete courier module.
            $partnerCode = $this->deliveryPartnerResolver->resolveCode($order);

            if ($partnerCode === PartnerCode::SHIPROCKET) {
                $this->logger->info('CodOrderShiprocketSync: Starting Shiprocket sync for order ' . $order->getIncrementId());

                // Create shipment through ShiprocketShipmentService
                $shipmentResult = $this->shiprocketShipmentService->createShipment($order);

                if ($shipmentResult['success']) {
                    // Store shipment data in order
                    $order->setData('shiprocket_order_id', $shipmentResult['shiprocket_order_id']);
                    $order->setData('shiprocket_shipment_id', $shipmentResult['shipment_id']);
                    $order->setData('shiprocket_awb_number', $shipmentResult['awb_code']);
                    $order->setData('shiprocket_courier_name', $shipmentResult['courier_name']);

                    // Record the delivery partner. This is the ONLY addition to the existing
                    // Shiprocket success path — all field-setting above is unchanged.
                    $order->setData('delivery_partner', PartnerCode::SHIPROCKET);

                    // Add order comment
                    $comment = sprintf(
                        'Shiprocket shipment created for order. Shipment ID: %s, AWB: %s, Courier: %s',
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
            } else {
                $this->syncViaDeliveryPartner($order);
            }

        } catch (\Exception $e) {
            // Log error but don't fail the order - Shiprocket sync should not block order placement
            $this->logger->error('CodOrderShiprocketSync: Exception for order ' . $order->getIncrementId() . ' - ' . $e->getMessage());
        }
    }

    /**
     * Create the forward shipment for a non-Shiprocket partner (ShadowFax, ...) via the
     * neutral resolver abstraction, then persist the result. Mirrors the Shiprocket success
     * path shape. Any failure is left for the partner's own backfill cron — the caller's
     * try/catch already guarantees a shipment failure never blocks order placement.
     *
     * @param \Magento\Sales\Model\Order $order
     * @return void
     */
    private function syncViaDeliveryPartner($order)
    {
        $this->logger->info('CodOrderShiprocketSync: Starting delivery-partner sync for order ' . $order->getIncrementId());

        $result = $this->deliveryPartnerResolver->resolve($order)->createShipment($order);

        if ($result->isSuccessful()) {
            // Stage delivery_partner + partner-specific identifier columns onto the order.
            $this->shipmentResultPersister->apply($order, $result);

            $comment = sprintf(
                '%s shipment created for order. Shipment ID: %s, AWB: %s, Courier: %s',
                $result->getPartnerCode(),
                $result->getShipmentId() ?: 'Pending',
                $result->getAwb() ?: 'Pending',
                $result->getCourierName() ?: 'Pending'
            );
            $order->addStatusHistoryComment($comment);

            $this->orderRepository->save($order);

            $this->logger->info('CodOrderShiprocketSync: ' . $result->getPartnerCode() . ' shipment created successfully for order ' . $order->getIncrementId());
        } else {
            // Leave for the partner's backfill cron to retry.
            $this->logger->warning('CodOrderShiprocketSync: delivery-partner shipment creation returned unsuccessful for order ' . $order->getIncrementId());
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

        // Process COD and wallet-only orders (Razorpay is handled by OrderManagement)
        $allowedMethods = ['cashondelivery', 'walletpayment'];
        if (!in_array($paymentMethod, $allowedMethods)) {
            $this->logger->debug('CodOrderShiprocketSync: Skipping order ' . $order->getIncrementId() . ' - payment method ' . $paymentMethod . ' not handled here');
            return false;
        }

        return true;
    }
}
