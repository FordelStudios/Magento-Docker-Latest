<?php
namespace Formula\Brand\Model\Product\Attribute\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Formula\Brand\Model\ResourceModel\Brand\CollectionFactory;

class Brand extends AbstractSource
{
    /**
     * @var CollectionFactory
     */
    protected $brandCollectionFactory;

    /**
     * @param CollectionFactory $brandCollectionFactory
     */
    public function __construct(
        CollectionFactory $brandCollectionFactory
    ) {
        $this->brandCollectionFactory = $brandCollectionFactory;
    }

    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        if (!$this->_options) {
            $this->_options = [];
            
            // Add empty option
            $this->_options[] = ['label' => __('-- Please Select --'), 'value' => ''];
            
            // Get all brands from your brand collection
            $brands = $this->brandCollectionFactory->create();
            
            foreach ($brands as $brand) {
                $this->_options[] = [
                    'label' => $brand->getName(),
                    'value' => $brand->getId()
                ];
            }
        }
        
        return $this->_options;
    }
}