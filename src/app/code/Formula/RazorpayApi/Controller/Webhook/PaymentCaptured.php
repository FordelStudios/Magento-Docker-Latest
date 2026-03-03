<?php
namespace Formula\RazorpayApi\Controller\Webhook;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Formula\RazorpayApi\Helper\Data as RazorpayHelper;
use Formula\RazorpayApi\Model\WebhookHandler;
use Psr\Log\LoggerInterface;

/**
 * Receives Razorpay webhook POST for payment.captured events.
 * Verifies HMAC-SHA256 signature and delegates to WebhookHandler.
 *
 * Route: POST /razorpayapi/webhook/paymentcaptured
 */
class PaymentCaptured implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /** @var RequestInterface */
    private $request;

    /** @var JsonFactory */
    private $resultJsonFactory;

    /** @var RazorpayHelper */
    private $helper;

    /** @var WebhookHandler */
    private $webhookHandler;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        RequestInterface $request,
        JsonFactory $resultJsonFactory,
        RazorpayHelper $helper,
        WebhookHandler $webhookHandler,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helper = $helper;
        $this->webhookHandler = $webhookHandler;
        $this->logger = $logger;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            // 1. Check if webhook handling is enabled
            if (!$this->helper->isWebhookEnabled()) {
                $this->logger->info('RazorpayWebhook: Webhook handling is disabled');
                return $result->setHttpResponseCode(200)->setData([
                    'status' => 'skipped',
                    'message' => 'Webhook handling disabled'
                ]);
            }

            // 2. Read raw body for HMAC verification
            $rawBody = file_get_contents('php://input');
            if (empty($rawBody)) {
                $this->logger->warning('RazorpayWebhook: Empty request body');
                return $result->setHttpResponseCode(400)->setData([
                    'status' => 'error',
                    'message' => 'Empty request body'
                ]);
            }

            // 3. Verify HMAC-SHA256 signature
            $signature = $this->request->getHeader('X-Razorpay-Signature');
            $webhookSecret = $this->helper->getWebhookSecret();

            if (empty($webhookSecret)) {
                $this->logger->error('RazorpayWebhook: Webhook secret not configured');
                return $result->setHttpResponseCode(500)->setData([
                    'status' => 'error',
                    'message' => 'Webhook secret not configured'
                ]);
            }

            $expectedSignature = hash_hmac('sha256', $rawBody, $webhookSecret);

            if (!hash_equals($expectedSignature, $signature ?? '')) {
                $this->logger->warning('RazorpayWebhook: Invalid signature', [
                    'received' => substr($signature ?? '', 0, 16) . '...',
                ]);
                return $result->setHttpResponseCode(401)->setData([
                    'status' => 'error',
                    'message' => 'Invalid signature'
                ]);
            }

            // 4. Parse payload
            $payload = json_decode($rawBody, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->warning('RazorpayWebhook: Invalid JSON payload');
                return $result->setHttpResponseCode(400)->setData([
                    'status' => 'error',
                    'message' => 'Invalid JSON'
                ]);
            }

            $event = $payload['event'] ?? '';
            $this->logger->info('RazorpayWebhook: Received event: ' . $event);

            // 5. Only handle payment.captured events
            if ($event !== 'payment.captured') {
                $this->logger->info('RazorpayWebhook: Ignoring event: ' . $event);
                return $result->setHttpResponseCode(200)->setData([
                    'status' => 'ok',
                    'message' => 'Event ignored: ' . $event
                ]);
            }

            // 6. Delegate to handler
            $handlerResult = $this->webhookHandler->handlePaymentCaptured($payload);

            return $result->setHttpResponseCode(200)->setData($handlerResult);

        } catch (\Exception $e) {
            // Always return 200 to Razorpay to prevent retries
            $this->logger->error('RazorpayWebhook: Unhandled exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $result->setHttpResponseCode(200)->setData([
                'status' => 'error',
                'message' => 'Internal error logged'
            ]);
        }
    }

    /**
     * Disable CSRF validation for webhook endpoint (Razorpay cannot send CSRF tokens)
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
