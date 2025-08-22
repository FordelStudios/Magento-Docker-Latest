<?php
/**
 * Test search criteria parsing
 */

// Test the search criteria parsing
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

// Parse the query string manually to see what we get
parse_str($queryString, $parsed);
echo "Parsed query string:\n";
print_r($parsed);

// Test with a simpler approach - try using POST with JSON body
echo "\n\nTrying POST approach:\n";

$postData = [
    'searchCriteria' => [
        'pageSize' => 10,
        'filterGroups' => [
            [
                'filters' => [
                    [
                        'field' => 'category_id',
                        'value' => '5',
                        'conditionType' => 'in'
                    ]
                ]
            ]
        ]
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
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
