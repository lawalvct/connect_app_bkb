<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Artisan;

// Bootstrap Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Subscription Expiration Command\n";
echo "=====================================\n\n";

try {
    // Test dry-run mode first
    echo "1. Testing dry-run mode:\n";
    echo "Command: php artisan subscriptions:expire --dry-run\n";

    $exitCode = Artisan::call('subscriptions:expire', ['--dry-run' => true]);
    echo "Exit Code: " . $exitCode . "\n";
    echo "Output:\n" . Artisan::output() . "\n";

    echo "\n" . str_repeat("-", 50) . "\n\n";

    // Test with notification option
    echo "2. Testing with notification option:\n";
    echo "Command: php artisan subscriptions:expire --dry-run --notify\n";

    $exitCode = Artisan::call('subscriptions:expire', [
        '--dry-run' => true,
        '--notify' => true
    ]);
    echo "Exit Code: " . $exitCode . "\n";
    echo "Output:\n" . Artisan::output() . "\n";

    echo "\n" . str_repeat("-", 50) . "\n\n";

    // Show command help
    echo "3. Command help:\n";
    echo "Command: php artisan help subscriptions:expire\n";

    $exitCode = Artisan::call('help', ['command_name' => 'subscriptions:expire']);
    echo "Exit Code: " . $exitCode . "\n";
    echo "Output:\n" . Artisan::output() . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Test completed. Check the output above for any issues.\n";
echo "To run manually: php artisan subscriptions:expire --dry-run\n";
echo "To schedule: The command is already added to Kernel.php\n";
echo "Schedule runs daily at 6 AM with --notify option\n";
?>
