<?php
declare(strict_types=1);

namespace Formula\RelatedProducts\Model;

use Formula\RelatedProducts\Api\RelatedProductsInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Link;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

class RelatedProducts implements RelatedProductsInterface
{
    private const FALLBACK_LIMIT = 8;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param ProductCollectionFactory $productCollectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        ProductCollectionFactory $productCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->productRepository = $productRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     */
    public function getRelatedProducts(string $sku): array
    {
        $products = $this->getLinkedProducts($sku, Link::LINK_TYPE_RELATED);

        // If no manual related products, use fallback recommendations
        if (empty($products)) {
            $products = $this->getFallbackProducts($sku, 'related');
        }

        return $products;
    }

    /**
     * @inheritdoc
     */
    public function getUpsellProducts(string $sku): array
    {
        $products = $this->getLinkedProducts($sku, Link::LINK_TYPE_UPSELL);

        // If no manual upsell products, use fallback recommendations
        if (empty($products)) {
            $products = $this->getFallbackProducts($sku, 'upsell');
        }

        return $products;
    }

    /**
     * @inheritdoc
     */
    public function getCrosssellProducts(string $sku): array
    {
        $products = $this->getLinkedProducts($sku, Link::LINK_TYPE_CROSSSELL);

        // If no manual crosssell products, use fallback recommendations
        if (empty($products)) {
            $products = $this->getFallbackProducts($sku, 'crosssell');
        }

        return $products;
    }

    /**
     * @inheritdoc
     */
    public function getAllLinkedProducts(string $sku)
    {
        return [
            'related' => $this->getRelatedProducts($sku),
            'upsell' => $this->getUpsellProducts($sku),
            'crosssell' => $this->getCrosssellProducts($sku)
        ];
    }

    /**
     * Get linked products by type with full details
     *
     * @param string $sku
     * @param int $linkType
     * @return ProductInterface[]
     * @throws NoSuchEntityException
     */
    private function getLinkedProducts(string $sku, int $linkType): array
    {
        $product = $this->productRepository->get($sku);
        $linkedProductIds = [];

        switch ($linkType) {
            case Link::LINK_TYPE_RELATED:
                $linkedProducts = $product->getRelatedProducts();
                break;
            case Link::LINK_TYPE_UPSELL:
                $linkedProducts = $product->getUpSellProducts();
                break;
            case Link::LINK_TYPE_CROSSSELL:
                $linkedProducts = $product->getCrossSellProducts();
                break;
            default:
                $linkedProducts = [];
        }

        foreach ($linkedProducts as $linkedProduct) {
            $linkedProductIds[] = $linkedProduct->getId();
        }

        if (empty($linkedProductIds)) {
            return [];
        }

        return $this->getFullProductDetails($linkedProductIds);
    }

    /**
     * Get fallback recommended products when no manual links exist
     *
     * @param string $sku
     * @param string $type (related, upsell, crosssell)
     * @return ProductInterface[]
     * @throws NoSuchEntityException
     */
    private function getFallbackProducts(string $sku, string $type): array
    {
        $product = $this->productRepository->get($sku);
        $productId = $product->getId();
        $categoryIds = $product->getCategoryIds();
        $price = (float)$product->getPrice();

        // Get brand attribute if available
        $brandId = null;
        $brandAttribute = $product->getCustomAttribute('brand');
        if ($brandAttribute) {
            $brandId = $brandAttribute->getValue();
        }

        // Build collection based on type
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter('entity_id', ['neq' => $productId]);
        $collection->addAttributeToFilter('status', Status::STATUS_ENABLED);
        $collection->addAttributeToFilter('visibility', [
            'in' => [
                Visibility::VISIBILITY_IN_CATALOG,
                Visibility::VISIBILITY_BOTH
            ]
        ]);

        // Filter by category (products in same category)
        if (!empty($categoryIds)) {
            $collection->addCategoriesFilter(['in' => $categoryIds]);
        }

        // Different strategies based on type
        switch ($type) {
            case 'upsell':
                // Upsell: Higher priced products (10% to 50% more expensive)
                if ($price > 0) {
                    $minPrice = $price * 1.1;
                    $maxPrice = $price * 1.5;
                    $collection->addAttributeToFilter('price', ['from' => $minPrice, 'to' => $maxPrice]);
                }
                $collection->setOrder('price', 'ASC');
                break;

            case 'crosssell':
                // Cross-sell: Complementary products (different category, same brand or similar price)
                if ($brandId) {
                    $collection->addAttributeToFilter('brand', $brandId);
                }
                // Similar price range (50% to 150% of current price)
                if ($price > 0) {
                    $minPrice = $price * 0.5;
                    $maxPrice = $price * 1.5;
                    $collection->addAttributeToFilter('price', ['from' => $minPrice, 'to' => $maxPrice]);
                }
                $collection->getSelect()->orderRand();
                break;

            case 'related':
            default:
                // Related: Same brand preferred, or same category
                if ($brandId) {
                    // Try same brand first
                    $brandCollection = clone $collection;
                    $brandCollection->addAttributeToFilter('brand', $brandId);
                    $brandCollection->setPageSize(self::FALLBACK_LIMIT);

                    if ($brandCollection->getSize() >= 4) {
                        $collection = $brandCollection;
                    }
                }
                $collection->getSelect()->orderRand();
                break;
        }

        $collection->setPageSize(self::FALLBACK_LIMIT);

        $productIds = $collection->getAllIds();

        if (empty($productIds)) {
            return [];
        }

        return $this->getFullProductDetails($productIds);
    }

    /**
     * Get full product details for array of product IDs
     *
     * @param array $productIds
     * @return ProductInterface[]
     */
    private function getFullProductDetails(array $productIds): array
    {
        $products = [];
        $storeId = $this->storeManager->getStore()->getId();

        foreach ($productIds as $productId) {
            try {
                $fullProduct = $this->productRepository->getById(
                    $productId,
                    false,
                    $storeId
                );
                $products[] = $fullProduct;
            } catch (NoSuchEntityException $e) {
                // Skip if product not found
                continue;
            }
        }

        return $products;
    }
}
