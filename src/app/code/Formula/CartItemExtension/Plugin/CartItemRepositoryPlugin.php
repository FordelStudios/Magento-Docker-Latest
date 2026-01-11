<?php
namespace Formula\CartItemExtension\Plugin;

use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Formula\Brand\Model\BrandRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Quote\Api\Data\CartItemExtensionFactory;
use Psr\Log\LoggerInterface;
use Formula\CartItemExtension\Model\Data\ProductMediaFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;

class CartItemRepositoryPlugin
{
    protected $brandRepository;
    protected $productRepository;
    protected $extensionFactory;
    protected $logger;
    protected $productMediaFactory;
    protected $categoryRepository;

    public function __construct(
        BrandRepository $brandRepository,
        ProductRepositoryInterface $productRepository,
        CartItemExtensionFactory $extensionFactory,
        LoggerInterface $logger,
        ProductMediaFactory $productMediaFactory,
        CategoryRepositoryInterface $categoryRepository
    ) {
        $this->brandRepository = $brandRepository;
        $this->productRepository = $productRepository;
        $this->extensionFactory = $extensionFactory;
        $this->logger = $logger;
        $this->productMediaFactory = $productMediaFactory;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Add brand_name to cart items
     *
     * @param CartItemRepositoryInterface $subject
     * @param CartItemInterface[] $result
     * @return CartItemInterface[]
     */
    public function afterGetList(
        CartItemRepositoryInterface $subject,
        $result
    ) {
        $this->logger->info('[CartItemExtension] afterGetList triggered');
        if (is_array($result)) {
            foreach ($result as $cartItem) {
                $this->addBrandNameToCartItem($cartItem);
            }
        }
        return $result;
    }

    /**
     * Add brand_name to a single cart item
     *
     * @param CartItemRepositoryInterface $subject
     * @param CartItemInterface $result
     * @return CartItemInterface
     */
    public function afterGet(
        CartItemRepositoryInterface $subject,
        $result
    ) {
        $this->logger->info('[CartItemExtension] afterGet triggered');
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
            $this->logger->info("[CartItemExtension] Processing SKU: $sku");

            if (!$sku) {
                $this->logger->warning("[CartItemExtension] No SKU found for cart item");
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

                $this->logger->info("[CartItemExtension] Found product ID: $productId");
                $this->logger->info("[CartItemExtension] Found brand ID: " . ($brandId ?? 'null'));

                $extensionAttributes = $cartItem->getExtensionAttributes();
                if ($extensionAttributes === null) {
                    $this->logger->info("[CartItemExtension] Creating new extension attributes");
                    $extensionAttributes = $this->extensionFactory->create();
                }

                // Only set productId if it's valid and greater than 0
                if ($productId && $productId > 0) {
                    $extensionAttributes->setProductIdDisplay($productId);
                    $this->logger->info("[CartItemExtension] productId set successfully: $productId");
                } else {
                    $this->logger->warning("[CartItemExtension] Invalid product ID: $productId");
                }

                if ($brandId) {
                    try {
                        $brand = $this->brandRepository->getById($brandId);
                        $brandName = $brand->getName();
                        $this->logger->info("[CartItemExtension] Found brand name: $brandName");

                        $extensionAttributes->setBrandName($brandName);
                        $this->logger->info("[CartItemExtension] brand_name set successfully");

                    } catch (NoSuchEntityException $e) {
                        $this->logger->warning("[CartItemExtension] Brand not found: " . $e->getMessage());
                    }
                } else {
                    $this->logger->info("[CartItemExtension] No brand ID found for product");
                }

                // Set salable_qty if available
                if ($productSalableQty !== null) {
                    $extensionAttributes->setSalableQty((int) $productSalableQty);
                    $this->logger->info('[CartItemExtension] salable_qty set: ' . (int)$productSalableQty);
                } else {
                    $this->logger->info('[CartItemExtension] salable_qty not available from product extension attributes');
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
                        $this->logger->info('[CartItemExtension] product_media set with ' . count($mediaDataItems) . ' entries');
                    } else {
                        $this->logger->info('[CartItemExtension] No media gallery entries for product');
                    }
                } catch (\Throwable $t) {
                    $this->logger->warning('[CartItemExtension] Failed to set product_media: ' . $t->getMessage());
                }

                // Populate category_names
                try {
                    $categoryIds = $product->getCategoryIds();
                    if (is_array($categoryIds) && !empty($categoryIds)) {
                        $categoryNames = [];
                        foreach ($categoryIds as $categoryId) {
                            try {
                                $category = $this->categoryRepository->get($categoryId);
                                $categoryName = $category->getName();
                                // Skip "Categories" root category
                                if ($categoryName && $categoryName !== 'Categories') {
                                    $categoryNames[] = $categoryName;
                                }
                            } catch (NoSuchEntityException $e) {
                                $this->logger->warning('[CartItemExtension] Category not found: ' . $categoryId);
                            }
                        }
                        if (!empty($categoryNames)) {
                            $extensionAttributes->setCategoryNames($categoryNames);
                            $this->logger->info('[CartItemExtension] category_names set: ' . implode(', ', $categoryNames));
                        }
                    } else {
                        $this->logger->info('[CartItemExtension] No category IDs for product');
                    }
                } catch (\Throwable $t) {
                    $this->logger->warning('[CartItemExtension] Failed to set category_names: ' . $t->getMessage());
                }

                $cartItem->setExtensionAttributes($extensionAttributes);

            } catch (NoSuchEntityException $e) {
                $this->logger->warning("[CartItemExtension] Product not found for SKU: $sku - " . $e->getMessage());
                // Don't set extension attributes if product doesn't exist
                return;
            }

        } catch (\Exception $e) {
            $this->logger->error("[CartItemExtension] Error setting extension attributes: " . $e->getMessage());
        }
    }
}
