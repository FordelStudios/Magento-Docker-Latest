<?php
namespace Formula\Review\Api\Data;

/**
 * Customer Review Status Interface
 */
interface CustomerReviewStatusInterface
{
    const HAS_REVIEW = 'has_review';
    const REVIEW_ID = 'review_id';
    const PRODUCT_SKU = 'product_sku';
    const CUSTOMER_ID = 'customer_id';

    /**
     * Get has review status
     *
     * @return bool
     */
    public function getHasReview();

    /**
     * Set has review status
     *
     * @param bool $hasReview
     * @return $this
     */
    public function setHasReview($hasReview);

    /**
     * Get review ID
     *
     * @return int|null
     */
    public function getReviewId();

    /**
     * Set review ID
     *
     * @param int|null $reviewId
     * @return $this
     */
    public function setReviewId($reviewId);

    /**
     * Get product SKU
     *
     * @return string
     */
    public function getProductSku();

    /**
     * Set product SKU
     *
     * @param string $sku
     * @return $this
     */
    public function setProductSku($sku);

    /**
     * Get customer ID
     *
     * @return int
     */
    public function getCustomerId();

    /**
     * Set customer ID
     *
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId($customerId);
}