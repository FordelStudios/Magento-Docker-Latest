<?php
namespace Formula\OrderCancellationReturn\Service;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class RazorpayRefundService
{
    protected $curl;
    protected $scopeConfig;
    protected $logger;

    public function __construct(
        Curl $curl,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->curl = $curl;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * Process Razorpay refund
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param float $amount
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processRefund($order, $amount)
    {
        try {
            $payment = $order->getPayment();
            $razorpayPaymentId = $payment->getAdditionalInformation('razorpay_payment_id');
            
            if (!$razorpayPaymentId) {
                throw new LocalizedException(__('Razorpay payment ID not found for this order.'));
            }

            $refundData = $this->createRefund($razorpayPaymentId, $amount);
            
            return [
                'success' => true,
                'transaction_id' => $refundData['id'] ?? null,
                'refund_amount' => $amount,
                'refund_method' => 'razorpay'
            ];

        } catch (\Exception $e) {
            $this->logger->error('Razorpay refund failed: ' . $e->getMessage());
            throw new LocalizedException(__('Razorpay refund failed: %1', $e->getMessage()));
        }
    }

    /**
     * Create refund via Razorpay API
     *
     * @param string $paymentId
     * @param float $amount
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function createRefund($paymentId, $amount)
    {
        $keyId = $this->scopeConfig->getValue('payment/razorpay/key_id', ScopeInterface::SCOPE_STORE);
        $keySecret = $this->scopeConfig->getValue('payment/razorpay/key_secret', ScopeInterface::SCOPE_STORE);
        
        if (!$keyId || !$keySecret) {
            throw new LocalizedException(__('Razorpay credentials not configured.'));
        }

        $url = "https://api.razorpay.com/v1/payments/{$paymentId}/refund";
        
        $data = [
            'amount' => (int)($amount * 100), // Convert to paise
            'speed' => 'normal',
            'notes' => [
                'reason' => 'Order cancellation/return refund'
            ]
        ];

        $this->curl->setCredentials($keyId, $keySecret);
        $this->curl->addHeader('Content-Type', 'application/json');
        $this->curl->post($url, json_encode($data));

        $response = $this->curl->getBody();
        $httpCode = $this->curl->getStatus();
        $responseData = json_decode($response, true);

        // Check for specific "already refunded" error first, regardless of HTTP status
        if (isset($responseData['error'])) {
            $errorMessage = $responseData['error']['description'] ?? 'Unknown error';
            $errorCode = $responseData['error']['code'] ?? null;

            // Handle "already refunded" case gracefully
            if ($errorCode === 'BAD_REQUEST_ERROR' && strpos($errorMessage, 'fully refunded already') !== false) {
                $this->logger->info('Razorpay payment already refunded, proceeding with order cancellation');
                return [
                    'id' => 'already_refunded_' . time(),
                    'amount' => (int)($amount * 100),
                    'status' => 'processed',
                    'notes' => ['reason' => 'Payment was already refunded']
                ];
            }

            // For other errors, log and throw exception
            $this->logger->error('Razorpay API error: ' . $response);
            throw new LocalizedException(__('Razorpay refund error: %1', $errorMessage));
        }

        // Check HTTP status for non-JSON errors
        if ($httpCode !== 200) {
            $this->logger->error('Razorpay API error: ' . $response);
            throw new LocalizedException(__('Razorpay refund API error: HTTP %1', $httpCode));
        }

        if (!$responseData) {
            throw new LocalizedException(__('Invalid response from Razorpay API'));
        }

        return $responseData;
    }
}