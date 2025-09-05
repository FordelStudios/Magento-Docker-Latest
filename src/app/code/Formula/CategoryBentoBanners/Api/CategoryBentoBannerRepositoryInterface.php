<?php
namespace Formula\CategoryBentoBanners\Api;

use Formula\CategoryBentoBanners\Api\Data\CategoryBentoBannerInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * @api
 */
interface CategoryBentoBannerRepositoryInterface
{
    /**
     * Save bento banner
     *
     * @param CategoryBentoBannerInterface $bentoBanner
     * @return CategoryBentoBannerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(CategoryBentoBannerInterface $bentoBanner);

    /**
     * Get bento banner by ID
     *
     * @param int $bentoBannerId
     * @return CategoryBentoBannerInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($bentoBannerId);

    /**
     * Get list
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Formula\CategoryBentoBanners\Api\Data\CategoryBentoBannerSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Delete bento banner
     *
     * @param CategoryBentoBannerInterface $bentoBanner
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(CategoryBentoBannerInterface $bentoBanner);

    /**
     * Delete bento banner by ID
     *
     * @param int $bentoBannerId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($bentoBannerId);

    /**
     * Get bento banners by category ID
     *
     * @param int $categoryId
     * @return CategoryBentoBannerInterface[]
     */
    public function getByCategoryId($categoryId);

    /**
     * Get all bento banners
     *
     * @return CategoryBentoBannerInterface[]
     */
    public function getAllBentoBanners();
}