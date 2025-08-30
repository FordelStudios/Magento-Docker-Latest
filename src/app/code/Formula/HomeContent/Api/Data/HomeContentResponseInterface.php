<?php
declare(strict_types=1);

namespace Formula\HomeContent\Api\Data;

interface HomeContentResponseInterface
{
    /**
     * Get hero banners
     *
     * @return string[]
     */
    public function getHeroBanners();

    /**
     * Set hero banners
     *
     * @param string[] $heroBanners
     * @return $this
     */
    public function setHeroBanners($heroBanners);

    /**
     * Get five step routine banner
     *
     * @return string
     */
    public function getFiveStepRoutineBanner();

    /**
     * Set five step routine banner
     *
     * @param string $banner
     * @return $this
     */
    public function setFiveStepRoutineBanner($banner);

    /**
     * Get three step routine banner
     *
     * @return string
     */
    public function getThreeStepRoutineBanner();

    /**
     * Set three step routine banner
     *
     * @param string $banner
     * @return $this
     */
    public function setThreeStepRoutineBanner($banner);

    /**
     * Get discover your formula banner
     *
     * @return string
     */
    public function getDiscoverYourFormulaBanner();

    /**
     * Set discover your formula banner
     *
     * @param string $banner
     * @return $this
     */
    public function setDiscoverYourFormulaBanner($banner);

    /**
     * Get best of korean formula banner
     *
     * @return string
     */
    public function getBestOfKoreanFormulaBanner();

    /**
     * Set best of korean formula banner
     *
     * @param string $banner
     * @return $this
     */
    public function setBestOfKoreanFormulaBanner($banner);

    /**
     * Get discover korean ingredients banners
     *
     * @return \Formula\HomeContent\Api\Data\KoreanIngredientInterface[]
     */
    public function getDiscoverKoreanIngredientsBanners();

    /**
     * Set discover korean ingredients banners
     *
     * @param \Formula\HomeContent\Api\Data\KoreanIngredientInterface[] $banners
     * @return $this
     */
    public function setDiscoverKoreanIngredientsBanners($banners);

    /**
     * Get perfect gift image
     *
     * @return string
     */
    public function getPerfectGiftImage();

    /**
     * Set perfect gift image
     *
     * @param string $image
     * @return $this
     */
    public function setPerfectGiftImage($image);

    /**
     * Get bottom banner
     *
     * @return string
     */
    public function getBottomBanner();

    /**
     * Set bottom banner
     *
     * @param string $banner
     * @return $this
     */
    public function setBottomBanner($banner);
}