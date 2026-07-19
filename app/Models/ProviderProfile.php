<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProviderProfile extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'business_name',
        'business_logo',
        'business_description',
        'business_address',
        'business_location',
        'working_hours',
        'service_radius',
        'bank_account_name',
        'bank_account_number',
        'bank_name',
        'bank_code',
        'bvn',
        'verification_date',
        'gallery_images',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'bvn',
        'bank_account_number',
        'bank_code',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'working_hours' => 'array',
        'gallery_images' => 'array',
        'verification_date' => 'datetime',
    ];

    /**
     * Get the user that owns the profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the service categories.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ServiceCategory::class, 'provider_categories', 'provider_id', 'category_id')
                    ->using(ProviderCategory::class)
                    ->withPivot('id')
                    ->withTimestamps();
    }

    /**
     * Get the vehicle brands supported.
     */
    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(VehicleBrand::class, 'provider_brands', 'provider_id', 'brand_id')
                    ->using(ProviderBrand::class)
                    ->withPivot('id')
                    ->withTimestamps();
    }

    /**
     * Get the average rating.
     */
    public function getAverageRatingAttribute(): float
    {
        return 0.0;
    }

    /**
     * Get the review count.
     */
    public function getReviewCountAttribute(): int
    {
        return 0;
    }

    /**
     * Check if profile is complete.
     */
    public function isComplete(): bool
    {
        return !empty($this->business_name)
            && !empty($this->business_address)
            && !empty($this->business_location)
            && !empty($this->bank_account_name)
            && !empty($this->bank_account_number)
            && !empty($this->bank_name)
            && $this->categories()->count() > 0
            && $this->brands()->count() > 0;
    }
}