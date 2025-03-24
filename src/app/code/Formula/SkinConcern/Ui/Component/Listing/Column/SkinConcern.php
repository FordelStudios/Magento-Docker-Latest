<?php
namespace Formula\SkinConcern\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Formula\SkinConcern\Model\ResourceModel\SkinConcern\CollectionFactory;

class SkinConcern extends Column
{
    /**
     * @var CollectionFactory
     */
    protected $skinconcernCollectionFactory;
    
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param CollectionFactory $skinconcernCollectionFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CollectionFactory $skinconcernCollectionFactory,
        array $components = [],
        array $data = []
    ) {
        $this->skinconcernCollectionFactory = $skinconcernCollectionFactory;
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
            // Create a map of skinconcern IDs to names for quick lookup
            $skinconcernCollection = $this->skinconcernCollectionFactory->create();
            $skinconcernMap = [];
            
            foreach ($skinconcernCollection as $skinconcern) {
                $skinconcernMap[$skinconcern->getId()] = $skinconcern->getName();
            }
            
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['skinconcern'])) {
                    $skinconcernIds = explode(',', $item['skinconcern']);
                    $skinconcernNames = [];
                    
                    foreach ($skinconcernIds as $id) {
                        if (isset($skinconcernMap[$id])) {
                            $skinconcernNames[] = $skinconcernMap[$id];
                        }
                    }
                    
                    $item[$this->getData('name')] = implode(', ', $skinconcernNames);
                }
            }
        }

        return $dataSource;
    }
}