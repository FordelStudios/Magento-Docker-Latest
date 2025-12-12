<?php

namespace Formula\ProductRoutine\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Formula\ProductRoutine\Model\Config\Source\FaceRoutineType;
use Formula\ProductRoutine\Model\Config\Source\HairRoutineType;
use Formula\ProductRoutine\Model\Config\Source\BodyRoutineType;
use Formula\ProductRoutine\Model\Config\Source\RoutineTiming;

class ProductRepositoryPlugin
{
    /**
     * @var FaceRoutineType
     */
    protected $faceRoutineType;

    /**
     * @var HairRoutineType
     */
    protected $hairRoutineType;

    /**
     * @var BodyRoutineType
     */
    protected $bodyRoutineType;

    /**
     * @var RoutineTiming
     */
    protected $routineTiming;

    /**
     * @param FaceRoutineType $faceRoutineType
     * @param HairRoutineType $hairRoutineType
     * @param BodyRoutineType $bodyRoutineType
     * @param RoutineTiming $routineTiming
     */
    public function __construct(
        FaceRoutineType $faceRoutineType,
        HairRoutineType $hairRoutineType,
        BodyRoutineType $bodyRoutineType,
        RoutineTiming $routineTiming
    ) {
        $this->faceRoutineType = $faceRoutineType;
        $this->hairRoutineType = $hairRoutineType;
        $this->bodyRoutineType = $bodyRoutineType;
        $this->routineTiming = $routineTiming;
    }

    /**
     * Add routine type labels to product extension attributes
     *
     * @param ProductRepositoryInterface $subject
     * @param ProductInterface $product
     * @return ProductInterface
     */
    public function afterGet(
        ProductRepositoryInterface $subject,
        ProductInterface $product
    ) {
        $this->addRoutineLabels($product);
        return $product;
    }

    /**
     * Add routine type labels to product list
     *
     * @param ProductRepositoryInterface $subject
     * @param \Magento\Catalog\Api\Data\ProductSearchResultsInterface $searchResults
     * @return \Magento\Catalog\Api\Data\ProductSearchResultsInterface
     */
    public function afterGetList(
        ProductRepositoryInterface $subject,
        \Magento\Catalog\Api\Data\ProductSearchResultsInterface $searchResults
    ) {
        $products = $searchResults->getItems();

        foreach ($products as $product) {
            $this->addRoutineLabels($product);
        }

        return $searchResults;
    }

    /**
     * Add routine type labels to product
     *
     * @param ProductInterface $product
     * @return void
     */
    protected function addRoutineLabels(ProductInterface $product)
    {
        $extensionAttributes = $product->getExtensionAttributes();
        if (!$extensionAttributes) {
            return;
        }

        // Process Face Routine Type
        $faceRoutineAttribute = $product->getCustomAttribute('face_routine_type');
        if ($faceRoutineAttribute && $faceRoutineAttribute->getValue()) {
            $faceRoutineValues = explode(',', $faceRoutineAttribute->getValue());
            $faceRoutineLabels = [];

            foreach ($faceRoutineValues as $value) {
                $label = $this->faceRoutineType->getOptionText($value);
                if ($label) {
                    $faceRoutineLabels[] = $label;
                }
            }

            if (!empty($faceRoutineLabels)) {
                $extensionAttributes->setFaceRoutineTypeLabels(implode(', ', $faceRoutineLabels));
            }
        }

        // Process Hair Routine Type
        $hairRoutineAttribute = $product->getCustomAttribute('hair_routine_type');
        if ($hairRoutineAttribute && $hairRoutineAttribute->getValue()) {
            $hairRoutineValues = explode(',', $hairRoutineAttribute->getValue());
            $hairRoutineLabels = [];

            foreach ($hairRoutineValues as $value) {
                $label = $this->hairRoutineType->getOptionText($value);
                if ($label) {
                    $hairRoutineLabels[] = $label;
                }
            }

            if (!empty($hairRoutineLabels)) {
                $extensionAttributes->setHairRoutineTypeLabels(implode(', ', $hairRoutineLabels));
            }
        }

        // Process Body Routine Type
        $bodyRoutineAttribute = $product->getCustomAttribute('body_routine_type');
        if ($bodyRoutineAttribute && $bodyRoutineAttribute->getValue()) {
            $bodyRoutineValues = explode(',', $bodyRoutineAttribute->getValue());
            $bodyRoutineLabels = [];

            foreach ($bodyRoutineValues as $value) {
                $label = $this->bodyRoutineType->getOptionText($value);
                if ($label) {
                    $bodyRoutineLabels[] = $label;
                }
            }

            if (!empty($bodyRoutineLabels)) {
                $extensionAttributes->setBodyRoutineTypeLabels(implode(', ', $bodyRoutineLabels));
            }
        }

        // Process Routine Timing
        $timingAttribute = $product->getCustomAttribute('routine_timing');
        if ($timingAttribute && $timingAttribute->getValue()) {
            $timingValue = $timingAttribute->getValue();
            $timingLabel = $this->routineTiming->getOptionText($timingValue);

            if ($timingLabel) {
                $extensionAttributes->setRoutineTimingLabel($timingLabel);
            }
        }

        $product->setExtensionAttributes($extensionAttributes);
    }
}
