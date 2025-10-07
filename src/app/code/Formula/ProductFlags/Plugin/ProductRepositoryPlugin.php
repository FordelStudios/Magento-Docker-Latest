<?php
namespace Formula\ProductFlags\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\ExtensionAttributesFactory;

class ProductRepositoryPlugin
{
    /**
     * @var ExtensionAttributesFactory
     */
    private $extensionAttributesFactory;

    /**
     * @param ExtensionAttributesFactory $extensionAttributesFactory
     */
    public function __construct(
        ExtensionAttributesFactory $extensionAttributesFactory
    ) {
        $this->extensionAttributesFactory = $extensionAttributesFactory;
    }

    /**
     * Add product flags to extension attributes
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

        // Get the EAV attribute values
        $giftset = (bool) $product->getData('giftset');
        $newArrival = (bool) $product->getData('new_arrival');
        $trending = (bool) $product->getData('trending');
        $popular = (bool) $product->getData('popular');

        // Set them to extension attributes for API compatibility
        $extensionAttributes->setGiftset($giftset);
        $extensionAttributes->setNewArrival($newArrival);
        $extensionAttributes->setTrending($trending);
        $extensionAttributes->setPopular($popular);
        $product->setExtensionAttributes($extensionAttributes);

        return $product;
    }

    /**
     * Add product flags to extension attributes for collection
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

            // Get the EAV attribute values
            $giftset = (bool) $product->getData('giftset');
            $newArrival = (bool) $product->getData('new_arrival');
            $trending = (bool) $product->getData('trending');
            $popular = (bool) $product->getData('popular');

            // Set them to extension attributes for API compatibility
            $extensionAttributes->setGiftset($giftset);
            $extensionAttributes->setNewArrival($newArrival);
            $extensionAttributes->setTrending($trending);
            $extensionAttributes->setPopular($popular);
            $product->setExtensionAttributes($extensionAttributes);
        }

        return $searchResults;
    }
}
