<?php
namespace Formula\FaceConcern\Model\Product\Attribute\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Formula\FaceConcern\Model\ResourceModel\FaceConcern\CollectionFactory;

class FaceConcern extends AbstractSource
{
    /**
     * @var CollectionFactory
     */
    protected $faceconcernCollectionFactory;

    /**
     * @param CollectionFactory $faceconcernCollectionFactory
     */
    public function __construct(
        CollectionFactory $faceconcernCollectionFactory
    ) {
        $this->faceconcernCollectionFactory = $faceconcernCollectionFactory;
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
            
            // Get all faceconcerns from your faceconcern collection
            $faceconcerns = $this->faceconcernCollectionFactory->create();
            
            foreach ($faceconcerns as $faceconcern) {
                $this->_options[] = [
                    'label' => $faceconcern->getName(),
                    'value' => $faceconcern->getId()
                ];
            }
        }
        
        return $this->_options;
    }
}