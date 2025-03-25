<?php
namespace Formula\Wishlist\Plugin;

use Formula\Wishlist\Api\Data\WishlistItemInterface;
use Formula\Wishlist\Api\WishlistRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Authorization\Model\UserContextInterface;

class WishlistRepositoryPlugin
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @param Session $customerSession
     * @param UserContextInterface $userContext
     */
    public function __construct(
        Session $customerSession,
        UserContextInterface $userContext
    ) {
        $this->customerSession = $customerSession;
        $this->userContext = $userContext;
    }

    /**
     * Get current customer ID from session or token context
     *
     * @return int|null
     */
    private function getCurrentCustomerId()
    {
        if ($this->customerSession->isLoggedIn()) {
            return $this->customerSession->getCustomerId();
        }

        if ($this->userContext->getUserType() == UserContextInterface::USER_TYPE_CUSTOMER) {
            return $this->userContext->getUserId();
        }

        return null;
    }

    /**
     * Set customer ID before saving wishlist item
     *
     * @param WishlistRepositoryInterface $subject
     * @param WishlistItemInterface $wishlistItem
     * @return array
     * @throws LocalizedException
     */
    public function beforeSave(
        WishlistRepositoryInterface $subject,
        WishlistItemInterface $wishlistItem
    ) {
        // Set customer ID if not already set
        if (!$wishlistItem->getCustomerId()) {
            $customerId = $this->getCurrentCustomerId();
            if ($customerId) {
                $wishlistItem->setCustomerId($customerId);
            }
        }

        return [$wishlistItem];
    }

    /**
     * Ensure customer can only access their own wishlist items
     *
     * @param WishlistRepositoryInterface $subject
     * @param callable $proceed
     * @param int $wishlistItemId
     * @return WishlistItemInterface
     * @throws NoSuchEntityException
     */
    public function aroundGetById(
        WishlistRepositoryInterface $subject,
        callable $proceed,
        $wishlistItemId
    ) {
        $wishlistItem = $proceed($wishlistItemId);
        
        $currentCustomerId = $this->getCurrentCustomerId();
        
        // Only allow access to own items
        if ($currentCustomerId && $wishlistItem->getCustomerId() != $currentCustomerId) {
            throw new NoSuchEntityException(__('Wishlist item not found'));
        }
        
        return $wishlistItem;
    }

    /**
     * Filter getByCustomerId to only return items for current customer
     *
     * @param WishlistRepositoryInterface $subject
     * @param callable $proceed
     * @param int $customerId
     * @return array
     */
    public function aroundGetByCustomerId(
        WishlistRepositoryInterface $subject,
        callable $proceed,
        $customerId
    ) {
        // If using API or logged in, ensure only own items are returned
        $currentCustomerId = $this->getCurrentCustomerId();
        if ($currentCustomerId) {
            $customerId = $currentCustomerId;
        }
        
        return $proceed($customerId);
    }
}
