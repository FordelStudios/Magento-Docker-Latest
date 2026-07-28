<?php
declare(strict_types=1);

namespace Formula\DeliveryPartner\Model;

use Formula\DeliveryPartner\Api\Data\DeliveryShipmentResultInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Persists a neutral DeliveryShipmentResult onto an order, partner-agnostically.
 *
 * Keeps the three shipment-creation call sites (Shiprocket place_after observer,
 * Shiprocket backfill cron, RazorpayApi OrderManagement) DRY and free of
 * per-partner column knowledge. The CALLER remains responsible for saving the
 * order — this only stages the data on the in-memory order object.
 *
 * Design note — asymmetric ON PURPOSE:
 *  - SHIPROCKET: writes ONLY `delivery_partner`. Every existing Shiprocket code
 *    path already persists its own `shiprocket_*` identifier columns; writing
 *    them again here would duplicate that logic and risk the two drifting apart.
 *    So for Shiprocket this class contributes exactly the one new column the
 *    routing work requires and touches nothing else — the Shiprocket branch stays
 *    behaviourally identical.
 *  - SHADOWFAX: writes `delivery_partner` + the `shadowfax_*` identifier columns.
 *    There is no legacy ShadowFax persistence path — this IS it.
 *
 * The `shadowfax_*` column names are referenced as string literals rather than via
 * a Formula\Shadowfax class so that Formula_DeliveryPartner carries NO dependency
 * on Formula_Shadowfax (the neutral module must never know its concrete couriers).
 */
class ShipmentResultPersister
{
    /**
     * Stage the shipment result onto the order. Caller must persist the order.
     *
     * @param OrderInterface $order
     * @param DeliveryShipmentResultInterface $result
     * @return void
     */
    public function apply(OrderInterface $order, DeliveryShipmentResultInterface $result): void
    {
        // Always record which partner produced this shipment, for every courier.
        $order->setData('delivery_partner', $result->getPartnerCode());

        if ($result->getPartnerCode() === PartnerCode::SHADOWFAX) {
            $order->setData('shadowfax_shipment_id', $result->getShipmentId());
            $order->setData('shadowfax_awb', $result->getAwb());
            $order->setData('shadowfax_courier_name', $result->getCourierName());
        }

        // SHIPROCKET intentionally writes nothing beyond delivery_partner above:
        // the shiprocket_* columns are owned/populated by the existing Shiprocket
        // code paths and must not be double-written here.
    }
}
