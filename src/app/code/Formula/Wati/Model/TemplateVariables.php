<?php
declare(strict_types=1);

namespace Formula\Wati\Model;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;

/**
 * Template Variables Model
 *
 * Defines all available template variables that can be used in Wati WhatsApp templates.
 * Admin should reference these exact variable names when creating templates in Wati dashboard.
 */
class TemplateVariables
{
    /**
     * Variable definitions with descriptions
     * Format: variable_name => [description, category]
     */
    const VARIABLE_DEFINITIONS = [
        // Customer Information
        'customer_name' => [
            'description' => 'Customer\'s first name (e.g., "John")',
            'category' => 'Customer',
            'example' => 'John'
        ],
        'customer_full_name' => [
            'description' => 'Customer\'s full name (e.g., "John Doe")',
            'category' => 'Customer',
            'example' => 'John Doe'
        ],
        'customer_email' => [
            'description' => 'Customer\'s email address',
            'category' => 'Customer',
            'example' => 'john@example.com'
        ],

        // Order Information
        'order_id' => [
            'description' => 'Order increment ID (e.g., "100000123")',
            'category' => 'Order',
            'example' => '100000123'
        ],
        'order_total' => [
            'description' => 'Order grand total with currency symbol (e.g., "₹1,299.00")',
            'category' => 'Order',
            'example' => '₹1,299.00'
        ],
        'order_total_plain' => [
            'description' => 'Order grand total without currency symbol (e.g., "1299.00")',
            'category' => 'Order',
            'example' => '1299.00'
        ],
        'order_subtotal' => [
            'description' => 'Order subtotal with currency symbol',
            'category' => 'Order',
            'example' => '₹1,199.00'
        ],
        'order_date' => [
            'description' => 'Order creation date (e.g., "22 Dec 2025")',
            'category' => 'Order',
            'example' => '22 Dec 2025'
        ],
        'order_time' => [
            'description' => 'Order creation time (e.g., "3:45 PM")',
            'category' => 'Order',
            'example' => '3:45 PM'
        ],
        'order_status' => [
            'description' => 'Current order status (e.g., "Processing")',
            'category' => 'Order',
            'example' => 'Processing'
        ],
        'payment_method' => [
            'description' => 'Payment method used (e.g., "Razorpay", "COD")',
            'category' => 'Order',
            'example' => 'Razorpay'
        ],
        'item_count' => [
            'description' => 'Total number of items in order',
            'category' => 'Order',
            'example' => '3'
        ],
        'product_names' => [
            'description' => 'Comma-separated list of product names (max 3 shown)',
            'category' => 'Order',
            'example' => 'Face Serum, Moisturizer, Sunscreen'
        ],
        'coupon_code' => [
            'description' => 'Coupon code used (empty if none)',
            'category' => 'Order',
            'example' => 'SAVE20'
        ],
        'discount_amount' => [
            'description' => 'Discount amount with currency symbol',
            'category' => 'Order',
            'example' => '₹200.00'
        ],

        // Shipping Information
        'shipping_name' => [
            'description' => 'Shipping recipient name',
            'category' => 'Shipping',
            'example' => 'John Doe'
        ],
        'shipping_address' => [
            'description' => 'Full shipping address (street lines)',
            'category' => 'Shipping',
            'example' => '123 Main Street, Apt 4B'
        ],
        'shipping_city' => [
            'description' => 'Shipping city',
            'category' => 'Shipping',
            'example' => 'Mumbai'
        ],
        'shipping_state' => [
            'description' => 'Shipping state/region',
            'category' => 'Shipping',
            'example' => 'Maharashtra'
        ],
        'shipping_pincode' => [
            'description' => 'Shipping postal/PIN code',
            'category' => 'Shipping',
            'example' => '400001'
        ],
        'shipping_country' => [
            'description' => 'Shipping country',
            'category' => 'Shipping',
            'example' => 'India'
        ],
        'shipping_phone' => [
            'description' => 'Shipping contact phone number',
            'category' => 'Shipping',
            'example' => '9876543210'
        ],
        'shipping_method' => [
            'description' => 'Shipping method name',
            'category' => 'Shipping',
            'example' => 'Standard Delivery'
        ],
        'shipping_cost' => [
            'description' => 'Shipping cost with currency symbol',
            'category' => 'Shipping',
            'example' => '₹99.00'
        ],

        // Tracking Information (Available after shipping)
        'tracking_number' => [
            'description' => 'Shipment tracking/AWB number',
            'category' => 'Tracking',
            'example' => 'AWB123456789'
        ],
        'courier_name' => [
            'description' => 'Courier/carrier name (from Shiprocket)',
            'category' => 'Tracking',
            'example' => 'Delhivery'
        ],
        'tracking_url' => [
            'description' => 'Direct tracking URL for the shipment',
            'category' => 'Tracking',
            'example' => 'https://shiprocket.co/tracking/AWB123'
        ],
        'estimated_delivery' => [
            'description' => 'Estimated delivery date (if available)',
            'category' => 'Tracking',
            'example' => '25 Dec 2025'
        ],

        // Refund/Cancellation (Available for cancelled/refunded orders)
        'refund_amount' => [
            'description' => 'Refund amount with currency symbol',
            'category' => 'Refund',
            'example' => '₹1,299.00'
        ],
        'cancellation_reason' => [
            'description' => 'Order cancellation reason (if provided)',
            'category' => 'Refund',
            'example' => 'Customer requested'
        ],
    ];

