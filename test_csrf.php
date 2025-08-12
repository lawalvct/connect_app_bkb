<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing CSRF and Session...\n";

try {
    // Test session start
    session_start();
    echo "Session started successfully\n";

    // Test CSRF token generation
    $token = csrf_token();
    echo "CSRF token generated: " . substr($token, 0, 20) . "...\n";

    // Test session configuration
    echo "Session driver: " . config('session.driver') . "\n";
    echo "Session lifetime: " . config('session.lifetime') . " minutes\n";

    echo "\nCSRF and Session test completed successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
