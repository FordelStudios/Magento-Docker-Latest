<?php
declare(strict_types=1);

namespace Formula\DeliveryPartner\Model\Data;

use Formula\DeliveryPartner\Api\Data\DeliveryShipmentResultInterface;

/**
 * Immutable, courier-agnostic shipment-creation result.
 */
class DeliveryShipmentResult implements DeliveryShipmentResultInterface
{
    /**
     * @param string $partnerCode
     * @param string|null $shipmentId
     * @param string|null $awb
     * @param string|null $courierName
     * @param bool $successful
     */
    public function __construct(
        private readonly string $partnerCode,
        private readonly ?string $shipmentId,
        private readonly ?string $awb,
        private readonly ?string $courierName,
        private readonly bool $successful
    ) {
    }

    /**
     * @return string
     */
    public function getPartnerCode(): string
    {
        return $this->partnerCode;
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
    public function getAwb(): ?string
    {
        return $this->awb;
    }

    /**
     * @return string|null
     */
    public function getCourierName(): ?string
    {
        return $this->courierName;
    }

    /**
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->successful;
    }
}
