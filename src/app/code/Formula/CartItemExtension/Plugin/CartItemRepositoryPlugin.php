<?php
namespace Formula\CartItemExtension\Plugin;

use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Formula\Brand\Model\BrandRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Quote\Api\Data\CartItemExtensionFactory;
use Psr\Log\LoggerInterface;

class CartItemRepositoryPlugin
{
    protected $brandRepository;
    protected $productRepository;
    protected $extensionFactory;
    protected $logger;

    public function __construct(
        BrandRepository $brandRepository,
        ProductRepositoryInterface $productRepository,
        CartItemExtensionFactory $extensionFactory,
        LoggerInterface $logger
    ) {
        $this->brandRepository = $brandRepository;
        $this->productRepository = $productRepository;
        $this->extensionFactory = $extensionFactory;
        $this->logger = $logger;
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
     * Add brand_name to cart item
     *
     * @param CartItemInterface $cartItem
     * @return void
     */
    private function addBrandNameToCartItem(CartItemInterface $cartItem)
    {
        try {
            $sku = $cartItem->getSku();
            $this->logger->info("[CartItemExtension] Processing SKU: $sku");

            $product = $this->productRepository->get($sku);
            $brandId = $product->getCustomAttribute('brand') ? 
                $product->getCustomAttribute('brand')->getValue() : null;

            $this->logger->info("[CartItemExtension] Found brand ID: " . ($brandId ?? 'null'));

            if ($brandId) {
                try {
                    $brand = $this->brandRepository->getById($brandId);
                    $brandName = $brand->getName();
                    $this->logger->info("[CartItemExtension] Found brand name: $brandName");

                    $extensionAttributes = $cartItem->getExtensionAttributes();
                    if ($extensionAttributes === null) {
                        $this->logger->info("[CartItemExtension] Creating new extension attributes");
                        $extensionAttributes = $this->extensionFactory->create();
                    }

                    $extensionAttributes->setBrandName($brandName);
                    $cartItem->setExtensionAttributes($extensionAttributes);
                    $this->logger->info("[CartItemExtension] brand_name set successfully");

                } catch (NoSuchEntityException $e) {
                    $this->logger->warning("[CartItemExtension] Brand not found: " . $e->getMessage());
                }
            } else {
                $this->logger->info("[CartItemExtension] No brand ID found for product");
            }
        } catch (\Exception $e) {
            $this->logger->error("[CartItemExtension] Error setting brand_name: " . $e->getMessage());
        }
    }
}
