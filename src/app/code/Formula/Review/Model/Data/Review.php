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
}