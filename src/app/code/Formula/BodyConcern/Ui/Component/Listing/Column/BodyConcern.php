<?php
namespace Formula\BodyConcern\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Formula\BodyConcern\Model\ResourceModel\BodyConcern\CollectionFactory;

class BodyConcern extends Column
{
    /**
     * @var CollectionFactory
     */
    protected $bodyconcernCollectionFactory;
    
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param CollectionFactory $bodyconcernCollectionFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CollectionFactory $bodyconcernCollectionFactory,
        array $components = [],
        array $data = []
    ) {
        $this->bodyconcernCollectionFactory = $bodyconcernCollectionFactory;
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
            // Create a map of bodyconcern IDs to names for quick lookup
            $bodyconcernCollection = $this->bodyconcernCollectionFactory->create();
            $bodyconcernMap = [];
            
            foreach ($bodyconcernCollection as $bodyconcern) {
                $bodyconcernMap[$bodyconcern->getId()] = $bodyconcern->getName();
            }
            
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['bodyconcern'])) {
                    $bodyconcernIds = explode(',', $item['bodyconcern']);
                    $bodyconcernNames = [];
                    
                    foreach ($bodyconcernIds as $id) {
                        if (isset($bodyconcernMap[$id])) {
                            $bodyconcernNames[] = $bodyconcernMap[$id];
                        }
                    }
                    
                    $item[$this->getData('name')] = implode(', ', $bodyconcernNames);
                }
            }
        }

        return $dataSource;
    }
}