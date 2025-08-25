<?php
namespace Formula\HairConcern\Model\Product\Attribute\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Formula\HairConcern\Model\ResourceModel\HairConcern\CollectionFactory;

class HairConcern extends AbstractSource
{
    /**
     * @var CollectionFactory
     */
    protected $hairconcernCollectionFactory;

    /**
     * @param CollectionFactory $hairconcernCollectionFactory
     */
    public function __construct(
        CollectionFactory $hairconcernCollectionFactory
    ) {
        $this->hairconcernCollectionFactory = $hairconcernCollectionFactory;
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
            
            // Get all hairconcerns from your hairconcern collection
            $hairconcerns = $this->hairconcernCollectionFactory->create();
            
            foreach ($hairconcerns as $hairconcern) {
                $this->_options[] = [
                    'label' => $hairconcern->getName(),
                    'value' => $hairconcern->getId()
                ];
            }
        }
        
        return $this->_options;
    }
}