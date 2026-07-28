<?php
declare(strict_types=1);

namespace Formula\Shadowfax\Model;

use Formula\Shadowfax\Api\ShadowfaxWebhookInterface;
use Formula\Shadowfax\Helper\Data as ShadowfaxHelper;
use Magento\Framework\Webapi\Rest\Request;
use Psr\Log\LoggerInterface;

/**
 * Webapi entrypoint for the inbound ShadowFax tracking-status webhook.
 *
 * Verifies the shared secret configured under Stores > Configuration >
 * Formula > ShadowFax (Helper\Data::getWebhookSecret()) against a header on
 * the incoming request via hash_equals() — same timing-safe-comparison
 * approach Formula_Shiprocket's ShiprocketWebhook uses for its x-api-key
 * header. Rejects (does not delegate to the handler) when the secret is
 * missing/wrong or the module is disabled in admin.
 *
 * ASSUMPTIONS TO RECONCILE — NOT VERIFIED AGAINST REAL SHADOWFAX DOCS:
 * We do not have ShadowFax's webhook documentation (behind their merchant
 * login). The signing header name and the payload field names below are our
 * best guess. Unit tests cover the secret-verification LOGIC against these
 * assumptions; they do not, and cannot, prove a real ShadowFax callback will
 * use this exact shape.
 *
 * @todo Reconcile HEADER_SECRET name + payload field names (FIELD_*) against
 *       real ShadowFax webhook docs when P1 credentials/documentation land.
 */
class ShadowfaxWebhook implements ShadowfaxWebhookInterface
{
    /**
     * Assumed header ShadowFax sends the shared webhook secret on.
     *
     * @todo Reconcile against real ShadowFax webhook docs when P1 lands.
     */
    public const HEADER_SECRET = 'x-shadowfax-secret';

    /**
     * Assumed payload field names.
     *
     * @todo Reconcile against real ShadowFax webhook docs when P1 lands.
     */
    public const FIELD_AWB = 'awb';
    public const FIELD_SHIPMENT_ID = 'shipment_id';
    public const FIELD_STATUS = 'status';

    /**
     * @var ShadowfaxWebhookHandler
     */
    private $webhookHandler;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShadowfaxHelper
     */
    private $shadowfaxHelper;

    /**
     * @param ShadowfaxWebhookHandler $webhookHandler
     * @param Request $request
     * @param LoggerInterface $logger
     * @param ShadowfaxHelper $shadowfaxHelper
     */
    public function __construct(
        ShadowfaxWebhookHandler $webhookHandler,
        Request $request,
        LoggerInterface $logger,
        ShadowfaxHelper $shadowfaxHelper
    ) {
        $this->webhookHandler = $webhookHandler;
        $this->request = $request;
        $this->logger = $logger;
        $this->shadowfaxHelper = $shadowfaxHelper;
    }

    /**
     * @param mixed $webhookData
     * @return array{success: bool, message: string}
     */
    public function handleStatusUpdate($webhookData = null)
    {
        if (!$this->shadowfaxHelper->isEnabled()) {
            $this->logger->warning('Shadowfax webhook received while integration is disabled in admin config');
            return ['success' => false, 'message' => 'ShadowFax integration is disabled'];
        }

        // getHeader() returns false (not null) when the header is absent —
        // Magento\Framework\HTTP\PhpEnvironment\Request::getHeader() signature.
        $incomingSecretHeader = $this->request->getHeader(self::HEADER_SECRET);
        $incomingSecret = $incomingSecretHeader !== false ? (string) $incomingSecretHeader : null;

        if (!$this->verifySecret($incomingSecret)) {
            $this->logger->warning('Shadowfax webhook rejected: missing or invalid webhook secret');
            return ['success' => false, 'message' => 'Invalid webhook secret'];
        }

        $webhookData = $this->resolvePayload($webhookData);

        if ($this->shadowfaxHelper->isDebugMode()) {
            $this->logger->info('Shadowfax webhook received', ['data' => $webhookData]);
        }

        try {
            $awb = $this->extractField($webhookData, self::FIELD_AWB);
            $shipmentId = $this->extractField($webhookData, self::FIELD_SHIPMENT_ID);
            $status = $this->extractField($webhookData, self::FIELD_STATUS);

            $result = $this->webhookHandler->handleTrackingUpdate($awb, $shipmentId, $status);

            return [
                'success' => $result['success'] ?? true,
                'message' => $result['message'] ?? 'Webhook processed',
            ];
        } catch (\Exception $e) {
            // Never let an exception here bubble into a 500 back to ShadowFax:
            // that risks retry-storms on their side. Log it loudly instead so
            // it is visible, but ack the call.
            $this->logger->error('Shadowfax webhook processing failed', [
                'error' => $e->getMessage(),
                'data' => $webhookData,
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Timing-safe comparison of the configured webhook secret against the
     * incoming header value. Rejects when either side is empty so an
     * unconfigured secret never accidentally accepts an unsigned request.
     *
     * @param string|null $incomingSecret
     * @return bool
     */
    private function verifySecret(?string $incomingSecret): bool
    {
        $configuredSecret = $this->shadowfaxHelper->getWebhookSecret();

        if (empty($configuredSecret) || empty($incomingSecret)) {
            return false;
        }

        return hash_equals($configuredSecret, $incomingSecret);
    }

    /**
     * @param mixed $webhookData
     * @return array
     */
    private function resolvePayload($webhookData): array
    {
        if ($webhookData === null || $webhookData === '' || (is_array($webhookData) && empty($webhookData))) {
            $webhookData = $this->request->getBodyParams();
        }

        if (is_object($webhookData)) {
            $webhookData = json_decode((string) json_encode($webhookData), true);
        }

        if (empty($webhookData)) {
            $rawInput = file_get_contents('php://input');
            if ($rawInput) {
                $webhookData = json_decode($rawInput, true);
            }
        }

        return is_array($webhookData) ? $webhookData : [];
    }

    /**
     * @param array $webhookData
     * @param string $field
     * @return string|null
     */
    private function extractField(array $webhookData, string $field): ?string
    {
        if (!isset($webhookData[$field]) || $webhookData[$field] === '') {
            return null;
        }

        return (string) $webhookData[$field];
    }
}
