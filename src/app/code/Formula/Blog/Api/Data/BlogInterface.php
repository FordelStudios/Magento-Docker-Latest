<?php
/**
 * Blog post interface
 *
 * @category  Formula
 * @package   Formula\Blog
 */
namespace Formula\Blog\Api\Data;

/**
 * Interface BlogInterface
 * @api
 */
interface BlogInterface
{
    /**
     * Constants for keys of data array
     */
    const BLOG_ID        = 'blog_id';
    const TITLE          = 'title';
    const CONTENT        = 'content';
    const IMAGE          = 'image';
    const AUTHOR         = 'author';
    const CREATED_AT  = 'created_at';
    const UPDATED_AT    = 'updated_at';
    const IS_PUBLISHED      = 'is_published';
    const PRODUCT_IDS    = 'product_ids';
    const TAGS = 'tags';
    const CATEGORY_IDS = 'category_ids';

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Get title
     *
     * @return string|null
     */
    public function getTitle();

    /**
     * Get content
     *
     * @return string|null
     */
    public function getContent();


    /**
     * Get image
     *
     * @return string|null
     */
    public function getImage();

    /**
     * Get author
     *
     * @return string|null
     */
    public function getAuthor();

    /**
     * Get creation time
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Get update time
     *
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Is published
     *
     * @return bool|null
     */
    public function getIsPublished();


    /**
     * Get product IDs
     *
     * @return string|null
     */
    public function getProductIds();

    /**
     * Set ID
     *
     * @param int $id
     * @return BlogInterface
     */
    public function setId($id);

    /**
     * Set title
     *
     * @param string $title
     * @return BlogInterface
     */
    public function setTitle($title);

    /**
     * Set content
     *
     * @param string $content
     * @return BlogInterface
     */
    public function setContent($content);


    /**
     * Set image
     *
     * @param string $image
     * @return BlogInterface
     */
    public function setImage($image);

    /**
     * Set author
     *
     * @param string $author
     * @return BlogInterface
     */
    public function setAuthor($author);

    /**
     * Set creation time
     *
     * @param string $createdAt
     * @return BlogInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * Set update time
     *
     * @param string $updatedAt
     * @return BlogInterface
     */
    public function setUpdatedAt($updatedAt);

    /**
     * Set is published
     *
     * @param bool|int $isPublished
     * @return BlogInterface
     */
    public function setIsPublished($isPublished);


    /**
     * Set product IDs
     *
     * @param string $productIds
     * @return BlogInterface
     */
    public function setProductIds($productIds);

    /**
     * @return string|null
     */
    public function getTags();

    /**
     * @param string|mixed[] $tags
     * @return $this
     */
    public function setTags($tags);

    /**
     * Get category IDs
     *
     * @return int[]|null
     */
    public function getCategoryIds();

    /**
     * Set category IDs
     *
     * @param int[]|string $categoryIds
     * @return $this
     */
    public function setCategoryIds($categoryIds);
}