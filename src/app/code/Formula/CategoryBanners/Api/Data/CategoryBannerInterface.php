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
    const SUBCATEGORY_NAMES = 'subcategory_names'; // Add this new constant
    const DISCOUNT_PERCENTAGE = 'discount_percentage';

    const IS_CAROUSEL_BANNER = 'is_carousel_banner';

    const IS_DISCOUNT_BANNER = 'is_discount_banner';

    const IS_SALE_BANNER = 'is_sale_banner';



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
     * Get subcategory names
     *
     * @return \Formula\CategoryBanners\Api\Data\SubcategoryInterface[]|null
     */
    public function getSubcategoryNames();

    /**
     * Set subcategory names
     *
     * @param \Formula\CategoryBanners\Api\Data\SubcategoryInterface[] $subcategoryNames
     * @return $this
     */
    public function setSubcategoryNames($subcategoryNames);

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


    /**
     * Get carousel banner
     *
     * @return bool
     */
    public function getIsCarouselBanner();

    /**
     * Set carousel banner
     *
     * @param bool $isCarouselBanner
     * @return $this
     */
    public function setIsCarouselBanner($isCarouselBanner);

    /**
     * Get discount banner
     *
     * @return bool
     */
    public function getIsDiscountBanner();

    /** 
     * Set discount banner
     *
     * @param bool $isDiscountBanner
     * @return $this
     */
    public function setIsDiscountBanner($isDiscountBanner);

    /**
     * Get sale banner
     *
     * @return bool
     */
    public function getIsSaleBanner();

    /**
     * Set sale banner
     *
     * @param bool $isSaleBanner
     * @return $this
     */
    public function setIsSaleBanner($isSaleBanner);
        
}