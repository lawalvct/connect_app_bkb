<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

echo "Database: " . DB::connection()->getDatabaseName() . "\n";
echo "Tables:\n";

$tables = DB::select('SHOW TABLES');
foreach($tables as $table) {
    foreach($table as $key => $value) {
        echo "- " . $value . "\n";
    }
}

// Check specifically for admin_fcm_tokens
$adminTokensTable = DB::select("SHOW TABLES LIKE 'admin_fcm_tokens'");
if (count($adminTokensTable) > 0) {
    echo "\nAdmin FCM Tokens table exists!\n";

    // Show table structure
    echo "Table structure:\n";
    $columns = DB::select("DESCRIBE admin_fcm_tokens");
    foreach($columns as $column) {
        echo "- {$column->Field} ({$column->Type})\n";
    }
} else {
    echo "\nAdmin FCM Tokens table does NOT exist.\n";
}
