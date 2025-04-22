<?php
// app/code/Formula/CategoryBanners/Model/CategoryBanner.php
namespace Formula\CategoryBanners\Model;

use Formula\CategoryBanners\Api\Data\CategoryBannerInterface;
use Magento\Framework\Model\AbstractModel;

class CategoryBanner extends AbstractModel implements CategoryBannerInterface
{
    /**
     * Constant for is_active field value
     */
    const STATUS_ENABLED = 1;

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Formula\CategoryBanners\Model\ResourceModel\CategoryBanner::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        return $this->setData(self::ENTITY_ID, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getCategoryId()
    {
        return $this->getData(self::CATEGORY_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setCategoryId($categoryId)
    {
        return $this->setData(self::CATEGORY_ID, $categoryId);
    }

    /**
     * {@inheritdoc}
     */
    public function getBannerImage()
    {
        return $this->getData(self::BANNER_IMAGE);
    }

    /**
     * {@inheritdoc}
     */
    public function setBannerImage($bannerImage)
    {
        return $this->setData(self::BANNER_IMAGE, $bannerImage);
    }

    /**
     * {@inheritdoc}
     */
    public function isActive()
    {
        return (bool) $this->getData(self::IS_ACTIVE);
    }

    /**
     * {@inheritdoc}
     */
    public function setIsActive($isActive)
    {
        // Convert various representations of boolean to 0/1
        if (is_string($isActive)) {
            if (strtolower($isActive) === 'true' || $isActive === '1') {
                $isActive = 1;
            } else if (strtolower($isActive) === 'false' || $isActive === '0') {
                $isActive = 0;
            }
        } else if (is_bool($isActive)) {
            $isActive = $isActive ? 1 : 0;
        }

        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * {@inheritdoc}
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * {@inheritdoc}
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * {@inheritdoc}
     */
    public function getSubcategories()
    {
        return $this->getData(self::SUBCATEGORIES);
    }

    /**
     * {@inheritdoc}
     */
    public function setSubcategories($subcategories)
    {
        return $this->setData(self::SUBCATEGORIES, $subcategories);
    }

    /**
     * {@inheritdoc}
     */
    public function getDiscountPercentage()
    {
        return $this->getData(self::DISCOUNT_PERCENTAGE);
    }

    /**
     * {@inheritdoc}
     */
    public function setDiscountPercentage($percentage)
    {
        return $this->setData(self::DISCOUNT_PERCENTAGE, $percentage);
    }

    /**
     * {@inheritdoc}
     */
    public function getIsCarouselBanner()
    {
        return $this->getData(self::IS_CAROUSEL_BANNER);
    }

    /**
     * {@inheritdoc}
     */
    public function setIsCarouselBanner($isCarouselBanner)
    {
        return $this->setData(self::IS_CAROUSEL_BANNER, $isCarouselBanner);
    }

    /**
     * {@inheritdoc}
     */
    public function getIsDiscountBanner()
    {
        return $this->getData(self::IS_DISCOUNT_BANNER);
    }

    /**
     * {@inheritdoc}
     */
    public function setIsDiscountBanner($isDiscountBanner)
    {
        return $this->setData(self::IS_DISCOUNT_BANNER, $isDiscountBanner);
    }

    /**
     *
     * {@inheritDoc}
     */
    public function getIsSaleBanner()
    {
        return $this->getData(self::IS_SALE_BANNER);
    }

    /**
     * {@inheritDoc}
     */
    public function setIsSaleBanner($isSaleBanner)
    {
        return $this->setData(self::IS_SALE_BANNER, $isSaleBanner);
    }

    /**
     * Process data before saving
     *
     * @return $this
     */
    public function beforeSave()
    {
        // Handle the current date
        if ($this->isObjectNew()) {
            if (! $this->getCreatedAt()) {
                $this->setCreatedAt(date('Y-m-d H:i:s'));
            }
        }

        $this->setUpdatedAt(date('Y-m-d H:i:s'));

        // Set default value for is_active if not provided
        if ($this->getData(self::IS_ACTIVE) === null) {
            $this->setIsActive(true);
        }

        return parent::beforeSave();
    }
}
