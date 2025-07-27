<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

echo "Testing Agora Configuration:\n";
echo "========================\n\n";

echo "env('AGORA_APP_ID'): " . (env('AGORA_APP_ID') ?: 'NULL') . "\n";
echo "env('AGORA_APP_CERTIFICATE'): " . (env('AGORA_APP_CERTIFICATE') ?: 'NULL') . "\n";

echo "\nconfig('services.agora.app_id'): " . (config('services.agora.app_id') ?: 'NULL') . "\n";
echo "config('services.agora.app_certificate'): " . (config('services.agora.app_certificate') ?: 'NULL') . "\n";

// Test AgoraHelper initialization
try {
    $helper = new App\Helpers\AgoraHelper();
    echo "\nAgoraHelper initialized successfully!\n";
} catch (Exception $e) {
    echo "\nAgoraHelper error: " . $e->getMessage() . "\n";
}
