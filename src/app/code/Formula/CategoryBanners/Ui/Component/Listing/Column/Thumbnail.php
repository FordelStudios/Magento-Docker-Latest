<?php
// app/code/Formula/CategoryBanners/Ui/Component/Listing/Column/Thumbnail.php
namespace Formula\CategoryBanners\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class Thumbnail extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var UrlInterface
     */
    protected $url;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $url
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        StoreManagerInterface $storeManager,
        UrlInterface $url,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->storeManager = $storeManager;
        $this->url = $url;
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
            $fieldName = $this->getData('name');
            $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[$fieldName]) && $item[$fieldName]) {
                    $imageUrl = $mediaUrl . 'formula/categorybanner/' . $item[$fieldName];
                    $item[$fieldName . '_src'] = $imageUrl;
                    $item[$fieldName . '_alt'] = 'Banner #' . $item['entity_id'];
                    $item[$fieldName . '_link'] = $this->url->getUrl(
                        'categorybanner/categorybanner/edit',
                        ['id' => $item['entity_id']]
                    );
                    $item[$fieldName . '_orig_src'] = $imageUrl;
                }
            }
        }

        return $dataSource;
    }
}