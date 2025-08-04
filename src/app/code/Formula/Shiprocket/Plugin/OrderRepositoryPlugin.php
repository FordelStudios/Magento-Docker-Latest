<?php
namespace Formula\Shiprocket\Plugin;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Psr\Log\LoggerInterface;

class OrderRepositoryPlugin
{
    /**
     * @var ExtensionAttributesFactory
     */
    private $extensionAttributesFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param ExtensionAttributesFactory $extensionAttributesFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ExtensionAttributesFactory $extensionAttributesFactory,
        LoggerInterface $logger
    ) {
        $this->extensionAttributesFactory = $extensionAttributesFactory;
        $this->logger = $logger;
    }

    /**
     * Add estimated delivery date to single order
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $order
     * @return OrderInterface
     */
    public function afterGet(
        OrderRepositoryInterface $subject,
        OrderInterface $order
    ) {
        $this->addEstimatedDeliveryDate($order);
        return $order;
    }

    /**
     * Add estimated delivery date to order list
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderSearchResultInterface $searchResult
     * @return OrderSearchResultInterface
     */
    public function afterGetList(
        OrderRepositoryInterface $subject,
        OrderSearchResultInterface $searchResult
    ) {
        foreach ($searchResult->getItems() as $order) {
            $this->addEstimatedDeliveryDate($order);
        }
        return $searchResult;
    }

    /**
     * Add estimated delivery date to order extension attributes
     *
     * @param OrderInterface $order
     * @return void
     */
    private function addEstimatedDeliveryDate(OrderInterface $order)
    {
        try {
            // Only add estimated delivery date for pending and processing orders
            if (!in_array($order->getStatus(), ['pending', 'processing'])) {
                return;
            }

            // Get extension attributes
            $extensionAttributes = $order->getExtensionAttributes();
            if ($extensionAttributes === null) {
                $extensionAttributes = $this->extensionAttributesFactory->create(OrderInterface::class);
            }

            // Check if already set to avoid duplicate processing
            if ($extensionAttributes->getEstDeliveryDate()) {
                return;
            }

            // Get the stored estimated delivery date from the order
            $estimatedDeliveryDate = $order->getData('est_delivery_date');
            
            if ($estimatedDeliveryDate) {
                $extensionAttributes->setEstDeliveryDate($estimatedDeliveryDate);
                $order->setExtensionAttributes($extensionAttributes);
            } else {
                $this->logger->info('Shiprocket Plugin: No stored delivery date found for order ' . $order->getIncrementId());
            }

        } catch (\Exception $e) {
            $this->logger->error('Shiprocket Plugin Error: ' . $e->getMessage());
            // Don't fail the order request if extension attribute processing fails
        }
    }
}