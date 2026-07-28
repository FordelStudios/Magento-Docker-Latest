<?php
declare(strict_types=1);

namespace Formula\DeliveryPartner\Model;

use Formula\DeliveryPartner\Api\Data\DeliveryShipmentResultInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Persists a neutral DeliveryShipmentResult onto an order, partner-agnostically.
 *
 * Keeps the shipment-creation call sites DRY and free of per-partner identifier-column
 * knowledge. The CALLER remains responsible for saving the order — this only stages the
 * data on the in-memory order object.
 *
 * Ownership boundary:
 *  - `delivery_partner` is NOT written here. It is stamped as INTENT at attempt/placement
 *    time by each call site (so it persists even when a shipment attempt fails and is
 *    immune to a later global config flip). This class must not double-own it.
 *  - SHADOWFAX: writes the three `shadowfax_*` identifier columns. There is no legacy
 *    ShadowFax persistence path — this IS it.
 *  - SHIPROCKET: writes nothing — the existing Shiprocket code paths persist their own
 *    `shiprocket_*` columns; double-writing them here would risk the two drifting apart.
 *
 * The `shadowfax_*` column names are referenced as string literals rather than via a
 * Formula\Shadowfax class so that Formula_DeliveryPartner carries NO dependency on
 * Formula_Shadowfax (the neutral module must never know its concrete couriers).
 */
class ShipmentResultPersister
{
    /**
     * Stage the shipment result's identifier columns onto the order. Caller must persist it.
     *
     * @param OrderInterface $order
     * @param DeliveryShipmentResultInterface $result
     * @return void
     */
    public function apply(OrderInterface $order, DeliveryShipmentResultInterface $result): void
    {
        if ($result->getPartnerCode() === PartnerCode::SHADOWFAX) {
            $order->setData('shadowfax_shipment_id', $result->getShipmentId());
            $order->setData('shadowfax_awb', $result->getAwb());
            $order->setData('shadowfax_courier_name', $result->getCourierName());
        }
    }
}
