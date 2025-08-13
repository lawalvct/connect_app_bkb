<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

echo "Testing Direct Email Send\n";
echo "========================\n\n";

try {
    // Create a simple test email
    $testEmail = 'lawalthb@gmail.com';
    $testFilename = 'test_export_' . date('Y-m-d_H-i-s') . '.csv';

    echo "Sending test email to: {$testEmail}\n";
    echo "Test filename: {$testFilename}\n\n";

    // Use Laravel's Mail facade to send a simple email
    Mail::raw("This is a test email from ConnectApp.\n\nYour export file '{$testFilename}' is ready for download.\n\nDownload link: " . url('storage/exports/' . $testFilename), function ($message) use ($testEmail) {
        $message->to($testEmail)
                ->subject('Test Export Ready - ConnectApp')
                ->from('ftilije@connectinc.app', 'Connect Inc');
    });

    echo "✅ Test email sent successfully!\n";
    echo "Check your email inbox.\n\n";

} catch (Exception $e) {
    echo "❌ Email failed: " . $e->getMessage() . "\n";
    echo "Full error: " . $e->getTraceAsString() . "\n\n";
}

// Also test with the actual mail class
echo "Testing with ExportReadyMail class:\n";
try {
    $mail = new \App\Mail\ExportReadyMail('test_export_' . date('H-i-s') . '.csv', 'csv');
    Mail::to('lawalthb@gmail.com')->send($mail);
    echo "✅ ExportReadyMail sent successfully!\n";
} catch (Exception $e) {
    echo "❌ ExportReadyMail failed: " . $e->getMessage() . "\n";
}

echo "\n=== Email Test Complete ===\n";
