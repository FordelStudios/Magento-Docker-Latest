<?php
namespace Formula\SkinConcern\Api;

use Formula\SkinConcern\Api\Data\SkinConcernInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface SkinConcernRepositoryInterface
{
    /**
     * Save skinconcern.
     *
     * @param SkinConcernInterface $skinconcern
     * @return SkinConcernInterface
     */
    public function save(SkinConcernInterface $skinconcern);

    /**
     * Get skinconcern by ID.
     *
     * @param int $skinconcernId
     * @return SkinConcernInterface
     */
    public function getById($skinconcernId);

    /**
     * Get skinconcern list.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Delete skinconcern.
     *
     * @param SkinConcernInterface $skinconcern
     * @return bool
     */
    public function delete(SkinConcernInterface $skinconcern);

    /**
     * Delete skinconcern by ID.
     *
     * @param int $skinconcernId
     * @return bool
     */
    public function deleteById($skinconcernId);

    /**
     * Update skinconcern.
     *
     * @param int $skinconcernId
     * @param SkinConcernInterface $skinconcern
     * @return SkinConcernInterface
     */
    public function update($skinconcernId, SkinConcernInterface $skinconcern);

}