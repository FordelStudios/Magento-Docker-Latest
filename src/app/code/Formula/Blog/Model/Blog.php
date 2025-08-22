<?php
/**
 * Blog model
 *
 * @category  Formula
 * @package   Formula\Blog
 */
namespace Formula\Blog\Model;

use Formula\Blog\Api\Data\BlogInterface;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Serialize\Serializer\Json;

class Blog extends AbstractModel implements BlogInterface, IdentityInterface
{
    /**
     * Blog post cache tag
     */
    const CACHE_TAG = 'formula_blog_post';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @var string
     */
    protected $_eventPrefix = 'formula_blog_post';

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * Blog constructor.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param Json $jsonSerializer
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        Json $jsonSerializer,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->jsonSerializer = $jsonSerializer;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Formula\Blog\Model\ResourceModel\Blog::class);
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
        return $this->getData(self::BLOG_ID);
    }

    /**
     * Get title
     *
     * @return string|null
     */
    public function getTitle()
    {
        return $this->getData(self::TITLE);
    }

    /**
     * Get content
     *
     * @return string|null
     */
    public function getContent()
    {
        return $this->getData(self::CONTENT);
    }


    /**
     * Get image
     *
     * @return string|null
     */
    public function getImage()
    {
        return $this->getData(self::IMAGE);
    }

    /**
     * Get author
     *
     * @return string|null
     */
    public function getAuthor()
    {
        return $this->getData(self::AUTHOR);
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
     * Is active
     *
     * @return bool|null
     */
    public function getIsPublished()
    {
        return (bool)$this->getData(self::IS_PUBLISHED);
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
     * @return BlogInterface
     */
    public function setId($id)
    {
        return $this->setData(self::BLOG_ID, $id);
    }

    /**
     * Set title
     *
     * @param string $title
     * @return BlogInterface
     */
    public function setTitle($title)
    {
        return $this->setData(self::TITLE, $title);
    }

    /**
     * Set content
     *
     * @param string $content
     * @return BlogInterface
     */
    public function setContent($content)
    {
        return $this->setData(self::CONTENT, $content);
    }


    /**
     * Set image
     *
     * @param string $image
     * @return BlogInterface
     */
    public function setImage($image)
    {
        return $this->setData(self::IMAGE, $image);
    }

    /**
     * Set author
     *
     * @param string $author
     * @return BlogInterface
     */
    public function setAuthor($author)
    {
        return $this->setData(self::AUTHOR, $author);
    }

    /**
     * Set creation time
     *
     * @param string $createdAt
     * @return BlogInterface
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Set update time
     *
     * @param string $updatedAt
     * @return BlogInterface
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * Set is published
     *
     * @param bool|int $isPublished
     * @return BlogInterface
     */
    public function setIsPublished($isPublished)
    {
        return $this->setData(self::IS_PUBLISHED, $isPublished);
    }

        
    /**
     * Set product IDs
     *
     * @param string $productIds
     * @return BlogInterface
     */
    public function setProductIds($productIds)
    {
        return $this->setData(self::PRODUCT_IDS, $productIds);
    }

    /**
     * Get Tags
     * 
     * @return string[]|null
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
     * Get category IDs
     *
     * @return int[]|null
     */
    public function getCategoryIds()
    {
        $categoryIds = $this->getData(self::CATEGORY_IDS);
        if ($categoryIds && is_string($categoryIds)) {
            try {
                return $this->jsonSerializer->unserialize($categoryIds);
            } catch (\Exception $e) {
                return [];
            }
        }
        return $categoryIds ?: [];
    }

    /**
     * Set category IDs
     *
     * @param int[]|string $categoryIds
     * @return $this
     */
    public function setCategoryIds($categoryIds)
    {
        if (is_array($categoryIds)) {
            $categoryIds = $this->jsonSerializer->serialize($categoryIds);
        }
        return $this->setData(self::CATEGORY_IDS, $categoryIds);
    }
}