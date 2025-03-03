<?php
namespace Formula\Brand\Api;

use Formula\Brand\Api\Data\BrandInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface BrandRepositoryInterface
{
    /**
     * Save brand.
     *
     * @param BrandInterface $brand
     * @return BrandInterface
     */
    public function save(BrandInterface $brand);

    /**
     * Get brand by ID.
     *
     * @param int $brandId
     * @return BrandInterface
     */
    public function getById($brandId);

    /**
     * Get brand list.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Delete brand.
     *
     * @param BrandInterface $brand
     * @return bool
     */
    public function delete(BrandInterface $brand);

    /**
     * Delete brand by ID.
     *
     * @param int $brandId
     * @return bool
     */
    public function deleteById($brandId);
}