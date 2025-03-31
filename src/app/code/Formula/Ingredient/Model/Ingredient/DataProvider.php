<?php
namespace Formula\Ingredient\Model\Ingredient;

use Formula\Ingredient\Model\ResourceModel\Ingredient\CollectionFactory;
use Formula\Ingredient\Model\IngredientFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    protected $collection;
    protected $dataPersistor;
    protected $loadedData;
    protected $ingredientFactory;
    protected $storeManager;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        IngredientFactory $ingredientFactory,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->ingredientFactory = $ingredientFactory;
        $this->storeManager = $storeManager;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();

        foreach ($items as $ingredient) {
            $data = $ingredient->getData();
            
            if (isset($data['logo']) && $data['logo']) {
                $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
                $data['logo'] = [
                    [
                        'name' => $data['logo'],
                        'url' => $mediaUrl . 'ingredient/logo/' . $data['logo'],
                        'type' => 'image'
                    ]
                ];
            }

            if (isset($data['benefits']) && !empty($data['benefits'])) {
                $decodedBenefits = json_decode($data['benefits'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedBenefits)) {
                    $data['benefits'] = implode(',', $decodedBenefits);
                }
            }

            
            $this->loadedData[$ingredient->getId()] = $data;
        }

        $data = $this->dataPersistor->get('ingredient');
        if (!empty($data)) {
            $ingredient = $this->ingredientFactory->create();
            $ingredient->setData($data);
            $this->loadedData[$ingredient->getId()] = $ingredient->getData();
            $this->dataPersistor->clear('ingredient');
        }

        return $this->loadedData ?: [];
    }
}