<?php

require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;

$capsule->addConnection([
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'database' => 'connect_app_db',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

try {
    // Test database connection
    $pdo = $capsule->getConnection()->getPdo();
    echo "Database connection: OK\n";

    // Check if subscribes table exists
    $tables = $capsule->getConnection()->select("SHOW TABLES LIKE 'subscribes'");
    if (count($tables) > 0) {
        echo "Subscribes table: EXISTS\n";

        // Count records
        $count = $capsule->table('subscribes')->count();
        echo "Total subscription plans: $count\n";

        if ($count > 0) {
            $plans = $capsule->table('subscribes')->limit(3)->get();
            echo "\nFirst few plans:\n";
            foreach ($plans as $plan) {
                echo "- ID: {$plan->id}, Name: {$plan->name}, Price: \${$plan->price}\n";
            }
        }
    } else {
        echo "Subscribes table: NOT FOUND\n";
    }

    // Check user_subscriptions table
    $tables = $capsule->getConnection()->select("SHOW TABLES LIKE 'user_subscriptions'");
    if (count($tables) > 0) {
        echo "\nUser subscriptions table: EXISTS\n";
        $count = $capsule->table('user_subscriptions')->count();
        echo "Total user subscriptions: $count\n";

        $activeCount = $capsule->table('user_subscriptions')
            ->where('status', 'active')
            ->where('deleted_flag', 'N')
            ->where('expires_at', '>', date('Y-m-d H:i:s'))
            ->count();
        echo "Active subscriptions: $activeCount\n";
    } else {
        echo "User subscriptions table: NOT FOUND\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
