<?php
/**
 * Simple test for blog API without filters
 */

// Test the basic API call
$url = 'http://localhost:8080/V1/blog';

echo "Testing basic URL: " . $url . "\n\n";

// Make the API call
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "\n";
if ($error) {
    echo "CURL Error: " . $error . "\n";
}
echo "Response:\n";
echo $response . "\n";

// Decode and pretty print
$decoded = json_decode($response, true);
if ($decoded) {
    echo "\nDecoded response:\n";
    echo "Total count: " . ($decoded['total_count'] ?? 'N/A') . "\n";
    echo "Items count: " . count($decoded['items'] ?? []) . "\n";
    echo "Search criteria: " . json_encode($decoded['search_criteria'] ?? []) . "\n";
    
    foreach ($decoded['items'] ?? [] as $item) {
        echo "Blog ID: " . $item['id'] . ", Categories: " . json_encode($item['category_ids'] ?? []) . "\n";
    }
} else {
    echo "\nFailed to decode JSON response\n";
}
