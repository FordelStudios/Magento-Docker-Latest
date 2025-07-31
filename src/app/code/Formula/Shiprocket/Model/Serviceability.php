<?php
namespace Formula\Shiprocket\Model;

use Formula\Shiprocket\Api\ServiceabilityInterface;
use Formula\Shiprocket\Helper\Data as ShiprocketHelper;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Psr\Log\LoggerInterface;

class Serviceability implements ServiceabilityInterface
{
    /**
     * Shiprocket API URLs
     */
    const SHIPROCKET_LOGIN_URL = 'https://apiv2.shiprocket.in/v1/external/auth/login';
    const SHIPROCKET_SERVICEABILITY_URL = 'https://apiv2.shiprocket.in/v1/external/courier/serviceability/';

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var JsonHelper
     */
    private $jsonHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShiprocketHelper
     */
    private $shiprocketHelper;

    /**
     * @var string
     */
    private $authToken;

    /**
     * Constructor
     *
     * @param Curl $curl
     * @param JsonHelper $jsonHelper
     * @param LoggerInterface $logger
     * @param ShiprocketHelper $shiprocketHelper
     */
    public function __construct(
        Curl $curl,
        JsonHelper $jsonHelper,
        LoggerInterface $logger,
        ShiprocketHelper $shiprocketHelper
    ) {
        $this->curl = $curl;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
        $this->shiprocketHelper = $shiprocketHelper;
    }

    /**
     * Check courier serviceability
     *
     * @param string $pincode
     * @param bool $cod
     * @param float $weight
     * @return mixed
     */
    public function checkServiceability($pincode, $cod, $weight)
    {
        try {
            // Check if module is enabled
            if (!$this->shiprocketHelper->isEnabled()) {
                return $this->createErrorResponse('Shiprocket integration is disabled');
            }

            // Validate configuration
            if (!$this->validateConfiguration()) {
                return $this->createErrorResponse('Invalid configuration. Please check admin settings.');
            }

            // Step 1: Authenticate and get token
            $token = $this->authenticate();
            
            if (!$token) {
                return $this->createErrorResponse('Authentication failed');
            }

            // Step 2: Call serviceability API
            $serviceabilityData = $this->getServiceability($token, $pincode, $cod, $weight);
            
            return $serviceabilityData;

        } catch (\Exception $e) {
            $this->logger->error('Shiprocket API Error: ' . $e->getMessage());
            return $this->createErrorResponse('API call failed: ' . $e->getMessage());
        }
    }

    /**
     * Authenticate with Shiprocket API
     *
     * @return string|false
     */
    private function authenticate()
    {
        try {
            if ($this->shiprocketHelper->isDebugMode()) {
                $this->logger->info('Shiprocket: Starting authentication process');
            }

            $this->curl->setHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]);

            $loginData = [
                'email' => $this->shiprocketHelper->getEmail(),
                'password' => $this->shiprocketHelper->getPassword()
            ];

            if ($this->shiprocketHelper->isDebugMode()) {
                $this->logger->info('Shiprocket: Authentication request data', ['email' => $loginData['email'], 'password' => $loginData['password']]);
            }

            $this->curl->post(self::SHIPROCKET_LOGIN_URL, $this->jsonHelper->jsonEncode($loginData));
            
            $response = $this->curl->getBody();
            $httpCode = $this->curl->getStatus();

            if ($this->shiprocketHelper->isDebugMode()) {
                $this->logger->info('Shiprocket: Authentication response', ['http_code' => $httpCode, 'response' => $response]);
            }

            if ($httpCode !== 200) {
                $this->logger->error('Shiprocket Authentication Failed. HTTP Code: ' . $httpCode . ', Response: ' . $response);
                return false;
            }

            $responseData = $this->jsonHelper->jsonDecode($response);
            
            if (isset($responseData['token'])) {
                $this->authToken = $responseData['token'];
                if ($this->shiprocketHelper->isDebugMode()) {
                    $this->logger->info('Shiprocket: Authentication successful');
                }
                return $this->authToken;
            }

            $this->logger->error('Shiprocket Authentication Failed: No token in response', ['response' => $responseData]);
            return false;

        } catch (\Exception $e) {
            $this->logger->error('Shiprocket Authentication Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get serviceability data from Shiprocket
     *
     * @param string $token
     * @param string $pincode
     * @param bool $cod
     * @param float $weight
     * @return array
     */
    private function getServiceability($token, $pincode, $cod, $weight)
    {
        try {
            if ($this->shiprocketHelper->isDebugMode()) {
                $this->logger->info('Shiprocket: Starting serviceability check', [
                    'pincode' => $pincode,
                    'cod' => $cod,
                    'weight' => $weight
                ]);
            }

            $this->curl->setHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]);

            $codValue = $cod ? '1' : '0';
            
            $url = self::SHIPROCKET_SERVICEABILITY_URL . '?' . http_build_query([
                'pickup_postcode' => $this->shiprocketHelper->getPickupPostcode(),
                'delivery_postcode' => $pincode,
                'weight' => $weight,
                'cod' => $codValue
            ]);

            if ($this->shiprocketHelper->isDebugMode()) {
                $this->logger->info('Shiprocket: Serviceability API URL', ['url' => $url]);
            }

            $this->curl->get($url);
            
            $response = $this->curl->getBody();
            $httpCode = $this->curl->getStatus();

            if ($this->shiprocketHelper->isDebugMode()) {
                $this->logger->info('Shiprocket: Serviceability response', ['http_code' => $httpCode, 'response' => $response]);
            }

            if ($httpCode !== 200) {
                $this->logger->error('Shiprocket Serviceability API Failed. HTTP Code: ' . $httpCode . ', Response: ' . $response);
                return $this->createErrorResponse('Serviceability API failed');
            }

            $responseData = $this->jsonHelper->jsonDecode($response);
            
            if ($this->shiprocketHelper->isDebugMode()) {
                $this->logger->info('Shiprocket: Serviceability check completed successfully');
            }
            
            return [
                'success' => true,
                'data' => $responseData
            ];

        } catch (\Exception $e) {
            $this->logger->error('Shiprocket Serviceability Exception: ' . $e->getMessage());
            return $this->createErrorResponse('Serviceability check failed: ' . $e->getMessage());
        }
    }

    /**
     * Validate configuration
     *
     * @return bool
     */
    private function validateConfiguration()
    {
        $email = $this->shiprocketHelper->getEmail();
        $password = $this->shiprocketHelper->getPassword();
        $pickupPostcode = $this->shiprocketHelper->getPickupPostcode();

        if (empty($email) || empty($password) || empty($pickupPostcode)) {
            $this->logger->error('Shiprocket configuration validation failed: Missing required fields');
            return false;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->logger->error('Shiprocket configuration validation failed: Invalid email format');
            return false;
        }

        return true;
    }

    /**
     * Create error response
     *
     * @param string $message
     * @return array
     */
    private function createErrorResponse($message)
    {
        return [
            'success' => false,
            'error' => $message
        ];
    }
}