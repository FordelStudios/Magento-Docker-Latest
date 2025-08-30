<?php
declare(strict_types=1);

namespace Formula\HomeContent\Ui\DataProvider\HomeContent;

use Formula\HomeContent\Model\ResourceModel\HomeContent\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;

class ListingDataProvider extends AbstractDataProvider
{
    protected $collection;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }

    public function getData()
    {
        if (!$this->getCollection()->isLoaded()) {
            $this->getCollection()->load();
        }
        
        $data = $this->getCollection()->toArray();
        
        return [
            'totalRecords' => $this->getCollection()->getSize(),
            'items' => array_values($data['items'])
        ];
    }
}