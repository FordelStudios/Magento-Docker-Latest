<?php
declare(strict_types=1);

namespace Formula\ZohoBooks\Model\Gst;

/**
 * Value object: the HSN code + GST rate resolved for a product category bucket
 */
class HsnResult
{
    /**
     * @param string $hsn HSN classification code (e.g. "3304")
     * @param float $rate GST rate as a percentage (e.g. 18.0)
     * @param bool $isFallback true when the category key missed the bucket map and the
     *                         default (skincare) bucket was used. Lets a downstream
     *                         consumer (e.g. the invoice mapper) detect and flag/log an
     *                         unclassified product instead of silently invoicing 3304.
     */
    public function __construct(
        public readonly string $hsn,
        public readonly float $rate,
        public readonly bool $isFallback = false
    ) {
    }
}
