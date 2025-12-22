<?php
declare(strict_types=1);

namespace Formula\Wati\Model;

use Formula\Wati\Api\WatiWebhookInterface;
use Formula\Wati\Service\WatiApiService;
use Formula\Wati\Helper\Data as WatiHelper;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Wati webhook handler for delivery status callbacks
 */
class WatiWebhook implements WatiWebhookInterface
{
    /**
     * @var WatiApiService
     */
    protected $watiApiService;

    /**
     * @var WatiHelper
     */
    protected $watiHelper;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param WatiApiService $watiApiService
     * @param WatiHelper $watiHelper
     * @param Request $request
     * @param LoggerInterface $logger
     */
    public function __construct(
        WatiApiService $watiApiService,
        WatiHelper $watiHelper,
        Request $request,
        LoggerInterface $logger
    ) {
        $this->watiApiService = $watiApiService;
        $this->watiHelper = $watiHelper;
        $this->request = $request;
        $this->logger = $logger;
    }

    /**
     * Handle delivery status webhook from Wati
     *
     * @param mixed $webhookData
     * @return array
     */
    public function handleDeliveryStatus($webhookData = null): array
    {
        try {
            // Get webhook data from various sources
            if ($webhookData === null || empty($webhookData)) {
                $webhookData = $this->request->getBodyParams();
            }

            // Convert object to array if needed
            if (is_object($webhookData)) {
                $webhookData = json_decode(json_encode($webhookData), true);
            }

            // Try raw input if still empty
            if (empty($webhookData)) {
                $rawInput = file_get_contents('php://input');
                if ($rawInput) {
                    $webhookData = json_decode($rawInput, true);
                }
            }

            $this->logger->info('Wati webhook received', [
                'data' => $webhookData
            ]);

            // Verify webhook authenticity
            if (!$this->verifyWebhookSecret()) {
                $this->logger->warning('Wati webhook: Invalid secret key');
                return [
                    'success' => false,
                    'message' => 'Invalid webhook secret'
                ];
            }

            if (empty($webhookData)) {
                return [
                    'success' => false,
                    'message' => 'Empty webhook data'
                ];
            }

            // Extract message ID and status from webhook
            // Wati webhook format may vary, handle common structures
            $messageId = $this->extractMessageId($webhookData);
            $status = $this->extractStatus($webhookData);

            if (!$messageId) {
                $this->logger->warning('Wati webhook: Missing message ID', ['data' => $webhookData]);
                return [
                    'success' => false,
                    'message' => 'Missing message ID'
                ];
            }

            // Map Wati status to our internal status
            $deliveryStatus = $this->mapWatiStatus($status);

            // Update message log
            $updated = $this->watiApiService->updateDeliveryStatus($messageId, $deliveryStatus, $webhookData);

            return [
                'success' => $updated,
                'message' => $updated ? 'Status updated successfully' : 'Message not found or update failed'
            ];

        } catch (\Exception $e) {
            $this->logger->error('Wati webhook processing failed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Webhook processing failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify webhook secret key
     *
     * @return bool
     */
    protected function verifyWebhookSecret(): bool
    {
        $configuredSecret = $this->watiHelper->getWebhookSecret();

        // If no secret is configured, allow all webhooks (for testing)
        if (empty($configuredSecret)) {
            $this->logger->debug('Wati webhook: No secret configured, allowing webhook');
            return true;
        }

        // Check various header formats that Wati might use
        $providedSecret = $this->request->getHeader('x-api-key')
            ?? $this->request->getHeader('X-Api-Key')
            ?? $this->request->getHeader('authorization')
            ?? $this->request->getHeader('x-webhook-secret')
            ?? null;

        if (empty($providedSecret)) {
            $this->logger->warning('Wati webhook: No secret provided in request headers');
            return false;
        }

        // Use timing-safe comparison
        return hash_equals($configuredSecret, $providedSecret);
    }

    /**
     * Extract message ID from webhook data
     *
     * @param array $webhookData
     * @return string|null
     */
    protected function extractMessageId(array $webhookData): ?string
    {
        // Try various possible field names
        $possibleFields = ['messageId', 'message_id', 'id', 'msgId', 'whatsappMessageId'];

        foreach ($possibleFields as $field) {
            if (!empty($webhookData[$field])) {
                return (string) $webhookData[$field];
            }
        }

        // Check nested structures
        if (!empty($webhookData['message']['id'])) {
            return (string) $webhookData['message']['id'];
        }

        if (!empty($webhookData['data']['messageId'])) {
            return (string) $webhookData['data']['messageId'];
        }

        return null;
    }

    /**
     * Extract status from webhook data
     *
     * @param array $webhookData
     * @return string|null
     */
    protected function extractStatus(array $webhookData): ?string
    {
        // Try various possible field names
        $possibleFields = ['status', 'eventType', 'event', 'messageStatus', 'deliveryStatus'];

        foreach ($possibleFields as $field) {
            if (!empty($webhookData[$field])) {
                return (string) $webhookData[$field];
            }
        }

        // Check nested structures
        if (!empty($webhookData['message']['status'])) {
            return (string) $webhookData['message']['status'];
        }

        if (!empty($webhookData['data']['status'])) {
            return (string) $webhookData['data']['status'];
        }

        return null;
    }

    /**
     * Map Wati status to internal delivery status
     *
     * @param string|null $watiStatus
     * @return string
     */
    protected function mapWatiStatus(?string $watiStatus): string
    {
        if (!$watiStatus) {
            return 'unknown';
        }

        $statusMap = [
            'sent' => 'sent',
            'submitted' => 'sent',
            'delivered' => 'delivered',
            'read' => 'read',
            'seen' => 'read',
            'failed' => 'failed',
            'error' => 'failed',
            'undelivered' => 'failed',
            'rejected' => 'failed',
        ];

        $normalizedStatus = strtolower(trim($watiStatus));

        return $statusMap[$normalizedStatus] ?? 'unknown';
    }
}
