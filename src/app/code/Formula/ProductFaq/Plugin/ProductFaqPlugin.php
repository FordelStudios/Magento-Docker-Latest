<?php
declare(strict_types=1);

namespace Formula\ProductFaq\Plugin;

use Formula\ProductFaq\Api\Data\FaqInterface;
use Formula\ProductFaq\Api\Data\FaqInterfaceFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Plugin to add per-product FAQs to product extension attributes
 * in the Product API response.
 *
 * Reads the `faqs` EAV attribute (JSON string) stored on the product,
 * decodes it into a list of {question, answer} objects and exposes it
 * as product.extension_attributes.faqs[].
 */
class ProductFaqPlugin
{
    /**
     * @var FaqInterfaceFactory
     */
    private $faqFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param FaqInterfaceFactory $faqFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        FaqInterfaceFactory $faqFactory,
        LoggerInterface $logger
    ) {
        $this->faqFactory = $faqFactory;
        $this->logger = $logger;
    }

    /**
     * Add FAQs to a single product
     *
     * @param ProductRepositoryInterface $subject
     * @param ProductInterface $product
     * @return ProductInterface
     */
    public function afterGet(
        ProductRepositoryInterface $subject,
        ProductInterface $product
    ): ProductInterface {
        $this->addFaqsData($product);
        return $product;
    }

    /**
     * Add FAQs to a product list
     *
     * @param ProductRepositoryInterface $subject
     * @param ProductSearchResultsInterface $searchResults
     * @return ProductSearchResultsInterface
     */
    public function afterGetList(
        ProductRepositoryInterface $subject,
        ProductSearchResultsInterface $searchResults
    ): ProductSearchResultsInterface {
        $products = $searchResults->getItems();

        if (empty($products)) {
            return $searchResults;
        }

        foreach ($products as $product) {
            $this->addFaqsData($product);
        }

        return $searchResults;
    }

    /**
     * Decode the `faqs` JSON attribute and set FaqInterface[] on
     * the product's extension attributes.
     *
     * Rows missing a question or an answer (empty/whitespace) are skipped.
     * Wrapped in try/catch so a bad payload never breaks product loading.
     *
     * @param ProductInterface $product
     * @return void
     */
    private function addFaqsData(ProductInterface $product): void
    {
        try {
            $extensionAttributes = $product->getExtensionAttributes();

            if (!$extensionAttributes) {
                return;
            }

            $rawFaqs = $product->getData('faqs');
            $faqs = [];

            if ($rawFaqs !== null && $rawFaqs !== '') {
                $rows = json_decode((string)$rawFaqs, true);

                if (is_array($rows)) {
                    foreach ($rows as $row) {
                        if (!is_array($row)) {
                            continue;
                        }

                        $question = isset($row[FaqInterface::QUESTION])
                            ? trim((string)$row[FaqInterface::QUESTION])
                            : '';
                        $answer = isset($row[FaqInterface::ANSWER])
                            ? trim((string)$row[FaqInterface::ANSWER])
                            : '';

                        if ($question === '' || $answer === '') {
                            continue;
                        }

                        /** @var FaqInterface $faq */
                        $faq = $this->faqFactory->create();
                        $faq->setQuestion($question);
                        $faq->setAnswer($answer);
                        $faqs[] = $faq;
                    }
                }
            }

            $extensionAttributes->setFaqs($faqs);
            $product->setExtensionAttributes($extensionAttributes);
        } catch (\Exception $e) {
            $this->logger->error(
                'Error adding FAQs to product: ' . $product->getSku(),
                ['exception' => $e->getMessage()]
            );
        }
    }
}
