<?php

namespace App\Models;

use App\Shared\Enums\DisputeStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispute extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'contract_id',
        'payer_id',
        'payee_id',
        'reason',
        'description',
        'status',
        'evidence',
        'admin_id',
        'admin_decision',
        'resolution_notes',
        'opened_at',
        'resolved_at',
    ];

    /**
     * Get the casts for the model.
     *
     * @return array
     */
    protected function casts(): array
    {
        return [
            'evidence' => 'array',
            'opened_at' => 'datetime',
            'resolved_at' => 'datetime',
            'status' => DisputeStatus::class,
        ];
    }

    /**
     * Get the contract.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the payer.
     */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    /**
     * Get the payee.
     */
    public function payee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payee_id');
    }

    /**
     * Get the admin.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Check if dispute is active.
     */
    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    /**
     * Resolve the dispute.
     */
    public function resolve(string $decision, ?string $notes = null): void
    {
        $this->update([
            'status' => DisputeStatus::RESOLVED,
            'admin_decision' => $decision,
            'resolution_notes' => $notes,
            'resolved_at' => now(),
        ]);
    }
}