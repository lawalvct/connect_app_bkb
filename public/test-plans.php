<?php

use App\Models\Subscribe;
use App\Models\UserSubscription;

// Simple test to verify the models and relationships work
$plans = Subscribe::withCount([
    'userSubscriptions',
    'userSubscriptions as active_user_subscriptions_count' => function ($query) {
        $query->where('status', 'active')
              ->where('expires_at', '>', now())
              ->where('deleted_flag', 'N');
    }
])->get();

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'plans_count' => $plans->count(),
    'plans' => $plans->map(function($plan) {
        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'price' => $plan->price,
            'is_active' => $plan->is_active,
            'user_subscriptions_count' => $plan->user_subscriptions_count,
            'active_user_subscriptions_count' => $plan->active_user_subscriptions_count,
        ];
    })
], JSON_PRETTY_PRINT);
