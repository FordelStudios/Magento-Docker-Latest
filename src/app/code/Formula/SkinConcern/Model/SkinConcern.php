<?php

namespace Formula\SkinConcern\Model;

use Formula\SkinConcern\Api\Data\SkinConcernInterface;
use Magento\Framework\Model\AbstractModel;

class SkinConcern extends AbstractModel implements SkinConcernInterface
{
    /**
     * @var string
     */
    const SKINCONCERN_ID = 'skinconcern_id';
    const NAME = 'name';
    const DESCRIPTION = 'description';
    const TAGLINE = 'tagline';
    const LOGO = 'logo';
    const PROMOTIONAL_BANNERS = 'promotional_banners';
    const TAGS = 'tags';
    const STATUS = 'status';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $jsonSerializer;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        array $data = []
    ) {
        parent::__construct($context, $registry);
        $this->jsonSerializer = $jsonSerializer;
    }

    protected function _construct()
    {
        $this->_init(\Formula\SkinConcern\Model\ResourceModel\SkinConcern::class);
    }

    /**
     * Get SkinConcern Id
     * 
     * @return int|null
     */
    public function getSkinConcernId()
    {
        return $this->getData(self::SKINCONCERN_ID);
    }

    /**
     * Set SkinConcern Id
     * 
     * @param int $skinconcernId
     * @return $this
     */
    public function setSkinConcernId($skinconcernId)
    {
        return $this->setData(self::SKINCONCERN_ID, $skinconcernId);
    }

    /**
     * Get SkinConcern Name
     * 
     * @return string
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * Set SkinConcern Name
     * 
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Get SkinConcern Description
     * 
     * @return string|null
     */
    public function getDescription()
    {
        return $this->getData(self::DESCRIPTION);
    }

    /**
     * Set SkinConcern Description
     * 
     * @param string|null $description
     * @return $this
     */
    public function setDescription($description)
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * Get SkinConcern Tagline
     * 
     * @return string|null
     */
    public function getTagline()
    {
        return $this->getData(self::TAGLINE);
    }

    /**
     * Set SkinConcern Tagline
     * 
     * @param string|null $tagline
     * @return $this
     */
    public function setTagline($tagline)
    {
        return $this->setData(self::TAGLINE, $tagline);
    }

    /**
     * Get SkinConcern Logo Path
     * 
     * @return string|null
     */
    public function getLogo()
    {
        return $this->getData(self::LOGO);
    }

    /**
     * Set SkinConcern Logo Path
     * 
     * @param string|null $logo
     * @return $this
     */
    public function setLogo($logo)
    {
        return $this->setData(self::LOGO, $logo);
    }

    /**
     * Get Promotional Banners
     * 
     * @return string|null
     */
    public function getPromotionalBanners()
    {
        $banners = $this->getData(self::PROMOTIONAL_BANNERS);
        if ($banners && is_string($banners)) {
            try {
                return $this->jsonSerializer->unserialize($banners);
            } catch (\Exception $e) {
                return [];
            }
        }
        return $banners ?: [];
    }

    /**
     * Set Promotional Banners
     * 
     * @param string|mixed[] $banners
     * @return $this
     */
    public function setPromotionalBanners($banners)
    {
        if (is_array($banners)) {
            $banners = $this->jsonSerializer->serialize($banners);
        }
        return $this->setData(self::PROMOTIONAL_BANNERS, $banners);
    }

    /**
     * Get Tags
     * 
     * @return string|null
     */
    public function getTags()
    {
        $tags = $this->getData(self::TAGS);
        if ($tags && is_string($tags)) {
            try {
                return $this->jsonSerializer->unserialize($tags);
            } catch (\Exception $e) {
                return [];
            }
        }
        return $tags ?: [];
    }

    /**
     * Set Tags
     * 
     * @param string|mixed[] $tags
     * @return $this
     */
    public function setTags($tags)
    {
        if (is_array($tags)) {
            $tags = $this->jsonSerializer->serialize($tags);
        }
        return $this->setData(self::TAGS, $tags);
    }

    /**
     * Get SkinConcern Status
     * 
     * @return int|null
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * Set SkinConcern Status
     * 
     * @param int $status
     * @return $this
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Get SkinConcern Creation Time
     * 
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set SkinConcern Creation Time
     * 
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Get SkinConcern Update Time
     * 
     * @return string|null
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * Set SkinConcern Update Time
     * 
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}