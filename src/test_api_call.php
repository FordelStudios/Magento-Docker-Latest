<?php
/**
 * Test API call for blog category filtering
 */

// Test the API call with a simpler URL
$url = 'http://localhost:8080/V1/blog';
$params = [
    'searchCriteria[pageSize]' => 10,
    'searchCriteria[filterGroups][0][filters][0][field]' => 'category_id',
    'searchCriteria[filterGroups][0][filters][0][value]' => '5',
    'searchCriteria[filterGroups][0][filters][0][conditionType]' => 'in'
];

$queryString = http_build_query($params);
$fullUrl = $url . '?' . $queryString;

echo "Testing URL: " . $fullUrl . "\n\n";

// Make the API call
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $fullUrl);
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
