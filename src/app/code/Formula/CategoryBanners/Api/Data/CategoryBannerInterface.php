<?php
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

    /**
     * @return int|null
     */
    public function getId();

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * @return int|null
     */
    public function getCategoryId();

    /**
     * @param int $categoryId
     * @return $this
     */
    public function setCategoryId($categoryId);

    /**
     * @return string|null
     */
    public function getBannerImage();

    /**
     * @param string $bannerImage
     * @return $this
     */
    public function setBannerImage($bannerImage);

    /**
     * @return string|null
     */
    public function getUrl();

    /**
     * @param string|null $url
     * @return $this
     */
    public function setUrl($url);

    /**
     * @return bool
     */
    public function isActive();

    /**
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive($isActive);

    /**
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);

    /**
     * @return bool
     */
    public function getIsCarouselBanner();

    /**
     * @param bool $isCarouselBanner
     * @return $this
     */
    public function setIsCarouselBanner($isCarouselBanner);

    /**
     * @return bool
     */
    public function getIsDiscountBanner();

    /**
     * @param bool $isDiscountBanner
     * @return $this
     */
    public function setIsDiscountBanner($isDiscountBanner);

    /**
     * @return bool
     */
    public function getIsSaleBanner();

    /**
     * @param bool $isSaleBanner
     * @return $this
     */
    public function setIsSaleBanner($isSaleBanner);

    /**
     * @return string|null
     */
    public function getSaleEndDate();

    /**
     * @param string|null $saleEndDate
     * @return $this
     */
    public function setSaleEndDate($saleEndDate);

    /**
     * @return string|null
     */
    public function getSaleStartDate();

    /**
     * @param string|null $saleStartDate
     * @return $this
     */
    public function setSaleStartDate($saleStartDate);
}
