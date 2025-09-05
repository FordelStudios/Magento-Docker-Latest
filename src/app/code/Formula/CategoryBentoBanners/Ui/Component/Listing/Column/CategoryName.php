<?php
namespace Formula\CategoryBentoBanners\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Catalog\Api\CategoryRepositoryInterface;

class CategoryName extends Column
{
    protected $categoryRepository;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CategoryRepositoryInterface $categoryRepository,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->categoryRepository = $categoryRepository;
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
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['category_id']) && $item['category_id']) {
                    try {
                        $category = $this->categoryRepository->get($item['category_id']);
                        $item['category_name'] = $category->getName();
                    } catch (\Exception $e) {
                        $item['category_name'] = 'Unknown';
                    }
                } else {
                    $item['category_name'] = 'Unknown';
                }
            }
        }

        return $dataSource;
    }
}