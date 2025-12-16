<?php

namespace Formula\ProductVariants\Api\Data;

/**
 * Interface for product variant data
 * @api
 */
interface VariantInterface
{
    const PRODUCT_ID = 'product_id';
    const SKU = 'sku';
    const NAME = 'name';
    const SIZE = 'size';
    const UNIT = 'unit';
    const PRICE = 'price';
    const SPECIAL_PRICE = 'special_price';
    const FINAL_PRICE = 'final_price';
    const IS_IN_STOCK = 'is_in_stock';
    const SALABLE_QTY = 'salable_qty';
    const IMAGE = 'image';

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
     * Get SKU
     *
     * @return string
     */
    public function getSku();

    /**
     * Set SKU
     *
     * @param string $sku
     * @return $this
     */
    public function setSku($sku);

    /**
     * Get name
     *
     * @return string
     */
    public function getName();

    /**
     * Set name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name);

    /**
     * Get size
     *
     * @return string|null
     */
    public function getSize();

    /**
     * Set size
     *
     * @param string|null $size
     * @return $this
     */
    public function setSize($size);

    /**
     * Get unit
     *
     * @return string|null
     */
    public function getUnit();

    /**
     * Set unit
     *
     * @param string|null $unit
     * @return $this
     */
    public function setUnit($unit);

    /**
     * Get price
     *
     * @return float
     */
    public function getPrice();

    /**
     * Set price
     *
     * @param float $price
     * @return $this
     */
    public function setPrice($price);

    /**
     * Get special price
     *
     * @return float|null
     */
    public function getSpecialPrice();

    /**
     * Set special price
     *
     * @param float|null $specialPrice
     * @return $this
     */
    public function setSpecialPrice($specialPrice);

    /**
     * Get final price
     *
     * @return float
     */
    public function getFinalPrice();

    /**
     * Set final price
     *
     * @param float $finalPrice
     * @return $this
     */
    public function setFinalPrice($finalPrice);

    /**
     * Get is in stock
     *
     * @return bool
     */
    public function getIsInStock();

    /**
     * Set is in stock
     *
     * @param bool $isInStock
     * @return $this
     */
    public function setIsInStock($isInStock);

    /**
     * Get salable qty
     *
     * @return float
     */
    public function getSalableQty();

    /**
     * Set salable qty
     *
     * @param float $salableQty
     * @return $this
     */
    public function setSalableQty($salableQty);

    /**
     * Get image URL
     *
     * @return string|null
     */
    public function getImage();

    /**
     * Set image URL
     *
     * @param string|null $image
     * @return $this
     */
    public function setImage($image);
}
