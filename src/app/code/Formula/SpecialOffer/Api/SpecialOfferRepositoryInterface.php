<?php
declare(strict_types=1);

namespace Formula\SpecialOffer\Api;

use Formula\SpecialOffer\Api\Data\SpecialOfferInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

interface SpecialOfferRepositoryInterface
{
    /**
     * Get special offer by ID
     *
     * @param int $entityId
     * @return SpecialOfferInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): SpecialOfferInterface;

    /**
     * Get list of special offers
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SpecialOfferInterface[]
     */
    public function getList(SearchCriteriaInterface $searchCriteria): array;

    /**
     * Get active special offers (filtered by date and is_active)
     *
     * @return SpecialOfferInterface[]
     */
    public function getActiveOffers(): array;

    /**
     * Save special offer
     *
     * @param SpecialOfferInterface $specialOffer
     * @return SpecialOfferInterface
     * @throws CouldNotSaveException
     */
    public function save(SpecialOfferInterface $specialOffer): SpecialOfferInterface;

    /**
     * Delete special offer
     *
     * @param SpecialOfferInterface $specialOffer
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(SpecialOfferInterface $specialOffer): bool;

    /**
     * Delete special offer by ID
     *
     * @param int $entityId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $entityId): bool;
}
