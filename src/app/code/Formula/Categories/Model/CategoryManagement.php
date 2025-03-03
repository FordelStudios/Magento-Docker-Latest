<?php
namespace Formula\Categories\Model;

use Formula\Categories\Api\CategoryManagementInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;

class CategoryManagement implements CategoryManagementInterface
{
    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;
    
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        ProductRepositoryInterface $productRepository
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicCategories($categoryId)
    {
        try {
            $category = $this->categoryRepository->get($categoryId);
            return $category->getProductLinks();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getProtectedCategories()
    {
        return ['message' => 'This is protected data'];
    }
}