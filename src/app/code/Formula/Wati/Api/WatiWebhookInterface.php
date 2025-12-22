<?php
declare(strict_types=1);

namespace Formula\Wati\Api;

/**
 * Interface for Wati webhook handling
 */
interface WatiWebhookInterface
{
    /**
     * Handle delivery status webhook from Wati
     *
     * @param mixed $webhookData
     * @return array
     */
    public function handleDeliveryStatus($webhookData = null);
}
