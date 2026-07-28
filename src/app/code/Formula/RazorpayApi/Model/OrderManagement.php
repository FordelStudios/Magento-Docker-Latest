<?php
namespace Formula\RazorpayApi\Model;

use Formula\RazorpayApi\Api\OrderManagementInterface;
use Formula\RazorpayApi\Api\Data\OrderResponseInterface;
use Formula\RazorpayApi\Api\Data\OrderResponseInterfaceFactory;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Framework\DB\Transaction;
use Formula\Shiprocket\Service\ShiprocketShipmentService;
use Formula\DeliveryPartner\Model\DeliveryPartnerResolver;
use Formula\DeliveryPartner\Model\PartnerCode;
use Formula\DeliveryPartner\Model\ShipmentResultPersister;
use Psr\Log\LoggerInterface;

class OrderManagement implements OrderManagementInterface
{
    protected $cartManagement;
    protected $cartRepository;
    protected $orderRepository;
    protected $transactionBuilder;
    protected $transactionRepository;
    protected $transaction;
    protected $orderResponseFactory;
    protected $shiprocketShipmentService;
    protected $logger;
    protected $deliveryPartnerResolver;
    protected $shipmentResultPersister;

    public function __construct(
        CartManagementInterface $cartManagement,
        CartRepositoryInterface $cartRepository,
        OrderRepositoryInterface $orderRepository,
        BuilderInterface $transactionBuilder,
        TransactionRepositoryInterface $transactionRepository,
        Transaction $transaction,
        OrderResponseInterfaceFactory $orderResponseFactory,
        ShiprocketShipmentService $shiprocketShipmentService,
        LoggerInterface $logger,
        DeliveryPartnerResolver $deliveryPartnerResolver,
        ShipmentResultPersister $shipmentResultPersister
    ) {
        $this->cartManagement = $cartManagement;
        $this->cartRepository = $cartRepository;
        $this->orderRepository = $orderRepository;
        $this->transactionBuilder = $transactionBuilder;
        $this->transactionRepository = $transactionRepository;
        $this->transaction = $transaction;
        $this->orderResponseFactory = $orderResponseFactory;
        $this->shiprocketShipmentService = $shiprocketShipmentService;
        $this->logger = $logger;
        $this->deliveryPartnerResolver = $deliveryPartnerResolver;
        $this->shipmentResultPersister = $shipmentResultPersister;
    }

