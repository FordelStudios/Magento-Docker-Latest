<?php
namespace Formula\SkinConcern\Model\Product\Attribute\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Formula\SkinConcern\Model\ResourceModel\SkinConcern\CollectionFactory;

class SkinConcern extends AbstractSource
{
    /**
     * @var CollectionFactory
     */
    protected $skinconcernCollectionFactory;

    /**
     * @param CollectionFactory $skinconcernCollectionFactory
     */
    public function __construct(
        CollectionFactory $skinconcernCollectionFactory
    ) {
        $this->skinconcernCollectionFactory = $skinconcernCollectionFactory;
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
            
            // Get all skinconcerns from your skinconcern collection
            $skinconcerns = $this->skinconcernCollectionFactory->create();
            
            foreach ($skinconcerns as $skinconcern) {
                $this->_options[] = [
                    'label' => $skinconcern->getName(),
                    'value' => $skinconcern->getId()
                ];
            }
        }
        
        return $this->_options;
    }
}