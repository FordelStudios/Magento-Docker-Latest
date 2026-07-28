<?php
declare(strict_types=1);

namespace Formula\ZohoBooks\Model\Gst;

use Formula\ZohoBooks\Helper\Data as ZohoBooksHelper;
use Magento\Framework\Exception\LocalizedException;

/**
 * Determines whether an invoice is intrastate (CGST+SGST) or interstate (IGST) by
 * comparing the organization's GST state code against the customer's GST state code.
 *
 * @todo Mapping a Magento region (region_id / "MH" / "Maharashtra") to a 2-digit GST
 *       state code is a LATER mapper concern. This resolver assumes it is handed
 *       already-normalized 2-digit codes on both sides — it does not look up a
 *       state-code table itself.
 */
class PlaceOfSupplyResolver
{
    public const SUPPLY_TYPE_INTRASTATE = 'intrastate';
    public const SUPPLY_TYPE_INTERSTATE = 'interstate';

    /**
     * @param ZohoBooksHelper $helper deterministic config read (org GST state code);
     *                                injected so resolve() stays unit-testable by mocking Helper
     */
    public function __construct(
        private readonly ZohoBooksHelper $helper
    ) {
    }

    /**
     * Resolve the place of supply + intrastate/interstate split for a customer.
     *
     * Never silently defaults the split — a wrong CGST/IGST split is a GST
     * compliance bug, so a missing customer or org state code throws instead of
     * guessing.
     *
     * @param string $customerStateCode already-normalized 2-digit GST state code
     * @return PlaceOfSupplyResult
     * @throws LocalizedException when the customer or org state code is missing
     */
    public function resolve(string $customerStateCode): PlaceOfSupplyResult
    {
        $normalizedCustomerCode = $this->normalizeStateCode(
            $customerStateCode,
            __('Customer GST state code is required and must be a valid 2-digit GST state code.')
        );

        $normalizedOrgCode = $this->normalizeStateCode(
            (string) $this->helper->getOrgGstStateCode(),
            __('Organization GST state code is not configured or is not a valid 2-digit GST state code.')
        );

        $supplyType = $normalizedOrgCode === $normalizedCustomerCode
            ? self::SUPPLY_TYPE_INTRASTATE
            : self::SUPPLY_TYPE_INTERSTATE;

        return new PlaceOfSupplyResult($supplyType, $normalizedCustomerCode);
    }

    /**
     * Normalize a GST state code to a canonical 2-digit string.
     *
     * Trims, then left zero-pads a 1-2 digit numeric code ("7" -> "07") so that
     * equivalent codes compare equal. Anything that is not exactly 2 digits after
     * padding (empty, "271", "AB", "2A") is rejected with a throw rather than
     * silently classified — a wrong CGST/IGST split is a GST compliance bug.
     *
     * @param string $stateCode raw code from config or the caller
     * @param \Magento\Framework\Phrase $errorMessage message for the thrown exception
     * @return string canonical 2-digit code
     * @throws LocalizedException when the code is empty or not a valid 2-digit code
     */
    private function normalizeStateCode(string $stateCode, \Magento\Framework\Phrase $errorMessage): string
    {
        $trimmed = trim($stateCode);

        if (preg_match('/^\d{1,2}$/', $trimmed)) {
            $trimmed = str_pad($trimmed, 2, '0', STR_PAD_LEFT);
        }

        if (!preg_match('/^\d{2}$/', $trimmed)) {
            throw new LocalizedException($errorMessage);
        }

        return $trimmed;
    }
}
