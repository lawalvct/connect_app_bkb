<?php
/**
 * Quick Setup Script for Call Testing
 * Creates conversation between User 3114 and User 4098
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Conversation;
use App\Models\ConversationParticipant;

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  ConnectApp Call Testing - Quick Setup                    ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Check/Create User 1
$user1 = User::find(3114);
if (!$user1) {
    echo "❌ User 1 (ID: 3114, lawalthb@gmail.com) not found!\n";
    echo "   Creating user...\n";
    $user1 = User::create([
        'name' => 'Oz Lawal',
        'email' => 'lawalthb@gmail.com',
        'password' => bcrypt('12345678'),
        'email_verified_at' => now(),
    ]);
    echo "✅ User 1 created\n\n";
} else {
    echo "✅ User 1: {$user1->name} ({$user1->email})\n";
}

// Check/Create User 2
$user2 = User::find(4098);
if (!$user2) {
    echo "❌ User 2 (ID: 4098, vick@gmail.com) not found!\n";
    echo "   Creating user...\n";
    $user2 = User::create([
        'name' => 'Vick',
        'email' => 'vick@gmail.com',
        'password' => bcrypt('12345678'),
        'email_verified_at' => now(),
    ]);
    echo "✅ User 2 created (New ID: {$user2->id})\n\n";
} else {
    echo "✅ User 2: {$user2->name} ({$user2->email})\n";
}

echo "\n";

// Find existing conversation
$conversation = Conversation::whereHas('participants', function($q) use ($user1) {
    $q->where('user_id', $user1->id)->where('is_active', true);
})->whereHas('participants', function($q) use ($user2) {
    $q->where('user_id', $user2->id)->where('is_active', true);
})->first();

if ($conversation) {
    echo "✅ Conversation already exists (ID: {$conversation->id})\n";

    // Make sure both participants are active
    ConversationParticipant::where('conversation_id', $conversation->id)
        ->whereIn('user_id', [$user1->id, $user2->id])
        ->update(['is_active' => true]);

} else {
    echo "📝 Creating new conversation...\n";

    $conversation = Conversation::create([
        'type' => 'private',
        'name' => 'Test Call Conversation',
        'created_by' => $user1->id,
        'is_active' => true,
        'last_message_at' => now()
    ]);

    // Add both participants
    ConversationParticipant::create([
        'conversation_id' => $conversation->id,
        'user_id' => $user1->id,
        'role' => 'member',
        'joined_at' => now(),
        'is_active' => true
    ]);

    ConversationParticipant::create([
        'conversation_id' => $conversation->id,
        'user_id' => $user2->id,
        'role' => 'member',
        'joined_at' => now(),
        'is_active' => true
    ]);

    echo "✅ Conversation created (ID: {$conversation->id})\n";
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  🎉 SETUP COMPLETE - Ready to Test!                       ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "📋 QUICK REFERENCE:\n";
echo "├─ Conversation ID: {$conversation->id}\n";
echo "├─ User 1: {$user1->email} (ID: {$user1->id})\n";
echo "├─ User 2: {$user2->email} (ID: {$user2->id})\n";
echo "└─ Password (both): 12345678\n\n";

echo "🚀 TESTING STEPS:\n\n";
echo "BROWSER 1:\n";
echo "  1. Go to: http://localhost:8000/test-calls-modern\n";
echo "  2. Click 'Oz Lawal' card or enter: {$user1->email}\n";
echo "  3. Click Login\n";
echo "  4. Conversation ID {$conversation->id} should auto-select\n";
echo "  5. Choose Audio or Video call\n";
echo "  6. Click 'Initiate Call' button\n\n";

echo "BROWSER 2 (use incognito/private mode):\n";
echo "  1. Go to: http://localhost:8000/test-calls-modern\n";
echo "  2. Enter: {$user2->email} / 12345678\n";
echo "  3. Click Login\n";
echo "  4. Conversation ID {$conversation->id} should auto-select\n";
echo "  5. Wait for 'Incoming Call!' banner\n";
echo "  6. Click 'Accept' button\n\n";

echo "✨ Both users will now be connected in a call!\n\n";

// Show participant details
$participants = ConversationParticipant::where('conversation_id', $conversation->id)
    ->with('user')
    ->get();

echo "👥 CONVERSATION PARTICIPANTS:\n";
foreach ($participants as $participant) {
    echo "   • {$participant->user->name} ({$participant->user->email}) - " .
         ($participant->is_active ? "✅ Active" : "❌ Inactive") . "\n";
}

echo "\n✅ If both users see the same conversation, you're ready to test!\n\n";
