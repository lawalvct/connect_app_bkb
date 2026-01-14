<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminActivityLog extends Model
{
    protected $fillable = [
        'admin_id', 'action', 'model_type', 'model_id',
        'description', 'changes', 'ip_address', 'user_agent'
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public static function log($action, $description, $modelType = null, $modelId = null, $changes = null)
    {
        return self::create([
            'admin_id' => auth('admin')->id(),
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'description' => $description,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
