<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->boot();

$stream = new App\Models\Stream();

echo "Testing Stream model methods:\n";

if (method_exists($stream, 'streamViewers')) {
    echo "✓ streamViewers method exists\n";
} else {
    echo "✗ streamViewers method NOT found\n";
}

if (method_exists($stream, 'streamChats')) {
    echo "✓ streamChats method exists\n";
} else {
    echo "✗ streamChats method NOT found\n";
}

if (method_exists($stream, 'streamPayments')) {
    echo "✓ streamPayments method exists\n";
} else {
    echo "✗ streamPayments method NOT found\n";
}

echo "Done!\n";
