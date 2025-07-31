<?php
namespace Formula\SkinType\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Formula\SkinType\Model\ResourceModel\SkinType\CollectionFactory;

class SkinType extends Column
{
    /**
     * @var CollectionFactory
     */
    protected $skintypeCollectionFactory;
    
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param CollectionFactory $skintypeCollectionFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CollectionFactory $skintypeCollectionFactory,
        array $components = [],
        array $data = []
    ) {
        $this->skintypeCollectionFactory = $skintypeCollectionFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            // Create a map of skintype IDs to names for quick lookup
            $skintypeCollection = $this->skintypeCollectionFactory->create();
            $skintypeMap = [];
            
            foreach ($skintypeCollection as $skintype) {
                $skintypeMap[$skintype->getId()] = $skintype->getName();
            }
            
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['skintype'])) {
                    $skintypeIds = explode(',', $item['skintype']);
                    $skintypeNames = [];
                    
                    foreach ($skintypeIds as $id) {
                        if (isset($skintypeMap[$id])) {
                            $skintypeNames[] = $skintypeMap[$id];
                        }
                    }
                    
                    $item[$this->getData('name')] = implode(', ', $skintypeNames);
                }
            }
        }

        return $dataSource;
    }
}