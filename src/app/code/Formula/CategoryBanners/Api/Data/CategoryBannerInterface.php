<?php
// app/code/Formula/CategoryBanners/Api/Data/CategoryBannerInterface.php
namespace Formula\CategoryBanners\Api\Data;

/**
 * @api
 */
interface CategoryBannerInterface
{
    const ENTITY_ID = 'entity_id';
    const CATEGORY_ID = 'category_id';
    const BANNER_IMAGE = 'banner_image';
    const IS_ACTIVE = 'is_active';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const SUBCATEGORIES = 'subcategories';
    const DISCOUNT_PERCENTAGE = 'discount_percentage';


    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Set ID
     *
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * Get category ID
     *
     * @return int
     */
    public function getCategoryId();

    /**
     * Set category ID
     *
     * @param int $categoryId
     * @return $this
     */
    public function setCategoryId($categoryId);

    /**
     * Get banner image
     *
     * @return string
     */
    public function getBannerImage();

    /**
     * Set banner image
     *
     * @param string $bannerImage
     * @return $this
     */
    public function setBannerImage($bannerImage);

    /**
     * Is active
     *
     * @return bool
     */
    public function isActive();

    /**
     * Set is active
     *
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive($isActive);

    /**
     * Get creation time
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * Set creation time
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * Get update time
     *
     * @return string
     */
    public function getUpdatedAt();

    /**
     * Set update time
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);

    /**
     * Get subcategories
     *
     * @return string|null
     */
    public function getSubcategories();

    /**
     * Set subcategories
     *
     * @param string $subcategories
     * @return $this
     */
    public function setSubcategories($subcategories);

    /**
     * Get discount percentage
     *
     * @return int|null
     */
    public function getDiscountPercentage();

    /**
     * Set discount percentage
     *
     * @param int $percentage
     * @return $this
     */
    public function setDiscountPercentage($percentage);
}