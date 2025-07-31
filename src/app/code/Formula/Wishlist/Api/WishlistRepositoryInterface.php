<?php
namespace Formula\Wishlist\Api;

use Formula\Wishlist\Api\Data\WishlistItemInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface WishlistRepositoryInterface
{
    /**
     * Save wishlist item
     *
     * @param WishlistItemInterface $wishlistItem
     * @return WishlistItemInterface
     * @throws LocalizedException
     */
    public function save(WishlistItemInterface $wishlistItem);

    /**
     * Get wishlist item by ID
     *
     * @param int $wishlistItemId
     * @return WishlistItemInterface
     * @throws NoSuchEntityException
     */
    public function getById($wishlistItemId);

    /**
     * Retrieve wishlist items matching the specified criteria
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Get wishlist items by customer ID
     *
     * @param int $customerId
     * @return WishlistItemInterface[]
     */
    public function getByCustomerId($customerId);

    /**
     * Delete wishlist item
     *
     * @param WishlistItemInterface $wishlistItem
     * @return bool
     * @throws LocalizedException
     */
    public function delete(WishlistItemInterface $wishlistItem);

    /**
     * Delete wishlist item by ID
     *
     * @param int $wishlistItemId
     * @return bool
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById($wishlistItemId);
}