<?php
namespace Formula\Shiprocket\Service;

use Formula\Shiprocket\Helper\Data as ShiprocketHelper;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class ShiprocketShipmentService
{
    protected $shiprocketHelper;
    protected $curl;
    protected $logger;
    
    const SHIPROCKET_API_URL = 'https://apiv2.shiprocket.in/v1/external/';
    const AUTH_TOKEN_ENDPOINT = 'auth/login';
    const CREATE_SHIPMENT_ENDPOINT = 'orders/create/adhoc';
    const CANCEL_SHIPMENT_ENDPOINT = 'orders/cancel';
    const SHIPMENT_TRACK_ENDPOINT = 'courier/track/';
    
    private $authToken = null;

    public function __construct(
        ShiprocketHelper $shiprocketHelper,
        Curl $curl,
        LoggerInterface $logger
    ) {
        $this->shiprocketHelper = $shiprocketHelper;
        $this->curl = $curl;
        $this->logger = $logger;
    }

    /**
     * Create shipment for order
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createShipment($order)
    {
        try {
            if (!$this->shiprocketHelper->isEnabled()) {
                throw new LocalizedException(__('Shiprocket is not enabled.'));
            }

            $this->authenticate();
            
            $shipmentData = $this->prepareShipmentData($order);
            $response = $this->callShiprocketAPI(self::CREATE_SHIPMENT_ENDPOINT, $shipmentData, 'POST');
            
            if (isset($response['order_id']) && isset($response['shipment_id'])) {
                return [
                    'success' => true,
                    'shiprocket_order_id' => $response['order_id'],
                    'shipment_id' => $response['shipment_id'],
                    'awb_code' => $response['awb_code'] ?? null,
                    'courier_name' => $response['courier_name'] ?? null,
                    'message' => 'Shipment created successfully'
                ];
            } else {
                throw new LocalizedException(__('Failed to create shipment: %1', $response['message'] ?? 'Unknown error'));
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Shiprocket shipment creation failed: ' . $e->getMessage());
            throw new LocalizedException(__('Shipment creation failed: %1', $e->getMessage()));
        }
    }

    /**
     * Cancel shipment
     *
     * @param int $shipmentId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function cancelShipment($shipmentId)
    {
        try {
            $this->authenticate();
            
            $cancelData = [
                'ids' => [$shipmentId]
            ];
            
            $response = $this->callShiprocketAPI(self::CANCEL_SHIPMENT_ENDPOINT, $cancelData, 'POST');
            
            return [
                'success' => true,
                'message' => 'Shipment cancelled successfully'
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to cancel shipment: ' . $e->getMessage());
            throw new LocalizedException(__('Failed to cancel shipment: %1', $e->getMessage()));
        }
    }

    /**
     * Track shipment
     *
     * @param string $awbCode
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function trackShipment($awbCode)
    {
        try {
            $this->authenticate();
            
            $response = $this->callShiprocketAPI(self::SHIPMENT_TRACK_ENDPOINT . $awbCode, [], 'GET');
            
            return [
                'success' => true,
                'tracking_data' => $response['tracking_data'] ?? [],
                'current_status' => $response['current_status'] ?? 'unknown'
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to track shipment: ' . $e->getMessage());
            throw new LocalizedException(__('Failed to track shipment: %1', $e->getMessage()));
        }
    }

    /**
     * Authenticate with Shiprocket API
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function authenticate()
    {
        if ($this->authToken) {
            return;
        }

        $email = $this->shiprocketHelper->getEmail();
        $password = $this->shiprocketHelper->getPassword();
        
        if (!$email || !$password) {
            throw new LocalizedException(__('Shiprocket credentials not configured.'));
        }

        $authData = [
            'email' => $email,
            'password' => $password
        ];

        $response = $this->callShiprocketAPI(self::AUTH_TOKEN_ENDPOINT, $authData, 'POST', false);
        
        if (isset($response['token'])) {
            $this->authToken = $response['token'];
        } else {
            throw new LocalizedException(__('Shiprocket authentication failed: %1', $response['message'] ?? 'Unknown error'));
        }
    }

    /**
     * Prepare shipment data for Shiprocket API
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return array
     */
    protected function prepareShipmentData($order)
    {
        $shippingAddress = $order->getShippingAddress();
        $billingAddress = $order->getBillingAddress();
        
        // Prepare order items
        $orderItems = [];
        $totalWeight = 0;
        
        foreach ($order->getAllVisibleItems() as $item) {
            $orderItems[] = [
                'name' => $item->getName(),
                'sku' => $item->getSku(),
                'units' => (int) $item->getQtyOrdered(),
                'selling_price' => $item->getPrice(),
                'discount' => $item->getDiscountAmount(),
                'tax' => $item->getTaxAmount(),
                'hsn' => 441122 // Default HSN code, can be configured per product
            ];
            // Add weight if available (default to 0.5 kg per item if not set)
            $itemWeight = $item->getWeight() ?: 0.5;
            $totalWeight += $itemWeight * $item->getQtyOrdered();
        }

        return [
            'order_id' => $order->getIncrementId(),
            'order_date' => $order->getCreatedAt(),
            'pickup_location' => 'Primary', // Default pickup location
            'billing_customer_name' => $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname(),
            'billing_last_name' => $billingAddress->getLastname(),
            'billing_address' => implode(', ', $billingAddress->getStreet()),
            'billing_address_2' => '',
            'billing_city' => $billingAddress->getCity(),
            'billing_pincode' => $billingAddress->getPostcode(),
            'billing_state' => $billingAddress->getRegion(),
            'billing_country' => $billingAddress->getCountryId(),
            'billing_email' => $billingAddress->getEmail(),
            'billing_phone' => $billingAddress->getTelephone(),
            'shipping_is_billing' => false,
            'shipping_customer_name' => $shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname(),
            'shipping_last_name' => $shippingAddress->getLastname(),
            'shipping_address' => implode(', ', $shippingAddress->getStreet()),
            'shipping_address_2' => '',
            'shipping_city' => $shippingAddress->getCity(),
            'shipping_pincode' => $shippingAddress->getPostcode(),
            'shipping_country' => $shippingAddress->getCountryId(),
            'shipping_state' => $shippingAddress->getRegion(),
            'shipping_email' => $shippingAddress->getEmail(),
            'shipping_phone' => $shippingAddress->getTelephone(),
            'order_items' => $orderItems,
            'payment_method' => 'Prepaid', // Razorpay orders are prepaid
            'shipping_charges' => $order->getShippingAmount(),
            'giftwrap_charges' => 0,
            'transaction_charges' => 0,
            'total_discount' => abs($order->getDiscountAmount()),
            'sub_total' => $order->getSubtotal(),
            'length' => 15, // Default dimensions in cm
            'breadth' => 10,
            'height' => 5,
            'weight' => max(0.5, $totalWeight), // Minimum 0.5 kg
            'pickup_postcode' => $this->shiprocketHelper->getPickupPostcode()
        ];
    }

    /**
     * Make API call to Shiprocket
     *
     * @param string $endpoint
     * @param array $data
     * @param string $method
     * @param bool $requireAuth
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function callShiprocketAPI($endpoint, $data, $method = 'POST', $requireAuth = true)
    {
        $url = self::SHIPROCKET_API_URL . $endpoint;
        
        $this->curl->addHeader('Content-Type', 'application/json');
        
        if ($requireAuth && $this->authToken) {
            $this->curl->addHeader('Authorization', 'Bearer ' . $this->authToken);
        }

        if ($method === 'POST') {
            $this->curl->post($url, json_encode($data));
        } else {
            $this->curl->get($url);
        }

        $response = $this->curl->getBody();
        $httpCode = $this->curl->getStatus();

        $responseData = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            return $responseData;
        } else {
            $errorMessage = $responseData['message'] ?? 'HTTP Error ' . $httpCode;
            throw new LocalizedException(__('Shiprocket API error: %1', $errorMessage));
        }
    }
}