<?php
declare(strict_types=1);

namespace Formula\Wati\Service;

use Formula\Wati\Helper\Data as WatiHelper;
use Formula\Wati\Api\Data\MessageLogInterfaceFactory;
use Formula\Wati\Api\MessageLogRepositoryInterface;
use Formula\Wati\Model\TemplateVariables;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;

/**
 * Wati API Service for sending WhatsApp messages
 */
class WatiApiService
{
    const SEND_TEMPLATE_ENDPOINT = '/api/v2/sendTemplateMessage';

    /**
     * @var WatiHelper
     */
    protected $watiHelper;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var MessageLogInterfaceFactory
     */
    protected $messageLogFactory;

    /**
     * @var MessageLogRepositoryInterface
     */
    protected $messageLogRepository;

    /**
     * @var TemplateVariables
     */
    protected $templateVariables;

    /**
     * @param WatiHelper $watiHelper
     * @param Curl $curl
     * @param LoggerInterface $logger
     * @param MessageLogInterfaceFactory $messageLogFactory
     * @param MessageLogRepositoryInterface $messageLogRepository
     * @param TemplateVariables $templateVariables
     */
    public function __construct(
        WatiHelper $watiHelper,
        Curl $curl,
        LoggerInterface $logger,
        MessageLogInterfaceFactory $messageLogFactory,
        MessageLogRepositoryInterface $messageLogRepository,
        TemplateVariables $templateVariables
    ) {
        $this->watiHelper = $watiHelper;
        $this->curl = $curl;
        $this->logger = $logger;
        $this->messageLogFactory = $messageLogFactory;
        $this->messageLogRepository = $messageLogRepository;
        $this->templateVariables = $templateVariables;
    }

