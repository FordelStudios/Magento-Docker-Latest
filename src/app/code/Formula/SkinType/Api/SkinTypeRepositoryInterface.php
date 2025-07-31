<?php
namespace Formula\SkinType\Api;

use Formula\SkinType\Api\Data\SkinTypeInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface SkinTypeRepositoryInterface
{
    /**
     * Save skintype.
     *
     * @param SkinTypeInterface $skintype
     * @return SkinTypeInterface
     */
    public function save(SkinTypeInterface $skintype);

    /**
     * Get skintype by ID.
     *
     * @param int $skintypeId
     * @return SkinTypeInterface
     */
    public function getById($skintypeId);

    /**
     * Get skintype list.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Delete skintype.
     *
     * @param SkinTypeInterface $skintype
     * @return bool
     */
    public function delete(SkinTypeInterface $skintype);

    /**
     * Delete skintype by ID.
     *
     * @param int $skintypeId
     * @return bool
     */
    public function deleteById($skintypeId);

    /**
     * Update skintype.
     *
     * @param int $skintypeId
     * @param SkinTypeInterface $skintype
     * @return SkinTypeInterface
     */
    public function update($skintypeId, SkinTypeInterface $skintype);

}