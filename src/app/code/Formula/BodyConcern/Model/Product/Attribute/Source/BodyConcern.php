<?php
namespace Formula\BodyConcern\Model\Product\Attribute\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Formula\BodyConcern\Model\ResourceModel\BodyConcern\CollectionFactory;

class BodyConcern extends AbstractSource
{
    /**
     * @var CollectionFactory
     */
    protected $bodyconcernCollectionFactory;

    /**
     * @param CollectionFactory $bodyconcernCollectionFactory
     */
    public function __construct(
        CollectionFactory $bodyconcernCollectionFactory
    ) {
        $this->bodyconcernCollectionFactory = $bodyconcernCollectionFactory;
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
            
            // Get all bodyconcerns from your bodyconcern collection
            $bodyconcerns = $this->bodyconcernCollectionFactory->create();
            
            foreach ($bodyconcerns as $bodyconcern) {
                $this->_options[] = [
                    'label' => $bodyconcern->getName(),
                    'value' => $bodyconcern->getId()
                ];
            }
        }
        
        return $this->_options;
    }
}