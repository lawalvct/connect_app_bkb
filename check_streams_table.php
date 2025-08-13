<?php

require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Checking Streams Table Structure\n";
echo "===============================\n\n";

try {
    $columns = \Illuminate\Support\Facades\Schema::getColumnListing('streams');

    echo "Columns in 'streams' table:\n";
    foreach ($columns as $column) {
        echo "- {$column}\n";
    }

    echo "\nTesting stream creation with current schema...\n";

    // Try to create a test stream to see what works
    $testData = [
        'user_id' => 3370,
        'title' => 'Test Stream',
        'description' => 'Test description',
        'channel_name' => 'test_' . time(),
    ];

    echo "Attempting to create stream with minimal data...\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
