<?php
// app/code/Formula/CategoryBanners/Api/CategoryBannerRepositoryInterface.php
namespace Formula\CategoryBanners\Api;

use Formula\CategoryBanners\Api\Data\CategoryBannerInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @api
 */
interface CategoryBannerRepositoryInterface
{
    /**
     * Save banner
     *
     * @param CategoryBannerInterface $banner
     * @return CategoryBannerInterface
     * @throws CouldNotSaveException
     */
    public function save(CategoryBannerInterface $banner);

    /**
     * Get banner by ID
     *
     * @param int $id
     * @return CategoryBannerInterface
     * @throws NoSuchEntityException
     */
    public function getById($id);

    /**
     * Get banners by category ID
     *
     * @param int $categoryId
     * @return CategoryBannerInterface[]
     */
    public function getByCategoryId($categoryId);

    /**
     * Delete banner
     *
     * @param CategoryBannerInterface $banner
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(CategoryBannerInterface $banner);

    /**
     * Delete banner by ID
     *
     * @param int $id
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById($id);
}