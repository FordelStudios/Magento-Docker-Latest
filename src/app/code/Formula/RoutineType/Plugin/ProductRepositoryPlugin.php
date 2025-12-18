<?php
declare(strict_types=1);

namespace Formula\RoutineType\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Formula\RoutineType\Model\Config\Source\RoutineType;

class ProductRepositoryPlugin
{
    /**
     * @var RoutineType
     */
    private $routineTypeSource;

    /**
     * @param RoutineType $routineTypeSource
     */
    public function __construct(RoutineType $routineTypeSource)
    {
        $this->routineTypeSource = $routineTypeSource;
    }

    /**
     * Add routine type labels extension attribute after getting product
     *
     * @param ProductRepositoryInterface $subject
     * @param ProductInterface $product
     * @return ProductInterface
     */
    public function afterGet(
        ProductRepositoryInterface $subject,
        ProductInterface $product
    ) {
        $this->addRoutineTypeLabels($product);
        return $product;
    }

    /**
     * Add routine type labels extension attribute after getting product list
     *
     * @param ProductRepositoryInterface $subject
     * @param ProductSearchResultsInterface $searchResults
     * @return ProductSearchResultsInterface
     */
    public function afterGetList(
        ProductRepositoryInterface $subject,
        ProductSearchResultsInterface $searchResults
    ) {
        $products = $searchResults->getItems();

        foreach ($products as $product) {
            $this->addRoutineTypeLabels($product);
        }

        return $searchResults;
    }

    /**
     * Add routine type labels to product extension attributes
     *
     * @param ProductInterface $product
     * @return void
     */
    private function addRoutineTypeLabels(ProductInterface $product): void
    {
        $extensionAttributes = $product->getExtensionAttributes();
        if (!$extensionAttributes) {
            return;
        }

        $routineTypeAttribute = $product->getCustomAttribute('routine_type');
        if ($routineTypeAttribute && $routineTypeAttribute->getValue()) {
            $routineTypeValue = $routineTypeAttribute->getValue();
            $routineTypeIds = explode(',', (string)$routineTypeValue);
            $routineTypeLabels = [];

            foreach ($routineTypeIds as $routineTypeId) {
                $label = $this->routineTypeSource->getOptionText(trim($routineTypeId));
                if ($label && $label !== '-- Please Select --') {
                    $routineTypeLabels[] = $label;
                }
            }

            if (!empty($routineTypeLabels)) {
                $extensionAttributes->setRoutineTypeLabels(implode(', ', $routineTypeLabels));
            }
        }

        $product->setExtensionAttributes($extensionAttributes);
    }
}
