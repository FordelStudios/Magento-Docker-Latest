<?php
/**
 * Reel model
 *
 * @category  Formula
 * @package   Formula\Reel
 */
namespace Formula\Reel\Model;

use Formula\Reel\Api\Data\ReelInterface;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class Reel extends AbstractModel implements ReelInterface, IdentityInterface
{
    /**
     * Reel post cache tag
     */
    const CACHE_TAG = 'formula_reel_post';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @var string
     */
    protected $_eventPrefix = 'formula_reel_post';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Formula\Reel\Model\ResourceModel\Reel::class);
    }

    /**
     * Get identities
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->getData(self::REEL_ID);
    }

    /**
     * Get description
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->getData(self::DESCRIPTION);
    }


    /**
     * Get video
     *
     * @return string|null
     */
    public function getVideo()
    {
        return $this->getData(self::VIDEO);
    }
    
    /**
     * Get timer
     *
     * @return string|null
     */
    public function getTimer(){
        return $this->getData(self::TIMER);
    }


    /**
     * Get creation time
     *
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Get update time
     *
     * @return string|null
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

   
    
    /**
     * Get product IDs
     *
     * @return string|null
     */
    public function getProductIds()
    {
        return $this->getData(self::PRODUCT_IDS);
    }

    /**
     * Set ID
     *
     * @param int $id
     * @return ReelInterface
     */
    public function setId($id)
    {
        return $this->setData(self::REEL_ID, $id);
    }

    /**
     * Set description
     *
     * @param string $description
     * @return ReelInterface
     */
    public function setDescription($description)
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * Set timer
     *
     * @param string $timer
     * @return ReelInterface
     */
    public function setTimer($timer)
    {
        return $this->setData(self::TIMER, $timer);
    }


    /**
     * Set video
     *
     * @param string $video
     * @return ReelInterface
     */
    public function setVideo($video)
    {
        return $this->setData(self::VIDEO, $video);
    }

    /**
     * Set creation time
     *
     * @param string $createdAt
     * @return ReelInterface
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Set update time
     *
     * @param string $updatedAt
     * @return ReelInterface
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }


        
    /**
     * Set product IDs
     *
     * @param string $productIds
     * @return ReelInterface
     */
    public function setProductIds($productIds)
    {
        return $this->setData(self::PRODUCT_IDS, $productIds);
    }

    /**
     * Get category IDs
     *
     * @return string|null
     */
    public function getCategoryIds()
    {
        return $this->getData(self::CATEGORY_IDS);
    }

    /**
     * Set category IDs
     *
     * @param string $categoryIds
     * @return ReelInterface
     */
    public function setCategoryIds($categoryIds)
    {
        return $this->setData(self::CATEGORY_IDS, $categoryIds);
    }

    /**
     * Get thumbnail
     *
     * @return string|null
     */
    public function getThumbnail()
    {
        return $this->getData(self::THUMBNAIL);
    }

    /**
     * Set thumbnail
     *
     * @param string $thumbnail
     * @return ReelInterface
     */
    public function setThumbnail($thumbnail)
    {
        return $this->setData(self::THUMBNAIL, $thumbnail);
    }

    /**
     * Get culture/country code
     *
     * @return string|null
     */
    public function getCulture()
    {
        return $this->getData(self::CULTURE);
    }

    /**
     * Set culture/country code
     *
     * @param string|null $culture
     * @return ReelInterface
     */
    public function setCulture($culture)
    {
        return $this->setData(self::CULTURE, $culture);
    }
}