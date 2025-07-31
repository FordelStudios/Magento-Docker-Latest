<?php

// src/app/code/Formula/CountryFormulaBanners/Plugin/AddCountryFormulaBannerDataSource.php
namespace Formula\CountryFormulaBanners\Plugin;

class AddCountryFormulaBannerDataSource
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
     * Add Country Formula Banner data source
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
        if ($requestName === 'countryformulabanner_listing_data_source') {
            return $this->objectManager->create(
                'Formula\CountryFormulaBanners\Model\ResourceModel\CountryFormulaBanner\Grid\Collection'
            );
        }
        
        return $proceed($requestName);
    }
}