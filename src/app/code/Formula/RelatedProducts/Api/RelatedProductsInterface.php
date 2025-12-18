<?php
declare(strict_types=1);

namespace Formula\RelatedProducts\Api;

/**
 * Interface for getting related/linked products with full details
 * @api
 */
interface RelatedProductsInterface
{
    /**
     * Get related products with full details
     *
     * @param string $sku
     * @return \Magento\Catalog\Api\Data\ProductInterface[]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRelatedProducts(string $sku): array;

    /**
     * Get up-sell products with full details
     *
     * @param string $sku
     * @return \Magento\Catalog\Api\Data\ProductInterface[]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getUpsellProducts(string $sku): array;

    /**
     * Get cross-sell products with full details
     *
     * @param string $sku
     * @return \Magento\Catalog\Api\Data\ProductInterface[]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCrosssellProducts(string $sku): array;

    /**
     * Get all linked products (related, upsell, crosssell) with full details
     *
     * @param string $sku
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAllLinkedProducts(string $sku);
}
