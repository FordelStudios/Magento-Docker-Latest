<?php
namespace Formula\Blog\Api;

use Formula\Blog\Api\Data\BlogInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface BlogRepositoryInterface
{
    /**
     * Save blog.
     *
     * @param BlogInterface $blog
     * @return BlogInterface
     */
    public function save(BlogInterface $blog);

    /**
     * Get blog by ID.
     *
     * @param int $blogId
     * @return BlogInterface
     */
    public function getById($blogId);

    /**
     * Get blog list.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Delete blog.
     *
     * @param BlogInterface $blog
     * @return bool
     */
    public function delete(BlogInterface $blog);

    /**
     * Delete blog by ID.
     *
     * @param int $blogId
     * @return bool
     */
    public function deleteById($blogId);

    /**
     * Update blog.
     *
     * @param int $blogId
     * @param BlogInterface $blog
     * @return BlogInterface
     */
    public function update($blogId, BlogInterface $blog);
}