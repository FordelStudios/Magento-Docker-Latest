<?php
namespace Formula\Review\Api\Data;

/**
 * Customer Purchase Verification Interface
 */
interface CustomerPurchaseVerificationInterface
{
    const HAS_PURCHASED = 'has_purchased';
    const PRODUCT_SKU = 'product_sku';
    const PRODUCT_ID = 'product_id';
    const CUSTOMER_ID = 'customer_id';
    const PURCHASE_COUNT = 'purchase_count';
    const LAST_PURCHASE_DATE = 'last_purchase_date';
    const ORDER_IDS = 'order_ids';

    /**
     * Get has purchased status
     *
     * @return bool
     */
    public function getHasPurchased();

    /**
     * Set has purchased status
     *
     * @param bool $hasPurchased
     * @return $this
     */
    public function setHasPurchased($hasPurchased);

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
     * Get product ID
     *
     * @return int|null
     */
    public function getProductId();

    /**
     * Set product ID
     *
     * @param int|null $productId
     * @return $this
     */
    public function setProductId($productId);

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

    /**
     * Get purchase count
     *
     * @return int
     */
    public function getPurchaseCount();

    /**
     * Set purchase count
     *
     * @param int $count
     * @return $this
     */
    public function setPurchaseCount($count);

    /**
     * Get last purchase date
     *
     * @return string|null
     */
    public function getLastPurchaseDate();

    /**
     * Set last purchase date
     *
     * @param string|null $date
     * @return $this
     */
    public function setLastPurchaseDate($date);

    /**
     * Get order IDs containing the product
     *
     * @return array
     */
    public function getOrderIds();

    /**
     * Set order IDs containing the product
     *
     * @param array $orderIds
     * @return $this
     */
    public function setOrderIds(array $orderIds);
}