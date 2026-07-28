<?php
declare(strict_types=1);

namespace Formula\ZohoBooks\Model\Gst;

use Magento\Framework\Exception\LocalizedException;

/**
 * Pure lookup: maps a Magento India address region (code and/or free-text name) to the
 * 2-digit Indian GST state/UT code that {@see PlaceOfSupplyResolver} requires.
 *
 * The region CODE/NAME values below are Magento's own `directory_country_region` seed data
 * for country "IN" (see vendor/magento/module-directory/Setup/Patch/Data/AddDataForIndia.php
 * and AddRegionsForIndia.php for Ladakh) - i.e. what Magento actually stores when a customer
 * picks a state from the checkout/admin region dropdown. The GST 2-digit codes are the
 * official CBIC/GST state codes used on GSTIN registrations (verified against the GST state
 * code tables published by ClearTax and Tally Solutions, cross-checked 2026-07-29).
 *
 * Two historical wrinkles, both intentional:
 * - Magento still lists "Dadra and Nagar Haveli" (DN) and "Daman and Diu" (DD) as two
 *   separate regions (pre-2020 data). GST has since merged them into a single UT with code
 *   26 - both Magento region codes map to that one merged GST code here.
 *   Old GST code 25 (legacy "Daman & Diu") is retired and NOT in this table.
 * - Magento has a single "Andhra Pradesh" (AP) region (no old/new distinction). The GST
 *   portal issued a *new* code 37 to Andhra Pradesh after Telangana's 2014 bifurcation and
 *   deprecated the old undivided-AP code 28. AP maps to 37 (current) here; 28 is intentionally
 *   NOT in this table since Magento has no way to distinguish a legacy vs. current AP address.
 *
 * @todo Confirm the region identifier(s) this store actually persists on customer/order
 *       addresses (region_id vs. region_code vs. free-text region) before wiring this into
 *       the live invoice/contact mappers - this class accepts either code or name so it can
 *       adapt to whichever the storefront/checkout actually sends, but the caller needs to
 *       know which one it has.
 * @todo Re-verify this table against the official GST/CBIC state code list (not just
 *       secondary sources) before go-live - a wrong state code produces a wrong CGST/IGST
 *       split, which is a GST compliance bug, not a cosmetic one.
 */
class StateCodeMapper
{
    /**
     * Single source of truth for the region code/name -> GST state code mapping.
     *
     * @var array<int, array{code: string, name: string, gst: string}>
     */
    private const STATE_TABLE = [
        ['code' => 'JK', 'name' => 'Jammu and Kashmir', 'gst' => '01'],
        ['code' => 'HP', 'name' => 'Himachal Pradesh', 'gst' => '02'],
        ['code' => 'PB', 'name' => 'Punjab', 'gst' => '03'],
        ['code' => 'CH', 'name' => 'Chandigarh', 'gst' => '04'],
        ['code' => 'UT', 'name' => 'Uttarakhand', 'gst' => '05'],
        ['code' => 'HR', 'name' => 'Haryana', 'gst' => '06'],
        ['code' => 'DL', 'name' => 'Delhi', 'gst' => '07'],
        ['code' => 'RJ', 'name' => 'Rajasthan', 'gst' => '08'],
        ['code' => 'UP', 'name' => 'Uttar Pradesh', 'gst' => '09'],
        ['code' => 'BR', 'name' => 'Bihar', 'gst' => '10'],
        ['code' => 'SK', 'name' => 'Sikkim', 'gst' => '11'],
        ['code' => 'AR', 'name' => 'Arunachal Pradesh', 'gst' => '12'],
        ['code' => 'NL', 'name' => 'Nagaland', 'gst' => '13'],
        ['code' => 'MN', 'name' => 'Manipur', 'gst' => '14'],
        ['code' => 'MZ', 'name' => 'Mizoram', 'gst' => '15'],
        ['code' => 'TR', 'name' => 'Tripura', 'gst' => '16'],
        ['code' => 'ML', 'name' => 'Meghalaya', 'gst' => '17'],
        ['code' => 'AS', 'name' => 'Assam', 'gst' => '18'],
        ['code' => 'WB', 'name' => 'West Bengal', 'gst' => '19'],
        ['code' => 'JH', 'name' => 'Jharkhand', 'gst' => '20'],
        ['code' => 'OR', 'name' => 'Odisha', 'gst' => '21'],
        ['code' => 'CT', 'name' => 'Chhattisgarh', 'gst' => '22'],
        ['code' => 'MP', 'name' => 'Madhya Pradesh', 'gst' => '23'],
        ['code' => 'GJ', 'name' => 'Gujarat', 'gst' => '24'],
        ['code' => 'DN', 'name' => 'Dadra and Nagar Haveli', 'gst' => '26'],
        ['code' => 'DD', 'name' => 'Daman and Diu', 'gst' => '26'],
        ['code' => 'MH', 'name' => 'Maharashtra', 'gst' => '27'],
        ['code' => 'KA', 'name' => 'Karnataka', 'gst' => '29'],
        ['code' => 'GA', 'name' => 'Goa', 'gst' => '30'],
        ['code' => 'LD', 'name' => 'Lakshadweep', 'gst' => '31'],
        ['code' => 'KL', 'name' => 'Kerala', 'gst' => '32'],
        ['code' => 'TN', 'name' => 'Tamil Nadu', 'gst' => '33'],
        ['code' => 'PY', 'name' => 'Puducherry', 'gst' => '34'],
        ['code' => 'AN', 'name' => 'Andaman and Nicobar Islands', 'gst' => '35'],
        ['code' => 'TG', 'name' => 'Telangana', 'gst' => '36'],
        ['code' => 'AP', 'name' => 'Andhra Pradesh', 'gst' => '37'],
        ['code' => 'LA', 'name' => 'Ladakh', 'gst' => '38'],
    ];

    /**
     * Resolve a Magento address region to its 2-digit GST state code.
     *
     * Tries the region CODE first (more reliable, less prone to free-text drift), then
     * falls back to the region NAME. Never silently defaults to a state when neither
     * identifier matches - a wrong state means a wrong CGST/IGST split, which is a GST
     * compliance bug, so unknown input throws rather than guessing.
     *
     * @param string|null $regionCode e.g. "MH" (case-insensitive)
     * @param string|null $regionName e.g. "Maharashtra" (case-insensitive)
     * @return string canonical 2-digit GST state code
     * @throws LocalizedException when neither identifier matches a known state/UT
     */
    public function resolve(?string $regionCode, ?string $regionName = null): string
    {
        $gstCode = $this->matchByCode($regionCode) ?? $this->matchByName($regionName);

        if ($gstCode === null) {
            throw new LocalizedException(__(
                'Unrecognized Indian GST state/UT for region code "%1" / region name "%2". '
                . 'Cannot determine place of supply without a valid state.',
                (string) $regionCode,
                (string) $regionName
            ));
        }

        return $gstCode;
    }

    private function matchByCode(?string $regionCode): ?string
    {
        $normalized = strtoupper(trim((string) $regionCode));
        if ($normalized === '') {
            return null;
        }

        foreach (self::STATE_TABLE as $row) {
            if ($row['code'] === $normalized) {
                return $row['gst'];
            }
        }

        return null;
    }

    private function matchByName(?string $regionName): ?string
    {
        $normalized = strtolower(trim((string) $regionName));
        if ($normalized === '') {
            return null;
        }

        foreach (self::STATE_TABLE as $row) {
            if (strtolower($row['name']) === $normalized) {
                return $row['gst'];
            }
        }

        return null;
    }
}
