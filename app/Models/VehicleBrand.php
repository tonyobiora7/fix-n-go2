<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleBrand extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'logo',
        'is_active',
    ];

    /**
     * Get the casts for the model.
     *
     * @return array
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the vehicle models for this brand.
     */
    public function models(): HasMany
    {
        return $this->hasMany(VehicleModel::class);
    }

    /**
     * Get the providers supporting this brand.
     */
    public function providers(): BelongsToMany
    {
        return $this->belongsToMany(ProviderProfile::class, 'provider_brands');
    }

    /**
     * Get the dealers selling this brand.
     */
    public function dealers(): BelongsToMany
    {
        return $this->belongsToMany(DealerProfile::class, 'dealer_brands');
    }

    /**
     * Get the brand name with first letter capitalized.
     */
    public function getFormattedNameAttribute(): string
    {
        return ucfirst($this->name);
    }
}