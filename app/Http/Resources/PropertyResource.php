<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'property_type' => [
                'id' => $this->propertyType->id,
                'name' => $this->propertyType->name,
                'slug' => $this->propertyType->slug,
            ],
            'property_status' => $this->property_status,
            'property_status_label' => $this->property_status_label,
            'price_info' => [
                'total_price' => $this->total_price,
                'monthly_rent' => $this->monthly_rent,
                'rent_deposit' => $this->rent_deposit,
                'formatted_price' => $this->formatted_price,
            ],
            'specifications' => [
                'land_area' => $this->land_area,
                'rooms_count' => $this->rooms_count,
                'bathrooms_count' => $this->bathrooms_count,
                'building_year' => $this->building_year,
                'total_units' => $this->total_units,
                'direction' => $this->direction,
                'document_type' => $this->document_type,
                'usage_type' => $this->usage_type,
            ],
            'location' => [
                'province' => $this->province,
                'city' => $this->city,
                'address' => $this->address,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            'features' => $this->features,
            'amenities' => $this->whenLoaded('amenities', function () {
                return $this->amenities->map(function ($amenity) {
                    return [
                        'id' => $amenity->id,
                        'name' => $amenity->name,
                        'icon' => $amenity->icon,
                        'category' => $amenity->category,
                    ];
                });
            }),
            'images' => $this->whenLoaded('images', function () {
                return $this->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'image_url' => $image->full_image_url,
                        'thumbnail_url' => $image->full_thumbnail_url,
                        'is_primary' => $image->is_primary,
                        'display_order' => $image->display_order,
                    ];
                });
            }),
            'primary_image_url' => $this->primary_image_url,
            'consultant_info' => $this->whenLoaded('consultant', function () {
                return [
                    'name' => $this->creator_display_name,
                    'company_name' => $this->consultant?->company_name,
                    'contact_phone' => $this->consultant?->contact_phone,
                    'contact_whatsapp' => $this->consultant?->contact_whatsapp,
                    'contact_telegram' => $this->consultant?->contact_telegram,
                    'is_verified' => $this->consultant?->is_verified ?? false,
                    'profile_image_url' => $this->consultant?->profile_image_url,
                ];
            }),
            'stats' => [
                'views_count' => $this->views_count,
                'favorites_count' => $this->favorites_count,
                'is_favorited' => $this->when(auth()->check(), function () {
                    return $this->favorites()->where('user_id', auth()->id())->exists();
                }),
                'is_featured' => $this->is_featured_active,
            ],
            'status' => $this->status,
            'status_label' => $this->status_label,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
