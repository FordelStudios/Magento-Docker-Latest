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
    const URL = 'url';
    const IS_ACTIVE = 'is_active';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const IS_CAROUSEL_BANNER = 'is_carousel_banner';
    const IS_DISCOUNT_BANNER = 'is_discount_banner';
    const IS_SALE_BANNER = 'is_sale_banner';
    const SALE_END_DATE = 'sale_end_date';
    const SALE_START_DATE = 'sale_start_date';

    public function getId();
    public function setId($id);
    public function getCategoryId();
    public function setCategoryId($categoryId);
    public function getBannerImage();
    public function setBannerImage($bannerImage);
    public function getUrl();
    public function setUrl($url);
    public function isActive();
    public function setIsActive($isActive);
    public function getCreatedAt();
    public function setCreatedAt($createdAt);
    public function getUpdatedAt();
    public function setUpdatedAt($updatedAt);
    public function getIsCarouselBanner();
    public function setIsCarouselBanner($isCarouselBanner);
    public function getIsDiscountBanner();
    public function setIsDiscountBanner($isDiscountBanner);
    public function getIsSaleBanner();
    public function setIsSaleBanner($isSaleBanner);

    /**
     * Get sale end date
     * @return string|null
     */
    public function getSaleEndDate();

    /**
     * Set sale end date
     * @param string|null $saleEndDate
     * @return $this
     */
    public function setSaleEndDate($saleEndDate);

    /**
     * Get sale start date
     * @return string|null
     */
    public function getSaleStartDate();

    /**
     * Set sale start date
     * @param string|null $saleStartDate
     * @return $this
     */
    public function setSaleStartDate($saleStartDate);
}
