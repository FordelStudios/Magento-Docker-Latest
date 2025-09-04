<?php
namespace Formula\Brand\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Store\Model\StoreManagerInterface;

class SalePageBanner extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param StoreManagerInterface $storeManager
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        StoreManagerInterface $storeManager,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->storeManager = $storeManager;
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
            $mediaUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
            
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['sale_page_banner'])) {
                    $bannerUrl = $mediaUrl . 'brand/sale_page_banner/' . $item['sale_page_banner'];
                    $item['sale_page_banner_src'] = $bannerUrl;
                    $item['sale_page_banner_alt'] = $item['name'] ?? 'Sale Page Banner';
                    $item['sale_page_banner_link'] = $bannerUrl;
                    $item['sale_page_banner_orig_src'] = $bannerUrl;
                }
            }
        }

        return $dataSource;
    }
}