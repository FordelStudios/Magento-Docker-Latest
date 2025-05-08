<?php
namespace Formula\DiscountPercentage\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class ProductRepositoryPlugin
{
    /**
     * @var ExtensionAttributesFactory
     */
    private $extensionAttributesFactory;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @param ExtensionAttributesFactory $extensionAttributesFactory
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        ExtensionAttributesFactory $extensionAttributesFactory,
        TimezoneInterface $timezone
    ) {
        $this->extensionAttributesFactory = $extensionAttributesFactory;
        $this->timezone                   = $timezone;
    }

    /**
     * Add discount_percentage extension attribute to product
     *
     * @param ProductRepositoryInterface $subject
     * @param ProductInterface $product
     * @return ProductInterface
     */
    public function afterGet(
        ProductRepositoryInterface $subject,
        ProductInterface $product
    ) {
        return $this->addDiscountPercentage($product);
    }

    /**
     * Add discount_percentage extension attribute to product collection
     *
     * @param ProductRepositoryInterface $subject
     * @param \Magento\Framework\Api\SearchResultsInterface $searchResults
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function afterGetList(
        ProductRepositoryInterface $subject,
        \Magento\Framework\Api\SearchResultsInterface $searchResults
    ) {
        $products = $searchResults->getItems();

        foreach ($products as $product) {
            $this->addDiscountPercentage($product);
        }

        return $searchResults;
    }

    /**
     * Calculate and add discount percentage to product
     *
     * @param ProductInterface $product
     * @return ProductInterface
     */
    private function addDiscountPercentage(ProductInterface $product)
    {
        $extensionAttributes = $product->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->extensionAttributesFactory->create(ProductInterface::class);
        }

        $regularPrice    = (float) $product->getPrice();
        $specialPrice    = null;
        $specialFromDate = null;
        $specialToDate   = null;
        $now             = $this->timezone->date()->format('Y-m-d H:i:s');

        // Get special price from custom attributes
        foreach ($product->getCustomAttributes() as $attribute) {
            if ($attribute->getAttributeCode() === 'special_price') {
                $specialPrice = (float) $attribute->getValue();
            }
            if ($attribute->getAttributeCode() === 'special_from_date') {
                $specialFromDate = $attribute->getValue();
            }
            if ($attribute->getAttributeCode() === 'special_to_date') {
                $specialToDate = $attribute->getValue();
            }
        }

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

        // Set the discount percentage extension attribute
        $extensionAttributes->setDiscountPercentage($discountPercentage);
        $product->setExtensionAttributes($extensionAttributes);

        return $product;
    }
}
