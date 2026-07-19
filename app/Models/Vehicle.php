<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehicle extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'make',
        'model',
        'year',
        'is_archived',
    ];

    /**
     * Get the casts for the model.
     *
     * @return array
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'is_archived' => 'boolean',
        ];
    }

    /**
     * Get the user (client).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the full vehicle name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->make} {$this->model} {$this->year}";
    }

    /**
     * Get the vehicle snapshot for contracts.
     */
    public function getSnapshot(): array
    {
        return [
            'vehicle_id' => $this->id,
            'make' => $this->make,
            'model' => $this->model,
            'year' => $this->year,
        ];
    }
}