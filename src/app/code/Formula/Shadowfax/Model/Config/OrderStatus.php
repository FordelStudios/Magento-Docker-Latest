<?php
declare(strict_types=1);

namespace Formula\Shadowfax\Model\Config;

use Magento\Sales\Model\Order;

/**
 * Single source of truth for ShadowFax-related Magento order statuses.
 *
 * These are OUR internal Magento status codes (prefixed `shadowfax_`) and the
 * Magento order state each one is assigned to. Both the data patch that
 * registers these statuses (Setup\Patch\Data\AddShadowfaxOrderStatuses) and
 * the future webhook status mapper (A4) read from this one map so the two
 * never drift apart.
 *
 * ASSUMPTION TO RECONCILE: the actual ShadowFax API status vocabulary
 * (the external status strings ShadowFax sends on their tracking webhook)
 * is not yet confirmed against P1 docs. The codes below are placeholders
 * for our internal Magento statuses only, chosen to mirror the shape of a
 * typical last-mile delivery lifecycle. The external ShadowFax status ->
 * internal status mapping is intentionally deferred to A4, once the real
 * ShadowFax webhook payload / status vocabulary is confirmed.
 */
class OrderStatus
{
    public const SHIPMENT_CREATED = 'shadowfax_shipment_created';
    public const PICKUP_SCHEDULED = 'shadowfax_pickup_scheduled';
    public const PICKED_UP = 'shadowfax_picked_up';
    public const IN_TRANSIT = 'shadowfax_in_transit';
    public const OUT_FOR_DELIVERY = 'shadowfax_out_for_delivery';
    public const DELIVERED = 'shadowfax_delivered';
    public const CANCELLED = 'shadowfax_cancelled';
    public const RTO_INITIATED = 'shadowfax_rto_initiated';
    public const RTO_DELIVERED = 'shadowfax_rto_delivered';

    /**
     * Map of status code => [label, target Magento order state].
     *
     * @return array<string, array{label: string, state: string}>
     */
    public static function getStatusMap(): array
    {
        return [
            self::SHIPMENT_CREATED => [
                'label' => 'ShadowFax Shipment Created',
                'state' => Order::STATE_PROCESSING,
            ],
            self::PICKUP_SCHEDULED => [
                'label' => 'ShadowFax Pickup Scheduled',
                'state' => Order::STATE_PROCESSING,
            ],
            self::PICKED_UP => [
                'label' => 'ShadowFax Picked Up',
                'state' => Order::STATE_PROCESSING,
            ],
            self::IN_TRANSIT => [
                'label' => 'ShadowFax In Transit',
                'state' => Order::STATE_PROCESSING,
            ],
            self::OUT_FOR_DELIVERY => [
                'label' => 'ShadowFax Out for Delivery',
                'state' => Order::STATE_PROCESSING,
            ],
            self::DELIVERED => [
                'label' => 'ShadowFax Delivered',
                'state' => Order::STATE_COMPLETE,
            ],
            self::CANCELLED => [
                'label' => 'ShadowFax Cancelled',
                'state' => Order::STATE_CANCELED,
            ],
            self::RTO_INITIATED => [
                'label' => 'ShadowFax RTO Initiated',
                'state' => Order::STATE_PROCESSING,
            ],
            self::RTO_DELIVERED => [
                'label' => 'ShadowFax RTO Delivered',
                'state' => Order::STATE_CLOSED,
            ],
        ];
    }
}
