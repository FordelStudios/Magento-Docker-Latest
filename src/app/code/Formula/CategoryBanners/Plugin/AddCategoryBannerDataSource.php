<?php

// src/app/code/Formula/CategoryBanners/Plugin/AddCategoryBannerDataSource.php
namespace Formula\CategoryBanners\Plugin;

class AddCategoryBannerDataSource
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * Add Category Banner data source
     *
     * @param \Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory $subject
     * @param string $requestName
     * @return \Magento\Framework\Data\Collection
     */
    public function aroundGetReport(
        \Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory $subject,
        \Closure $proceed,
        $requestName
    ) {
        if ($requestName === 'categorybanner_listing_data_source') {
            return $this->objectManager->create(
                'Formula\CategoryBanners\Model\ResourceModel\CategoryBanner\Grid\Collection'
            );
        }
        
        return $proceed($requestName);
    }
}