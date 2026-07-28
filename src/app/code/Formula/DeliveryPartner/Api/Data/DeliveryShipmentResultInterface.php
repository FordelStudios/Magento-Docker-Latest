<?php
declare(strict_types=1);

namespace Formula\DeliveryPartner\Api\Data;

/**
 * Neutral shipment-creation result, normalized across delivery partners.
 *
 * Each courier adapter (Shiprocket, ShadowFax, ...) maps its own native
 * return shape (array, DTO, etc.) into an implementation of this interface,
 * so call sites never need to know which courier actually handled the order.
 */
interface DeliveryShipmentResultInterface
{
    /**
     * The partner code that produced this result, e.g. PartnerCode::SHIPROCKET.
     *
     * @return string
     */
    public function getPartnerCode(): string;

    /**
     * @return string|null
     */
    public function getShipmentId(): ?string;

    /**
     * @return string|null
     */
    public function getAwb(): ?string;

    /**
     * @return string|null
     */
    public function getCourierName(): ?string;

    /**
     * @return bool
     */
    public function isSuccessful(): bool;
}