    public function createOrder($cartId, $paymentData, $billingAddress)
    {
        /** @var OrderResponseInterface $response */
        $response = $this->orderResponseFactory->create();

        try {
            $quote = $this->cartRepository->get($cartId);
            
            // Set payment method with Razorpay data
            $payment = $quote->getPayment();
            $payment->setMethod('razorpay');
            
            // Set additional information for Razorpay
            $payment->setAdditionalInformation('razorpay_payment_id', $paymentData['razorpay_payment_id']);
            $payment->setAdditionalInformation('razorpay_order_id', $paymentData['razorpay_order_id']);
            $payment->setAdditionalInformation('razorpay_signature', $paymentData['razorpay_signature']);
            
            // Update billing address if provided
            if ($billingAddress && is_array($billingAddress)) {
                $existingAddress = $quote->getBillingAddress();
                if ($existingAddress) {
                    $existingAddress->setFirstname($billingAddress['firstname'] ?? 'John');
                    $existingAddress->setLastname($billingAddress['lastname'] ?? 'Doe');
                    $existingAddress->setStreet($billingAddress['street'] ?? ['123 Street']);
                    $existingAddress->setCity($billingAddress['city'] ?? 'City');
                    $existingAddress->setCountryId($billingAddress['country_id'] ?? 'IN');
                    $existingAddress->setRegion($billingAddress['region'] ?? 'State');
                    $existingAddress->setRegionId($billingAddress['region_id'] ?? 0);
                    $existingAddress->setPostcode($billingAddress['postcode'] ?? '123456');
                    $existingAddress->setTelephone($billingAddress['telephone'] ?? '1234567890');
                    $existingAddress->setEmail($billingAddress['email'] ?? 'test@example.com');
                }

                // Also set shipping address (same as billing) to prevent NULL shipping address failures
                $shippingAddress = $quote->getShippingAddress();
                if ($shippingAddress) {
                    $shippingAddress->setFirstname($billingAddress['firstname'] ?? 'John');
                    $shippingAddress->setLastname($billingAddress['lastname'] ?? 'Doe');
                    $shippingAddress->setStreet($billingAddress['street'] ?? ['123 Street']);
                    $shippingAddress->setCity($billingAddress['city'] ?? 'City');
                    $shippingAddress->setCountryId($billingAddress['country_id'] ?? 'IN');
                    $shippingAddress->setRegion($billingAddress['region'] ?? 'State');
                    $shippingAddress->setRegionId($billingAddress['region_id'] ?? 0);
                    $shippingAddress->setPostcode($billingAddress['postcode'] ?? '123456');
                    $shippingAddress->setTelephone($billingAddress['telephone'] ?? '1234567890');
                    $shippingAddress->setEmail($billingAddress['email'] ?? 'test@example.com');
                    // Ensure shipping method is set if missing. This is the
                    // online (Razorpay) order path, where shipping is free, so
                    // default to free shipping rather than the COD flat rate.
                    if (!$shippingAddress->getShippingMethod()) {
                        $shippingAddress->setShippingMethod('freeshipping_freeshipping');
                        $shippingAddress->setCollectShippingRates(true);
                        $shippingAddress->collectShippingRates();
                    }
                }
            }
            
            // Save quote before placing order
            $this->cartRepository->save($quote);
            
            // Create order
            $orderId = $this->cartManagement->placeOrder($cartId);
            
            // Load the created order
            $order = $this->orderRepository->get($orderId);

            // Stamp the resolved delivery partner as INTENT on the order BEFORE payment
            // processing. processPaymentAndUpdateOrder() already saves the order, so this
            // field persists regardless of whether shipment creation later succeeds — leaving
            // the order discoverable by the partner's backfill cron and immune to a later
            // global active-partner config flip. We do NOT modify processPaymentAndUpdateOrder;
            // we only set a field on the order object before it runs, and we never add a new save.
            $this->stampDeliveryPartnerIntent($order);

            // **IMPORTANT: Process the payment and update order status**
            $this->processPaymentAndUpdateOrder($order, $paymentData);

            // Create the forward shipment automatically via the active delivery partner.
            // NOTE: this is the ONLY shipment-aware step; payment capture, invoicing and
            // order-state transitions above are untouched. Shipment creation is fully
            // isolated (never rethrows) so it can never break a payment that already
            // succeeded — see createPartnerShipment().
            $this->logger->info('Attempting to create delivery-partner shipment for Razorpay order: ' . $order->getIncrementId());
            $shipmentData = $this->createPartnerShipment($order);
            
            // Update Razorpay table
            $this->updateRazorpayOrderData($order, $paymentData);
            
            // Build successful response
            $response->setSuccess(true);
            $response->setOrderId($orderId);
            $response->setIncrementId($order->getIncrementId());
            $response->setStatus($order->getStatus());
            $response->setState($order->getState());
            $response->setTotalAmount($order->getGrandTotal());
            $response->setCurrency($order->getOrderCurrencyCode());
            $response->setCreatedAt($order->getCreatedAt());
            $response->setRazorpayPaymentId($paymentData['razorpay_payment_id']);
            $response->setRazorpayOrderId($paymentData['razorpay_order_id']);
            
            // Add shipment information to response
            if ($shipmentData && $shipmentData['success']) {
                $response->setMessage('Order created, payment processed, and shipment scheduled successfully!');
                
                // Set shipment tracking fields
                $response->setShiprocketOrderId($shipmentData['shiprocket_order_id'] ?? null);
                $response->setShiprocketShipmentId($shipmentData['shipment_id'] ?? null);
                $response->setShiprocketAwbNumber($shipmentData['awb_code'] ?? null);
                $response->setShiprocketCourierName($shipmentData['courier_name'] ?? null);
            } else {
                $response->setMessage('Order created and payment processed successfully!');
            }
            
        } catch (\Exception $e) {
            // Check if webhook already created the order (race condition)
            // Retry up to 3 times with 1s delay — webhook may still be processing
            $existingOrder = null;
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                $existingOrder = $this->findOrderByRazorpayPayment($paymentData['razorpay_payment_id'], $cartId);
                if ($existingOrder) break;
                if ($attempt < 3) sleep(1);
            }
            if ($existingOrder) {
                $this->logger->info('RazorpayOrderManagement: Webhook already created order, returning it', [
                    'increment_id' => $existingOrder->getIncrementId(),
                    'razorpay_payment_id' => $paymentData['razorpay_payment_id'],
                ]);
                $response->setSuccess(true);
                $response->setOrderId($existingOrder->getId());
                $response->setIncrementId($existingOrder->getIncrementId());
                $response->setStatus($existingOrder->getStatus());
                $response->setState($existingOrder->getState());
                $response->setTotalAmount($existingOrder->getGrandTotal());
                $response->setCurrency($existingOrder->getOrderCurrencyCode());
                $response->setCreatedAt($existingOrder->getCreatedAt());
                $response->setRazorpayPaymentId($paymentData['razorpay_payment_id']);
                $response->setRazorpayOrderId($paymentData['razorpay_order_id']);
                $response->setMessage('Order already created via webhook');
                return $response;
            }

            // Build error response
            $this->logger->error('RazorpayOrderManagement: Order creation failed', [
                'cart_id' => $cartId,
                'error' => $e->getMessage(),
            ]);
            $response->setSuccess(false);
            $response->setError(true);
            $response->setMessage($e->getMessage());
            $response->setErrorCode($e->getCode());
        }

