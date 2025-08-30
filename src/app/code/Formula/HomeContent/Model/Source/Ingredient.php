<?php
declare(strict_types=1);

namespace Formula\HomeContent\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Ingredient implements OptionSourceInterface
{
    protected $ingredientCollection;

    public function __construct(
        \Formula\Ingredient\Model\ResourceModel\Ingredient\CollectionFactory $ingredientCollectionFactory
    ) {
        $this->ingredientCollection = $ingredientCollectionFactory;
    }

    public function toOptionArray()
    {
        $options = [
            [
                'value' => '',
                'label' => __('Select Ingredient')
            ]
        ];

        $collection = $this->ingredientCollection->create();
        foreach ($collection as $ingredient) {
            $options[] = [
                'value' => $ingredient->getIngredientId(),
                'label' => $ingredient->getName()
            ];
        }

        return $options;
    }
}