<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Firebase Configuration Check ===\n\n";

// Check service account file
$credPath = storage_path('app/firebase-credentials.json');
echo "1. Service Account File\n";
echo "   Path: $credPath\n";
echo "   Exists: " . (file_exists($credPath) ? "✅ YES" : "❌ NO") . "\n";

if (file_exists($credPath)) {
    $creds = json_decode(file_get_contents($credPath), true);
    echo "   Has project_id: " . (isset($creds['project_id']) ? "✅ YES ({$creds['project_id']})" : "❌ NO") . "\n";
    echo "   Has private_key: " . (isset($creds['private_key']) ? "✅ YES" : "❌ NO") . "\n";
    echo "   Has client_email: " . (isset($creds['client_email']) ? "✅ YES ({$creds['client_email']})" : "❌ NO") . "\n";
}

echo "\n2. Environment Variables\n";
echo "   FIREBASE_SERVER_KEY: " . (config('services.firebase.server_key') ? "✅ Set (" . strlen(config('services.firebase.server_key')) . " chars)" : "❌ Not set") . "\n";
echo "   FIREBASE_PROJECT_ID: " . (config('services.firebase.project_id') ?: "❌ Not set") . "\n";
echo "   FIREBASE_CREDENTIALS_PATH: " . (config('services.firebase.credentials_path') ?: "❌ Not set") . "\n";

echo "\n3. FCM Token for User 3114\n";
$tokens = DB::table('user_fcm_tokens')
    ->where('user_id', 3114)
    ->where('is_active', true)
    ->get();
echo "   Active tokens found: " . $tokens->count() . "\n";
foreach ($tokens as $token) {
    echo "   - Platform: {$token->platform}\n";
    echo "     Token: " . substr($token->fcm_token, 0, 50) . "...\n";
    echo "     Last used: " . ($token->last_used_at ?: 'Never') . "\n";
}

echo "\n=== Recommendation ===\n";

$serverKey = config('services.firebase.server_key');
if ($serverKey && strlen($serverKey) < 100) {
    echo "⚠️  WARNING: Your FIREBASE_SERVER_KEY is too short (" . strlen($serverKey) . " chars)\n";
    echo "   It looks like a VAPID key, not a Firebase Server Key.\n";
    echo "   Firebase Legacy Server Keys start with 'AAAA' and are ~152+ characters.\n";
    echo "   \n";
    echo "   To fix:\n";
    echo "   1. Go to Firebase Console → Project Settings → Cloud Messaging\n";
    echo "   2. Copy the 'Server key' under Cloud Messaging API (Legacy)\n";
    echo "   3. Update FIREBASE_SERVER_KEY in .env\n";
    echo "   4. Run: php artisan config:clear\n";
} else if (!$serverKey) {
    echo "❌ FIREBASE_SERVER_KEY is not set!\n";
} else {
    echo "✅ FIREBASE_SERVER_KEY looks valid\n";
}

echo "\n";
