<?php
namespace Formula\Wishlist\Api\Data;


use Magento\Framework\Api\ExtensibleDataInterface;

interface WishlistItemInterface extends ExtensibleDataInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const WISHLIST_ITEM_ID = 'wishlist_item_id';
    const CUSTOMER_ID = 'customer_id';
    const PRODUCT_ID = 'product_id';
    const ADDED_AT = 'added_at';

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Get wishlist item ID
     *
     * @return int|null
     */
    public function getWishlistItemId();

    /**
     * Set wishlist item ID
     *
     * @param int $wishlistItemId
     * @return $this
     */
    public function setWishlistItemId($wishlistItemId);

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
     * Get product ID
     *
     * @return int
     */
    public function getProductId();

    /**
     * Set product ID
     *
     * @param int $productId
     * @return $this
     */
    public function setProductId($productId);


    /**
     * Get added at time
     *
     * @return string
     */
    public function getAddedAt();

    /**
     * Set added at time
     *
     * @param string $addedAt
     * @return $this
     */
    public function setAddedAt($addedAt);


    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Formula\Wishlist\Api\Data\WishlistItemExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Formula\Wishlist\Api\Data\WishlistItemExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Formula\Wishlist\Api\Data\WishlistItemExtensionInterface $extensionAttributes
    );
}