        return $response;
    }
    
    /**
     * Process payment and update order status
     */
    private function processPaymentAndUpdateOrder($order, $paymentData)
    {
        try {
            $payment = $order->getPayment();
            
            // Set transaction details
            $payment->setTransactionId($paymentData['razorpay_payment_id']);
            $payment->setLastTransId($paymentData['razorpay_payment_id']);
            $payment->setIsTransactionClosed(false);
            
            // Create payment transaction
            $transaction = $this->transactionBuilder
                ->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($paymentData['razorpay_payment_id'])
                ->setFailSafe(true)
                ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);
            
            // Add transaction details
            $transaction->setAdditionalInformation('razorpay_payment_id', $paymentData['razorpay_payment_id']);
            $transaction->setAdditionalInformation('razorpay_order_id', $paymentData['razorpay_order_id']);
            $transaction->setAdditionalInformation('razorpay_signature', $paymentData['razorpay_signature']);
            
            // Register payment capture
            $payment->registerCaptureNotification($order->getGrandTotal());
            
            // Update order status
            $order->setState(Order::STATE_PROCESSING);
            $order->setStatus(Order::STATE_PROCESSING);
            
            // Add order comment
            $order->addStatusHistoryComment(
                'Payment successful via Razorpay. Payment ID: ' . $paymentData['razorpay_payment_id'],
                Order::STATE_PROCESSING
            );
            
            // Save everything
            $this->transactionRepository->save($transaction);
            $this->orderRepository->save($order);
            
        } catch (\Exception $e) {
            // Log error but don't fail the order creation
            error_log('Payment processing failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Stamp the resolved delivery partner onto the order as intent, before payment processing.
     *
     * resolveCode() never throws (it always returns a code, defaulting to Shiprocket), but this
     * runs on the LIVE prepaid path immediately before capture, so it is defensively wrapped:
     * a stamp failure must never break a payment. It only sets a field on the in-memory order;
     * persistence rides the existing save inside processPaymentAndUpdateOrder() — NO new save.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return void
     */
    private function stampDeliveryPartnerIntent($order)
    {
        try {
            $order->setData('delivery_partner', $this->deliveryPartnerResolver->resolveCode($order));
        } catch (\Exception $e) {
            $this->logger->error(
                'Delivery-partner intent stamp failed for Razorpay order: ' . $order->getIncrementId()
                . ' - ' . $e->getMessage()
            );
        }
    }

    /**
     * Route forward-shipment creation to the order's active delivery partner.
     *
     * Shiprocket (the default) keeps its existing, byte-for-byte code path; any other
     * partner (currently only ShadowFax) goes through the neutral resolver abstraction.
     * Both branches are individually isolated and NEVER rethrow into the payment flow.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return array
     */
    private function createPartnerShipment($order)
    {
        try {
            $code = $this->deliveryPartnerResolver->resolveCode($order);
        } catch (\Exception $e) {
            // Resolver misconfiguration must not break a captured payment. Fall back to
            // the historical Shiprocket path (the safe default) and log loudly.
            $this->logger->error(
                'Delivery-partner resolution failed for Razorpay order: ' . $order->getIncrementId()
                . ' - falling back to Shiprocket - ' . $e->getMessage()
            );
            $code = PartnerCode::SHIPROCKET;
        }

        if ($code === PartnerCode::SHIPROCKET) {
            return $this->createShiprocketShipment($order);
        }

        return $this->createNonShiprocketShipment($order);
    }

    /**
     * Create a forward shipment via a non-Shiprocket partner (ShadowFax, ...) through the
     * neutral Formula_DeliveryPartner abstraction. This module depends ONLY on
     * Formula_DeliveryPartner — never directly on any concrete courier module.
     *
     * Mirrors createShiprocketShipment()'s isolation exactly: fully try/catch wrapped,
     * NEVER throws, always returns an array so a shipment failure can never unwind the
     * payment/invoice that already succeeded. Failed orders are left for the partner's
     * own self-healing backfill cron.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return array
     */
    private function createNonShiprocketShipment($order)
    {
        try {
            $result = $this->deliveryPartnerResolver->resolve($order)->createShipment($order);

            if ($result->isSuccessful()) {
                // Stage delivery_partner + shadowfax_* columns onto the order.
                $this->shipmentResultPersister->apply($order, $result);

                $comment = sprintf(
                    '%s shipment created successfully. Shipment ID: %s, AWB: %s, Courier: %s',
                    $result->getPartnerCode(),
                    $result->getShipmentId() ?: 'TBD',
                    $result->getAwb() ?: 'TBD',
                    $result->getCourierName() ?: 'TBD'
                );
                $order->addStatusHistoryComment($comment);

                $this->orderRepository->save($order);

                $this->logger->info(
                    'Delivery-partner shipment created for Razorpay order: ' . $order->getIncrementId()
                    . ' via ' . $result->getPartnerCode()
                );

                return ['success' => true, 'partner' => $result->getPartnerCode()];
            }

            // Unsuccessful (e.g. no AWB yet). Do NOT fail the order — leave it for the
            // partner's backfill cron to retry.
            $this->logger->warning(
                'Delivery-partner shipment creation returned unsuccessful for Razorpay order: '
                . $order->getIncrementId()
            );
            return ['success' => false, 'message' => 'Shipment creation failed'];
        } catch (\Exception $e) {
            // Log but never fail the order creation — mirrors the Shiprocket isolation.
            $this->logger->error(
                'Exception during delivery-partner shipment for Razorpay order: '
                . $order->getIncrementId() . ' - ' . $e->getMessage()
            );
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create Shiprocket shipment for order
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return array
     */
    private function createShiprocketShipment($order)
    {
        try {
            // Create shipment through ShiprocketShipmentService
            $shipmentResult = $this->shiprocketShipmentService->createShipment($order);

            if ($shipmentResult['success']) {
                // Store shipment data in order
                $order->setData('shiprocket_order_id', $shipmentResult['shiprocket_order_id']);
                $order->setData('shiprocket_shipment_id', $shipmentResult['shipment_id']);
                $order->setData('shiprocket_awb_number', $shipmentResult['awb_code']);
                $order->setData('shiprocket_courier_name', $shipmentResult['courier_name']);
                // delivery_partner is already stamped as intent before payment capture
                // (see stampDeliveryPartnerIntent) — this branch is otherwise unchanged.

                // Update order status to shipment created
                $order->setStatus('shipment_created');

                // Add order comment
                $comment = sprintf(
                    'Shiprocket shipment created successfully. Shipment ID: %s, AWB: %s, Courier: %s',
                    $shipmentResult['shipment_id'],
                    $shipmentResult['awb_code'] ?: 'TBD',
                    $shipmentResult['courier_name'] ?: 'TBD'
                );
                $order->addStatusHistoryComment($comment, 'shipment_created');

                // Save order with shipment data
                $this->orderRepository->save($order);

                $this->logger->info('Shiprocket shipment created successfully for Razorpay order: ' . $order->getIncrementId(), $shipmentResult);

                return $shipmentResult;
            } else {
                // Log error but don't fail the order creation
                $this->logger->warning('Shiprocket shipment creation failed for Razorpay order: ' . $order->getIncrementId(), $shipmentResult);
                return ['success' => false, 'message' => 'Shipment creation failed'];
            }

        } catch (\Exception $e) {
            // Log error but don't fail the order creation
            $this->logger->error('Exception during shipment creation for Razorpay order: ' . $order->getIncrementId() . ' - ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Update Razorpay order data manually
     */
    private function updateRazorpayOrderData($order, $paymentData)
    {
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $resource = $objectManager->get('\Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();
            $tableName = $resource->getTableName('razorpay_sales_order');
            
            // Check if record exists
            $select = $connection->select()
                ->from($tableName)
                ->where('order_id = ?', $order->getIncrementId());
            
            $existingRecord = $connection->fetchRow($select);
            
            $data = [
                'order_id' => $order->getIncrementId(),
                'rzp_order_id' => $paymentData['razorpay_order_id'],
                'rzp_payment_id' => $paymentData['razorpay_payment_id'],
                'rzp_webhook_data' => json_encode($paymentData),
                'rzp_webhook_notified_at' => date('Y-m-d H:i:s'),
                'rzp_update_order_cron_status' => 1
            ];
            
            if ($existingRecord) {
                // Update existing record
                $connection->update($tableName, $data, ['entity_id = ?' => $existingRecord['entity_id']]);
            } else {
                // Insert new record
                $connection->insert($tableName, $data);
            }
            
        } catch (\Exception $e) {
            // Log error but don't fail the order creation
            error_log('Razorpay table update failed: ' . $e->getMessage());
        }
    }

    /**
     * Find an existing order by Razorpay payment ID or quote ID (webhook may have created it)
     */
    private function findOrderByRazorpayPayment($razorpayPaymentId, $cartId = null)
    {
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $resource = $objectManager->get('\Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();

            // Strategy 1: Look up in razorpay_sales_order table by payment ID
            $tableName = $resource->getTableName('razorpay_sales_order');
            $select = $connection->select()
                ->from($tableName, ['order_id'])
                ->where('rzp_payment_id = ?', $razorpayPaymentId);

            $row = $connection->fetchRow($select);
            if ($row && !empty($row['order_id'])) {
                $searchCriteria = $objectManager->get('\Magento\Framework\Api\SearchCriteriaBuilder')
                    ->addFilter('increment_id', $row['order_id'])
                    ->create();
                $orders = $this->orderRepository->getList($searchCriteria)->getItems();
                if (!empty($orders)) {
                    return reset($orders);
                }
            }

            // Strategy 2: Look up in sales_order by quote_id (faster — no dependency on razorpay table timing)
            if ($cartId) {
                $searchCriteria = $objectManager->get('\Magento\Framework\Api\SearchCriteriaBuilder')
                    ->addFilter('quote_id', $cartId)
                    ->create();
                $orders = $this->orderRepository->getList($searchCriteria)->getItems();
                if (!empty($orders)) {
                    return reset($orders);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('RazorpayOrderManagement: Failed to find existing order', [
                'payment_id' => $razorpayPaymentId,
                'cart_id' => $cartId,
                'error' => $e->getMessage(),
            ]);
        }
        return null;
    }
}