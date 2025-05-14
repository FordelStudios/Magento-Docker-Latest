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
        TimezoneInterface $timezone,
    ) {
        $this->extensionAttributesFactory = $extensionAttributesFactory;
        $this->timezone                   = $timezone;
    }

    /**
     * Add discount_percentage to extension attributes
     *
     * @param ProductRepositoryInterface $subject
     * @param ProductInterface $product
     * @return ProductInterface
     */
    public function afterGet(
        ProductRepositoryInterface $subject,
        ProductInterface $product
    ) {
        $extensionAttributes = $product->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->extensionAttributesFactory->create(ProductInterface::class);
        }

        // Get the EAV attribute value
        $discountPercentage = $product->getData('discount_percentage') ?: 0;

        // Set it to extension attributes for API compatibility
        $extensionAttributes->setDiscountPercentage($discountPercentage);
        $product->setExtensionAttributes($extensionAttributes);

        return $product;
    }

    /**
     * Add discount_percentage to extension attributes for collection
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
            $extensionAttributes = $product->getExtensionAttributes();
            if ($extensionAttributes === null) {
                $extensionAttributes = $this->extensionAttributesFactory->create(ProductInterface::class);
            }

            // Get the EAV attribute value
            $discountPercentage = $product->getData('discount_percentage') ?: 0;

            // Set it to extension attributes for API compatibility
            $extensionAttributes->setDiscountPercentage($discountPercentage);
            $product->setExtensionAttributes($extensionAttributes);
        }

        return $searchResults;
    }
}
