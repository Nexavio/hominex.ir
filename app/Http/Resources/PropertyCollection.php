<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PropertyCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'properties' => $this->collection->map(function ($property) {
                return [
                    'id' => $property->id,
                    'title' => $property->title,
                    'description' => $property->description,
                    'property_type' => $property->propertyType->name ?? 'نامشخص',
                    'property_status' => $property->property_status,
                    'property_status_label' => $property->property_status_label,
                    'formatted_price' => $property->formatted_price,
                    'total_price' => $property->total_price,
                    'monthly_rent' => $property->monthly_rent,
                    'rent_deposit' => $property->rent_deposit,
                    'land_area' => $property->land_area,
                    'rooms_count' => $property->rooms_count,
                    'bathrooms_count' => $property->bathrooms_count,
                    'building_year' => $property->building_year,
                    'city' => $property->city,
                    'province' => $property->province,
                    'direction' => $property->direction,
                    'views_count' => $property->views_count,
                    'is_featured' => $property->is_featured_active,
                    'primary_image_url' => $property->primary_image_url,
                    'images_count' => $property->images->count(),
                    'amenities_count' => $property->amenities->count(),
                    'creator_name' => $property->creator_display_name,
                    'consultant_company' => $property->consultant?->company_name,
                    'is_favorited' => auth()->check() ?
                        $property->favorites()->where('user_id', auth()->id())->exists() : false,
                    'published_at' => $property->published_at?->toISOString(),
                    'created_at' => $property->created_at->toISOString(),
                ];
            }),
            'meta' => [
                'total' => $this->resource->total(),
                'per_page' => $this->resource->perPage(),
                'current_page' => $this->resource->currentPage(),
                'last_page' => $this->resource->lastPage(),
                'has_more_pages' => $this->resource->hasMorePages(),
                'from' => $this->resource->firstItem(),
                'to' => $this->resource->lastItem(),
            ],
            'links' => [
                'first' => $this->resource->url(1),
                'last' => $this->resource->url($this->resource->lastPage()),
                'prev' => $this->resource->previousPageUrl(),
                'next' => $this->resource->nextPageUrl(),
            ],
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'success' => true,
            'message' => 'لیست املاک با موفقیت دریافت شد.',
            'timestamp' => now()->toISOString(),
        ];
    }
}
