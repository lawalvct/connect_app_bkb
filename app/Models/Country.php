<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'phone_code',
        'timezone',
        'flag',
        'active'
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Get the users for the country.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the flag URL for the country
     */
    public function getFlagUrlAttribute()
    {
        if (!$this->code) {
            return null;
        }

        return 'https://flagcdn.com/w80/' . strtolower($this->code) . '.png';
    }

    /**
     * Get a small flag URL for the country
     */
    public function getSmallFlagUrlAttribute()
    {
        if (!$this->code) {
            return null;
        }

        return 'https://flagcdn.com/w40/' . strtolower($this->code) . '.png';
    }
}
