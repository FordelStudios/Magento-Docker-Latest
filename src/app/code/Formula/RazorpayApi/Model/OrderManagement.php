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

    public function __construct(
        CartManagementInterface $cartManagement,
        CartRepositoryInterface $cartRepository,
        OrderRepositoryInterface $orderRepository,
        BuilderInterface $transactionBuilder,
        TransactionRepositoryInterface $transactionRepository,
        Transaction $transaction,
        OrderResponseInterfaceFactory $orderResponseFactory,
        ShiprocketShipmentService $shiprocketShipmentService,
        LoggerInterface $logger
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
            }
            
            // Save quote before placing order
            $this->cartRepository->save($quote);
            
            // Create order
            $orderId = $this->cartManagement->placeOrder($cartId);
            
            // Load the created order
            $order = $this->orderRepository->get($orderId);
            
            // **IMPORTANT: Process the payment and update order status**
            $this->processPaymentAndUpdateOrder($order, $paymentData);
            
            // Create Shiprocket shipment automatically
            $shipmentData = $this->createShiprocketShipment($order);
            
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
            // Build error response
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
                
                $this->logger->info('Shiprocket shipment created for order: ' . $order->getIncrementId(), $shipmentResult);
                
                return $shipmentResult;
            } else {
                // Log error but don't fail the order creation
                $this->logger->warning('Shiprocket shipment creation failed for order: ' . $order->getIncrementId(), $shipmentResult);
                return ['success' => false, 'message' => 'Shipment creation failed'];
            }
            
        } catch (\Exception $e) {
            // Log error but don't fail the order creation
            $this->logger->error('Exception during shipment creation for order: ' . $order->getIncrementId() . ' - ' . $e->getMessage());
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
}