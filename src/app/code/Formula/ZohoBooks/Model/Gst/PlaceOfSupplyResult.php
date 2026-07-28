<?php
declare(strict_types=1);

namespace Formula\ZohoBooks\Model\Gst;

/**
 * Value object: the resolved place-of-supply + tax-split behavior for an invoice.
 */
class PlaceOfSupplyResult
{
    /**
     * @param string $supplyType PlaceOfSupplyResolver::SUPPLY_TYPE_INTRASTATE|SUPPLY_TYPE_INTERSTATE
     * @param string $placeOfSupply the (already-normalized) 2-digit customer GST state code
     */
    public function __construct(
        public readonly string $supplyType,
        public readonly string $placeOfSupply
    ) {
    }

    /**
     * Split a GST rate into its invoice-line tax components.
     *
     * Intrastate (same state) -> CGST + SGST, split evenly.
     * Interstate (different state) -> IGST, full rate.
     *
     * @param float $rate GST rate as a percentage (e.g. 18.0)
     * @return array{igst?: float, cgst?: float, sgst?: float}
     */
    public function splitTax(float $rate): array
    {
        if ($this->supplyType === PlaceOfSupplyResolver::SUPPLY_TYPE_INTERSTATE) {
            return ['igst' => $rate];
        }

        $half = $rate / 2;

        return ['cgst' => $half, 'sgst' => $half];
    }
}
