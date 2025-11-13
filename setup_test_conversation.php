<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Conversation;
use App\Models\ConversationParticipant;

echo "=== Setting Up Test Conversation for Call Testing ===\n\n";

// Get the two users
$user1 = User::find(3114);
$user2 = User::find(4098);

if (!$user1) {
    echo "❌ User 1 (ID: 3114) not found!\n";
    exit(1);
}

if (!$user2) {
    echo "❌ User 2 (ID: 4098) not found!\n";
    echo "Creating User 2...\n";
    $user2 = User::create([
        'name' => 'Vick',
        'email' => 'vick@gmail.com',
        'password' => bcrypt('12345678'),
        'email_verified_at' => now(),
    ]);
    echo "✅ User 2 created (ID: {$user2->id})\n";
}

echo "✅ User 1: {$user1->name} ({$user1->email}) - ID: {$user1->id}\n";
echo "✅ User 2: {$user2->name} ({$user2->email}) - ID: {$user2->id}\n\n";

// Check if conversation exists between these users
$existingConversation = Conversation::whereHas('participants', function($q) use ($user1) {
    $q->where('user_id', $user1->id);
})->whereHas('participants', function($q) use ($user2) {
    $q->where('user_id', $user2->id);
})->where('type', 'direct')->first();

if ($existingConversation) {
    echo "✅ Existing conversation found: ID {$existingConversation->id}\n";
    echo "   Name: " . ($existingConversation->name ?? 'Direct Message') . "\n";
    echo "   Participants: " . $existingConversation->participants()->count() . "\n\n";

    // Ensure both users are active participants
    foreach ([$user1->id, $user2->id] as $userId) {
        $participant = ConversationParticipant::where('conversation_id', $existingConversation->id)
            ->where('user_id', $userId)
            ->first();

        if ($participant) {
            $participant->update(['is_active' => true]);
        } else {
            ConversationParticipant::create([
                'conversation_id' => $existingConversation->id,
                'user_id' => $userId,
                'role' => 'member',
                'joined_at' => now(),
                'is_active' => true
            ]);
        }
    }

    echo "✅ Both users are active participants\n\n";
    echo "🎯 USE THIS CONVERSATION ID: {$existingConversation->id}\n\n";
    exit(0);
}

// Create new conversation
echo "Creating new conversation...\n";

$conversation = Conversation::create([
    'type' => 'private',
    'name' => "{$user1->name} & {$user2->name}",
    'created_by' => $user1->id,
    'is_active' => true,
    'last_message_at' => now()
]);

echo "✅ Conversation created: ID {$conversation->id}\n";

// Add both users as participants
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

echo "✅ Added {$user1->name} as participant\n";
echo "✅ Added {$user2->name} as participant\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🎉 SETUP COMPLETE!\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "📋 CONVERSATION DETAILS:\n";
echo "   ID: {$conversation->id}\n";
echo "   Name: {$conversation->name}\n";
echo "   Type: {$conversation->type}\n";
echo "   Created: {$conversation->created_at}\n\n";

echo "👥 PARTICIPANTS:\n";
echo "   1. {$user1->name} ({$user1->email}) - ID: {$user1->id}\n";
echo "   2. {$user2->name} ({$user2->email}) - ID: {$user2->id}\n\n";

echo "🎯 NEXT STEPS:\n";
echo "   1. Open Browser 1: http://localhost:8000/test-calls-modern\n";
echo "   2. Login as: {$user1->email} / 12345678\n";
echo "   3. Select conversation ID: {$conversation->id}\n";
echo "   4. Open Browser 2 (incognito): http://localhost:8000/test-calls-modern\n";
echo "   5. Login as: {$user2->email} / 12345678\n";
echo "   6. Select SAME conversation ID: {$conversation->id}\n";
echo "   7. From Browser 1, click 'Initiate Call'\n";
echo "   8. From Browser 2, click 'Accept'\n\n";

echo "✅ Both users should now see this conversation!\n";
