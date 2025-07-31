<?php
namespace Formula\SkinType\Model\Product\Attribute\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Formula\SkinType\Model\ResourceModel\SkinType\CollectionFactory;

class SkinType extends AbstractSource
{
    /**
     * @var CollectionFactory
     */
    protected $skintypeCollectionFactory;

    /**
     * @param CollectionFactory $skintypeCollectionFactory
     */
    public function __construct(
        CollectionFactory $skintypeCollectionFactory
    ) {
        $this->skintypeCollectionFactory = $skintypeCollectionFactory;
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
            
            // Get all skintypes from your skintype collection
            $skintypes = $this->skintypeCollectionFactory->create();
            
            foreach ($skintypes as $skintype) {
                $this->_options[] = [
                    'label' => $skintype->getName(),
                    'value' => $skintype->getId()
                ];
            }
        }
        
        return $this->_options;
    }
}