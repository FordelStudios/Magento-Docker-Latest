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
}