<?php
namespace Formula\Review\Model\Data;

use Formula\Review\Api\Data\CustomerPurchaseVerificationInterface;
use Magento\Framework\DataObject;

/**
 * Customer Purchase Verification Data Model
 */
class CustomerPurchaseVerification extends DataObject implements CustomerPurchaseVerificationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getHasPurchased()
    {
        return (bool)$this->getData(self::HAS_PURCHASED);
    }

    /**
     * {@inheritdoc}
     */
    public function setHasPurchased($hasPurchased)
    {
        return $this->setData(self::HAS_PURCHASED, (bool)$hasPurchased);
    }

    /**
     * {@inheritdoc}
     */
    public function getProductSku()
    {
        return (string)$this->getData(self::PRODUCT_SKU);
    }

    /**
     * {@inheritdoc}
     */
    public function setProductSku($sku)
    {
        return $this->setData(self::PRODUCT_SKU, $sku);
    }

    /**
     * {@inheritdoc}
     */
    public function getProductId()
    {
        return $this->getData(self::PRODUCT_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setProductId($productId)
    {
        return $this->setData(self::PRODUCT_ID, $productId ? (int)$productId : null);
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerId()
    {
        return (int)$this->getData(self::CUSTOMER_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, (int)$customerId);
    }

    /**
     * {@inheritdoc}
     */
    public function getPurchaseCount()
    {
        return (int)$this->getData(self::PURCHASE_COUNT);
    }

    /**
     * {@inheritdoc}
     */
    public function setPurchaseCount($count)
    {
        return $this->setData(self::PURCHASE_COUNT, (int)$count);
    }

    /**
     * {@inheritdoc}
     */
    public function getLastPurchaseDate()
    {
        return $this->getData(self::LAST_PURCHASE_DATE);
    }

    /**
     * {@inheritdoc}
     */
    public function setLastPurchaseDate($date)
    {
        return $this->setData(self::LAST_PURCHASE_DATE, $date);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrderIds()
    {
        return (array)$this->getData(self::ORDER_IDS);
    }

    /**
     * {@inheritdoc}
     */
    public function setOrderIds(array $orderIds)
    {
        return $this->setData(self::ORDER_IDS, $orderIds);
    }
}