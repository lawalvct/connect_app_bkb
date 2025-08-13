<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

// Check if admin_fcm_tokens table exists
$adminTokensTable = DB::select("SHOW TABLES LIKE 'admin_fcm_tokens'");
if (count($adminTokensTable) > 0) {
    echo "Admin FCM Tokens table already exists!\n";
    exit;
}

echo "Creating admin_fcm_tokens table...\n";

try {
    // Create the table manually
    DB::statement("
        CREATE TABLE `admin_fcm_tokens` (
            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `admin_id` bigint unsigned NOT NULL,
            `fcm_token` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
            `device_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `device_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `platform` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `browser` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `is_active` tinyint(1) NOT NULL DEFAULT '1',
            `notification_preferences` json DEFAULT NULL,
            `last_used_at` timestamp NULL DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT NULL,
            `updated_at` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `admin_fcm_unique` (`admin_id`,`fcm_token`),
            KEY `admin_fcm_tokens_admin_id_is_active_index` (`admin_id`,`is_active`),
            CONSTRAINT `admin_fcm_tokens_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    echo "Admin FCM Tokens table created successfully!\n";

    // Insert migration record
    DB::table('migrations')->insert([
        'migration' => '2025_08_13_015023_create_admin_fcm_tokens_table',
        'batch' => DB::table('migrations')->max('batch') + 1
    ]);

    echo "Migration record added.\n";

} catch (Exception $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
