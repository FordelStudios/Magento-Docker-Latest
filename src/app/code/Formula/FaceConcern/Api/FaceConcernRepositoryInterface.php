<?php
namespace Formula\FaceConcern\Api;

use Formula\FaceConcern\Api\Data\FaceConcernInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface FaceConcernRepositoryInterface
{
    /**
     * Save faceconcern.
     *
     * @param FaceConcernInterface $faceconcern
     * @return FaceConcernInterface
     */
    public function save(FaceConcernInterface $faceconcern);

    /**
     * Get faceconcern by ID.
     *
     * @param int $faceconcernId
     * @return FaceConcernInterface
     */
    public function getById($faceconcernId);

    /**
     * Get faceconcern list.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Delete faceconcern.
     *
     * @param FaceConcernInterface $faceconcern
     * @return bool
     */
    public function delete(FaceConcernInterface $faceconcern);

    /**
     * Delete faceconcern by ID.
     *
     * @param int $faceconcernId
     * @return bool
     */
    public function deleteById($faceconcernId);

    /**
     * Update faceconcern.
     *
     * @param int $faceconcernId
     * @param FaceConcernInterface $faceconcern
     * @return FaceConcernInterface
     */
    public function update($faceconcernId, FaceConcernInterface $faceconcern);

}