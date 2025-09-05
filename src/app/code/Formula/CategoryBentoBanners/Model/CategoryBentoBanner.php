<?php
namespace Formula\CategoryBentoBanners\Model;

use Formula\CategoryBentoBanners\Api\Data\CategoryBentoBannerInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\App\ObjectManager;

class CategoryBentoBanner extends AbstractModel implements CategoryBentoBannerInterface
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
        $this->_init(\Formula\CategoryBentoBanners\Model\ResourceModel\CategoryBentoBanner::class);
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
    public function getUrl()
    {
        return $this->getData(self::URL);
    }

    /**
     * {@inheritdoc}
     */
    public function setUrl($url)
    {
        return $this->setData(self::URL, $url);
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
    public function getCategoryName()
    {
        return $this->getData(self::CATEGORY_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function setCategoryName($categoryName)
    {
        return $this->setData(self::CATEGORY_NAME, $categoryName);
    }

    /**
     * Process data before saving
     *
     * @return $this
     */
    public function beforeSave()
    {
        $logger = ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class);
        $logger->info('Model beforeSave: Starting', ['data' => $this->getData()]);
        
        if ($this->isObjectNew()) {
            $logger->info('Model beforeSave: New object detected');
            if (! $this->getCreatedAt()) {
                $this->setCreatedAt(date('Y-m-d H:i:s'));
                $logger->info('Model beforeSave: Set created_at');
            }
        } else {
            $logger->info('Model beforeSave: Existing object, ID: ' . $this->getId());
        }

        $this->setUpdatedAt(date('Y-m-d H:i:s'));
        $logger->info('Model beforeSave: Set updated_at');

        if ($this->getData(self::IS_ACTIVE) === null) {
            $this->setIsActive(true);
            $logger->info('Model beforeSave: Set is_active to true');
        }

        $logger->info('Model beforeSave: Final data before parent::beforeSave()', ['data' => $this->getData()]);
        return parent::beforeSave();
    }
}