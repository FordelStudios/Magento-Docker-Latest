<?php
declare(strict_types=1);

namespace Formula\SpecialOffer\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class Thumbnail extends Column
{
    public const ALT_FIELD = 'title';

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritDoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');

            foreach ($dataSource['data']['items'] as &$item) {
                $imageName = $item[$fieldName] ?? '';
                $item[$fieldName . '_src'] = $this->getImageUrl($imageName);
                $item[$fieldName . '_alt'] = $item[self::ALT_FIELD] ?? '';
                $item[$fieldName . '_link'] = $this->urlBuilder->getUrl(
                    'specialoffer/specialoffer/edit',
                    ['entity_id' => $item['entity_id']]
                );
                $item[$fieldName . '_orig_src'] = $this->getImageUrl($imageName);
            }
        }

        return $dataSource;
    }

    /**
     * Get image URL
     */
    private function getImageUrl(string $imageName): string
    {
        if (!$imageName) {
            return '';
        }

        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        return $mediaUrl . 'specialoffer/' . $imageName;
    }
}