    /**
     * Send order status notification via WhatsApp
     *
     * @param OrderInterface $order
     * @param string $status
     * @return array
     */
    public function sendOrderStatusNotification(OrderInterface $order, string $status): array
    {
        if (!$this->watiHelper->isEnabled()) {
            return ['success' => false, 'error' => 'Wati integration is disabled'];
        }

        $templateName = $this->watiHelper->getTemplateForStatus($status);
        if (!$templateName) {
            $this->logger->info('Wati: No template configured for status: ' . $status);
            return ['success' => false, 'error' => 'No template for status: ' . $status];
        }

        // Get phone number from shipping address, fallback to billing
        $shippingAddress = $order->getShippingAddress();
        if (!$shippingAddress) {
            $shippingAddress = $order->getBillingAddress();
        }

        if (!$shippingAddress || !$shippingAddress->getTelephone()) {
            $this->logger->warning('Wati: No phone number available for order ' . $order->getIncrementId());
            return ['success' => false, 'error' => 'No phone number available'];
        }

        $phoneNumber = $this->watiHelper->formatPhoneForWhatsApp($shippingAddress->getTelephone());

        // Build template parameters using TemplateVariables model
        $variables = $this->templateVariables->extractVariablesFromOrder($order, $status);
        $parameters = $this->templateVariables->toWatiParameters($variables);

        // Create message log entry
        $messageLog = $this->messageLogFactory->create();
        $messageLog->setOrderId($order->getEntityId());
        $messageLog->setOrderIncrementId($order->getIncrementId());
        $messageLog->setPhoneNumber($phoneNumber);
        $messageLog->setTemplateName($templateName);
        $messageLog->setOrderStatus($status);

        try {
            $result = $this->callWatiApi($phoneNumber, $templateName, $parameters);

            $messageLog->setRequestPayload(json_encode([
                'phone' => $phoneNumber,
                'template' => $templateName,
                'parameters' => $parameters,
                'variables' => $variables // Store readable variables for debugging
            ]));
            $messageLog->setResponsePayload(json_encode($result));

            if ($result['success']) {
                $messageLog->setMessageId($result['message_id'] ?? null);
                $messageLog->setDeliveryStatus('sent');
                $this->logger->info('Wati: Message sent for order ' . $order->getIncrementId(), [
                    'message_id' => $result['message_id'] ?? 'N/A',
                    'phone' => $phoneNumber,
                    'template' => $templateName,
                    'variables_sent' => array_keys($variables)
                ]);
            } else {
                $messageLog->setDeliveryStatus('failed');
                $messageLog->setErrorMessage($result['error'] ?? 'Unknown error');
                $this->logger->error('Wati: Failed to send message for order ' . $order->getIncrementId(), [
                    'error' => $result['error'] ?? 'Unknown',
                    'phone' => $phoneNumber
                ]);
            }

            $this->messageLogRepository->save($messageLog);
            return $result;

        } catch (\Exception $e) {
            $messageLog->setDeliveryStatus('failed');
            $messageLog->setErrorMessage($e->getMessage());

            try {
                $this->messageLogRepository->save($messageLog);
            } catch (\Exception $saveException) {
                $this->logger->error('Wati: Failed to save message log: ' . $saveException->getMessage());
            }

            $this->logger->error('Wati: Exception for order ' . $order->getIncrementId(), [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get all available template variables
     *
     * @return array
     */
    public function getAvailableVariables(): array
    {
        return $this->templateVariables->getVariablesByCategory();
    }

    /**
     * Call Wati API to send template message
     *
     * @param string $phoneNumber
     * @param string $templateName
     * @param array $parameters
     * @return array
     * @throws LocalizedException
     */
    protected function callWatiApi(string $phoneNumber, string $templateName, array $parameters): array
    {
        $endpoint = $this->watiHelper->getApiEndpoint();
        $token = $this->watiHelper->getApiToken();

        if (!$endpoint || !$token) {
            throw new LocalizedException(__('Wati API configuration is incomplete'));
        }

        $url = $endpoint . self::SEND_TEMPLATE_ENDPOINT . '?whatsappNumber=' . $phoneNumber;

        $payload = [
            'template_name' => $templateName,
            'broadcast_name' => 'order_notification_' . time(),
            'parameters' => $parameters
        ];

        // Reset curl for new request
        $this->curl = new Curl();
        $this->curl->addHeader('Content-Type', 'application/json-patch+json');
        // Ensure Bearer prefix is present
        if (stripos($token, 'Bearer ') !== 0) {
            $token = 'Bearer ' . $token;
        }
        $this->curl->addHeader('Authorization', $token);
        $this->curl->setOption(CURLOPT_TIMEOUT, 30);

        if ($this->watiHelper->isDebugMode()) {
            $this->logger->debug('Wati API Request', [
                'url' => $url,
                'payload' => $payload
            ]);
        }

        $this->curl->post($url, json_encode($payload));

        $response = $this->curl->getBody();
        $httpCode = $this->curl->getStatus();

        if ($this->watiHelper->isDebugMode()) {
            $this->logger->debug('Wati API Response', [
                'http_code' => $httpCode,
                'response' => $response
            ]);
        }

        $responseData = json_decode($response, true);

        // Check for successful response
        if ($httpCode >= 200 && $httpCode < 300) {
            // Wati API returns result: true on success
            if (isset($responseData['result']) && $responseData['result'] === true) {
                return [
                    'success' => true,
                    'message_id' => $responseData['messageId'] ?? $responseData['info'] ?? null,
                    'response' => $responseData
                ];
            }
        }

        // Handle error response
        $errorMessage = $responseData['message'] ?? $responseData['error'] ?? $responseData['info'] ?? 'HTTP ' . $httpCode;

        return [
            'success' => false,
            'error' => $errorMessage,
            'http_code' => $httpCode,
            'response' => $responseData
        ];
    }

    /**
     * Update message delivery status from webhook
     *
     * @param string $messageId
     * @param string $status
     * @param array $webhookData
     * @return bool
     */
    public function updateDeliveryStatus(string $messageId, string $status, array $webhookData = []): bool
    {
        try {
            $messageLog = $this->messageLogRepository->getByMessageId($messageId);
            if ($messageLog) {
                $messageLog->setDeliveryStatus($status);
                if (in_array($status, ['delivered', 'read'])) {
                    $messageLog->setDeliveredAt(date('Y-m-d H:i:s'));
                }
                $this->messageLogRepository->save($messageLog);

                $this->logger->info('Wati: Delivery status updated', [
                    'message_id' => $messageId,
                    'status' => $status
                ]);

                return true;
            }

            $this->logger->warning('Wati: Message log not found for message ID: ' . $messageId);

        } catch (\Exception $e) {
            $this->logger->error('Wati: Failed to update delivery status', [
                'message_id' => $messageId,
                'error' => $e->getMessage()
            ]);
        }

        return false;
    }

    /**
     * Test API configuration
     *
     * @return array
     */
    public function testConfiguration(): array
    {
        if (!$this->watiHelper->isEnabled()) {
            return ['success' => false, 'error' => 'Wati integration is disabled'];
        }

        $endpoint = $this->watiHelper->getApiEndpoint();
        $token = $this->watiHelper->getApiToken();

        if (empty($endpoint)) {
            return ['success' => false, 'error' => 'API endpoint not configured'];
        }

        if (empty($token)) {
            return ['success' => false, 'error' => 'API token not configured'];
        }

        return [
            'success' => true,
            'endpoint' => $endpoint,
            'token_configured' => true,
            'debug_mode' => $this->watiHelper->isDebugMode()
        ];
    }
}
