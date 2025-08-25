<?php
namespace Formula\BodyConcern\Api;

use Formula\BodyConcern\Api\Data\BodyConcernInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface BodyConcernRepositoryInterface
{
    /**
     * Save bodyconcern.
     *
     * @param BodyConcernInterface $bodyconcern
     * @return BodyConcernInterface
     */
    public function save(BodyConcernInterface $bodyconcern);

    /**
     * Get bodyconcern by ID.
     *
     * @param int $bodyconcernId
     * @return BodyConcernInterface
     */
    public function getById($bodyconcernId);

    /**
     * Get bodyconcern list.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Delete bodyconcern.
     *
     * @param BodyConcernInterface $bodyconcern
     * @return bool
     */
    public function delete(BodyConcernInterface $bodyconcern);

    /**
     * Delete bodyconcern by ID.
     *
     * @param int $bodyconcernId
     * @return bool
     */
    public function deleteById($bodyconcernId);

    /**
     * Update bodyconcern.
     *
     * @param int $bodyconcernId
     * @param BodyConcernInterface $bodyconcern
     * @return BodyConcernInterface
     */
    public function update($bodyconcernId, BodyConcernInterface $bodyconcern);

}