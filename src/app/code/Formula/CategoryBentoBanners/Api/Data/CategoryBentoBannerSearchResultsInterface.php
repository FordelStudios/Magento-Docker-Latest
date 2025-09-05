<?php
namespace Formula\CategoryBentoBanners\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface for bento banner search results.
 * @api
 */
interface CategoryBentoBannerSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get bento banners list.
     *
     * @return \Formula\CategoryBentoBanners\Api\Data\CategoryBentoBannerInterface[]
     */
    public function getItems();

    /**
     * Set bento banners list.
     *
     * @param \Formula\CategoryBentoBanners\Api\Data\CategoryBentoBannerInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}