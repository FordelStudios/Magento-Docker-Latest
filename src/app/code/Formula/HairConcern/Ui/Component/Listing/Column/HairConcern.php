<?php
namespace Formula\HairConcern\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Formula\HairConcern\Model\ResourceModel\HairConcern\CollectionFactory;

class HairConcern extends Column
{
    /**
     * @var CollectionFactory
     */
    protected $hairconcernCollectionFactory;
    
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param CollectionFactory $hairconcernCollectionFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CollectionFactory $hairconcernCollectionFactory,
        array $components = [],
        array $data = []
    ) {
        $this->hairconcernCollectionFactory = $hairconcernCollectionFactory;
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
            // Create a map of hairconcern IDs to names for quick lookup
            $hairconcernCollection = $this->hairconcernCollectionFactory->create();
            $hairconcernMap = [];
            
            foreach ($hairconcernCollection as $hairconcern) {
                $hairconcernMap[$hairconcern->getId()] = $hairconcern->getName();
            }
            
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['hairconcern'])) {
                    $hairconcernIds = explode(',', $item['hairconcern']);
                    $hairconcernNames = [];
                    
                    foreach ($hairconcernIds as $id) {
                        if (isset($hairconcernMap[$id])) {
                            $hairconcernNames[] = $hairconcernMap[$id];
                        }
                    }
                    
                    $item[$this->getData('name')] = implode(', ', $hairconcernNames);
                }
            }
        }

        return $dataSource;
    }
}