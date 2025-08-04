<?php
namespace Formula\Shiprocket\Plugin;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Formula\Shiprocket\Model\Serviceability;
use Psr\Log\LoggerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;

class OrderSavePlugin
{
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
     * @param Serviceability $serviceability
     * @param LoggerInterface $logger
     * @param DateTime $dateTime
     */
    public function __construct(
        Serviceability $serviceability,
        LoggerInterface $logger,
        DateTime $dateTime
    ) {
        $this->serviceability = $serviceability;
        $this->logger = $logger;
        $this->dateTime = $dateTime;
    }

    /**
     * Calculate delivery date before order save
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $order
     * @return array
     */
    public function beforeSave(
        OrderRepositoryInterface $subject,
        OrderInterface $order
    ) {
        try {
            // Only process if order status is pending or processing
            if (!in_array($order->getStatus(), ['pending', 'processing'])) {
                return [$order];
            }

            // Skip if delivery date already calculated
            if ($order->getData('est_delivery_date')) {
                return [$order];
            }

            // Skip if order doesn't have shipping address
            $shippingAddress = $order->getShippingAddress();
            if (!$shippingAddress || !$shippingAddress->getPostcode()) {
                $this->logger->info('Shiprocket Save Plugin: No shipping address or postcode found for order ' . $order->getIncrementId());
                return [$order];
            }

            $pincode = $shippingAddress->getPostcode();
            $weight = $order->getWeight() ?: 1.0; // Default to 1kg if no weight
            $isCod = $this->isCodPayment($order);

            $this->logger->info('Shiprocket Save Plugin: Calculating delivery date for order ' . $order->getIncrementId(), [
                'pincode' => $pincode,
                'weight' => $weight,
                'cod' => $isCod,
                'status' => $order->getStatus()
            ]);

            // Call serviceability API
            $serviceabilityResult = $this->serviceability->checkServiceability($pincode, $isCod, $weight);

            $estimatedDeliveryDate = null;

            if ($serviceabilityResult['success'] && isset($serviceabilityResult['data']['data']['available_courier_companies'])) {
                $estimatedDeliveryDate = $this->calculateEstimatedDeliveryDate($serviceabilityResult['data']['data']);
                $this->logger->info('Shiprocket Save Plugin: Calculated delivery date for order ' . $order->getIncrementId() . ': ' . $estimatedDeliveryDate);
            } else {
                // Fallback to a default estimate if API fails
                $estimatedDeliveryDate = $this->getDefaultEstimatedDeliveryDate();
                $this->logger->info('Shiprocket Save Plugin: Using default delivery estimate for order ' . $order->getIncrementId() . ': ' . $estimatedDeliveryDate);
            }

            // Store the delivery date in the order
            if ($estimatedDeliveryDate) {
                $order->setData('est_delivery_date', $estimatedDeliveryDate);
            }

        } catch (\Exception $e) {
            $this->logger->error('Shiprocket Save Plugin Error: ' . $e->getMessage());
            // Don't fail the order save process if delivery date calculation fails
        }

        return [$order];
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

        // Calculate delivery date from current time (order creation time)
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
        // Calculate from current time (order creation time)
        $currentTime = $this->dateTime->gmtTimestamp();
        $deliveryTime = $currentTime + (5 * 24 * 60 * 60); // Default 5 days
        
        return date('Y-m-d', $deliveryTime);
    }
}