<?php

namespace App\Models;

use App\Shared\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'contract_id',
        'reference',
        'amount',
        'platform_fee',
        'net_amount',
        'status',
        'payer_id',
        'payee_id',
        'payment_date',
        'release_date',
        'guarantee_start',
        'guarantee_end',
    ];

    /**
     * Get the casts for the model.
     *
     * @return array
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'platform_fee' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'payment_date' => 'datetime',
            'release_date' => 'datetime',
            'guarantee_start' => 'datetime',
            'guarantee_end' => 'datetime',
            'status' => PaymentStatus::class,
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
     * Get the transactions.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Check if payment is held.
     */
    public function isHeld(): bool
    {
        return $this->status->isHeld();
    }

    /**
     * Check if payment is in terminal state.
     */
    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    /**
     * Release payment early.
     */
    public function release(): void
    {
        $this->update([
            'status' => PaymentStatus::RELEASED,
            'release_date' => now(),
        ]);
    }

    /**
     * Freeze payment (for disputes).
     */
    public function freeze(): void
    {
        if ($this->isHeld()) {
            $this->update([
                'status' => PaymentStatus::FROZEN,
            ]);
        }
    }

    /**
     * Refund payment.
     */
    public function refund(): void
    {
        $this->update([
            'status' => PaymentStatus::REFUNDED,
        ]);
    }
}