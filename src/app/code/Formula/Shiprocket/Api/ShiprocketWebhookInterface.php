<?php
namespace Formula\Shiprocket\Api;

interface ShiprocketWebhookInterface
{
    /**
     * Handle all Shiprocket status updates (shipments and returns)
     *
     * @param mixed $webhookData Optional - will read from request body if not provided
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function handleStatusUpdate($webhookData = null);
}