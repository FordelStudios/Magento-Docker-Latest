<?php
namespace Formula\RazorpayApi\Model;

use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Formula\Shiprocket\Service\ShiprocketShipmentService;
use Formula\OrderCancellationReturn\Service\RefundProcessor;
use Formula\OrderCancellationReturn\Service\InventoryService;
use Psr\Log\LoggerInterface;

/**
 * Handles Razorpay payment.captured webhook events.
 *
 * Creates Magento orders from quotes when the frontend flow failed
 * after Razorpay captured payment. Idempotent — skips if order already exists.
 *
 * Intentionally duplicates order creation logic from OrderManagement.php
 * rather than refactoring, to avoid risk to the working frontend flow.
 */
class WebhookHandler
{
    /** @var CartManagementInterface */
    private $cartManagement;

    /** @var CartRepositoryInterface */
    private $cartRepository;

    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var BuilderInterface */
    private $transactionBuilder;

    /** @var TransactionRepositoryInterface */
    private $transactionRepository;

    /** @var ResourceConnection */
    private $resourceConnection;

    /** @var OrderCollectionFactory */
    private $orderCollectionFactory;

    /** @var ShiprocketShipmentService */
    private $shiprocketShipmentService;

    /** @var RefundProcessor */
    private $refundProcessor;

