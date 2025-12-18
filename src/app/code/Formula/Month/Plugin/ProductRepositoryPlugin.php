<?php
declare(strict_types=1);

namespace Formula\Month\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Formula\Month\Model\Config\Source\Month;

class ProductRepositoryPlugin
{
    /**
     * @var Month
     */
    private $monthSource;

    /**
     * @param Month $monthSource
     */
    public function __construct(Month $monthSource)
    {
        $this->monthSource = $monthSource;
    }

    /**
     * Add month labels extension attribute after getting product
     *
     * @param ProductRepositoryInterface $subject
     * @param ProductInterface $product
     * @return ProductInterface
     */
    public function afterGet(
        ProductRepositoryInterface $subject,
        ProductInterface $product
    ) {
        $this->addMonthLabels($product);
        return $product;
    }

    /**
     * Add month labels extension attribute after getting product list
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
            $this->addMonthLabels($product);
        }

        return $searchResults;
    }

    /**
     * Add month labels to product extension attributes
     *
     * @param ProductInterface $product
     * @return void
     */
    private function addMonthLabels(ProductInterface $product): void
    {
        $extensionAttributes = $product->getExtensionAttributes();
        if (!$extensionAttributes) {
            return;
        }

        $monthAttribute = $product->getCustomAttribute('month');
        if ($monthAttribute && $monthAttribute->getValue()) {
            $monthValue = $monthAttribute->getValue();
            $monthIds = explode(',', (string)$monthValue);
            $monthLabels = [];

            foreach ($monthIds as $monthId) {
                $label = $this->monthSource->getOptionText(trim($monthId));
                if ($label && $label !== '-- Please Select --') {
                    $monthLabels[] = $label;
                }
            }

            if (!empty($monthLabels)) {
                $extensionAttributes->setMonthLabels(implode(', ', $monthLabels));
            }
        }

        $product->setExtensionAttributes($extensionAttributes);
    }
}
