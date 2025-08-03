<?php

require_once __DIR__ . '/vendor/autoload.php';

use Pusher\Pusher;

echo "Testing Pusher connection...\n";

try {
    // Load environment variables
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    $pusher = new Pusher(
        $_ENV['PUSHER_APP_KEY'],
        $_ENV['PUSHER_APP_SECRET'],
        $_ENV['PUSHER_APP_ID'],
        [
            'cluster' => $_ENV['PUSHER_APP_CLUSTER'],
            'useTLS' => true
        ]
    );

    echo "Pusher configuration:\n";
    echo "App ID: " . $_ENV['PUSHER_APP_ID'] . "\n";
    echo "Key: " . $_ENV['PUSHER_APP_KEY'] . "\n";
    echo "Cluster: " . $_ENV['PUSHER_APP_CLUSTER'] . "\n\n";

    $data = [
        'message' => 'Test message from PHP script',
        'timestamp' => date('Y-m-d H:i:s'),
        'test_id' => uniqid()
    ];

    echo "Sending test message...\n";
    $result = $pusher->trigger('test-pusher', 'test.message', $data);

    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    echo "Message sent successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