    /** @var InventoryService */
    private $inventoryService;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        CartManagementInterface $cartManagement,
        CartRepositoryInterface $cartRepository,
        OrderRepositoryInterface $orderRepository,
        BuilderInterface $transactionBuilder,
        TransactionRepositoryInterface $transactionRepository,
        ResourceConnection $resourceConnection,
        OrderCollectionFactory $orderCollectionFactory,
        ShiprocketShipmentService $shiprocketShipmentService,
        RefundProcessor $refundProcessor,
        InventoryService $inventoryService,
        LoggerInterface $logger
    ) {
        $this->cartManagement = $cartManagement;
        $this->cartRepository = $cartRepository;
        $this->orderRepository = $orderRepository;
        $this->transactionBuilder = $transactionBuilder;
        $this->transactionRepository = $transactionRepository;
        $this->resourceConnection = $resourceConnection;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->shiprocketShipmentService = $shiprocketShipmentService;
        $this->refundProcessor = $refundProcessor;
        $this->inventoryService = $inventoryService;
        $this->logger = $logger;
    }

    /**
     * Handle payment.captured event from Razorpay webhook.
     *
     * @param array $payload Full webhook payload
     * @return array Result with status and message
     */
    public function handlePaymentCaptured(array $payload)
    {
        $paymentEntity = $payload['payload']['payment']['entity'] ?? [];

        $rzpPaymentId = $paymentEntity['id'] ?? '';
        $rzpOrderId = $paymentEntity['order_id'] ?? '';
        $amount = ($paymentEntity['amount'] ?? 0) / 100; // paise to rupees
        $notes = $paymentEntity['notes'] ?? [];
        $cartId = $notes['magento_cart_id'] ?? null;

        $this->logger->info('RazorpayWebhook: Processing payment.captured', [
            'rzp_payment_id' => $rzpPaymentId,
            'rzp_order_id' => $rzpOrderId,
            'amount' => $amount,
            'cart_id' => $cartId,
        ]);

        // Validate required data
        if (empty($rzpPaymentId) || empty($rzpOrderId)) {
            $this->logger->error('RazorpayWebhook: Missing payment_id or order_id');
            return ['status' => 'error', 'message' => 'Missing payment or order ID'];
        }

        if (empty($cartId)) {
            $this->logger->error('RazorpayWebhook: No magento_cart_id in notes', [
                'notes' => $notes,
            ]);
            return ['status' => 'error', 'message' => 'No cart ID in payment notes'];
        }

        // 1. IDEMPOTENCY: Check if order already exists for this Razorpay payment
        if ($this->orderAlreadyExists($rzpPaymentId, $rzpOrderId)) {
            $this->logger->info('RazorpayWebhook: Order already exists, skipping', [
                'rzp_payment_id' => $rzpPaymentId,
            ]);
            return ['status' => 'ok', 'message' => 'Order already exists'];
        }

        // 2. Load quote
        try {
            $quote = $this->cartRepository->get($cartId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->warning('RazorpayWebhook: Quote not found or expired', [
                'cart_id' => $cartId,
                'rzp_payment_id' => $rzpPaymentId,
            ]);
            return [
                'status' => 'error',
                'message' => 'Quote not found (cart_id: ' . $cartId . '). Manual order creation required.',
                'requires_manual' => true,
            ];
        }

        // 3. Check quote is still active
        if (!$quote->getIsActive()) {
            // Quote already converted to order — idempotency via quote check
            $this->logger->info('RazorpayWebhook: Quote already inactive (order likely exists)', [
                'cart_id' => $cartId,
            ]);
            return ['status' => 'ok', 'message' => 'Quote already converted'];
        }

        // 4. Set payment method and Razorpay data on quote
        try {
            $payment = $quote->getPayment();
            $payment->setMethod('razorpay');
            $payment->setAdditionalInformation('razorpay_payment_id', $rzpPaymentId);
            $payment->setAdditionalInformation('razorpay_order_id', $rzpOrderId);
            $payment->setAdditionalInformation('razorpay_signature', 'webhook_verified');

            $this->cartRepository->save($quote);
        } catch (\Exception $e) {
            $this->logger->error('RazorpayWebhook: Failed to set payment on quote', [
                'cart_id' => $cartId,
                'error' => $e->getMessage(),
            ]);
            return ['status' => 'error', 'message' => 'Failed to set payment: ' . $e->getMessage()];
        }

        // 4b. Ensure shipping address is set (prevents NULL country_id failures)
        $shippingAddress = $quote->getShippingAddress();
        if ($shippingAddress && !$shippingAddress->getCountryId()) {
            $billingAddress = $quote->getBillingAddress();
            if ($billingAddress && $billingAddress->getCountryId()) {
                $shippingAddress->setFirstname($billingAddress->getFirstname());
                $shippingAddress->setLastname($billingAddress->getLastname());
                $shippingAddress->setStreet($billingAddress->getStreet());
                $shippingAddress->setCity($billingAddress->getCity());
                $shippingAddress->setCountryId($billingAddress->getCountryId());
                $shippingAddress->setRegion($billingAddress->getRegion());
                $shippingAddress->setRegionId($billingAddress->getRegionId());
                $shippingAddress->setPostcode($billingAddress->getPostcode());
                $shippingAddress->setTelephone($billingAddress->getTelephone());
                $shippingAddress->setEmail($billingAddress->getEmail());
                if (!$shippingAddress->getShippingMethod()) {
                    $shippingAddress->setShippingMethod('flatrate_flatrate');
                    $shippingAddress->setCollectShippingRates(true);
                    $shippingAddress->collectShippingRates();
                }
                $this->cartRepository->save($quote);
                $this->logger->info('RazorpayWebhook: Copied billing address to shipping address', [
                    'cart_id' => $cartId,
                ]);
            }
        }

        // 5. Place order
        try {
            $orderId = $this->cartManagement->placeOrder($cartId);
            $order = $this->orderRepository->get($orderId);

            $this->logger->info('RazorpayWebhook: Order created successfully', [
                'order_id' => $orderId,
                'increment_id' => $order->getIncrementId(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('RazorpayWebhook: Failed to place order', [
                'cart_id' => $cartId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ['status' => 'error', 'message' => 'Failed to place order: ' . $e->getMessage()];
        }

        // 6. Process payment (transaction + status)
        $paymentData = [
            'razorpay_payment_id' => $rzpPaymentId,
            'razorpay_order_id' => $rzpOrderId,
            'razorpay_signature' => 'webhook_verified',
        ];
        $this->processPaymentAndUpdateOrder($order, $paymentData);

        // 7. Create Shiprocket shipment (non-blocking)
        $this->createShiprocketShipment($order);

        // 8. Update razorpay_sales_order table
        $this->updateRazorpayOrderData($order, $paymentData);

        return [
            'status' => 'ok',
            'message' => 'Order created via webhook',
            'order_id' => $order->getIncrementId(),
        ];
    }

    /**
     * Handle refund events from Razorpay (manual refund on dashboard or API).
     *
     * When a refund happens on Razorpay side, we must:
     * 1. Find the corresponding Magento order
     * 2. Cancel the Shiprocket shipment (if active)
     * 3. Restore inventory
     * 4. Credit wallet portion (if any) back to customer wallet
     * 5. Update order status to cancelled
     *
     * Note: We do NOT call RazorpayRefundService here because the refund
     * already happened on Razorpay's side — we'd get "already refunded".
     *
     * @param array $payload Full webhook payload
     * @return array
     */
    public function handlePaymentRefunded(array $payload)
    {
        // Extract payment entity from refund payload
        $paymentEntity = $payload['payload']['payment']['entity'] ?? [];
        $refundEntity = $payload['payload']['refund']['entity'] ?? $paymentEntity;

        $rzpPaymentId = $paymentEntity['id'] ?? ($refundEntity['payment_id'] ?? '');
        $rzpOrderId = $paymentEntity['order_id'] ?? ($refundEntity['order_id'] ?? '');

        $this->logger->info('RazorpayWebhook: Processing refund event', [
            'rzp_payment_id' => $rzpPaymentId,
            'rzp_order_id' => $rzpOrderId,
        ]);

        if (empty($rzpPaymentId)) {
            $this->logger->error('RazorpayWebhook: Missing payment_id in refund event');
            return ['status' => 'error', 'message' => 'Missing payment ID in refund event'];
        }

        // Find the Magento order by Razorpay payment ID
        $order = $this->findOrderByRazorpayPaymentId($rzpPaymentId);
        if (!$order) {
            $this->logger->warning('RazorpayWebhook: Order not found for refunded payment', [
                'rzp_payment_id' => $rzpPaymentId,
            ]);
            return ['status' => 'error', 'message' => 'Order not found for payment: ' . $rzpPaymentId];
        }

        $incrementId = $order->getIncrementId();

        // Idempotency: skip if order is already cancelled/closed
        if (in_array($order->getState(), [Order::STATE_CANCELED, Order::STATE_CLOSED])) {
            $this->logger->info('RazorpayWebhook: Order already cancelled/closed, skipping refund handling', [
                'order' => $incrementId,
                'state' => $order->getState(),
            ]);
            return ['status' => 'ok', 'message' => 'Order already in terminal state'];
        }

        // 1. Cancel Shiprocket shipment if exists
        $shipmentId = $order->getData('shiprocket_shipment_id');
        if ($shipmentId) {
            try {
                $this->shiprocketShipmentService->cancelShipment($shipmentId);
                $order->addCommentToStatusHistory(
                    '[Razorpay Refund] Shiprocket shipment cancelled (ID: ' . $shipmentId . ')'
                );
                $this->logger->info('RazorpayWebhook: Shiprocket shipment cancelled', [
                    'order' => $incrementId,
                    'shipment_id' => $shipmentId,
                ]);
            } catch (\Exception $e) {
                $order->addCommentToStatusHistory(
                    '[Razorpay Refund] Failed to cancel Shiprocket shipment: ' . $e->getMessage()
                );
                $this->logger->error('RazorpayWebhook: Shiprocket cancellation failed', [
                    'order' => $incrementId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 2. Restore inventory
        try {
            $this->inventoryService->restoreInventoryForCancellation($order);
            $this->logger->info('RazorpayWebhook: Inventory restored for refunded order', [
                'order' => $incrementId,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('RazorpayWebhook: Inventory restoration failed', [
                'order' => $incrementId,
                'error' => $e->getMessage(),
            ]);
        }

        // 3. Credit wallet portion back (Razorpay portion is already refunded by Razorpay)
        $walletAmountUsed = $order->getWalletAmountUsed() ?: 0;
        if ($walletAmountUsed > 0) {
            try {
                $walletRefundService = \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Formula\OrderCancellationReturn\Service\WalletRefundService::class);
                $walletRefundService->processRefund(
                    $order,
                    $walletAmountUsed,
                    \Formula\Wallet\Api\Data\WalletTransactionInterface::REFERENCE_TYPE_ORDER_CANCEL
                );
                $order->addCommentToStatusHistory(
                    sprintf('[Razorpay Refund] Wallet refunded: %s', $walletAmountUsed)
                );
            } catch (\Exception $e) {
                $order->addCommentToStatusHistory(
                    '[Razorpay Refund] Wallet refund failed: ' . $e->getMessage()
                );
                $this->logger->error('RazorpayWebhook: Wallet refund failed', [
                    'order' => $incrementId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 4. Update order status
        $order->setState(Order::STATE_CANCELED);
        $order->setStatus(Order::STATE_CANCELED);
        $order->addCommentToStatusHistory(
            '[Razorpay Refund] Order cancelled due to Razorpay refund. Payment ID: ' . $rzpPaymentId
        );
        $this->orderRepository->save($order);

        $this->logger->info('RazorpayWebhook: Order cancelled due to Razorpay refund', [
            'order' => $incrementId,
            'rzp_payment_id' => $rzpPaymentId,
        ]);

        return [
            'status' => 'ok',
            'message' => 'Order cancelled due to refund',
            'order_id' => $incrementId,
        ];
    }

    /**
     * Find order by Razorpay payment ID via razorpay_sales_order table
     *
     * @param string $rzpPaymentId
     * @return Order|null
     */
    private function findOrderByRazorpayPaymentId($rzpPaymentId)
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('razorpay_sales_order');

            $select = $connection->select()
                ->from($tableName, ['order_id'])
                ->where('rzp_payment_id = ?', $rzpPaymentId);

            $row = $connection->fetchRow($select);
            if ($row && !empty($row['order_id'])) {
                $collection = $this->orderCollectionFactory->create();
                $collection->addFieldToFilter('increment_id', $row['order_id']);
                $collection->setPageSize(1);
                $order = $collection->getFirstItem();
                return $order->getId() ? $order : null;
            }
        } catch (\Exception $e) {
            $this->logger->error('RazorpayWebhook: Failed to find order by payment ID', [
                'rzp_payment_id' => $rzpPaymentId,
                'error' => $e->getMessage(),
            ]);
        }
        return null;
    }

    /**
     * Check if an order already exists for this Razorpay payment.
     * Checks razorpay_sales_order table by payment ID or order ID.
     */
    private function orderAlreadyExists(string $rzpPaymentId, string $rzpOrderId): bool
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('razorpay_sales_order');

            $select = $connection->select()
                ->from($tableName)
                ->where('rzp_payment_id = ?', $rzpPaymentId);

            $existing = $connection->fetchRow($select);
            if ($existing) {
                return true;
            }

            // Also check by Razorpay order ID
            $select = $connection->select()
                ->from($tableName)
                ->where('rzp_order_id = ?', $rzpOrderId);

            return (bool) $connection->fetchRow($select);
        } catch (\Exception $e) {
            // If table doesn't exist or query fails, assume no order exists
            $this->logger->warning('RazorpayWebhook: Idempotency check failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Process payment and set order to processing state.
     * Duplicated from OrderManagement::processPaymentAndUpdateOrder()
     */
    private function processPaymentAndUpdateOrder(Order $order, array $paymentData): void
    {
        try {
            $payment = $order->getPayment();

            $payment->setTransactionId($paymentData['razorpay_payment_id']);
            $payment->setLastTransId($paymentData['razorpay_payment_id']);
            $payment->setIsTransactionClosed(false);

            $transaction = $this->transactionBuilder
                ->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($paymentData['razorpay_payment_id'])
                ->setFailSafe(true)
                ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);

            $transaction->setAdditionalInformation('razorpay_payment_id', $paymentData['razorpay_payment_id']);
            $transaction->setAdditionalInformation('razorpay_order_id', $paymentData['razorpay_order_id']);
            $transaction->setAdditionalInformation('source', 'webhook');

            $payment->registerCaptureNotification($order->getGrandTotal());

            $order->setState(Order::STATE_PROCESSING);
            $order->setStatus(Order::STATE_PROCESSING);
            $order->addStatusHistoryComment(
                'Payment captured via Razorpay webhook. Payment ID: ' . $paymentData['razorpay_payment_id'],
                Order::STATE_PROCESSING
            );

            $this->transactionRepository->save($transaction);
            $this->orderRepository->save($order);

            $this->logger->info('RazorpayWebhook: Payment processed', [
                'order' => $order->getIncrementId(),
                'payment_id' => $paymentData['razorpay_payment_id'],
            ]);
        } catch (\Exception $e) {
            $this->logger->error('RazorpayWebhook: Payment processing failed', [
                'order' => $order->getIncrementId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create Shiprocket shipment for order (non-blocking).
     * Duplicated from OrderManagement::createShiprocketShipment()
     */
    private function createShiprocketShipment(Order $order): void
    {
        try {
            $this->logger->info('RazorpayWebhook: Creating Shiprocket shipment', [
                'order' => $order->getIncrementId(),
            ]);

            $shipmentResult = $this->shiprocketShipmentService->createShipment($order);

            if ($shipmentResult['success']) {
                $order->setData('shiprocket_order_id', $shipmentResult['shiprocket_order_id']);
                $order->setData('shiprocket_shipment_id', $shipmentResult['shipment_id']);
                $order->setData('shiprocket_awb_number', $shipmentResult['awb_code']);
                $order->setData('shiprocket_courier_name', $shipmentResult['courier_name']);
                $order->setStatus('shipment_created');

                $comment = sprintf(
                    'Shiprocket shipment created via webhook. Shipment ID: %s, AWB: %s, Courier: %s',
                    $shipmentResult['shipment_id'],
                    $shipmentResult['awb_code'] ?: 'TBD',
                    $shipmentResult['courier_name'] ?: 'TBD'
                );
                $order->addStatusHistoryComment($comment, 'shipment_created');
                $this->orderRepository->save($order);

                $this->logger->info('RazorpayWebhook: Shiprocket shipment created', $shipmentResult);
            } else {
                $this->logger->warning('RazorpayWebhook: Shiprocket shipment failed', [
                    'order' => $order->getIncrementId(),
                    'result' => $shipmentResult,
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('RazorpayWebhook: Shiprocket exception', [
                'order' => $order->getIncrementId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update razorpay_sales_order table with payment data.
     * Duplicated from OrderManagement::updateRazorpayOrderData()
     */
    private function updateRazorpayOrderData(Order $order, array $paymentData): void
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('razorpay_sales_order');

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
                'rzp_update_order_cron_status' => 1,
            ];

            if ($existingRecord) {
                $connection->update($tableName, $data, ['entity_id = ?' => $existingRecord['entity_id']]);
            } else {
                $connection->insert($tableName, $data);
            }

            $this->logger->info('RazorpayWebhook: razorpay_sales_order table updated', [
                'order' => $order->getIncrementId(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('RazorpayWebhook: Table update failed', [
                'order' => $order->getIncrementId(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
