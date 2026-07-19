<?php
declare(strict_types=1);

namespace Formula\ProductFaq\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Converts the FAQ rows posted by the dynamicRows editor into the JSON
 * string that is persisted in the `faqs` product attribute.
 *
 * Empty rows (missing question or answer once trimmed) are dropped, and an
 * empty result is stored as null so the attribute reads clean.
 */
class SerializeFaqsObserver implements ObserverInterface
{
    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param Json $serializer
     */
    public function __construct(Json $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getData('product');

        if (!$product) {
            return;
        }

        $faqs = $product->getData('faqs');

        // Only intervene when the dynamicRows editor posted an array of rows.
        // When the attribute is already a JSON string (e.g. API/import), leave it alone.
        if (!is_array($faqs)) {
            return;
        }

        $clean = [];
        foreach ($faqs as $row) {
            if (!is_array($row)) {
                continue;
            }

            $question = isset($row['question']) ? trim((string)$row['question']) : '';
            $answer = isset($row['answer']) ? trim((string)$row['answer']) : '';

            if ($question === '' || $answer === '') {
                continue;
            }

            $clean[] = [
                'question' => $question,
                'answer' => $answer,
            ];
        }

        $product->setData('faqs', empty($clean) ? null : $this->serializer->serialize($clean));
    }
}
