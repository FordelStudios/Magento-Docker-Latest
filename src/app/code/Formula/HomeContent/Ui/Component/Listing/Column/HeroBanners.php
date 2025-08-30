<?php
declare(strict_types=1);

namespace Formula\HomeContent\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Store\Model\StoreManagerInterface;

class HeroBanners extends Column
{
    protected $storeManager;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        StoreManagerInterface $storeManager,
        array $components = [],
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item[$fieldName]) && $item[$fieldName]) {
                    $heroBanners = json_decode($item[$fieldName], true);
                    if (is_array($heroBanners) && !empty($heroBanners)) {
                        $count = count($heroBanners);
                        $item[$fieldName] = $count . ' image' . ($count > 1 ? 's' : '');
                    } else {
                        $item[$fieldName] = 'No images';
                    }
                } else {
                    $item[$fieldName] = 'No images';
                }
            }
        }

        return $dataSource;
    }
}