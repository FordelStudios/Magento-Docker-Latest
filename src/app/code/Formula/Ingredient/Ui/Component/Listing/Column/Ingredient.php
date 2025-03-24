<?php
namespace Formula\Ingredient\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Formula\Ingredient\Model\IngredientRepository;

class Ingredient extends Column
{
    /**
     * @var IngredientRepository
     */
    protected $ingredientRepository;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param IngredientRepository $ingredientRepository
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        IngredientRepository $ingredientRepository,
        array $components = [],
        array $data = []
    ) {
        $this->ingredientRepository = $ingredientRepository;
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
                if (isset($item['ingredient']) && !empty($item['ingredient'])) {
                    try {
                        $ingredient = $this->ingredientRepository->getById($item['ingredient']);
                        $item[$this->getData('name')] = $ingredient->getName();
                    } catch (\Exception $e) {
                        $item[$this->getData('name')] = __('Unknown Ingredient');
                    }
                } else {
                    $item[$this->getData('name')] = __('Not Set');
                }
            }
        }
        
        return $dataSource;
    }
}