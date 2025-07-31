<?php
namespace Formula\Wishlist\Model\ResourceModel\WishlistItem;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'wishlist_item_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Formula\Wishlist\Model\WishlistItem::class,
            \Formula\Wishlist\Model\ResourceModel\WishlistItem::class
        );
    }

    /**
     * Add customer filter to collection
     *
     * @param int $customerId
     * @return $this
     */
    public function addCustomerFilter($customerId)
    {
        $this->addFieldToFilter('customer_id', (int)$customerId);
        return $this;
    }

    /**
     * Add product filter to collection
     *
     * @param int $productId
     * @return $this
     */
    public function addProductFilter($productId)
    {
        $this->addFieldToFilter('product_id', (int)$productId);
        return $this;
    }
}