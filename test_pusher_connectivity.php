<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Pusher Network Connectivity Test ===\n\n";

try {
    // Test 1: DNS Resolution
    echo "1. Testing DNS resolution...\n";
    $pusherHosts = [
        'api-eu.pusher.com',
        'sockjs-eu.pusher.com',
        'ws-eu.pusher.com'
    ];

    foreach ($pusherHosts as $host) {
        $ip = gethostbyname($host);
        if ($ip !== $host) {
            echo "   ✅ {$host} resolves to: {$ip}\n";
        } else {
            echo "   ❌ Failed to resolve: {$host}\n";
        }
    }
    echo "\n";

    // Test 2: Port connectivity
    echo "2. Testing port connectivity...\n";
    $host = 'api-eu.pusher.com';
    $port = 443;

    $connection = @fsockopen($host, $port, $errno, $errstr, 10);
    if ($connection) {
        echo "   ✅ Successfully connected to {$host}:{$port}\n";
        fclose($connection);
    } else {
        echo "   ❌ Failed to connect to {$host}:{$port} - Error: {$errstr} ({$errno})\n";
    }
    echo "\n";

    // Test 3: cURL test with detailed error reporting
    echo "3. Testing cURL connection...\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api-eu.pusher.com");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_HEADER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "   ❌ cURL Error: {$error}\n";
    } else {
        echo "   ✅ cURL connection successful - HTTP Code: {$httpCode}\n";
    }
    echo "\n";

    // Test 4: Alternative Pusher configuration
    echo "4. Testing with alternative Pusher configuration...\n";

    $pusherConfig = config('broadcasting.connections.pusher');

    // Try with different options
    $pusher = new \Pusher\Pusher(
        $pusherConfig['key'],
        $pusherConfig['secret'],
        $pusherConfig['app_id'],
        [
            'cluster' => $pusherConfig['options']['cluster'],
            'useTLS' => false, // Try without TLS first
            'curl_options' => [
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_VERBOSE => true
            ]
        ]
    );

    try {
        $result = $pusher->trigger('test-channel', 'test-event', ['message' => 'test']);
        echo "   ✅ Pusher connection successful!\n";
        echo "   Result: " . json_encode($result) . "\n";
    } catch (\Exception $e) {
        echo "   ❌ Pusher connection failed: " . $e->getMessage() . "\n";

        // Try with HTTPS
        echo "   Retrying with HTTPS...\n";
        $pusherHttps = new \Pusher\Pusher(
            $pusherConfig['key'],
            $pusherConfig['secret'],
            $pusherConfig['app_id'],
            [
                'cluster' => $pusherConfig['options']['cluster'],
                'useTLS' => true,
                'curl_options' => [
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => 0,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_CONNECTTIMEOUT => 10
                ]
            ]
        );

        try {
            $result = $pusherHttps->trigger('test-channel', 'test-event', ['message' => 'test']);
            echo "   ✅ HTTPS Pusher connection successful!\n";
            echo "   Result: " . json_encode($result) . "\n";
        } catch (\Exception $e2) {
            echo "   ❌ HTTPS Pusher connection also failed: " . $e2->getMessage() . "\n";
        }
    }

    echo "\n";

    // Test 5: System information
    echo "5. System information:\n";
    echo "   PHP Version: " . PHP_VERSION . "\n";
    echo "   cURL Version: " . curl_version()['version'] . "\n";
    echo "   OpenSSL Version: " . (curl_version()['ssl_version'] ?? 'Not available') . "\n";
    echo "   User Agent: " . (curl_version()['version'] ?? 'Unknown') . "\n";
    echo "\n";

    // Test 6: Troubleshooting suggestions
    echo "6. Troubleshooting suggestions:\n";
    echo "   a) Check your internet connection\n";
    echo "   b) Try different Pusher cluster (us2, us3, ap1, etc.)\n";
    echo "   c) Check firewall settings\n";
    echo "   d) Try updating your .env with:\n";
    echo "      PUSHER_APP_CLUSTER=us2\n";
    echo "      PUSHER_SCHEME=http\n";
    echo "   e) Contact your hosting provider about Pusher access\n";
    echo "\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "============================================================\n";
echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";
