<?php
namespace Formula\CartItemExtension\Plugin;

use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\GuestCartItemRepositoryInterface;
use Formula\Brand\Model\BrandRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Api\ProductRepositoryInterface;

class GuestCartItemRepositoryPlugin
{
    protected $brandRepository;
    protected $productRepository;
    
    public function __construct(
        BrandRepository $brandRepository,
        ProductRepositoryInterface $productRepository
    ) {
        $this->brandRepository = $brandRepository;
        $this->productRepository = $productRepository;
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
                
            if ($brandId) {
                try {
                    $brand = $this->brandRepository->getById($brandId);
                    $extensionAttributes = $cartItem->getExtensionAttributes();
                    if ($extensionAttributes) {
                        $extensionAttributes->setBrandName($brand->getName());
                        $cartItem->setExtensionAttributes($extensionAttributes);
                    }
                } catch (NoSuchEntityException $e) {
                    // Brand not found
                }
            }
        } catch (\Exception $e) {
            // Product not found or other error
        }
    }
} 