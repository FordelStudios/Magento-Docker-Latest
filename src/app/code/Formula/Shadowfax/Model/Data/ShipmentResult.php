<?php
declare(strict_types=1);

namespace Formula\Shadowfax\Model\Data;

/**
 * Typed result of a ShadowFax create-shipment call.
 *
 * @todo Field names (awb/shipment_id/courier_name) are assumed from typical
 *       courier-aggregator response conventions; reconcile against real ShadowFax
 *       API docs when P1 credentials land.
 */
class ShipmentResult
{
    /**
     * @var string|null
     */
    private $awb;

    /**
     * @var string|null
     */
    private $shipmentId;

    /**
     * @var string|null
     */
    private $courierName;

    /**
     * @var array
     */
    private $raw;

    /**
     * @param string|null $awb
     * @param string|null $shipmentId
     * @param string|null $courierName
     * @param array $raw
     */
    public function __construct(?string $awb, ?string $shipmentId, ?string $courierName, array $raw = [])
    {
        $this->awb = $awb;
        $this->shipmentId = $shipmentId;
        $this->courierName = $courierName;
        $this->raw = $raw;
    }

    /**
     * @return string|null
     */
    public function getAwb(): ?string
    {
        return $this->awb;
    }

    /**
     * @return string|null
     */
    public function getShipmentId(): ?string
    {
        return $this->shipmentId;
    }

    /**
     * @return string|null
     */
    public function getCourierName(): ?string
    {
        return $this->courierName;
    }

    /**
     * Full raw API response, kept for debugging/reconciliation.
     *
     * @return array
     */
    public function getRaw(): array
    {
        return $this->raw;
    }
}
