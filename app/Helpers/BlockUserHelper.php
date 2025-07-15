<?php

namespace App\Helpers;

use App\Models\BlockUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BlockUserHelper
{
    /**
     * Get list of blocked user IDs for a user
     */
    public static function blockUserList($userId)
    {
        try {
            // Check if BlockUser model/table exists
            if (class_exists('App\Models\BlockUser')) {
                return BlockUser::where('user_id', $userId)
                    ->where('deleted_flag', 'N')
                    ->pluck('block_user_id')
                    ->toArray();
            } else {
                // Fallback to direct DB query if model doesn't exist
                return DB::table('block_users')
                    ->where('user_id', $userId)
                    ->where('deleted_flag', 'N')
                    ->pluck('block_user_id')
                    ->toArray();
            }
        } catch (\Exception $e) {
            // Log the error but return empty array to avoid breaking the application
            Log::error('Error fetching blocked users', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Block a user
     */
    public static function insert($data)
    {
        try {
            if (isset($data['user_id']) && isset($data['block_user_id'])) {
                // Check if already blocked
                $existing = DB::table('block_users')
                    ->where('user_id', $data['user_id'])
                    ->where('block_user_id', $data['block_user_id'])
                    ->first();

                if ($existing) {
                    // Update if soft deleted
                    if ($existing->deleted_flag === 'Y') {
                        return DB::table('block_users')
                            ->where('id', $existing->id)
                            ->update([
                                'deleted_flag' => 'N',
                                'updated_at' => now()
                            ]);
                    }
                    return true; // Already blocked
                }

                // Add created_at and updated_at
                $data['created_at'] = now();
                $data['updated_at'] = now();
                $data['deleted_flag'] = 'N';

                // Insert new block
                if (class_exists('App\Models\BlockUser')) {
                    return BlockUser::create($data)->id;
                } else {
                    return DB::table('block_users')->insertGetId($data);
                }
            }
            return false;
        } catch (\Exception $e) {
            Log::error('Error blocking user', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Unblock a user
     */
    public static function unblock($userId, $blockedUserId)
    {
        try {
            if (class_exists('App\Models\BlockUser')) {
                return BlockUser::where('user_id', $userId)
                    ->where('block_user_id', $blockedUserId)
                    ->update([
                        'deleted_flag' => 'Y',
                        'updated_at' => now()
                    ]);
            } else {
                return DB::table('block_users')
                    ->where('user_id', $userId)
                    ->where('block_user_id', $blockedUserId)
                    ->update([
                        'deleted_flag' => 'Y',
                        'updated_at' => now()
                    ]);
            }
        } catch (\Exception $e) {
            Log::error('Error unblocking user', [
                'user_id' => $userId,
                'blocked_user_id' => $blockedUserId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if a user is blocked
     */
    public static function isBlocked($userId, $blockedUserId)
    {
        try {
            if (class_exists('App\Models\BlockUser')) {
                return BlockUser::where('user_id', $userId)
                    ->where('block_user_id', $blockedUserId)
                    ->where('deleted_flag', 'N')
                    ->exists();
            } else {
                return DB::table('block_users')
                    ->where('user_id', $userId)
                    ->where('block_user_id', $blockedUserId)
                    ->where('deleted_flag', 'N')
                    ->exists();
            }
        } catch (\Exception $e) {
            Log::error('Error checking if user is blocked', [
                'user_id' => $userId,
                'blocked_user_id' => $blockedUserId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
