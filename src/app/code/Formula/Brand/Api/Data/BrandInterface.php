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
    const TAGS = 'tags';

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
    public function getTags();

    /**
     * @param string|mixed[] $tags
     * @return $this
     */
    public function setTags($tags);


}