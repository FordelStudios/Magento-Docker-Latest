#!/usr/bin/env php
<?php
/**
 * Shiprocket Order ID Format Test Script
 *
 * Tests whether Shiprocket accepts alphanumeric order IDs with hyphens
 * Format: TFS-DDMMYY-NNNN (e.g., TFS-140126-0001)
 *
 * Usage: php test_shiprocket_order_id.php <email> <password>
 *
 * NOTE: This creates a REAL order in Shiprocket (even in test mode).
 * You may need to cancel it manually in the Shiprocket dashboard.
 */

define('SHIPROCKET_API_URL', 'https://apiv2.shiprocket.in/v1/external/');

// Color output helpers
function success($msg) { echo "\033[32m✓ $msg\033[0m\n"; }
function error($msg) { echo "\033[31m✗ $msg\033[0m\n"; }
function info($msg) { echo "\033[36mℹ $msg\033[0m\n"; }
function warn($msg) { echo "\033[33m⚠ $msg\033[0m\n"; }

// Validate arguments
if ($argc < 3) {
    echo "Usage: php test_shiprocket_order_id.php <shiprocket_email> <shiprocket_password>\n";
    echo "\nExample:\n";
    echo "  php test_shiprocket_order_id.php your@email.com yourpassword\n";
    exit(1);
}

$email = $argv[1];
$password = $argv[2];

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║     SHIPROCKET ORDER ID FORMAT TEST                        ║\n";
echo "║     Testing: TFS-DDMMYY-NNNN format                        ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Step 1: Authenticate
info("Step 1: Authenticating with Shiprocket...");

$authResponse = callAPI('auth/login', [
    'email' => $email,
    'password' => $password
], 'POST', null);

if (!$authResponse || !isset($authResponse['token'])) {
    error("Authentication failed!");
    if (isset($authResponse['message'])) {
        error("Error: " . $authResponse['message']);
    }
    exit(1);
}

$token = $authResponse['token'];
success("Authentication successful! Token obtained.");
echo "\n";

// Step 2: Generate test order ID in new format
$testOrderId = 'TFS-' . date('dmy') . '-0001';
info("Step 2: Testing order creation with ID: $testOrderId");
echo "\n";

// Step 3: Prepare test order data (minimal required fields)
$orderData = [
    'order_id' => $testOrderId,
    'order_date' => date('Y-m-d H:i'),
    'pickup_location' => 'Primary',
    'channel_id' => '',
    'comment' => 'TEST ORDER - Please cancel - Testing order ID format',

    // Billing details (dummy data)
    'billing_customer_name' => 'Test',
    'billing_last_name' => 'Customer',
    'billing_address' => '123 Test Street',
    'billing_address_2' => 'Test Area',
    'billing_city' => 'Mumbai',
    'billing_pincode' => '400001',
    'billing_state' => 'Maharashtra',
    'billing_country' => 'India',
    'billing_email' => 'test@example.com',
    'billing_phone' => '9999999999',

    // Shipping details (same as billing for test)
    'shipping_is_billing' => true,

    // Order details
    'order_items' => [
        [
            'name' => 'Test Product',
            'sku' => 'TEST-SKU-001',
            'units' => 1,
            'selling_price' => 100,
            'discount' => 0,
            'tax' => 0,
            'hsn' => '33049900'
        ]
    ],

    'payment_method' => 'Prepaid',
    'shipping_charges' => 0,
    'giftwrap_charges' => 0,
    'transaction_charges' => 0,
    'total_discount' => 0,
    'sub_total' => 100,
    'length' => 10,
    'breadth' => 10,
    'height' => 10,
    'weight' => 0.5
];

info("Sending order to Shiprocket API...");
info("Order ID being tested: " . $orderData['order_id']);
echo "\n";

// Step 4: Create order
$createResponse = callAPI('orders/create/adhoc', $orderData, 'POST', $token);

echo "═══════════════════════════════════════════════════════════════\n";
echo "                        RESULTS                                 \n";
echo "═══════════════════════════════════════════════════════════════\n\n";

if ($createResponse && isset($createResponse['order_id'])) {
    success("ORDER CREATED SUCCESSFULLY!");
    echo "\n";
    success("Shiprocket ACCEPTS alphanumeric order IDs with hyphens!");
    echo "\n";
    echo "Response details:\n";
    echo "  - Shiprocket Order ID: " . $createResponse['order_id'] . "\n";
    echo "  - Shipment ID: " . ($createResponse['shipment_id'] ?? 'N/A') . "\n";
    echo "  - Status: " . ($createResponse['status'] ?? 'N/A') . "\n";
    echo "  - Your Order ID: $testOrderId\n";
    echo "\n";
    warn("IMPORTANT: Please cancel this test order in Shiprocket dashboard!");
    warn("Go to: https://app.shiprocket.in/orders");
    echo "\n";

    // Try to get the order to verify
    info("Verifying order was created with correct ID...");
    // Note: Shiprocket doesn't have a direct "get by order_id" endpoint easily accessible

} else {
    error("ORDER CREATION FAILED!");
    echo "\n";

    if (isset($createResponse['message'])) {
        error("Error message: " . $createResponse['message']);
    }
    if (isset($createResponse['errors'])) {
        error("Validation errors:");
        print_r($createResponse['errors']);
    }

    echo "\nFull response:\n";
    print_r($createResponse);

    echo "\n";

    // Check if it's specifically an order_id format issue
    $errorMsg = strtolower(json_encode($createResponse));
    if (strpos($errorMsg, 'order_id') !== false || strpos($errorMsg, 'order id') !== false) {
        error("The error appears to be related to order_id format!");
        error("Shiprocket may NOT accept alphanumeric order IDs with hyphens.");
    } else {
        warn("The error might be unrelated to order_id format.");
        warn("Check the error message above for details.");
    }
}

echo "\n";

/**
 * Make API call to Shiprocket
 */
function callAPI($endpoint, $data, $method = 'POST', $token = null) {
    $url = SHIPROCKET_API_URL . $endpoint;

    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error("cURL Error: $error");
        return null;
    }

    $decoded = json_decode($response, true);

    if ($httpCode >= 400) {
        warn("HTTP $httpCode response received");
    }

    return $decoded;
}
