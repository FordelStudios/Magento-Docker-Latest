<?php
namespace Formula\Shiprocket\Model;

use Formula\Shiprocket\Api\ShiprocketWebhookInterface;
use Formula\Shiprocket\Model\ShiprocketWebhookHandler;
use Formula\Shiprocket\Model\ShiprocketShipmentWebhookHandler;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class ShiprocketWebhook implements ShiprocketWebhookInterface
{
    protected $returnWebhookHandler;
    protected $shipmentWebhookHandler;
    protected $request;
    protected $logger;

    public function __construct(
        ShiprocketWebhookHandler $returnWebhookHandler,
        ShiprocketShipmentWebhookHandler $shipmentWebhookHandler,
        Request $request,
        LoggerInterface $logger
    ) {
        $this->returnWebhookHandler = $returnWebhookHandler;
        $this->shipmentWebhookHandler = $shipmentWebhookHandler;
        $this->request = $request;
        $this->logger = $logger;
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
            if ($webhookData === null) {
                $webhookData = $this->request->getBodyParams();
            }

            // Convert to array if it's an object
            if (is_object($webhookData)) {
                $webhookData = json_decode(json_encode($webhookData), true);
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
        // TODO: Get configured API key from Shiprocket config
        // For now, just check if key exists
        return !empty($apiKey);
    }
}