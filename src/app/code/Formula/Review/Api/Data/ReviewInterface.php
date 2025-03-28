<?php
namespace Formula\Review\Api\Data;

interface ReviewInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title);

    /**
     * @return string
     */
    public function getNickname();

    /**
     * @param string $nickname
     * @return $this
     */
    public function setNickname($nickname);

    /**
     * @return string
     */
    public function getDetail();

    /**
     * @param string $detail
     * @return $this
     */
    public function setDetail($detail);

    /**
     * @return int
     */
    public function getRatings();

    /**
     * @param int $ratings
     * @return $this
     */
    public function setRatings($ratings);
    
    /**
     * @return array
     */
    public function getRatingsDetails();
    
    /**
     * @param array $ratingsDetails
     * @return $this
     */
    public function setRatingsDetails($ratingsDetails);

    /**
     * @return string
     */
    public function getProductSku();

    /**
     * @param string $productSku
     * @return $this
     */
    public function setProductSku($productSku);
    
    /**
     * @return string
     */
    public function getStatus();
    
    /**
     * @param string $status
     * @return $this
     */
    public function setStatus($status);
    
    /**
     * @return string
     */
    public function getCreatedAt();
    
    /**
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);
    
    /**
     * @return string
     */
    public function getUpdatedAt();
    
    /**
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);
}