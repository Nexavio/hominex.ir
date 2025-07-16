<?php

// app/Models/SiteSetting.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'setting_key',
        'setting_value',
        'setting_type',
        'category',
        'description',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public static function getValue(string $key, $default = null)
    {
        $setting = static::where('setting_key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return match ($setting->setting_type) {
            'boolean' => filter_var($setting->setting_value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->setting_value,
            'json' => json_decode($setting->setting_value, true),
            default => $setting->setting_value,
        };
    }

    public static function setValue(string $key, $value, string $type = 'string'): bool
    {
        $setting = static::firstOrNew(['setting_key' => $key]);

        $setting->setting_value = match ($type) {
            'boolean' => $value ? 'true' : 'false',
            'json' => json_encode($value),
            default => (string) $value,
        };

        $setting->setting_type = $type;

        return $setting->save();
    }
}
