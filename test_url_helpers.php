<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing URL helpers...\n";

// Test standard Laravel url() helper
echo "url() helper: " . url('uploads/profiles/') . "\n";

// Test if app_url() function exists
if (function_exists('app_url')) {
    echo "app_url() helper exists: " . app_url('uploads/profiles/') . "\n";
} else {
    echo "app_url() helper does NOT exist\n";
}

// Test app()->url() if it exists
try {
    echo "config('app.url'): " . config('app.url') . "\n";
} catch (Exception $e) {
    echo "config('app.url') failed: " . $e->getMessage() . "\n";
}

echo "Done.\n";
