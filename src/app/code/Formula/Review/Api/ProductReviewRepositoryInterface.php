<?php
namespace Formula\Review\Api;

interface ProductReviewRepositoryInterface
{
    /**
     * Get product reviews
     *
     * @param string $sku
     * @return \Formula\Review\Api\Data\ReviewInterface[]
     */
    public function getList($sku);

    /**
     * Get review by ID
     *
     * @param int $id
     * @return \Formula\Review\Api\Data\ReviewInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get($id);

    /**
     * Create review
     *
     * @param \Formula\Review\Api\Data\ReviewInterface $review
     * @return \Formula\Review\Api\Data\ReviewInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function create(\Formula\Review\Api\Data\ReviewInterface $review);
}