    /**
     * @var PriceHelper
     */
    protected $priceHelper;

    /**
     * @param PriceHelper $priceHelper
     */
    public function __construct(
        PriceHelper $priceHelper
    ) {
        $this->priceHelper = $priceHelper;
    }

    /**
     * Get all variable definitions grouped by category
     *
     * @return array
     */
    public function getVariableDefinitions(): array
    {
        return self::VARIABLE_DEFINITIONS;
    }

    /**
     * Get variables grouped by category for admin display
     *
     * @return array
     */
    public function getVariablesByCategory(): array
    {
        $grouped = [];
        foreach (self::VARIABLE_DEFINITIONS as $name => $info) {
            $category = $info['category'];
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][$name] = $info;
        }
        return $grouped;
    }

    /**
     * Extract all variables from an order
     *
     * @param OrderInterface $order
     * @param string|null $status Optional status override
     * @return array Key-value pairs of variable_name => value
     */
    public function extractVariablesFromOrder(OrderInterface $order, ?string $status = null): array
    {
        $variables = [];

        // Customer Information
        $variables['customer_name'] = $this->getCustomerFirstName($order);
        $variables['customer_full_name'] = $this->getCustomerFullName($order);
        $variables['customer_email'] = $order->getCustomerEmail() ?: '';

        // Order Information
        $variables['order_id'] = $order->getIncrementId();
        $variables['order_total'] = $this->priceHelper->currency($order->getGrandTotal(), true, false);
        $variables['order_total_plain'] = number_format((float)$order->getGrandTotal(), 2);
        $variables['order_subtotal'] = $this->priceHelper->currency($order->getSubtotal(), true, false);
        $variables['order_date'] = $order->getCreatedAt()
            ? date('d M Y', strtotime($order->getCreatedAt()))
            : '';
        $variables['order_time'] = $order->getCreatedAt()
            ? date('g:i A', strtotime($order->getCreatedAt()))
            : '';
        $variables['order_status'] = $status ?: ucfirst($order->getStatus() ?: '');
        $variables['payment_method'] = $this->getPaymentMethodTitle($order);
        $variables['item_count'] = (string)$order->getTotalItemCount();
        $variables['product_names'] = $this->getProductNames($order);
        $variables['coupon_code'] = $order->getCouponCode() ?: '';
        $variables['discount_amount'] = $order->getDiscountAmount()
            ? $this->priceHelper->currency(abs((float)$order->getDiscountAmount()), true, false)
            : '';

        // Shipping Information
        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress) {
            $variables['shipping_name'] = $shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname();
            $street = $shippingAddress->getStreet();
            $variables['shipping_address'] = is_array($street) ? implode(', ', $street) : ($street ?: '');
            $variables['shipping_city'] = $shippingAddress->getCity() ?: '';
            $variables['shipping_state'] = $shippingAddress->getRegion() ?: '';
            $variables['shipping_pincode'] = $shippingAddress->getPostcode() ?: '';
            $variables['shipping_country'] = $shippingAddress->getCountryId() ?: '';
            $variables['shipping_phone'] = $shippingAddress->getTelephone() ?: '';
        } else {
            // Fallback to billing address for virtual orders
            $billingAddress = $order->getBillingAddress();
            if ($billingAddress) {
                $variables['shipping_name'] = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();
                $street = $billingAddress->getStreet();
                $variables['shipping_address'] = is_array($street) ? implode(', ', $street) : ($street ?: '');
                $variables['shipping_city'] = $billingAddress->getCity() ?: '';
                $variables['shipping_state'] = $billingAddress->getRegion() ?: '';
                $variables['shipping_pincode'] = $billingAddress->getPostcode() ?: '';
                $variables['shipping_country'] = $billingAddress->getCountryId() ?: '';
                $variables['shipping_phone'] = $billingAddress->getTelephone() ?: '';
            } else {
                $variables['shipping_name'] = '';
                $variables['shipping_address'] = '';
                $variables['shipping_city'] = '';
                $variables['shipping_state'] = '';
                $variables['shipping_pincode'] = '';
                $variables['shipping_country'] = '';
                $variables['shipping_phone'] = '';
            }
        }
        $variables['shipping_method'] = $order->getShippingDescription() ?: '';
        $variables['shipping_cost'] = $this->priceHelper->currency($order->getShippingAmount(), true, false);

        // Tracking Information (from Shiprocket integration)
        $variables['tracking_number'] = $order->getData('shiprocket_awb_number') ?: '';
        $variables['courier_name'] = $order->getData('shiprocket_courier_name') ?: '';
        $variables['tracking_url'] = $this->buildTrackingUrl($order);
        $variables['estimated_delivery'] = $order->getData('shiprocket_etd')
            ? date('d M Y', strtotime($order->getData('shiprocket_etd')))
            : '';

        // Refund Information
        $variables['refund_amount'] = $order->getTotalRefunded()
            ? $this->priceHelper->currency($order->getTotalRefunded(), true, false)
            : $this->priceHelper->currency($order->getGrandTotal(), true, false);
        $variables['cancellation_reason'] = $order->getData('cancellation_reason') ?: 'N/A';

        return $variables;
    }

    /**
     * Convert variables array to Wati API parameters format
     *
     * @param array $variables Key-value pairs
     * @return array Wati API format [['name' => 'var_name', 'value' => 'var_value'], ...]
     */
    public function toWatiParameters(array $variables): array
    {
        $parameters = [];
        foreach ($variables as $name => $value) {
            $parameters[] = [
                'name' => $name,
                'value' => (string)$value
            ];
        }
        return $parameters;
    }

    /**
     * Get customer first name
     *
     * @param OrderInterface $order
     * @return string
     */
    protected function getCustomerFirstName(OrderInterface $order): string
    {
        $name = $order->getCustomerFirstname();
        if (!$name) {
            $billingAddress = $order->getBillingAddress();
            if ($billingAddress) {
                $name = $billingAddress->getFirstname();
            }
        }
        return $name ?: 'Customer';
    }

    /**
     * Get customer full name
     *
     * @param OrderInterface $order
     * @return string
     */
    protected function getCustomerFullName(OrderInterface $order): string
    {
        $firstName = $order->getCustomerFirstname();
        $lastName = $order->getCustomerLastname();

        if (!$firstName) {
            $billingAddress = $order->getBillingAddress();
            if ($billingAddress) {
                $firstName = $billingAddress->getFirstname();
                $lastName = $billingAddress->getLastname();
            }
        }

        $fullName = trim(($firstName ?: '') . ' ' . ($lastName ?: ''));
        return $fullName ?: 'Customer';
    }

    /**
     * Get payment method title
     *
     * @param OrderInterface $order
     * @return string
     */
    protected function getPaymentMethodTitle(OrderInterface $order): string
    {
        $payment = $order->getPayment();
        if ($payment) {
            // Try to get the method title
            $method = $payment->getMethodInstance();
            if ($method) {
                try {
                    return $method->getTitle();
                } catch (\Exception $e) {
                    // Fallback to method code
                    return ucfirst(str_replace('_', ' ', $payment->getMethod()));
                }
            }
            return ucfirst(str_replace('_', ' ', $payment->getMethod()));
        }
        return 'N/A';
    }

    /**
     * Get product names from order (max 3)
     *
     * @param OrderInterface $order
     * @return string
     */
    protected function getProductNames(OrderInterface $order): string
    {
        $names = [];
        $items = $order->getAllVisibleItems();
        $count = 0;

        foreach ($items as $item) {
            if ($count >= 3) {
                $remaining = count($items) - 3;
                if ($remaining > 0) {
                    $names[] = "+{$remaining} more";
                }
                break;
            }
            $names[] = $item->getName();
            $count++;
        }

        return implode(', ', $names);
    }

    /**
     * Build tracking URL
     *
     * @param OrderInterface $order
     * @return string
     */
    protected function buildTrackingUrl(OrderInterface $order): string
    {
        $awb = $order->getData('shiprocket_awb_number');
        if ($awb) {
            // Shiprocket tracking URL format
            return 'https://www.shiprocket.in/shipment-tracking/?awb=' . $awb;
        }
        return '';
    }
}
