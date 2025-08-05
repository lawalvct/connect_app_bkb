<?php

// Test Excel Export
require_once 'vendor/autoload.php';

use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;

try {
    echo "Testing Excel export...\n";

    // Create export instance
    $export = new UsersExport([]);
    echo "UsersExport instance created successfully\n";

    // Test collection
    $collection = $export->collection();
    echo "Collection retrieved: " . $collection->count() . " users\n";

    // Test headings
    $headings = $export->headings();
    echo "Headings: " . implode(', ', $headings) . "\n";

    echo "Excel export components are working!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
