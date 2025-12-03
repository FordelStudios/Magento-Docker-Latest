<?php
declare(strict_types=1);

namespace Formula\Review\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Plugin to add review aggregation data (rating_summary, reviews_count)
 * to product extension attributes in the Product API response.
 *
 * Uses EAV attributes that are synced via observers and cron.
 */
class ProductReviewAggregationPlugin
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Add review aggregation data to a single product
     *
     * @param ProductRepositoryInterface $subject
     * @param ProductInterface $product
     * @return ProductInterface
     */
    public function afterGet(
        ProductRepositoryInterface $subject,
        ProductInterface $product
    ): ProductInterface {
        $this->addReviewAggregationData($product);
        return $product;
    }

    /**
     * Add review aggregation data to product list
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
            $this->addReviewAggregationData($product);
        }

        return $searchResults;
    }

    /**
     * Add review aggregation data to a single product's extension attributes
     *
     * Reads from EAV attributes (rating_summary, reviews_count) which are
     * synced via observers when reviews change and via hourly cron job.
     *
     * @param ProductInterface $product
     * @return void
     */
    private function addReviewAggregationData(ProductInterface $product): void
    {
        try {
            $extensionAttributes = $product->getExtensionAttributes();

            if ($extensionAttributes) {
                // Get values from EAV attributes (already loaded with product)
                $ratingSummary = $product->getData('rating_summary');
                $reviewsCount = $product->getData('reviews_count');

                $extensionAttributes->setRatingSummary(
                    $ratingSummary !== null ? (float)$ratingSummary : 0.0
                );
                $extensionAttributes->setReviewsCount(
                    $reviewsCount !== null ? (int)$reviewsCount : 0
                );
                $product->setExtensionAttributes($extensionAttributes);
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'Error adding review aggregation to product: ' . $product->getSku(),
                ['exception' => $e->getMessage()]
            );
        }
    }
}
