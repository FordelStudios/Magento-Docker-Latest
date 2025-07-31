<?php
namespace Formula\Wishlist\Api\Data;

/**
 * Extension interface for @see \Formula\Wishlist\Api\Data\WishlistItemInterface
 */
interface WishlistItemExtensionInterface extends \Magento\Framework\Api\ExtensionAttributesInterface
{
    /**
     * Get product
     *
     * @return \Magento\Catalog\Api\Data\ProductInterface|null
     */
    public function getProduct();

    /**
     * Set product
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return $this
     */
    public function setProduct(\Magento\Catalog\Api\Data\ProductInterface $product);
}