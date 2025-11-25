<?php
namespace Formula\Shiprocket\Model;

use Formula\Shiprocket\Api\ShiprocketWebhookInterface;
use Formula\Shiprocket\Model\ShiprocketWebhookHandler;
use Formula\Shiprocket\Model\ShiprocketShipmentWebhookHandler;
use Formula\Shiprocket\Helper\Data as ShiprocketHelper;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class ShiprocketWebhook implements ShiprocketWebhookInterface
{
    protected $returnWebhookHandler;
    protected $shipmentWebhookHandler;
    protected $request;
    protected $logger;
    protected $shiprocketHelper;

    public function __construct(
        ShiprocketWebhookHandler $returnWebhookHandler,
        ShiprocketShipmentWebhookHandler $shipmentWebhookHandler,
        Request $request,
        LoggerInterface $logger,
        ShiprocketHelper $shiprocketHelper
    ) {
        $this->returnWebhookHandler = $returnWebhookHandler;
        $this->shipmentWebhookHandler = $shipmentWebhookHandler;
        $this->request = $request;
        $this->logger = $logger;
        $this->shiprocketHelper = $shiprocketHelper;
    }

    /**
     * Handle all Shiprocket status updates (shipments and returns)
     *
     * @param mixed $webhookData
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function handleStatusUpdate($webhookData = null)
    {
        try {
            // Get webhook data from request body if not provided
            if ($webhookData === null || empty($webhookData)) {
                $webhookData = $this->request->getBodyParams();
            }

            // Convert to array if it's an object
            if (is_object($webhookData)) {
                $webhookData = json_decode(json_encode($webhookData), true);
            }

            // If data is still empty, try reading raw input
            if (empty($webhookData)) {
                $rawInput = file_get_contents('php://input');
                if ($rawInput) {
                    $webhookData = json_decode($rawInput, true);
                }
            }

            $this->logger->info('Shiprocket unified webhook received', ['data' => $webhookData]);

            // Verify API key token
            $apiKey = $this->request->getHeader('x-api-key');
            if (!$this->verifyApiKey($apiKey)) {
                throw new LocalizedException(__('Invalid API key'));
            }

            // Determine if this is a return or shipment update
            if (isset($webhookData['return_id']) || 
                (isset($webhookData['current_status']) && 
                 strpos(strtolower($webhookData['current_status']), 'return') !== false)) {
                // Handle return status update
                $result = $this->returnWebhookHandler->handleReturnWebhook($webhookData);
            } else {
                // Handle shipment status update
                $result = $this->shipmentWebhookHandler->handleShipmentWebhook($webhookData);
            }

            return [
                'success' => $result['success'] ?? true,
                'message' => $result['message'] ?? 'Webhook processed successfully'
            ];

        } catch (\Exception $e) {
            $this->logger->error('Shiprocket unified webhook processing failed', [
                'error' => $e->getMessage(),
                'data' => $webhookData
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify API key token
     *
     * @param string $apiKey
     * @return bool
     */
    protected function verifyApiKey($apiKey)
    {
        $configuredKey = $this->shiprocketHelper->getWebhookSecretKey();

        // If no key is configured, log warning and reject
        if (empty($configuredKey)) {
            $this->logger->warning('Shiprocket webhook secret key not configured in admin. Please configure it under Stores > Configuration > Formula > Shiprocket.');
            return false;
        }

        // If no key provided in request, reject
        if (empty($apiKey)) {
            $this->logger->warning('Shiprocket webhook received without x-api-key header');
            return false;
        }

        // Use hash_equals for timing-safe comparison
        return hash_equals($configuredKey, $apiKey);
    }
}