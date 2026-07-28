<?php
declare(strict_types=1);

namespace Formula\DeliveryPartner\Api;

use Formula\DeliveryPartner\Api\Data\DeliveryShipmentResultInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Neutral contract every courier adapter (Shiprocket, ShadowFax, ...) implements.
 *
 * Order/payment call sites depend ONLY on this interface — never on a specific
 * courier module — so couriers can be swapped or added without touching
 * business logic. Shiprocket and ShadowFax must NOT depend on each other;
 * both depend only on Formula_DeliveryPartner.
 */
interface DeliveryPartnerInterface
{
    /**
     * Stable identifier for this partner, e.g. PartnerCode::SHIPROCKET.
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * @param OrderInterface $order
     * @return DeliveryShipmentResultInterface
     */
    public function createShipment(OrderInterface $order): DeliveryShipmentResultInterface;
}
