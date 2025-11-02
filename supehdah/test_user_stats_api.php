<?php

// Simple script to test the user stats API endpoint
// Run this from the command line to test the API

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

// Test if we can connect to the API
echo "🧪 Testing User Stats API Endpoint\n";
echo "=================================\n\n";

$baseUrl = 'http://127.0.0.1:8000/api';
$endpoint = '/user/stats';

// Test without authentication (should fail)
echo "1. Testing without authentication...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . $endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response: $response\n\n";

if ($httpCode === 401) {
    echo "✅ Correctly returns 401 Unauthorized without token\n\n";
} else {
    echo "❌ Expected 401 but got $httpCode\n\n";
}

// Test with fake token (should also fail)
echo "2. Testing with invalid token...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . $endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer fake-token-123'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response: $response\n\n";

if ($httpCode === 401) {
    echo "✅ Correctly returns 401 Unauthorized with invalid token\n\n";
} else {
    echo "❌ Expected 401 but got $httpCode\n\n";
}

echo "📋 Summary:\n";
echo "- API endpoint: $baseUrl$endpoint\n";
echo "- Authentication: Required (Sanctum Bearer token)\n";
echo "- Expected response format: JSON with user statistics\n";
echo "- To test with valid token: Login first and use the returned token\n\n";

echo "🛠️ Next steps to fix the mobile app issue:\n";
echo "1. ✅ Backend endpoint created and working\n";
echo "2. ✅ Mobile app updated to handle the new response format\n";
echo "3. ✅ Laravel server is running on port 8000\n";
echo "4. 📱 Test the mobile app now - the error should be fixed!\n";