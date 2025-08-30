<?php
declare(strict_types=1);

namespace Formula\HomeContent\Model;

use Magento\Framework\Model\AbstractModel;
use Formula\HomeContent\Api\Data\HomeContentInterface;
use Formula\HomeContent\Model\ResourceModel\HomeContent as HomeContentResourceModel;

class HomeContent extends AbstractModel implements HomeContentInterface
{
    protected function _construct()
    {
        $this->_init(HomeContentResourceModel::class);
    }

    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    public function getHeroBanners()
    {
        $heroBanners = $this->getData(self::HERO_BANNERS);
        return $heroBanners ? json_decode($heroBanners, true) : [];
    }

    public function setHeroBanners($heroBanners)
    {
        $data = is_array($heroBanners) ? json_encode($heroBanners) : $heroBanners;
        return $this->setData(self::HERO_BANNERS, $data);
    }

    public function getFiveStepRoutineBanner()
    {
        return $this->getData(self::FIVE_STEP_ROUTINE_BANNER);
    }

    public function setFiveStepRoutineBanner($fiveStepRoutineBanner)
    {
        return $this->setData(self::FIVE_STEP_ROUTINE_BANNER, $fiveStepRoutineBanner);
    }

    public function getThreeStepRoutineBanner()
    {
        return $this->getData(self::THREE_STEP_ROUTINE_BANNER);
    }

    public function setThreeStepRoutineBanner($threeStepRoutineBanner)
    {
        return $this->setData(self::THREE_STEP_ROUTINE_BANNER, $threeStepRoutineBanner);
    }

    public function getDiscoverYourFormulaBanner()
    {
        return $this->getData(self::DISCOVER_YOUR_FORMULA_BANNER);
    }

    public function setDiscoverYourFormulaBanner($discoverYourFormulaBanner)
    {
        return $this->setData(self::DISCOVER_YOUR_FORMULA_BANNER, $discoverYourFormulaBanner);
    }

    public function getBestOfKoreanFormulaBanner()
    {
        return $this->getData(self::BEST_OF_KOREAN_FORMULA_BANNER);
    }

    public function setBestOfKoreanFormulaBanner($bestOfKoreanFormulaBanner)
    {
        return $this->setData(self::BEST_OF_KOREAN_FORMULA_BANNER, $bestOfKoreanFormulaBanner);
    }

    public function getDiscoverKoreanIngredientsBanners()
    {
        $banners = $this->getData(self::DISCOVER_KOREAN_INGREDIENTS_BANNERS);
        return $banners ? json_decode($banners, true) : [];
    }

    public function setDiscoverKoreanIngredientsBanners($discoverKoreanIngredientsBanners)
    {
        $data = is_array($discoverKoreanIngredientsBanners) ? json_encode($discoverKoreanIngredientsBanners) : $discoverKoreanIngredientsBanners;
        return $this->setData(self::DISCOVER_KOREAN_INGREDIENTS_BANNERS, $data);
    }

    public function getPerfectGiftImage()
    {
        return $this->getData(self::PERFECT_GIFT_IMAGE);
    }

    public function setPerfectGiftImage($perfectGiftImage)
    {
        return $this->setData(self::PERFECT_GIFT_IMAGE, $perfectGiftImage);
    }

    public function getBottomBanner()
    {
        return $this->getData(self::BOTTOM_BANNER);
    }

    public function setBottomBanner($bottomBanner)
    {
        return $this->setData(self::BOTTOM_BANNER, $bottomBanner);
    }

    public function getActive()
    {
        return (bool)$this->getData(self::ACTIVE);
    }

    public function setActive($active)
    {
        return $this->setData(self::ACTIVE, (bool)$active);
    }

    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}