<?php

// Test basic autoloading
require_once __DIR__ . '/vendor/autoload.php';

echo "Autoloader loaded successfully!\n";

// Test if we can access basic Laravel classes
try {
    $app = new \Illuminate\Foundation\Application(
        basePath: __DIR__
    );
    echo "Laravel Application class loaded successfully!\n";
} catch (Exception $e) {
    echo "Error loading Laravel Application: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
