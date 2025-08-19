<?php
namespace Formula\CartItemExtension\Plugin;

use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\GuestCartItemRepositoryInterface;
use Formula\Brand\Model\BrandRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Formula\CartItemExtension\Model\Data\ProductMediaFactory;
use Magento\Quote\Api\Data\CartItemExtensionFactory;
use Psr\Log\LoggerInterface;

class GuestCartItemRepositoryPlugin
{
    protected $brandRepository;
    protected $productRepository;
    protected $productMediaFactory;
    protected $extensionFactory;
    protected $logger;
    
    public function __construct(
        BrandRepository $brandRepository,
        ProductRepositoryInterface $productRepository,
        ProductMediaFactory $productMediaFactory,
        CartItemExtensionFactory $extensionFactory,
        LoggerInterface $logger
    ) {
        $this->brandRepository = $brandRepository;
        $this->productRepository = $productRepository;
        $this->productMediaFactory = $productMediaFactory;
        $this->extensionFactory = $extensionFactory;
        $this->logger = $logger;
    }
    
    /**
     * Add brand_name to guest cart items
     *
     * @param GuestCartItemRepositoryInterface $subject
     * @param CartItemInterface[] $result
     * @return CartItemInterface[]
     */
    public function afterGetList(
        GuestCartItemRepositoryInterface $subject,
        $result
    ) {
        $this->logger->info('[CartItemExtension] Guest afterGetList triggered');
        if (is_array($result)) {
            foreach ($result as $cartItem) {
                $this->addBrandNameToCartItem($cartItem);
            }
        }
        
        return $result;
    }
    
    /**
     * Add brand_name to a single guest cart item
     *
     * @param GuestCartItemRepositoryInterface $subject
     * @param CartItemInterface $result
     * @return CartItemInterface
     */
    public function afterGet(
        GuestCartItemRepositoryInterface $subject,
        $result
    ) {
        $this->logger->info('[CartItemExtension] Guest afterGet triggered');
        if ($result instanceof CartItemInterface) {
            $this->addBrandNameToCartItem($result);
        }
        
        return $result;
    }
    
    /**
     * Add brand_name and productId to cart item
     *
     * @param CartItemInterface $cartItem
     * @return void
     */
    private function addBrandNameToCartItem(CartItemInterface $cartItem)
    {
        try {
            $sku = $cartItem->getSku();
            $this->logger->info("[CartItemExtension] Guest Processing SKU: $sku");

            if (!$sku) {
                $this->logger->warning("[CartItemExtension] Guest No SKU found for cart item");
                return;
            }

            try {
                $product = $this->productRepository->get($sku);
                $productId = $product->getId();
                $productExtensionAttributes = $product->getExtensionAttributes();
                $productSalableQty = null;
                if ($productExtensionAttributes && method_exists($productExtensionAttributes, 'getSalableQty')) {
                    $productSalableQty = $productExtensionAttributes->getSalableQty();
                }
                $brandId = $product->getCustomAttribute('brand') ? 
                    $product->getCustomAttribute('brand')->getValue() : null;

                $this->logger->info("[CartItemExtension] Guest Found product ID: $productId");
                $this->logger->info("[CartItemExtension] Guest Found brand ID: " . ($brandId ?? 'null'));

                $extensionAttributes = $cartItem->getExtensionAttributes();
                if ($extensionAttributes === null) {
                    $this->logger->info("[CartItemExtension] Guest Creating new extension attributes");
                    $extensionAttributes = $this->extensionFactory->create();
                }

                // Only set productId if it's valid and greater than 0
                if ($productId && $productId > 0) {
                    $extensionAttributes->setProductIdDisplay($productId);
                    $this->logger->info("[CartItemExtension] Guest productId set successfully: $productId");
                } else {
                    $this->logger->warning("[CartItemExtension] Guest Invalid product ID: $productId");
                }

                if ($brandId) {
                    try {
                        $brand = $this->brandRepository->getById($brandId);
                        $brandName = $brand->getName();
                        $this->logger->info("[CartItemExtension] Guest Found brand name: $brandName");

                        $extensionAttributes->setBrandName($brandName);
                        $this->logger->info("[CartItemExtension] Guest brand_name set successfully");

                    } catch (NoSuchEntityException $e) {
                        $this->logger->warning("[CartItemExtension] Guest Brand not found: " . $e->getMessage());
                    }
                } else {
                    $this->logger->info("[CartItemExtension] Guest No brand ID found for product");
                }

                // Set salable_qty if available
                if ($productSalableQty !== null) {
                    $extensionAttributes->setSalableQty((int) $productSalableQty);
                    $this->logger->info('[CartItemExtension] Guest salable_qty set: ' . (int)$productSalableQty);
                } else {
                    $this->logger->info('[CartItemExtension] Guest salable_qty not available from product extension attributes');
                }

                // Populate product media (id and file only)
                try {
                    $mediaEntries = $product->getMediaGalleryEntries();
                    if (is_array($mediaEntries) && !empty($mediaEntries)) {
                        $mediaDataItems = [];
                        foreach ($mediaEntries as $mediaEntry) {
                            $mediaItem = $this->productMediaFactory->create();
                            $mediaItem->setId($mediaEntry->getId());
                            $mediaItem->setFile($mediaEntry->getFile());
                            $mediaDataItems[] = $mediaItem;
                        }
                        $extensionAttributes->setProductMedia($mediaDataItems);
                        $this->logger->info('[CartItemExtension] Guest product_media set with ' . count($mediaDataItems) . ' entries');
                    } else {
                        $this->logger->info('[CartItemExtension] Guest No media gallery entries for product');
                    }
                } catch (\Throwable $t) {
                    $this->logger->warning('[CartItemExtension] Guest Failed to set product_media: ' . $t->getMessage());
                }

                $cartItem->setExtensionAttributes($extensionAttributes);

            } catch (NoSuchEntityException $e) {
                $this->logger->warning("[CartItemExtension] Guest Product not found for SKU: $sku - " . $e->getMessage());
                // Don't set extension attributes if product doesn't exist
                return;
            }

        } catch (\Exception $e) {
            $this->logger->error("[CartItemExtension] Guest Error setting extension attributes: " . $e->getMessage());
        }
    }
} 