<?php
namespace Formula\Review\Model\Data;

use Formula\Review\Api\Data\CustomerReviewStatusInterface;
use Magento\Framework\DataObject;

/**
 * Customer Review Status Data Model
 */
class CustomerReviewStatus extends DataObject implements CustomerReviewStatusInterface
{
    /**
     * {@inheritdoc}
     */
    public function getHasReview()
    {
        return (bool)$this->getData(self::HAS_REVIEW);
    }

    /**
     * {@inheritdoc}
     */
    public function setHasReview($hasReview)
    {
        return $this->setData(self::HAS_REVIEW, (bool)$hasReview);
    }

    /**
     * {@inheritdoc}
     */
    public function getReviewId()
    {
        return $this->getData(self::REVIEW_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setReviewId($reviewId)
    {
        return $this->setData(self::REVIEW_ID, $reviewId);
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
}