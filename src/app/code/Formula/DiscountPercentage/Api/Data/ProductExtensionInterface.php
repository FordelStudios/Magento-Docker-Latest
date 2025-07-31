<?php
namespace Formula\DiscountPercentage\Api\Data;

interface ProductExtensionInterface extends \Magento\Catalog\Api\Data\ProductExtensionInterface
{
    /**
     * @return float|null
     */
    public function getDiscountPercentage();

    /**
     * @param float $discountPercentage
     * @return $this
     */
    public function setDiscountPercentage($discountPercentage);
}
