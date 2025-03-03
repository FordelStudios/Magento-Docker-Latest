<?php
namespace Formula\Brand\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class PromotionalBanners extends Column
{
    protected $storeManager;
    protected $jsonSerializer;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        StoreManagerInterface $storeManager,
        Json $jsonSerializer,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->storeManager = $storeManager;
        $this->jsonSerializer = $jsonSerializer;
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $mediaUrl = $this->storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            );

            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['promotional_banners'])) {
                    try {
                        $banners = $this->jsonSerializer->unserialize($item['promotional_banners']);
                        if (is_array($banners)) {
                            $html = '<div class="grid-thumbnail-container">';
                            foreach ($banners as $banner) {
                                $imageUrl = $mediaUrl . 'brand/banner/' . $banner;
                                $html .= sprintf(
                                    '<img src="%s" alt="Banner" class="admin__control-thumbnail" style="max-width: 75px; margin: 2px;"/>',
                                    $imageUrl
                                );
                            }
                            $html .= '</div>';
                            $item['promotional_banners'] = $html;
                        }
                    } catch (\Exception $e) {
                        $item['promotional_banners'] = '';
                    }
                }
            }
        }

        return $dataSource;
    }
}