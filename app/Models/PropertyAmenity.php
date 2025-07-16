<?php

// app/Models/PropertyAmenity.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PropertyAmenity extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'category',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'property_has_amenities', 'amenity_id', 'property_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
