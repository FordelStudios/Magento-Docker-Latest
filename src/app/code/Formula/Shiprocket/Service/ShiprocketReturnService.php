<?php
namespace Formula\Shiprocket\Service;

use Formula\Shiprocket\Helper\Data as ShiprocketHelper;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class ShiprocketReturnService
{
    protected $shiprocketHelper;
    protected $curl;
    protected $logger;
    
    const SHIPROCKET_API_URL = 'https://apiv2.shiprocket.in/v1/external/';
    const AUTH_TOKEN_ENDPOINT = 'auth/login';
    const CREATE_RETURN_ENDPOINT = 'orders/create/return';
    const RETURN_STATUS_ENDPOINT = 'orders/show/';
    
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
     * Create return pickup request
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param string $reason
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createReturnPickup($order, $reason = 'Customer Return')
    {
        try {
            if (!$this->shiprocketHelper->isEnabled()) {
                throw new LocalizedException(__('Shiprocket is not enabled.'));
            }

            $this->authenticate();
            
            $returnData = $this->prepareReturnData($order, $reason);
            $response = $this->callShiprocketAPI(self::CREATE_RETURN_ENDPOINT, $returnData, 'POST');
            
            if (isset($response['return_id'])) {
                return [
                    'success' => true,
                    'return_id' => $response['return_id'],
                    'pickup_id' => $response['pickup_id'] ?? null,
                    'message' => 'Return pickup scheduled successfully'
                ];
            } else {
                throw new LocalizedException(__('Failed to create return pickup: %1', $response['message'] ?? 'Unknown error'));
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Shiprocket return pickup creation failed: ' . $e->getMessage());
            throw new LocalizedException(__('Return pickup creation failed: %1', $e->getMessage()));
        }
    }

    /**
     * Get return status
     *
     * @param int $returnId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getReturnStatus($returnId)
    {
        try {
            $this->authenticate();
            
            $response = $this->callShiprocketAPI(self::RETURN_STATUS_ENDPOINT . $returnId, [], 'GET');
            
            return [
                'success' => true,
                'status' => $response['status'] ?? 'unknown',
                'tracking_id' => $response['tracking_id'] ?? null,
                'pickup_date' => $response['pickup_date'] ?? null,
                'delivery_date' => $response['delivery_date'] ?? null
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get return status: ' . $e->getMessage());
            throw new LocalizedException(__('Failed to get return status: %1', $e->getMessage()));
        }
    }

    /**
     * Cancel return pickup
     *
     * @param int $returnId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function cancelReturnPickup($returnId)
    {
        try {
            $this->authenticate();
            
            $response = $this->callShiprocketAPI('orders/cancel/return/' . $returnId, [], 'POST');
            
            return [
                'success' => true,
                'message' => 'Return pickup cancelled successfully'
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to cancel return pickup: ' . $e->getMessage());
            throw new LocalizedException(__('Failed to cancel return pickup: %1', $e->getMessage()));
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
     * Prepare return data for Shiprocket API
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param string $reason
     * @return array
     */
    protected function prepareReturnData($order, $reason)
    {
        $shippingAddress = $order->getShippingAddress();
        
        // Prepare order items
        $orderItems = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $orderItems[] = [
                'name' => $item->getName(),
                'sku' => $item->getSku(),
                'units' => (int) $item->getQtyOrdered(),
                'selling_price' => $item->getPrice()
            ];
        }

        return [
            'order_id' => $order->getIncrementId(),
            'order_date' => $order->getCreatedAt(),
            'pickup_customer_name' => $shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname(),
            'pickup_address' => implode(', ', $shippingAddress->getStreet()),
            'pickup_address_2' => '',
            'pickup_city' => $shippingAddress->getCity(),
            'pickup_state' => $shippingAddress->getRegion(),
            'pickup_country' => $shippingAddress->getCountryId(),
            'pickup_pincode' => $shippingAddress->getPostcode(),
            'pickup_email' => $shippingAddress->getEmail(),
            'pickup_phone' => $shippingAddress->getTelephone(),
            'drop_address' => $this->shiprocketHelper->getConfigValue('pickup_address'),
            'drop_address_2' => '',
            'drop_city' => $this->shiprocketHelper->getConfigValue('pickup_city'),
            'drop_state' => $this->shiprocketHelper->getConfigValue('pickup_state'),
            'drop_country' => 'India',
            'drop_pincode' => $this->shiprocketHelper->getPickupPostcode(),
            'drop_phone' => $this->shiprocketHelper->getConfigValue('pickup_phone'),
            'order_items' => $orderItems,
            'payment_method' => 'Prepaid',
            'total_discount' => $order->getDiscountAmount(),
            'sub_total' => $order->getSubtotal(),
            'length' => 10,
            'breadth' => 10, 
            'height' => 10,
            'weight' => 1,
            'return_reason' => $reason
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