<?php
namespace Formula\Brand\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Formula\Brand\Model\BrandRepository;

class Brand extends Column
{
    /**
     * @var BrandRepository
     */
    protected $brandRepository;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param BrandRepository $brandRepository
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        BrandRepository $brandRepository,
        array $components = [],
        array $data = []
    ) {
        $this->brandRepository = $brandRepository;
        parent::__construct($context, $uiComponentFactory, $components, $data);
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
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['brand']) && !empty($item['brand'])) {
                    try {
                        $brand = $this->brandRepository->getById($item['brand']);
                        $item[$this->getData('name')] = $brand->getName();
                    } catch (\Exception $e) {
                        $item[$this->getData('name')] = __('Unknown Brand');
                    }
                } else {
                    $item[$this->getData('name')] = __('Not Set');
                }
            }
        }
        
        return $dataSource;
    }
}