<?php
declare(strict_types=1);

namespace Formula\HomeContent\Model\Data;

use Formula\HomeContent\Api\Data\HomeContentResponseInterface;
use Magento\Framework\Api\AbstractExtensibleObject;

class HomeContentResponse extends AbstractExtensibleObject implements HomeContentResponseInterface
{
    const HERO_BANNERS = 'hero_banners';
    const FIVE_STEP_ROUTINE_BANNER = 'five_step_routine_banner';
    const THREE_STEP_ROUTINE_BANNER = 'three_step_routine_banner';
    const DISCOVER_YOUR_FORMULA_BANNER = 'discover_your_formula_banner';
    const BEST_OF_KOREAN_FORMULA_BANNER = 'best_of_korean_formula_banner';
    const DISCOVER_KOREAN_INGREDIENTS_BANNERS = 'discover_korean_ingredients_banners';
    const PERFECT_GIFT_IMAGE = 'perfect_gift_image';
    const BOTTOM_BANNER = 'bottom_banner';
    const BOTTOM_BANNER_URL = 'bottom_banner_url';

    public function getHeroBanners()
    {
        return $this->_get(self::HERO_BANNERS) ?: [];
    }

    public function setHeroBanners($heroBanners)
    {
        return $this->setData(self::HERO_BANNERS, $heroBanners);
    }

    public function getFiveStepRoutineBanner()
    {
        return $this->_get(self::FIVE_STEP_ROUTINE_BANNER) ?: '';
    }

    public function setFiveStepRoutineBanner($banner)
    {
        return $this->setData(self::FIVE_STEP_ROUTINE_BANNER, $banner);
    }

    public function getThreeStepRoutineBanner()
    {
        return $this->_get(self::THREE_STEP_ROUTINE_BANNER) ?: '';
    }

    public function setThreeStepRoutineBanner($banner)
    {
        return $this->setData(self::THREE_STEP_ROUTINE_BANNER, $banner);
    }

    public function getDiscoverYourFormulaBanner()
    {
        return $this->_get(self::DISCOVER_YOUR_FORMULA_BANNER) ?: '';
    }

    public function setDiscoverYourFormulaBanner($banner)
    {
        return $this->setData(self::DISCOVER_YOUR_FORMULA_BANNER, $banner);
    }

    public function getBestOfKoreanFormulaBanner()
    {
        return $this->_get(self::BEST_OF_KOREAN_FORMULA_BANNER) ?: '';
    }

    public function setBestOfKoreanFormulaBanner($banner)
    {
        return $this->setData(self::BEST_OF_KOREAN_FORMULA_BANNER, $banner);
    }

    public function getDiscoverKoreanIngredientsBanners()
    {
        return $this->_get(self::DISCOVER_KOREAN_INGREDIENTS_BANNERS) ?: [];
    }

    public function setDiscoverKoreanIngredientsBanners($banners)
    {
        return $this->setData(self::DISCOVER_KOREAN_INGREDIENTS_BANNERS, $banners);
    }

    public function getPerfectGiftImage()
    {
        return $this->_get(self::PERFECT_GIFT_IMAGE) ?: '';
    }

    public function setPerfectGiftImage($image)
    {
        return $this->setData(self::PERFECT_GIFT_IMAGE, $image);
    }

    public function getBottomBanner()
    {
        return $this->_get(self::BOTTOM_BANNER) ?: '';
    }

    public function setBottomBanner($banner)
    {
        return $this->setData(self::BOTTOM_BANNER, $banner);
    }

    public function getBottomBannerUrl()
    {
        return $this->_get(self::BOTTOM_BANNER_URL) ?: '';
    }

    public function setBottomBannerUrl($url)
    {
        return $this->setData(self::BOTTOM_BANNER_URL, $url);
    }
}