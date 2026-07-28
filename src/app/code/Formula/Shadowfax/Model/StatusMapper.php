<?php
declare(strict_types=1);

namespace Formula\Shadowfax\Model;

use Formula\Shadowfax\Model\Config\OrderStatus;

/**
 * Pure mapping: assumed ShadowFax tracking-webhook external status string ->
 * our internal Formula_Shadowfax OrderStatus code (see Model\Config\OrderStatus,
 * the single source of truth for the internal codes/states).
 *
 * ASSUMPTION TO RECONCILE — NOT VERIFIED AGAINST REAL SHADOWFAX DOCS:
 * The real ShadowFax webhook status vocabulary is behind their merchant login;
 * we do not have credentials or their webhook documentation yet. The external
 * status strings below (DELIVERED, OUT_FOR_DELIVERY, IN_TRANSIT, ...) are our
 * best guess based on typical Indian last-mile courier vocabularies (they
 * mirror the shape Shiprocket uses). This class is unit-tested against the
 * ASSUMED vocabulary only — that proves the mapping LOGIC is correct, not
 * that it matches what ShadowFax actually sends on the wire.
 *
 * @todo Reconcile external status strings against real ShadowFax webhook docs
 *       when P1 credentials/documentation land. Add/rename entries as needed —
 *       this table is intentionally the ONLY place that vocabulary is assumed.
 */
class StatusMapper
{
    /**
     * Assumed external ShadowFax status (upper-cased) => internal OrderStatus code.
     * Matching is case-insensitive (see mapExternalStatus()).
     *
     * @var array<string, string>
     */
    private const EXTERNAL_TO_INTERNAL_MAP = [
        'SHIPMENT_CREATED' => OrderStatus::SHIPMENT_CREATED,
        'PICKUP_SCHEDULED' => OrderStatus::PICKUP_SCHEDULED,
        'PICKED_UP' => OrderStatus::PICKED_UP,
        'PICKED' => OrderStatus::PICKED_UP,
        'IN_TRANSIT' => OrderStatus::IN_TRANSIT,
        'OUT_FOR_DELIVERY' => OrderStatus::OUT_FOR_DELIVERY,
        'DELIVERED' => OrderStatus::DELIVERED,
        'CANCELLED' => OrderStatus::CANCELLED,
        'CANCELED' => OrderStatus::CANCELLED,
        'RTO' => OrderStatus::RTO_INITIATED,
        'RTO_INITIATED' => OrderStatus::RTO_INITIATED,
        'RTO_DELIVERED' => OrderStatus::RTO_DELIVERED,
    ];

    /**
     * Map an assumed ShadowFax external status string to our internal
     * OrderStatus code. Case-insensitive; leading/trailing whitespace is
     * trimmed. Returns null for anything not in the assumed vocabulary —
     * callers must treat null as "unknown, do not act" rather than guessing.
     *
     * @param string $externalStatus
     * @return string|null
     */
    public function mapExternalStatus(string $externalStatus): ?string
    {
        $key = strtoupper(trim($externalStatus));
        if ($key === '') {
            return null;
        }

        return self::EXTERNAL_TO_INTERNAL_MAP[$key] ?? null;
    }
}
