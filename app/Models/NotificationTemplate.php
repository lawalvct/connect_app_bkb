<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'subject',
        'content',
        'variables',
        'is_active',
        'description'
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Methods
    public function render($data = [])
    {
        $content = $this->content;

        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $content = str_replace('{{' . $key . '}}', $value, $content);
            }
        }

        return $content;
    }

    public function renderSubject($data = [])
    {
        if (!$this->subject) {
            return null;
        }

        $subject = $this->subject;

        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $subject = str_replace('{{' . $key . '}}', $value, $subject);
            }
        }

        return $subject;
    }
}space App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    //
}
