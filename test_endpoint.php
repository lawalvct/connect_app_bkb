<?php

require_once 'vendor/autoload.php';

// Test the getPlans endpoint directly
$baseUrl = 'http://localhost/connect_app_backend_new-1/public';
$endpoint = $baseUrl . '/admin/subscriptions/plans/get';

// Test with cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

echo "HTTP Code: $httpCode\n";
echo "Response Headers:\n";
echo substr($response, 0, $headerSize) . "\n";
echo "Response Body:\n";
echo substr($response, $headerSize) . "\n";

curl_close($ch);
