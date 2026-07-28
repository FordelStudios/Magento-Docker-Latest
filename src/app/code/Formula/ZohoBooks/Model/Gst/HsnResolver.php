<?php
declare(strict_types=1);

namespace Formula\ZohoBooks\Model\Gst;

/**
 * Pure lookup: maps a product category bucket key to its HSN code + GST rate.
 *
 * These buckets are the AGREED INTERIM SCOPE (confirmed with the product owner) for
 * classifying FormulaShop products into Zoho Books invoice lines. A per-product HSN
 * attribute is a possible later option if the bucket-level mapping proves too coarse.
 *
 * The actual classification of a given Magento product into one of these bucket keys
 * (e.g. reading its category tree / attributes) happens in a LATER mapper task. This
 * class is intentionally a pure lookup — it never reaches into the product or the DB.
 *
 * @todo Confirm HSN codes and GST rate with the client's accountant before going live.
 *       3304 (beauty/skincare preparations), 3401 (soap), 3305 (hair preparations) at
 *       18% GST are the standard Indian classifications for cosmetics, but a wrong HSN
 *       is a compliance issue — this must be signed off, not assumed.
 */
class HsnResolver
{
    public const BUCKET_SKINCARE = 'skincare';
    public const BUCKET_SOAP = 'soap';
    public const BUCKET_HAIRCARE = 'haircare';

    /**
     * Single source of truth for the category-bucket -> HSN/rate mapping.
     *
     * @var array<string, array{hsn: string, rate: float}>
     */
    private const BUCKET_MAP = [
        self::BUCKET_SKINCARE => ['hsn' => '3304', 'rate' => 18.0],
        self::BUCKET_SOAP => ['hsn' => '3401', 'rate' => 18.0],
        self::BUCKET_HAIRCARE => ['hsn' => '3305', 'rate' => 18.0],
    ];

    /**
     * Bucket used when the given category key is unknown/unmapped.
     */
    private const FALLBACK_BUCKET = self::BUCKET_SKINCARE;

    /**
     * Resolve a category bucket key to its HSN code + GST rate.
     *
     * Unknown or empty keys fall back to the skincare bucket rather than throwing,
     * since HSN/rate resolution always needs *a* value to build the invoice line —
     * the fallback is a deliberately safe default, not a silent data-loss risk.
     *
     * @param string $categoryKey case-insensitive bucket key (e.g. "skincare")
     * @return HsnResult
     */
    public function resolve(string $categoryKey): HsnResult
    {
        $normalizedKey = strtolower(trim($categoryKey));
        $bucket = self::BUCKET_MAP[$normalizedKey] ?? self::BUCKET_MAP[self::FALLBACK_BUCKET];

        return new HsnResult($bucket['hsn'], $bucket['rate']);
    }
}
