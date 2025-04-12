<?php
namespace Formula\Reel\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class Video extends Column
{
    protected $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[$fieldName]) && !empty($item[$fieldName])) {
                    $mediaUrl = $this->urlBuilder->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]);
                    $videoUrl = $mediaUrl . 'reel/video/' . $item[$fieldName];
                    
                    // Create video preview with controls
                    $item[$fieldName] = '
                    <video width="150" height="100" controls>
                        <source src="' . $videoUrl . '" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>';
                }
            }
        }

        return $dataSource;
    }
}