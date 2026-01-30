<?php
declare(strict_types=1);

namespace Formula\SpecialOffer\Model\SpecialOffer;

use Formula\SpecialOffer\Model\ResourceModel\SpecialOffer\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
    private array $loadedData = [];

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }

    /**
     * @inheritDoc
     */
    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        foreach ($items as $specialOffer) {
            $data = $specialOffer->getData();

            // Convert image to format expected by UI component
            if (isset($data['image']) && $data['image']) {
                $imageName = $data['image'];
                $data['image'] = [
                    [
                        'name' => $imageName,
                        'url' => $this->getMediaUrl($imageName),
                    ]
                ];
            }

            $this->loadedData[$specialOffer->getEntityId()] = $data;
        }

        $data = $this->dataPersistor->get('formula_special_offer');
        if (!empty($data)) {
            $specialOffer = $this->collection->getNewEmptyItem();
            $specialOffer->setData($data);
            $this->loadedData[$specialOffer->getEntityId()] = $specialOffer->getData();
            $this->dataPersistor->clear('formula_special_offer');
        }

        return $this->loadedData;
    }

    /**
     * Get media URL for image
     */
    private function getMediaUrl(string $imageName): string
    {
        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        );
        return $mediaUrl . 'specialoffer/' . $imageName;
    }
}
