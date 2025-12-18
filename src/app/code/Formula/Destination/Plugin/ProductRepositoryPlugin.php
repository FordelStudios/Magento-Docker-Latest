<?php
declare(strict_types=1);

namespace Formula\Destination\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;

class ProductRepositoryPlugin
{
    /**
     * Add destination extension attribute after getting product
     *
     * @param ProductRepositoryInterface $subject
     * @param ProductInterface $product
     * @return ProductInterface
     */
    public function afterGet(
        ProductRepositoryInterface $subject,
        ProductInterface $product
    ) {
        $this->addDestinationAttribute($product);
        return $product;
    }

    /**
     * Add destination extension attribute after getting product list
     *
     * @param ProductRepositoryInterface $subject
     * @param ProductSearchResultsInterface $searchResults
     * @return ProductSearchResultsInterface
     */
    public function afterGetList(
        ProductRepositoryInterface $subject,
        ProductSearchResultsInterface $searchResults
    ) {
        $products = $searchResults->getItems();

        foreach ($products as $product) {
            $this->addDestinationAttribute($product);
        }

        return $searchResults;
    }

    /**
     * Add destination attribute to product extension attributes
     *
     * @param ProductInterface $product
     * @return void
     */
    private function addDestinationAttribute(ProductInterface $product): void
    {
        $extensionAttributes = $product->getExtensionAttributes();
        if (!$extensionAttributes) {
            return;
        }

        $destinationAttribute = $product->getCustomAttribute('destination');
        $destination = $destinationAttribute ? $destinationAttribute->getValue() : null;

        $extensionAttributes->setDestination($destination);
        $product->setExtensionAttributes($extensionAttributes);
    }
}
