<?php
namespace Formula\CartItemExtension\Plugin;

use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\GuestCartItemRepositoryInterface;
use Formula\Brand\Model\BrandRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Formula\CartItemExtension\Model\Data\ProductMediaFactory;
use Magento\Quote\Api\Data\CartItemExtensionFactory;

class GuestCartItemRepositoryPlugin
{
    protected $brandRepository;
    protected $productRepository;
    protected $productMediaFactory;
    protected $extensionFactory;
    
    public function __construct(
        BrandRepository $brandRepository,
        ProductRepositoryInterface $productRepository,
        ProductMediaFactory $productMediaFactory,
        CartItemExtensionFactory $extensionFactory
    ) {
        $this->brandRepository = $brandRepository;
        $this->productRepository = $productRepository;
        $this->productMediaFactory = $productMediaFactory;
        $this->extensionFactory = $extensionFactory;
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
        if ($result instanceof CartItemInterface) {
            $this->addBrandNameToCartItem($result);
        }
        
        return $result;
    }
    
    /**
     * Add brand_name to cart item
     *
     * @param CartItemInterface $cartItem
     * @return void
     */
    private function addBrandNameToCartItem(CartItemInterface $cartItem)
    {
        try {
            $product = $this->productRepository->get($cartItem->getSku());
            $brandId = $product->getCustomAttribute('brand') ? 
                $product->getCustomAttribute('brand')->getValue() : null;
            $productExtensionAttributes = $product->getExtensionAttributes();
            $productSalableQty = null;
            if ($productExtensionAttributes && method_exists($productExtensionAttributes, 'getSalableQuantity')) {
                $productSalableQty = $productExtensionAttributes->getSalableQuantity();
            }
            
            $extensionAttributes = $cartItem->getExtensionAttributes();
            if ($extensionAttributes === null) {
                $extensionAttributes = $this->extensionFactory->create();
            }

            if ($brandId) {
                try {
                    $brand = $this->brandRepository->getById($brandId);
                    $extensionAttributes->setBrandName($brand->getName());
                } catch (NoSuchEntityException $e) {
                    // Brand not found
                }
            }

            // Set product media entries
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
                }
            } catch (\Throwable $t) {
                // ignore silently for guests
            }

            // Set salable_qty if available
            if ($productSalableQty !== null) {
                $extensionAttributes->setSalableQty((int)$productSalableQty);
            }

            $cartItem->setExtensionAttributes($extensionAttributes);
        } catch (\Exception $e) {
            // Product not found or other error
        }
    }
} 