<?php
namespace Formula\Review\Api;

interface ProductReviewRepositoryInterface
{
    /**
     * Get product reviews
     *
     * @param string $sku
     * @return \Formula\Review\Api\Data\ReviewInterface[]
     */
    public function getList($sku);

    /**
     * Get review by ID
     *
     * @param int $id
     * @return \Formula\Review\Api\Data\ReviewInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get($id);

    /**
     * Create review
     *
     * @param \Formula\Review\Api\Data\ReviewInterface $review
     * @return \Formula\Review\Api\Data\ReviewInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function create(\Formula\Review\Api\Data\ReviewInterface $review);

    /**
     * Update review
     *
     * @param int $id
     * @param \Formula\Review\Api\Data\ReviewInterface $review
     * @return \Formula\Review\Api\Data\ReviewInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function update($id, \Formula\Review\Api\Data\ReviewInterface $review);
    
    /**
     * Delete review by ID
     *
     * @param int $id
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById($id);
    
    /**
     * Get all reviews
     *
     * @return \Formula\Review\Api\Data\ReviewInterface[]
     */
    public function getAllReviews();

    /**
     * Check if the authenticated customer has an existing review for a product
     *
     * @param string $sku
     * @return \Formula\Review\Api\Data\CustomerReviewStatusInterface
     * @throws \Magento\Framework\Exception\AuthorizationException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCustomerExistingReview($sku);

    /**
     * Verify if the authenticated customer has purchased a product
     *
     * @param string|null $sku Product SKU (optional if productId is provided)
     * @param int|null $productId Product ID (optional if sku is provided)
     * @return \Formula\Review\Api\Data\CustomerPurchaseVerificationInterface
     * @throws \Magento\Framework\Exception\AuthorizationException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\InvalidArgumentException
     */
    public function verifyCustomerPurchase($sku = null, $productId = null);

    /**
     * Debug method to help troubleshoot SKU matching issues
     * This can be called to see what's happening with SKU matching
     *
     * @param string $sku
     * @return array
     */
    public function debugSkuMatching($sku);
}