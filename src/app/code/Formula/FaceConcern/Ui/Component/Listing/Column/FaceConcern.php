<?php
namespace Formula\FaceConcern\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Formula\FaceConcern\Model\ResourceModel\FaceConcern\CollectionFactory;

class FaceConcern extends Column
{
    /**
     * @var CollectionFactory
     */
    protected $faceconcernCollectionFactory;
    
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param CollectionFactory $faceconcernCollectionFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CollectionFactory $faceconcernCollectionFactory,
        array $components = [],
        array $data = []
    ) {
        $this->faceconcernCollectionFactory = $faceconcernCollectionFactory;
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
            // Create a map of faceconcern IDs to names for quick lookup
            $faceconcernCollection = $this->faceconcernCollectionFactory->create();
            $faceconcernMap = [];
            
            foreach ($faceconcernCollection as $faceconcern) {
                $faceconcernMap[$faceconcern->getId()] = $faceconcern->getName();
            }
            
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['faceconcern'])) {
                    $faceconcernIds = explode(',', $item['faceconcern']);
                    $faceconcernNames = [];
                    
                    foreach ($faceconcernIds as $id) {
                        if (isset($faceconcernMap[$id])) {
                            $faceconcernNames[] = $faceconcernMap[$id];
                        }
                    }
                    
                    $item[$this->getData('name')] = implode(', ', $faceconcernNames);
                }
            }
        }

        return $dataSource;
    }
}