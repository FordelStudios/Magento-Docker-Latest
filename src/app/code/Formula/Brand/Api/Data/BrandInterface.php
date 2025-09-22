<?php
namespace Formula\Brand\Api\Data;

interface BrandInterface
{
    const BRAND_ID = 'brand_id';
    const NAME = 'name';
    const DESCRIPTION = 'description';
    const TAGLINE = 'tagline';
    const LOGO = 'logo';
    const PROMOTIONAL_BANNERS = 'promotional_banners';
    const SALE_PAGE_BANNER = 'sale_page_banner';
    const TAGS = 'tags';
    const IS_KOREAN = 'is_korean';
    const IS_HOMEGROWN = 'is_homegrown';
    const IS_TRENDING = 'is_trending';
    const IS_POPULAR = 'is_popular';
    const COUNTRY = 'country';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * @return int|null
     */
    public function getBrandId();

    /**
     * @param int $brandId
     * @return $this
     */
    public function setBrandId($brandId);

    /**
     * @return string|null
     */
    public function getName();

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name);

    /**
     * @return string|null
     */
    public function getDescription();

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description);

    /**
     * @return string|null
     */
    public function getTagline();

    /**
     * @param string $tagline
     * @return $this
     */
    public function setTagline($tagline);

    /**
     * @return string|null
     */
    public function getLogo();

    /**
     * @param string $logo
     * @return $this
     */
    public function setLogo($logo);

    /**
     * @return string|null
     */
    public function getPromotionalBanners();

    /**
     * @param string|mixed[] $banners
     * @return $this
     */
    public function setPromotionalBanners($banners);

    /**
     * @return string|null
     */
    public function getSalePageBanner();

    /**
     * @param string $salePageBanner
     * @return $this
     */
    public function setSalePageBanner($salePageBanner);

    /**
     * @return string|null
     */
    public function getTags();

    /**
     * @param string|mixed[] $tags
     * @return $this
     */
    public function setTags($tags);

    /**
     * @return bool|null
     */
    public function getIsKorean();

    /**
     * @param bool $isKorean
     * @return $this
     */

     public function setIsKorean($isKorean);

    /**
     * @return bool|null
     */
    public function getIsHomegrown();

    /**
     * @param bool $isHomegrown
     * @return $this
     */
    public function setIsHomegrown($isHomegrown);

    /**
     * @return bool|null
     */
    public function getIsTrending();

    /**
     * @param bool $isTrending
     * @return $this
     */
    public function setIsTrending($isTrending);

    /**
     * @return bool|null
     */
    public function getIsPopular();

    /**
     * @param bool $isPopular
     * @return $this
     */    
    public function setIsPopular($isPopular);

    /**
     * @return string|null
     */
    public function getCountry();

    /**
     * @param string $country
     * @return $this
     */
    public function setCountry($country);

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


}