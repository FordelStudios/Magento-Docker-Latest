<?php
namespace Formula\Blog\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class Products implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @param CollectionFactory $productCollectionFactory
     */
    public function __construct(
        CollectionFactory $productCollectionFactory
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $collection = $this->productCollectionFactory->create()
            ->addAttributeToSelect(['name'])
            ->addAttributeToFilter('status', 1)
            ->setOrder('name', 'ASC');
        
        $options = [];
        foreach ($collection as $product) {
            $options[] = [
                'value' => $product->getId(),
                'label' => $product->getName() . ' (ID: ' . $product->getId() . ')'
            ];
        }
        
        return $options;
    }
}