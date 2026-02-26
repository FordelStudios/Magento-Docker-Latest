<?php
declare(strict_types=1);

namespace Formula\HomeContent\Api\Data;

interface HomeContentInterface
{
    const ENTITY_ID = 'entity_id';
    const HERO_BANNERS = 'hero_banners';
    const FIVE_STEP_ROUTINE_BANNER = 'five_step_routine_banner';
    const THREE_STEP_ROUTINE_BANNER = 'three_step_routine_banner';
    const DISCOVER_YOUR_FORMULA_BANNER = 'discover_your_formula_banner';
    const BEST_OF_KOREAN_FORMULA_BANNER = 'best_of_korean_formula_banner';
    const DISCOVER_KOREAN_INGREDIENTS_BANNERS = 'discover_korean_ingredients_banners';
    const PERFECT_GIFT_IMAGE = 'perfect_gift_image';
    const BOTTOM_BANNER = 'bottom_banner';
    const BOTTOM_BANNER_URL = 'bottom_banner_url';
    const ACTIVE = 'active';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * Get entity ID
     *
     * @return int|null
     */
    public function getEntityId();

    /**
     * Set entity ID
     *
     * @param int $entityId
     * @return $this
     */
    public function setEntityId($entityId);

    /**
     * Get hero banners
     *
     * @return \Formula\HomeContent\Api\Data\HeroBannerInterface[]
     */
    public function getHeroBanners();

    /**
     * Set hero banners
     *
     * @param \Formula\HomeContent\Api\Data\HeroBannerInterface[]|array|string $heroBanners
     * @return $this
     */
    public function setHeroBanners($heroBanners);

    /**
     * Get five step routine banner
     *
     * @return string|null
     */
    public function getFiveStepRoutineBanner();

    /**
     * Set five step routine banner
     *
     * @param string $fiveStepRoutineBanner
     * @return $this
     */
    public function setFiveStepRoutineBanner($fiveStepRoutineBanner);

    /**
     * Get three step routine banner
     *
     * @return string|null
     */
    public function getThreeStepRoutineBanner();

    /**
     * Set three step routine banner
     *
     * @param string $threeStepRoutineBanner
     * @return $this
     */
    public function setThreeStepRoutineBanner($threeStepRoutineBanner);

    /**
     * Get discover your formula banner
     *
     * @return string|null
     */
    public function getDiscoverYourFormulaBanner();

    /**
     * Set discover your formula banner
     *
     * @param string $discoverYourFormulaBanner
     * @return $this
     */
    public function setDiscoverYourFormulaBanner($discoverYourFormulaBanner);

    /**
     * Get best of korean formula banner
     *
     * @return string|null
     */
    public function getBestOfKoreanFormulaBanner();

    /**
     * Set best of korean formula banner
     *
     * @param string $bestOfKoreanFormulaBanner
     * @return $this
     */
    public function setBestOfKoreanFormulaBanner($bestOfKoreanFormulaBanner);

    /**
     * Get discover korean ingredients banners
     *
     * @return array
     */
    public function getDiscoverKoreanIngredientsBanners();

    /**
     * Set discover korean ingredients banners
     *
     * @param array|string $discoverKoreanIngredientsBanners
     * @return $this
     */
    public function setDiscoverKoreanIngredientsBanners($discoverKoreanIngredientsBanners);

    /**
     * Get perfect gift image
     *
     * @return string|null
     */
    public function getPerfectGiftImage();

    /**
     * Set perfect gift image
     *
     * @param string $perfectGiftImage
     * @return $this
     */
    public function setPerfectGiftImage($perfectGiftImage);

    /**
     * Get bottom banner
     *
     * @return string|null
     */
    public function getBottomBanner();

    /**
     * Set bottom banner
     *
     * @param string $bottomBanner
     * @return $this
     */
    public function setBottomBanner($bottomBanner);

    /**
     * Get bottom banner URL
     *
     * @return string|null
     */
    public function getBottomBannerUrl();

    /**
     * Set bottom banner URL
     *
     * @param string $bottomBannerUrl
     * @return $this
     */
    public function setBottomBannerUrl($bottomBannerUrl);

    /**
     * Get active status
     *
     * @return bool
     */
    public function getActive();

    /**
     * Set active status
     *
     * @param bool $active
     * @return $this
     */
    public function setActive($active);

    /**
     * Get created at timestamp
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created at timestamp
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * Get updated at timestamp
     *
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set updated at timestamp
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);
}