<?php

namespace Formula\Reel\Api\Data;

interface ReelInterface
{
    const REEL_ID = 'reel_id';
    const TIMER = 'timer';
    const DESCRIPTION = 'description';
    const VIDEO = 'video';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const PRODUCT_IDS    = 'product_ids';
    const CATEGORY_IDS   = 'category_ids';
    const THUMBNAIL      = 'thumbnail';
    const CULTURE        = 'culture';

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Get description
     *
     * @return string|null
     */
    public function getDescription();


    /**
     * Get video
     *
     * @return string|null
     */
    public function getVideo();

    /**
     * Get timer
     *
     * @return string|null
     */
    public function getTimer();

    /**
     * Get creation time
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Get update time
     *
     * @return string|null
     */
    public function getUpdatedAt();


    /**
     * Get product IDs
     *
     * @return string|null
     */
    public function getProductIds();

    /**
     * Get category IDs
     *
     * @return string|null
     */
    public function getCategoryIds();

    /**
     * Get thumbnail
     *
     * @return string|null
     */
    public function getThumbnail();

    /**
     * Set ID
     *
     * @param int $id
     * @return ReelInterface
     */
    public function setId($id);

    /**
     * Set description
     *
     * @param string $description
     * @return ReelInterface
     */
    public function setDescription($description);


    /**
     * Set video
     *
     * @param string $video
     * @return ReelInterface
     */
    public function setVideo($video);

    /**
     * Set timer
     *
     * @param string $timer
     * @return ReelInterface
     */
    public function setTimer($timer);

    /**
     * Set creation time
     *
     * @param string $createdAt
     * @return ReelInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * Set update time
     *
     * @param string $updatedAt
     * @return ReelInterface
     */
    public function setUpdatedAt($updatedAt);


    /**
     * Set product IDs
     *
     * @param string $productIds
     * @return ReelInterface
     */
    public function setProductIds($productIds);

    /**
     * Set category IDs
     *
     * @param string $categoryIds
     * @return ReelInterface
     */
    public function setCategoryIds($categoryIds);

    /**
     * Set thumbnail
     *
     * @param string $thumbnail
     * @return ReelInterface
     */
    public function setThumbnail($thumbnail);

    /**
     * Get culture/country code
     *
     * @return string|null
     */
    public function getCulture();

    /**
     * Set culture/country code
     *
     * @param string|null $culture
     * @return ReelInterface
     */
    public function setCulture($culture);
}

