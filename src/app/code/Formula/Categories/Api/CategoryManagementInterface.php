<?php
namespace Formula\Categories\Api;

interface CategoryManagementInterface
{
    /**
     * Get public category data
     *
     * @param int $categoryId
     * @return \Magento\Catalog\Api\Data\CategoryProductLinkInterface[]
     */
    public function getPublicCategories($categoryId);

    /**
     * Get protected category data
     *
     * @return mixed
     */
    public function getProtectedCategories();
}