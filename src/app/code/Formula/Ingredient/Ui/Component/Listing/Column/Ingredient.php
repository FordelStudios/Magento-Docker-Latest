<?php
namespace Formula\Ingredient\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Formula\Ingredient\Model\ResourceModel\Ingredient\CollectionFactory;

class Ingredient extends Column
{
    /**
     * @var CollectionFactory
     */
    protected $ingredientCollectionFactory;
    
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param CollectionFactory $ingredientCollectionFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CollectionFactory $ingredientCollectionFactory,
        array $components = [],
        array $data = []
    ) {
        $this->ingredientCollectionFactory = $ingredientCollectionFactory;
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
            // Create a map of ingredient IDs to names for quick lookup
            $ingredientCollection = $this->ingredientCollectionFactory->create();
            $ingredientMap = [];
            
            foreach ($ingredientCollection as $ingredient) {
                $ingredientMap[$ingredient->getId()] = $ingredient->getName();
            }
            
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['ingredient'])) {
                    $ingredientIds = explode(',', $item['ingredient']);
                    $ingredientNames = [];
                    
                    foreach ($ingredientIds as $id) {
                        if (isset($ingredientMap[$id])) {
                            $ingredientNames[] = $ingredientMap[$id];
                        }
                    }
                    
                    $item[$this->getData('name')] = implode(', ', $ingredientNames);
                }
            }
        }

        return $dataSource;
    }
}