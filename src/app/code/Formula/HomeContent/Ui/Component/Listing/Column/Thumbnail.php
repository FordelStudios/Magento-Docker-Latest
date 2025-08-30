<?php
declare(strict_types=1);

namespace Formula\HomeContent\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;

class Thumbnail extends Column
{
    const ALT_FIELD = 'name';

    protected $storeManager;
    protected $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        StoreManagerInterface $storeManager,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->storeManager = $storeManager;
        $this->urlBuilder = $urlBuilder;
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item[$fieldName]) && $item[$fieldName]) {
                    $item[$fieldName . '_src'] = $this->getImageUrl($item[$fieldName]);
                    $item[$fieldName . '_alt'] = 'Image';
                    $item[$fieldName . '_orig_src'] = $this->getImageUrl($item[$fieldName]);
                    $item[$fieldName . '_link'] = $this->urlBuilder->getUrl(
                        'formula_homecontent/homecontent/edit',
                        ['id' => $item['entity_id']]
                    );
                } else {
                    $item[$fieldName . '_src'] = '';
                    $item[$fieldName . '_alt'] = 'No Image';
                    $item[$fieldName . '_orig_src'] = '';
                    $item[$fieldName . '_link'] = '';
                }
            }
        }

        return $dataSource;
    }

    protected function getImageUrl($imagePath)
    {
        if (!$imagePath) {
            return '';
        }

        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            return $imagePath;
        }

        $baseUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        return $baseUrl . 'formula/homecontent/' . ltrim($imagePath, '/');
    }
}