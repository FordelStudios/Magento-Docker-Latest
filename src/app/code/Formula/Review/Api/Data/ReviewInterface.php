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
     * @return string
     */
    public function getProductSku();

    /**
     * @param string $productSku
     * @return $this
     */
    public function setProductSku($productSku);
}