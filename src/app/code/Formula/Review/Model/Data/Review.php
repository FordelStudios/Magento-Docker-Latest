<?php
namespace Formula\Review\Model\Data;

use Formula\Review\Api\Data\ReviewInterface;
use Magento\Framework\Api\AbstractExtensibleObject;

class Review extends AbstractExtensibleObject implements ReviewInterface
{
    /**
     * @return int
     */
    public function getId()
    {
        return $this->_get('id');
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        return $this->setData('id', $id);
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->_get('title');
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        return $this->setData('title', $title);
    }

    /**
     * @return string
     */
    public function getNickname()
    {
        return $this->_get('nickname');
    }

    /**
     * @param string $nickname
     * @return $this
     */
    public function setNickname($nickname)
    {
        return $this->setData('nickname', $nickname);
    }

    /**
     * @return string
     */
    public function getDetail()
    {
        return $this->_get('detail');
    }

    /**
     * @param string $detail
     * @return $this
     */
    public function setDetail($detail)
    {
        return $this->setData('detail', $detail);
    }

    /**
     * @return int
     */
    public function getRatings()
    {
        return $this->_get('ratings');
    }

    /**
     * @param int $ratings
     * @return $this
     */
    public function setRatings($ratings)
    {
        return $this->setData('ratings', $ratings);
    }
    
    /**
     * @return array
     */
    public function getRatingsDetails()
    {
        return $this->_get('ratings_details');
    }
    
    /**
     * @param array $ratingsDetails
     * @return $this
     */
    public function setRatingsDetails($ratingsDetails)
    {
        return $this->setData('ratings_details', $ratingsDetails);
    }

    /**
     * @return string
     */
    public function getProductSku()
    {
        return $this->_get('product_sku');
    }

    /**
     * @param string $productSku
     * @return $this
     */
    public function setProductSku($productSku)
    {
        return $this->setData('product_sku', $productSku);
    }
    
    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->_get('status');
    }
    
    /**
     * @param string $status
     * @return $this
     */
    public function setStatus($status)
    {
        return $this->setData('status', $status);
    }
    
    /**
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->_get('created_at');
    }
    
    /**
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData('created_at', $createdAt);
    }
    
    /**
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->_get('updated_at');
    }
    
    /**
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData('updated_at', $updatedAt);
    }

    /**
     * @return bool
     */
    public function getIsRecommended()
    {
        return $this->_get('is_recommended');
    }

    /**
     * @param bool $isRecommended
     * @return $this
     */
    public function setIsRecommended($isRecommended)
    {
        return $this->setData('is_recommended', $isRecommended);
    }

    /**
     * @return string[]
     */
    public function getImages()
    {
        return $this->_get('images');
    }

    /**
     * @param string[] $images
     * @return $this
     */
    public function setImages($images)
    {
        return $this->setData('images', $images);
    }
}