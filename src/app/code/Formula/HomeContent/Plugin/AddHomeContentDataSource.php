<?php

namespace Formula\HomeContent\Plugin;

class AddHomeContentDataSource
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
     * Add Home Content data source
     *
     * @param \Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory $subject
     * @param \Closure $proceed
     * @param string $requestName
     * @return \Magento\Framework\Data\Collection
     */
    public function aroundGetReport(
        \Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory $subject,
        \Closure $proceed,
        $requestName
    ) {
        if ($requestName === 'formula_homecontent_listing_data_source') {
            return $this->objectManager->create('FormulaHomeContentGridCollection');
        }
        
        return $proceed($requestName);
    }
}