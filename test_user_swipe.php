<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\UserSwipe;

echo "Testing UserSwipe model...\n";

try {
    $swipe = UserSwipe::getTodayRecord(3114);
    echo "✅ UserSwipe created successfully!\n";
    echo "Total swipes: " . $swipe->total_swipes . "\n";
    echo "Right swipes: " . $swipe->right_swipes . "\n";
    echo "Left swipes: " . $swipe->left_swipes . "\n";
    echo "Super likes: " . $swipe->super_likes . "\n";
    echo "Daily limit: " . $swipe->daily_limit . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "Done.\n";
