<?php
declare(strict_types=1);

namespace Formula\ZohoBooks\Model\Catalog;

use Formula\ZohoBooks\Model\Gst\HsnResolver;

/**
 * Pure classifier: maps a product's category names to the HSN bucket key that
 * {@see HsnResolver} expects (skincare/soap/haircare).
 *
 * This is intentionally decoupled from HOW those category names were obtained - it never
 * reaches into the product, the category tree, or the DB. Resolving a Magento product's
 * actual category names (which does require a DB read) is the caller's concern (see the
 * invoice mapper), so this class stays deterministic and unit-testable with plain arrays.
 *
 * @todo This substring/keyword classification is the AGREED INTERIM SCOPE for bucketing
 *       products into HSN codes. A per-product HSN attribute is a possible later option if
 *       this proves too coarse (e.g. a product legitimately in both "Hair" and "Soap"
 *       categories). Confirm the bucket rules below with the client's accountant before
 *       go-live - a wrong HSN is a compliance issue, not a cosmetic one.
 */
class ProductCategoryClassifier
{
    /**
     * Keyword groups, in priority order (first matching group wins).
     *
     * Single source of truth for the classification rules - each entry maps a target
     * HSN bucket (see {@see HsnResolver}) to the case-insensitive substrings that route a
     * category name into it.
     *
     * @var array<string, string[]>
     */
    private const KEYWORD_RULES = [
        HsnResolver::BUCKET_HAIRCARE => ['hair'],
        HsnResolver::BUCKET_SOAP => ['soap', 'cleanser', 'face wash', 'body wash'],
    ];

    /**
     * Bucket used when no category name matches any keyword rule.
     */
    private const FALLBACK_BUCKET = HsnResolver::BUCKET_SKINCARE;

    /**
     * Classify a product into an HSN bucket key from its category names.
     *
     * Case-insensitive substring match; the first rule (in KEYWORD_RULES order) with any
     * matching category name wins. An empty list, or a list that matches no rule, falls
     * back to skincare - a deliberately safe default since every invoice line needs *a*
     * bucket to resolve HSN/rate from.
     *
     * @param string[] $categoryNames the product's category names (in any order)
     * @return string one of HsnResolver::BUCKET_*
     */
    public function classify(array $categoryNames): string
    {
        foreach (self::KEYWORD_RULES as $bucket => $keywords) {
            if ($this->matchesAny($categoryNames, $keywords)) {
                return $bucket;
            }
        }

        return self::FALLBACK_BUCKET;
    }

    /**
     * @param string[] $categoryNames
     * @param string[] $keywords
     */
    private function matchesAny(array $categoryNames, array $keywords): bool
    {
        foreach ($categoryNames as $categoryName) {
            $normalized = strtolower((string) $categoryName);
            foreach ($keywords as $keyword) {
                if (str_contains($normalized, $keyword)) {
                    return true;
                }
            }
        }

        return false;
    }
}
