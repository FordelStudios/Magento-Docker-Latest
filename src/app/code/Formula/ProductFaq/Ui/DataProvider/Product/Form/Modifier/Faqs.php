<?php
declare(strict_types=1);

namespace Formula\ProductFaq\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Element\Textarea;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;

/**
 * Product-form modifier that renders the per-product FAQs editor as a
 * friendly repeating-rows (dynamicRows) control: each row is a Question
 * input + an Answer textarea, with add / remove. Admins never touch the
 * raw JSON that backs the `faqs` attribute.
 */
class Faqs extends AbstractModifier
{
    /**
     * Attribute / field code that stores the FAQ JSON.
     */
    const FIELD_FAQS = 'faqs';

    /**
     * Fieldset name injected into the product form meta.
     */
    const FIELDSET_FAQS = 'product_faqs';

    /**
     * @var LocatorInterface
     */
    private $locator;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param LocatorInterface $locator
     * @param Json $serializer
     */
    public function __construct(
        LocatorInterface $locator,
        Json $serializer
    ) {
        $this->locator = $locator;
        $this->serializer = $serializer;
    }

    /**
     * Bind the stored FAQ rows so the dynamicRows editor is pre-populated on edit.
     *
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
        $product = $this->locator->getProduct();
        $productId = $product->getId();

        if (!$productId) {
            return $data;
        }

        $rows = [];
        $rawFaqs = $product->getData(self::FIELD_FAQS);

        if ($rawFaqs !== null && $rawFaqs !== '') {
            try {
                $decoded = $this->serializer->unserialize((string)$rawFaqs);
            } catch (\Exception $e) {
                $decoded = null;
            }

            if (is_array($decoded)) {
                foreach ($decoded as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $rows[] = [
                        'question' => isset($row['question']) ? (string)$row['question'] : '',
                        'answer' => isset($row['answer']) ? (string)$row['answer'] : '',
                    ];
                }
            }
        }

        // VERIFY: dynamicRows binding on real admin.
        // Standard product-form modifier convention binds attribute data under
        // $data[$productId][DATA_SOURCE_DEFAULT]['<field>'] (DATA_SOURCE_DEFAULT = 'product'),
        // which maps to the form dataScope `data.product.faqs`.
        $data[$productId][self::DATA_SOURCE_DEFAULT][self::FIELD_FAQS] = $rows;

        return $data;
    }

    /**
     * Inject the FAQs fieldset + dynamicRows editor into the product form meta.
     *
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        $meta[self::FIELDSET_FAQS] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Product FAQs'),
                        'componentType' => Fieldset::NAME,
                        'dataScope' => '',
                        'collapsible' => true,
                        'sortOrder' => 200,
                    ],
                ],
            ],
            'children' => [
                self::FIELD_FAQS => $this->getFaqsDynamicRowsConfig(),
            ],
        ];

        return $meta;
    }

    /**
     * Meta config for the `faqs` dynamicRows element.
     *
     * @return array
     */
    private function getFaqsDynamicRowsConfig(): array
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => 'dynamicRows',
                        'label' => __('Product FAQs'),
                        'addButtonLabel' => __('Add FAQ'),
                        'renderDefaultRecord' => false,
                        'recordTemplate' => 'record',
                        'dataScope' => self::FIELD_FAQS,
                        'dndConfig' => [
                            'enabled' => true,
                        ],
                        'sortOrder' => 10,
                    ],
                ],
            ],
            'children' => [
                'record' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'componentType' => Container::NAME,
                                'component' => 'Magento_Ui/js/dynamic-rows/record',
                                'isTemplate' => true,
                                'is_collection' => true,
                                'dataScope' => '',
                            ],
                        ],
                    ],
                    'children' => [
                        'question' => $this->getInputFieldConfig(
                            __('Question'),
                            'question',
                            Input::NAME,
                            10
                        ),
                        'answer' => $this->getInputFieldConfig(
                            __('Answer'),
                            'answer',
                            Textarea::NAME,
                            20
                        ),
                        'actionDelete' => $this->getActionDeleteConfig(30),
                    ],
                ],
            ],
        ];
    }

    /**
     * Build a required text field config (input or textarea).
     *
     * @param \Magento\Framework\Phrase $label
     * @param string $dataScope
     * @param string $formElement
     * @param int $sortOrder
     * @return array
     */
    private function getInputFieldConfig($label, string $dataScope, string $formElement, int $sortOrder): array
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Field::NAME,
                        'formElement' => $formElement,
                        'dataType' => Text::NAME,
                        'label' => $label,
                        'dataScope' => $dataScope,
                        'sortOrder' => $sortOrder,
                        'required' => true,
                        'validation' => [
                            'required-entry' => true,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build the row action-delete config.
     *
     * @param int $sortOrder
     * @return array
     */
    private function getActionDeleteConfig(int $sortOrder): array
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => 'actionDelete',
                        'dataType' => Text::NAME,
                        'label' => '',
                        'fit' => true,
                        'sortOrder' => $sortOrder,
                    ],
                ],
            ],
        ];
    }
}
