<?php
// app/code/Formula/CountryFormulaBanners/Model/CountryFormulaBanner.php
namespace Formula\CountryFormulaBanners\Model;

use Formula\CountryFormulaBanners\Api\Data\CountryFormulaBannerInterface;
use Magento\Framework\Model\AbstractModel;

class CountryFormulaBanner extends AbstractModel implements CountryFormulaBannerInterface
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
        $this->_init(\Formula\CountryFormulaBanners\Model\ResourceModel\CountryFormulaBanner::class);
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
    public function getCountryId() 
    {
        return $this->getData(self::COUNTRY_ID);
    }


    /**
     * {@inheritdoc}
     */
    public function setCountryId($countryId) 
    {
        return $this->setData(self::COUNTRY_ID, $countryId);
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
