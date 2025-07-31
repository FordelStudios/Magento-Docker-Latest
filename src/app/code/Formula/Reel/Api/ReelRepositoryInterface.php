<?php
/**
 * @category  Formula
 * @package   Formula_Reel
 */
namespace Formula\Reel\Api;

use Formula\Reel\Api\Data\ReelInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interface ReelRepositoryInterface
 * @api
 */
interface ReelRepositoryInterface
{
    /**
     * Save reel
     *
     * @param ReelInterface $reel
     * @return ReelInterface
     * @throws LocalizedException
     */
    public function save(ReelInterface $reel);

    /**
     * Get reel by ID
     *
     * @param int $reelId
     * @return ReelInterface
     * @throws NoSuchEntityException
     */
    public function getById($reelId);

    /**
     * Get brand list.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Delete reel
     *
     * @param ReelInterface $reel
     * @return bool
     * @throws LocalizedException
     */
    public function delete(ReelInterface $reel);

    /**
     * Delete reel by ID
     *
     * @param int $reelId
     * @return bool
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById($reelId);


    /**
     * Update reel.
     *
     * @param int $reelId
     * @param ReelInterface $reel
     * @return ReelInterface
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     */
    public function update($reelId, ReelInterface $reel);
}