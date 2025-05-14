<?php
namespace Formula\DiscountPercentage\Plugin;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class ProductResourcePlugin
{
    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        TimezoneInterface $timezone,
    ) {
        $this->timezone = $timezone;

    }

    /**
     * Calculate and set discount percentage before saving product
     *
     * @param ProductResource $subject
     * @param Product $product
     * @return array
     */
    public function beforeSave(ProductResource $subject, Product $product)
    {
        $this->calculateDiscountPercentage($product);
        return [$product];
    }

    /**
     * Calculate discount percentage
     *
     * @param Product $product
     * @return void
     */
    private function calculateDiscountPercentage(Product $product)
    {
        $regularPrice    = (float) $product->getPrice();
        $specialPrice    = $product->getSpecialPrice() ? (float) $product->getSpecialPrice() : null;
        $specialFromDate = $product->getSpecialFromDate();
        $specialToDate   = $product->getSpecialToDate();
        $now             = $this->timezone->date()->format('Y-m-d H:i:s');

        $discountPercentage = 0;

        // Check if special price exists, is valid, and is less than regular price
        $isSpecialPriceValid = ($specialPrice && $regularPrice > 0 && $specialPrice < $regularPrice);

        // Check if current date is within the special price date range
        $isDateValid = true;
        if ($specialFromDate && $now < $specialFromDate) {
            $isDateValid = false;
        }

        if ($specialToDate && $now > $specialToDate) {
            $isDateValid = false;
        }

        // Calculate discount percentage only if both price and date are valid
        if ($isSpecialPriceValid && $isDateValid) {
            $discountPercentage = round(100 - (($specialPrice / $regularPrice) * 100), 2);
        }

        // Set the discount percentage attribute
        $product->setData('discount_percentage', $discountPercentage);

    }
}
