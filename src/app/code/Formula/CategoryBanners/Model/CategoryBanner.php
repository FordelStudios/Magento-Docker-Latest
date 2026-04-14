<?php
// app/code/Formula/CategoryBanners/Model/CategoryBanner.php
namespace Formula\CategoryBanners\Model;

use Formula\CategoryBanners\Api\Data\CategoryBannerInterface;
use Magento\Framework\Model\AbstractModel;

class CategoryBanner extends AbstractModel implements CategoryBannerInterface
{
    const STATUS_ENABLED = 1;

    protected function _construct()
    {
        $this->_init(\Formula\CategoryBanners\Model\ResourceModel\CategoryBanner::class);
    }

    public function getId() { return $this->getData(self::ENTITY_ID); }
    public function setId($id) { return $this->setData(self::ENTITY_ID, $id); }
    public function getCategoryId() { return $this->getData(self::CATEGORY_ID); }
    public function setCategoryId($categoryId) { return $this->setData(self::CATEGORY_ID, $categoryId); }
    public function getBannerImage() { return $this->getData(self::BANNER_IMAGE); }
    public function setBannerImage($bannerImage) { return $this->setData(self::BANNER_IMAGE, $bannerImage); }
    public function getUrl() { return $this->getData(self::URL); }
    public function setUrl($url) { return $this->setData(self::URL, $url); }

    public function isActive() { return (bool) $this->getData(self::IS_ACTIVE); }

    public function setIsActive($isActive)
    {
        if (is_string($isActive)) {
            $isActive = (strtolower($isActive) === 'true' || $isActive === '1') ? 1 : 0;
        } elseif (is_bool($isActive)) {
            $isActive = $isActive ? 1 : 0;
        }
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    public function getCreatedAt() { return $this->getData(self::CREATED_AT); }
    public function setCreatedAt($createdAt) { return $this->setData(self::CREATED_AT, $createdAt); }
    public function getUpdatedAt() { return $this->getData(self::UPDATED_AT); }
    public function setUpdatedAt($updatedAt) { return $this->setData(self::UPDATED_AT, $updatedAt); }
    public function getIsCarouselBanner() { return $this->getData(self::IS_CAROUSEL_BANNER); }
    public function setIsCarouselBanner($isCarouselBanner) { return $this->setData(self::IS_CAROUSEL_BANNER, $isCarouselBanner); }
    public function getIsDiscountBanner() { return $this->getData(self::IS_DISCOUNT_BANNER); }
    public function setIsDiscountBanner($isDiscountBanner) { return $this->setData(self::IS_DISCOUNT_BANNER, $isDiscountBanner); }
    public function getIsSaleBanner() { return $this->getData(self::IS_SALE_BANNER); }
    public function setIsSaleBanner($isSaleBanner) { return $this->setData(self::IS_SALE_BANNER, $isSaleBanner); }
    public function getSaleEndDate() { return $this->getData(self::SALE_END_DATE); }
    public function setSaleEndDate($saleEndDate) { return $this->setData(self::SALE_END_DATE, $saleEndDate); }
    public function getSaleStartDate() { return $this->getData(self::SALE_START_DATE); }
    public function setSaleStartDate($saleStartDate) { return $this->setData(self::SALE_START_DATE, $saleStartDate); }

    public function beforeSave()
    {
        if ($this->isObjectNew() && !$this->getCreatedAt()) {
            $this->setCreatedAt(date('Y-m-d H:i:s'));
        }
        $this->setUpdatedAt(date('Y-m-d H:i:s'));
        if ($this->getData(self::IS_ACTIVE) === null) {
            $this->setIsActive(true);
        }
        return parent::beforeSave();
    }
}
