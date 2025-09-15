<?php
namespace Formula\CashOnDeliveryApi\Model;

use Formula\CashOnDeliveryApi\Api\CodOrderManagementInterface;
use Formula\CashOnDeliveryApi\Api\Data\CodOrderResponseInterface;
use Formula\CashOnDeliveryApi\Api\Data\CodOrderResponseInterfaceFactory;
use Formula\Shiprocket\Service\ShiprocketShipmentService;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

class CodOrderManagement implements CodOrderManagementInterface
{
    protected $cartManagement;
    protected $cartRepository;
    protected $orderRepository;
    protected $codOrderResponseFactory;
    protected $shiprocketShipmentService;
    protected $logger;

    public function __construct(
        CartManagementInterface $cartManagement,
        CartRepositoryInterface $cartRepository,
        OrderRepositoryInterface $orderRepository,
        CodOrderResponseInterfaceFactory $codOrderResponseFactory,
        ShiprocketShipmentService $shiprocketShipmentService,
        LoggerInterface $logger
    ) {
        $this->cartManagement = $cartManagement;
        $this->cartRepository = $cartRepository;
        $this->orderRepository = $orderRepository;
        $this->codOrderResponseFactory = $codOrderResponseFactory;
        $this->shiprocketShipmentService = $shiprocketShipmentService;
        $this->logger = $logger;
    }

    /**
     * Create order with Cash on Delivery payment
     *
     * @param string $cartId
     * @param mixed $billingAddress
     * @param mixed $shippingAddress
     * @return \Formula\CashOnDeliveryApi\Api\Data\CodOrderResponseInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function createOrder($cartId, $billingAddress, $shippingAddress = null)
    {
        /** @var CodOrderResponseInterface $response */
        $response = $this->codOrderResponseFactory->create();

        try {
            $quote = $this->cartRepository->get($cartId);
            
            // Set payment method to Cash on Delivery
            $payment = $quote->getPayment();
            $payment->setMethod('cashondelivery');
            
            // Update billing address if provided
            if ($billingAddress && is_array($billingAddress)) {
                $existingBillingAddress = $quote->getBillingAddress();
                if ($existingBillingAddress) {
                    $this->updateAddress($existingBillingAddress, $billingAddress);
                }
            }
            
            // Update shipping address if provided, otherwise use billing address
            $finalShippingAddress = $shippingAddress ?: $billingAddress;
            if ($finalShippingAddress && is_array($finalShippingAddress)) {
                $existingShippingAddress = $quote->getShippingAddress();
                if ($existingShippingAddress) {
                    $this->updateAddress($existingShippingAddress, $finalShippingAddress);
                }
            }
            
            // Save quote before placing order
            $this->cartRepository->save($quote);
            
            // Create order
            $orderId = $this->cartManagement->placeOrder($cartId);
            
            // Load the created order
            $order = $this->orderRepository->get($orderId);
            
            // Update order status to pending (COD starts as pending)
            $order->setState(Order::STATE_NEW);
            $order->setStatus('pending');
            $order->addStatusHistoryComment('Cash on Delivery order created successfully.', 'pending');
            
            // Create Shiprocket shipment automatically
            $shipmentData = $this->createShiprocketShipment($order);
            
            // If shipment created, update status to processing
            if ($shipmentData && $shipmentData['success']) {
                $order->setState(Order::STATE_PROCESSING);
                $order->setStatus('processing');
            }
            
            // Save order
            $this->orderRepository->save($order);
            
            // Build successful response
            $response->setSuccess(true);
            $response->setOrderId($orderId);
            $response->setIncrementId($order->getIncrementId());
            $response->setStatus($order->getStatus());
            $response->setState($order->getState());
            $response->setTotalAmount($order->getGrandTotal());
            $response->setCurrency($order->getOrderCurrencyCode());
            $response->setCreatedAt($order->getCreatedAt());
            $response->setPaymentMethod('cashondelivery');
            
            // Add shipment information to response
            if ($shipmentData && $shipmentData['success']) {
                $response->setMessage('COD order created and shipment scheduled successfully!');
                
                // Set shipment tracking fields
                $response->setShiprocketOrderId($shipmentData['shiprocket_order_id'] ?? null);
                $response->setShiprocketShipmentId($shipmentData['shipment_id'] ?? null);
                $response->setShiprocketAwbNumber($shipmentData['awb_code'] ?? null);
                $response->setShiprocketCourierName($shipmentData['courier_name'] ?? null);
            } else {
                $response->setMessage('COD order created successfully!');
            }
            
        } catch (\Exception $e) {
            // Build error response
            $response->setSuccess(false);
            $response->setError(true);
            $response->setMessage($e->getMessage());
            $response->setErrorCode($e->getCode());
            
            $this->logger->error('COD order creation failed: ' . $e->getMessage());
        }

        return $response;
    }

    /**
     * Update address with provided data
     *
     * @param \Magento\Quote\Model\Quote\Address $address
     * @param array $addressData
     */
    private function updateAddress($address, $addressData)
    {
        $address->setFirstname($addressData['firstname'] ?? 'John');
        $address->setLastname($addressData['lastname'] ?? 'Doe');
        $address->setStreet($addressData['street'] ?? ['123 Street']);
        $address->setCity($addressData['city'] ?? 'City');
        $address->setCountryId($addressData['country_id'] ?? 'IN');
        $address->setRegion($addressData['region'] ?? 'State');
        $address->setRegionId($addressData['region_id'] ?? 0);
        $address->setPostcode($addressData['postcode'] ?? '123456');
        $address->setTelephone($addressData['telephone'] ?? '1234567890');
        $address->setEmail($addressData['email'] ?? 'test@example.com');
    }

    /**
     * Create Shiprocket shipment for COD order
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
                
                // Add order comment
                $comment = sprintf(
                    'Shiprocket shipment created for COD order. Shipment ID: %s, AWB: %s, Courier: %s',
                    $shipmentResult['shipment_id'],
                    $shipmentResult['awb_code'] ?: 'TBD',
                    $shipmentResult['courier_name'] ?: 'TBD'
                );
                $order->addStatusHistoryComment($comment, 'processing');
                
                $this->logger->info('Shiprocket shipment created for COD order: ' . $order->getIncrementId(), $shipmentResult);
                
                return $shipmentResult;
            } else {
                // Log error but don't fail the order creation
                $this->logger->warning('Shiprocket shipment creation failed for COD order: ' . $order->getIncrementId(), $shipmentResult);
                return ['success' => false, 'message' => 'Shipment creation failed'];
            }
            
        } catch (\Exception $e) {
            // Log error but don't fail the order creation
            $this->logger->error('Exception during shipment creation for COD order: ' . $order->getIncrementId() . ' - ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}