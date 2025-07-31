<?php
namespace Formula\Shiprocket\Model;

use Formula\Shiprocket\Api\ServiceabilityInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Psr\Log\LoggerInterface;

class Serviceability implements ServiceabilityInterface
{
    /**
     * Shiprocket API credentials and configuration
     */
    const SHIPROCKET_LOGIN_URL = 'https://apiv2.shiprocket.in/v1/external/auth/login';
    const SHIPROCKET_SERVICEABILITY_URL = 'https://apiv2.shiprocket.in/v1/external/courier/serviceability/';
    const SHIPROCKET_EMAIL = 'guptamayankita.fs@gmail.com';
    const SHIPROCKET_PASSWORD = 'Ktqh8zHaVby$fR2p';
    const PICKUP_POSTCODE = '700001';

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
     * @var string
     */
    private $authToken;

    /**
     * Constructor
     *
     * @param Curl $curl
     * @param JsonHelper $jsonHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        Curl $curl,
        JsonHelper $jsonHelper,
        LoggerInterface $logger
    ) {
        $this->curl = $curl;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
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
            $this->curl->setHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]);

            $loginData = [
                'email' => self::SHIPROCKET_EMAIL,
                'password' => self::SHIPROCKET_PASSWORD
            ];

            $this->curl->post(self::SHIPROCKET_LOGIN_URL, $this->jsonHelper->jsonEncode($loginData));
            
            $response = $this->curl->getBody();
            $httpCode = $this->curl->getStatus();

            if ($httpCode !== 200) {
                $this->logger->error('Shiprocket Authentication Failed. HTTP Code: ' . $httpCode);
                return false;
            }

            $responseData = $this->jsonHelper->jsonDecode($response);
            
            if (isset($responseData['token'])) {
                $this->authToken = $responseData['token'];
                return $this->authToken;
            }

            return false;

        } catch (\Exception $e) {
            $this->logger->error('Authentication Exception: ' . $e->getMessage());
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
            $this->curl->setHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]);

            $codValue = $cod ? '1' : '0';
            
            $url = self::SHIPROCKET_SERVICEABILITY_URL . '?' . http_build_query([
                'pickup_postcode' => self::PICKUP_POSTCODE,
                'delivery_postcode' => $pincode,
                'weight' => $weight,
                'cod' => $codValue
            ]);

            $this->curl->get($url);
            
            $response = $this->curl->getBody();
            $httpCode = $this->curl->getStatus();

            if ($httpCode !== 200) {
                $this->logger->error('Shiprocket Serviceability API Failed. HTTP Code: ' . $httpCode);
                return $this->createErrorResponse('Serviceability API failed');
            }

            $responseData = $this->jsonHelper->jsonDecode($response);
            
            return [
                'success' => true,
                'data' => $responseData
            ];

        } catch (\Exception $e) {
            $this->logger->error('Serviceability Exception: ' . $e->getMessage());
            return $this->createErrorResponse('Serviceability check failed: ' . $e->getMessage());
        }
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