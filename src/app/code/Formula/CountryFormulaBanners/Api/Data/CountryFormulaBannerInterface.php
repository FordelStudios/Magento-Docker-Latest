<?php
// app/code/Formula/CountryFormulaBanners/Api/Data/CountryFormulaBannerInterface.php
namespace Formula\CountryFormulaBanners\Api\Data;

/**
 * @api
 */
interface CountryFormulaBannerInterface
{
    const ENTITY_ID = 'entity_id';
    const COUNTRY_ID = 'country_id';  // Changed from COUNTRY to COUNTRY_ID
    const BANNER_IMAGE = 'banner_image';
    const URL = 'url';
    const IS_ACTIVE = 'is_active';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
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
     * Get country ID
     *
     * @return string
     */
    public function getCountryId();  

    /**
     * Set country ID
     *
     * @param string $countryId  
     * @return $this
     */
    public function setCountryId($countryId);  

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
     * Get URL
     *
     * @return string
     */
    public function getUrl();

    /**
     * Set URL
     *
     * @param string $url
     * @return $this
     */
    public function setUrl($url);

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