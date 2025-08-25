<?php
namespace Formula\HairConcern\Api;

use Formula\HairConcern\Api\Data\HairConcernInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface HairConcernRepositoryInterface
{
    /**
     * Save hairconcern.
     *
     * @param HairConcernInterface $hairconcern
     * @return HairConcernInterface
     */
    public function save(HairConcernInterface $hairconcern);

    /**
     * Get hairconcern by ID.
     *
     * @param int $hairconcernId
     * @return HairConcernInterface
     */
    public function getById($hairconcernId);

    /**
     * Get hairconcern list.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Delete hairconcern.
     *
     * @param HairConcernInterface $hairconcern
     * @return bool
     */
    public function delete(HairConcernInterface $hairconcern);

    /**
     * Delete hairconcern by ID.
     *
     * @param int $hairconcernId
     * @return bool
     */
    public function deleteById($hairconcernId);

    /**
     * Update hairconcern.
     *
     * @param int $hairconcernId
     * @param HairConcernInterface $hairconcern
     * @return HairConcernInterface
     */
    public function update($hairconcernId, HairConcernInterface $hairconcern);

}