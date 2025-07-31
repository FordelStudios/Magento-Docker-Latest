<?php
namespace Formula\RazorpayApi\Controller\Index;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class CreateOrder implements HttpPostActionInterface, CsrfAwareActionInterface
{
    protected $request;
    protected $resultJsonFactory;
    protected $curl;
    protected $scopeConfig;
    protected $logger;

    const RAZORPAY_API_URL = 'https://api.razorpay.com/v1/orders';
    const CONFIG_PATH_KEY_ID = 'payment/razorpay/key_id';
    const CONFIG_PATH_KEY_SECRET = 'payment/razorpay/key_secret';

    public function __construct(
        Http $request,
        JsonFactory $resultJsonFactory,
        Curl $curl,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->curl = $curl;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            // Get request data
            $inputData = json_decode($this->request->getContent(), true);
            
            if (!isset($inputData['orderData'])) {
                throw new \InvalidArgumentException('Missing orderData parameter');
            }

            $orderData = $inputData['orderData'];

            // Validate input data
            if (!isset($orderData['amount']) || !isset($orderData['currency']) || !isset($orderData['cartId'])) {
                throw new \InvalidArgumentException('Missing required parameters: amount, currency, or cartId');
            }

            // Get Razorpay credentials
            $keyId = $this->scopeConfig->getValue(self::CONFIG_PATH_KEY_ID, ScopeInterface::SCOPE_STORE);
            $keySecret = $this->scopeConfig->getValue(self::CONFIG_PATH_KEY_SECRET, ScopeInterface::SCOPE_STORE);

            if (!$keyId || !$keySecret) {
                throw new \Exception('Razorpay credentials not configured');
            }

            // Prepare request data for Razorpay API
            $requestData = [
                'amount' => (int)$orderData['amount'],
                'currency' => $orderData['currency'],
                'receipt' => 'receipt_' . $orderData['cartId'],
                'notes' => [
                    'magento_cart_id' => $orderData['cartId']
                ]
            ];

            // Set up cURL request
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->setOption(CURLOPT_TIMEOUT, 30);
            $this->curl->setOption(CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($keyId . ':' . $keySecret)
            ]);

            // Make API call to Razorpay
            $this->curl->post(self::RAZORPAY_API_URL, json_encode($requestData));

            $httpCode = $this->curl->getStatus();
            $responseBody = $this->curl->getBody();

            if ($httpCode !== 200) {
                $errorData = json_decode($responseBody, true);
                $errorMessage = isset($errorData['error']['description']) 
                    ? $errorData['error']['description'] 
                    : 'Unknown error occurred';
                
                throw new \Exception('Razorpay API Error: ' . $errorMessage, $httpCode);
            }

            // Parse successful response
            $razorpayResponse = json_decode($responseBody, true);

            if (!$razorpayResponse || !isset($razorpayResponse['id'])) {
                throw new \Exception('Invalid response from Razorpay API');
            }

            // Build response data manually to preserve object structure
            $responseData = [
                'success' => true,
                'id' => $razorpayResponse['id'],
                'entity' => $razorpayResponse['entity'],
                'amount' => $razorpayResponse['amount'],
                'amount_paid' => $razorpayResponse['amount_paid'],
                'amount_due' => $razorpayResponse['amount_due'],
                'currency' => $razorpayResponse['currency'],
                'receipt' => $razorpayResponse['receipt'],
                'offer_id' => $razorpayResponse['offer_id'],
                'status' => $razorpayResponse['status'],
                'attempts' => $razorpayResponse['attempts'],
                'notes' => $razorpayResponse['notes'], // This will preserve the object structure
                'created_at' => $razorpayResponse['created_at'],
                'message' => 'Razorpay order created successfully'
            ];

            return $result->setData($responseData);

        } catch (\Exception $e) {
            $this->logger->error('Razorpay Order Creation Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $errorResponse = [
                'success' => false,
                'error' => true,
                'message' => $e->getMessage(),
                'error_code' => $e->getCode()
            ];

            return $result->setData($errorResponse);
        }
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}