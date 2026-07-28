<?php
declare(strict_types=1);

namespace Formula\Shadowfax\Api;

/**
 * Inbound ShadowFax tracking-status webhook entrypoint.
 *
 * ASSUMPTION TO RECONCILE: the real ShadowFax webhook contract (payload shape,
 * signing header) is behind their merchant login and not yet confirmed. See
 * Model\ShadowfaxWebhook for the specific assumed header/field names.
 *
 * @todo Reconcile against real ShadowFax webhook docs when P1 lands.
 */
interface ShadowfaxWebhookInterface
{
    /**
     * Handle an inbound ShadowFax tracking status-update webhook call.
     *
     * @param mixed $webhookData Optional - read from request body if not provided
     * @return array
     */
    public function handleStatusUpdate($webhookData = null);
}
