<?php
declare(strict_types=1);

namespace Formula\LoginOtp\Service;

use Magento\Framework\Exception\LocalizedException;

/**
 * India-only mobile phone validation + normalization.
 *
 * Storage format: 10-digit bare string (e.g. "9876543210"). Matches existing
 * customer_address_entity.telephone format on Formula's prod DB (per audit
 * 2026-05-28 in [[formula-data-baseline]]).
 *
 * For WATI calls we prepend "91" (see WatiOtpSender).
 */
class PhoneValidator
{
    private const INDIA_MOBILE_REGEX = '/^[6-9][0-9]{9}$/';

    /**
     * Normalize user input to bare 10-digit form.
     *
     * Accepts: "9876543210", "+91 9876543210", "+91-9876-543-210", "91 9876543210".
     * Rejects anything that doesn't reduce to a valid 10-digit Indian mobile.
     */
    public function normalize(string $rawPhone): string
    {
        // Strip everything except digits.
        $digits = preg_replace('/[^0-9]/', '', $rawPhone);

        // 12 digits starting with 91 → drop country code.
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) !== 10 || !preg_match(self::INDIA_MOBILE_REGEX, $digits)) {
            throw new LocalizedException(__('Please enter a valid 10-digit Indian mobile number.'));
        }

        return $digits;
    }

    /**
     * Whether a normalized string is a valid Indian mobile.
     * Use after `normalize()` for fast re-checks.
     */
    public function isValid(string $normalizedPhone): bool
    {
        return (bool) preg_match(self::INDIA_MOBILE_REGEX, $normalizedPhone);
    }
}
