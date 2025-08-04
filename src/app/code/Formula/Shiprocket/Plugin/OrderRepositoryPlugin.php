<?php
namespace Formula\Shiprocket\Plugin;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Formula\Shiprocket\Model\Serviceability;
use Psr\Log\LoggerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;

class OrderRepositoryPlugin
{
    /**
     * @var ExtensionAttributesFactory
     */
    private $extensionAttributesFactory;

    /**
     * @var Serviceability
     */
    private $serviceability;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * Constructor
     *
     * @param ExtensionAttributesFactory $extensionAttributesFactory
     * @param Serviceability $serviceability
     * @param LoggerInterface $logger
     * @param DateTime $dateTime
     */
    public function __construct(
        ExtensionAttributesFactory $extensionAttributesFactory,
        Serviceability $serviceability,
        LoggerInterface $logger,
        DateTime $dateTime
    ) {
        $this->extensionAttributesFactory = $extensionAttributesFactory;
        $this->serviceability = $serviceability;
        $this->logger = $logger;
        $this->dateTime = $dateTime;
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

            // Check if already set to avoid duplicate API calls
            if ($extensionAttributes->getEstDeliveryDate()) {
                return;
            }

            // Get shipping address
            $shippingAddress = $this->getShippingAddress($order);
            if (!$shippingAddress || !$shippingAddress->getPostcode()) {
                $this->logger->info('Shiprocket: No shipping address or postcode found for order ' . $order->getIncrementId());
                return;
            }

            $pincode = $shippingAddress->getPostcode();
            $weight = $order->getWeight() ?: 1.0; // Default to 1kg if no weight
            $isCod = $this->isCodPayment($order);

            // Call serviceability API
            $serviceabilityResult = $this->serviceability->checkServiceability($pincode, $isCod, $weight);

            if ($serviceabilityResult['success'] && isset($serviceabilityResult['data']['data']['available_courier_companies'])) {
                $estimatedDeliveryDate = $this->calculateEstimatedDeliveryDate($serviceabilityResult['data']['data']);
                $extensionAttributes->setEstDeliveryDate($estimatedDeliveryDate);
            } else {
                // Fallback to a default estimate if API fails
                $estimatedDeliveryDate = $this->getDefaultEstimatedDeliveryDate();
                $extensionAttributes->setEstDeliveryDate($estimatedDeliveryDate);
                $this->logger->info('Shiprocket: Using default delivery estimate for order ' . $order->getIncrementId());
            }

            $order->setExtensionAttributes($extensionAttributes);

        } catch (\Exception $e) {
            $this->logger->error('Shiprocket Plugin Error: ' . $e->getMessage());
            // Don't fail the order request if serviceability check fails
        }
    }

    /**
     * Get shipping address from order
     *
     * @param OrderInterface $order
     * @return \Magento\Sales\Api\Data\OrderAddressInterface|null
     */
    private function getShippingAddress(OrderInterface $order)
    {
        // Try to get from extension attributes first (shipping assignments)
        $extensionAttributes = $order->getExtensionAttributes();
        if ($extensionAttributes && $extensionAttributes->getShippingAssignments()) {
            $shippingAssignments = $extensionAttributes->getShippingAssignments();
            if (!empty($shippingAssignments[0]) && $shippingAssignments[0]->getShipping()) {
                return $shippingAssignments[0]->getShipping()->getAddress();
            }
        }

        // Fallback to direct shipping address
        return $order->getShippingAddress();
    }

    /**
     * Check if payment is Cash on Delivery
     *
     * @param OrderInterface $order
     * @return bool
     */
    private function isCodPayment(OrderInterface $order)
    {
        $payment = $order->getPayment();
        return $payment && $payment->getMethod() === 'cashondelivery';
    }

    /**
     * Calculate estimated delivery date from serviceability response
     *
     * @param array $serviceabilityData
     * @return string
     */
    private function calculateEstimatedDeliveryDate(array $serviceabilityData)
    {
        $minEtd = PHP_INT_MAX;

        // Find the minimum ETD from available courier companies
        if (isset($serviceabilityData['available_courier_companies']) && is_array($serviceabilityData['available_courier_companies'])) {
            foreach ($serviceabilityData['available_courier_companies'] as $courier) {
                if (isset($courier['etd']) && is_numeric($courier['etd'])) {
                    $etd = (int)$courier['etd'];
                    if ($etd < $minEtd) {
                        $minEtd = $etd;
                    }
                }
            }
        }

        // If no valid ETD found, use default
        if ($minEtd === PHP_INT_MAX) {
            $minEtd = 3; // Default 3 days
        }

        // Calculate delivery date
        $currentTime = $this->dateTime->gmtTimestamp();
        $deliveryTime = $currentTime + ($minEtd * 24 * 60 * 60); // Add ETD days
        
        return date('Y-m-d', $deliveryTime);
    }

    /**
     * Get default estimated delivery date (fallback)
     *
     * @return string
     */
    private function getDefaultEstimatedDeliveryDate()
    {
        $currentTime = $this->dateTime->gmtTimestamp();
        $deliveryTime = $currentTime + (5 * 24 * 60 * 60); // Default 5 days
        
        return date('Y-m-d', $deliveryTime);
    }
}