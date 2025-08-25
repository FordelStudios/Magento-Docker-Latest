<?php

namespace Formula\BodyConcern\Model;

use Formula\BodyConcern\Api\Data\BodyConcernInterface;
use Magento\Framework\Model\AbstractModel;

class BodyConcern extends AbstractModel implements BodyConcernInterface
{
    /**
     * @var string
     */
    const BODYCONCERN_ID = 'bodyconcern_id';
    const NAME = 'name';
    const DESCRIPTION = 'description';
    const LOGO = 'logo';
    const TAGS = 'tags';
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
        $this->_init(\Formula\BodyConcern\Model\ResourceModel\BodyConcern::class);
    }

    /**
     * Get BodyConcern Id
     * 
     * @return int|null
     */
    public function getBodyConcernId()
    {
        return $this->getData(self::BODYCONCERN_ID);
    }

    /**
     * Set BodyConcern Id
     * 
     * @param int $bodyconcernId
     * @return $this
     */
    public function setBodyConcernId($bodyconcernId)
    {
        return $this->setData(self::BODYCONCERN_ID, $bodyconcernId);
    }

    /**
     * Get BodyConcern Name
     * 
     * @return string
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * Set BodyConcern Name
     * 
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Get BodyConcern Description
     * 
     * @return string|null
     */
    public function getDescription()
    {
        return $this->getData(self::DESCRIPTION);
    }

    /**
     * Set BodyConcern Description
     * 
     * @param string|null $description
     * @return $this
     */
    public function setDescription($description)
    {
        return $this->setData(self::DESCRIPTION, $description);
    }



    /**
     * Get BodyConcern Logo Path
     * 
     * @return string|null
     */
    public function getLogo()
    {
        return $this->getData(self::LOGO);
    }

    /**
     * Set BodyConcern Logo Path
     * 
     * @param string|null $logo
     * @return $this
     */
    public function setLogo($logo)
    {
        return $this->setData(self::LOGO, $logo);
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
     * Get BodyConcern Creation Time
     * 
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set BodyConcern Creation Time
     * 
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Get BodyConcern Update Time
     * 
     * @return string|null
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * Set BodyConcern Update Time
     * 
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}