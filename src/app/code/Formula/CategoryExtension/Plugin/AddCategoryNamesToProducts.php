<?php
namespace Formula\CategoryExtension\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class AddCategoryNamesToProducts
{
    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $categoryCache = [];

    /**
     * @param CategoryRepositoryInterface $categoryRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        LoggerInterface $logger
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->logger = $logger;
    }

    /**
     * Add category names to product
     *
     * @param ProductRepositoryInterface $subject
     * @param ProductInterface $product
     * @return ProductInterface
     */
    public function afterGet(
        ProductRepositoryInterface $subject,
        ProductInterface $product
    ) {
        $this->addCategoryNames($product);
        return $product;
    }

    /**
     * Add category names to product search results
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
            $this->addCategoryNames($product);
        }
        
        return $searchResults;
    }

    /**
     * Add category names to a product
     *
     * @param ProductInterface $product
     * @return void
     */
    private function addCategoryNames(ProductInterface $product)
    {
        try {
            $extensionAttributes = $product->getExtensionAttributes();
            
            if ($extensionAttributes) {
                $categoryNames = [];
                
                // Get category links from extension attributes
                $categoryLinks = $extensionAttributes->getCategoryLinks();
                
                if ($categoryLinks) {
                    foreach ($categoryLinks as $categoryLink) {
                        $categoryId = $categoryLink->getCategoryId();
                        $categoryName = $this->getCategoryName($categoryId);
                        
                        if ($categoryName) {
                            $categoryNames[] = $categoryName;
                        }
                    }
                }
                
                // Set category names in extension attributes
                $extensionAttributes->setCategoryNames($categoryNames);
                $product->setExtensionAttributes($extensionAttributes);
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'Error adding category names to product: ' . $product->getSku(),
                ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Get category name by ID with caching
     *
     * @param string|int $categoryId
     * @return string|null
     */
    private function getCategoryName($categoryId): ?string
    {
        // Check cache first
        if (isset($this->categoryCache[$categoryId])) {
            return $this->categoryCache[$categoryId];
        }

        try {
            $category = $this->categoryRepository->get($categoryId);
            $categoryName = $category->getName();
            
            // Cache the result
            $this->categoryCache[$categoryId] = $categoryName;
            
            return $categoryName;
        } catch (NoSuchEntityException $e) {
            $this->logger->warning(
                'Category not found: ' . $categoryId,
                ['exception' => $e->getMessage()]
            );
            
            // Cache null result to avoid repeated lookups
            $this->categoryCache[$categoryId] = null;
            return null;
        } catch (\Exception $e) {
            $this->logger->error(
                'Error getting category name for ID: ' . $categoryId,
                ['exception' => $e->getMessage()]
            );
            return null;
        }
    }
}