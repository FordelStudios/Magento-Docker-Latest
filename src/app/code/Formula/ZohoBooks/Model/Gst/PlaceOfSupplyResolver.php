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
        $normalizedCustomerCode = trim($customerStateCode);
        if ($normalizedCustomerCode === '') {
            throw new LocalizedException(
                __('Customer GST state code is required to determine place of supply.')
            );
        }

        $normalizedOrgCode = trim((string) $this->helper->getOrgGstStateCode());
        if ($normalizedOrgCode === '') {
            throw new LocalizedException(
                __('Organization GST state code is not configured; cannot determine place of supply.')
            );
        }

        $supplyType = $normalizedOrgCode === $normalizedCustomerCode
            ? self::SUPPLY_TYPE_INTRASTATE
            : self::SUPPLY_TYPE_INTERSTATE;

        return new PlaceOfSupplyResult($supplyType, $normalizedCustomerCode);
    }
}
