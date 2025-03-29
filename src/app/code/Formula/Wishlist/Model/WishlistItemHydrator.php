<?php
namespace Formula\Wishlist\Model;

use Formula\Wishlist\Api\Data\WishlistItemInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class WishlistItemHydrator
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        ProductRepositoryInterface $productRepository
    ) {
        $this->productRepository = $productRepository;
    }

    /**
     * Hydrate wishlist item with product data
     *
     * @param WishlistItemInterface $wishlistItem
     * @return WishlistItemInterface
     */
    public function hydrate(WishlistItemInterface $wishlistItem)
    {
        try {
            $productId = $wishlistItem->getProductId();
            if ($productId) {
                $product = $this->productRepository->getById($productId);
                $extensionAttributes = $wishlistItem->getExtensionAttributes();
                if ($extensionAttributes === null) {
                    $extensionAttributes = $this->getWishlistItemExtensionFactory()->create();
                }
                $extensionAttributes->setProduct($product);
                $wishlistItem->setExtensionAttributes($extensionAttributes);
            }
        } catch (NoSuchEntityException $e) {
            // Product doesn't exist anymore, but we still return the wishlist item
        }
        
        return $wishlistItem;
    }

    /**
     * Get WishlistItemExtension factory
     *
     * @return \Formula\Wishlist\Api\Data\WishlistItemExtensionFactory
     */
    private function getWishlistItemExtensionFactory()
    {
        return \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Formula\Wishlist\Api\Data\WishlistItemExtensionFactory::class);
    }
}