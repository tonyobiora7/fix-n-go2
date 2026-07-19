<?php

namespace App\Models;

use App\Shared\Enums\ContractStatus;
use App\Shared\Enums\ContractType;
use App\Shared\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Contract extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'contracts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'chat_id',
        'type',
        'status',
        'client_id',
        'provider_id',
        'dealer_id',
        'vehicle_snapshot',
        'description',
        'quantity',
        'amount',
        'payment_method',
        'platform_fee',
        'net_amount',
        'guarantee_days',
        'current_version',
        'accepted_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => 'string',
        'status' => 'string',
        'payment_method' => 'string',
        'vehicle_snapshot' => 'array',
        'quantity' => 'integer',
        'amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'guarantee_days' => 'integer',
        'current_version' => 'integer',
        'accepted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'accepted_at',
        'completed_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the chat that owns the contract.
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class, 'chat_id');
    }

    /**
     * Get the client who created the contract.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Get the service provider for the contract.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    /**
     * Get the dealer for the contract.
     */
    public function dealer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dealer_id');
    }

    /**
     * Get all versions of the contract.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ContractVersion::class, 'contract_id');
    }

    /**
     * Get the current version of the contract.
     */
    public function currentVersion(): HasOne
    {
        return $this->hasOne(ContractVersion::class, 'contract_id')
            ->where('version_number', $this->current_version)
            ->latest();
    }

    /**
     * Get the payment associated with the contract.
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'contract_id');
    }

    /**
     * Get the dispute associated with the contract.
     */
    public function dispute(): HasOne
    {
        return $this->hasOne(Dispute::class, 'contract_id');
    }

    /**
     * Get the reviews for the contract.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'contract_id');
    }

    /**
     * Check if the contract is a job contract.
     *
     * @return bool
     */
    public function isJob(): bool
    {
        return $this->type === ContractType::JOB->value || $this->type === 'job';
    }

    /**
     * Check if the contract is a supply contract.
     *
     * @return bool
     */
    public function isSupply(): bool
    {
        return $this->type === ContractType::SUPPLY->value || $this->type === 'supply';
    }

    /**
     * Check if the contract uses protected payment.
     *
     * @return bool
     */
    public function isProtectedPayment(): bool
    {
        return $this->payment_method === PaymentMethod::PROTECTED->value || $this->payment_method === 'protected';
    }

    /**
     * Check if the contract is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === ContractStatus::ACTIVE->value || $this->status === 'active';
    }

    /**
     * Check if the contract is completed.
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === ContractStatus::COMPLETED->value || $this->status === 'completed';
    }

    /**
     * Check if the contract is in guarantee period.
     *
     * @return bool
     */
    public function isGuaranteed(): bool
    {
        return $this->status === ContractStatus::GUARANTEED->value || $this->status === 'guaranteed';
    }

    /**
     * Check if the contract is disputed.
     *
     * @return bool
     */
    public function isDisputed(): bool
    {
        return $this->status === ContractStatus::DISPUTED->value || $this->status === 'disputed';
    }

    /**
     * Check if the contract is closed.
     *
     * @return bool
     */
    public function isClosed(): bool
    {
        return $this->status === ContractStatus::CLOSED->value || $this->status === 'closed';
    }

    /**
     * Check if the contract is cancelled.
     *
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->status === ContractStatus::CANCELLED->value || $this->status === 'cancelled';
    }

    /**
     * Check if the contract is pending acceptance.
     *
     * @return bool
     */
    public function isPendingAcceptance(): bool
    {
        return $this->status === ContractStatus::PENDING_ACCEPTANCE->value || $this->status === 'pending_acceptance';
    }

    /**
     * Get the party who created the contract.
     *
     * @return User|null
     */
    public function getCreator(): ?User
    {
        if ($this->isJob()) {
            return $this->client;
        }

        if ($this->isSupply()) {
            return $this->dealer;
        }

        return null;
    }

    /**
     * Get the party who receives the contract.
     *
     * @return User|null
     */
    public function getRecipient(): ?User
    {
        if ($this->isJob()) {
            return $this->provider;
        }

        if ($this->isSupply()) {
            return $this->client;
        }

        return null;
    }

    /**
     * Get the payer for the contract.
     *
     * @return User|null
     */
    public function getPayer(): ?User
    {
        return $this->client;
    }

    /**
     * Get the payee for the contract.
     *
     * @return User|null
     */
    public function getPayee(): ?User
    {
        if ($this->isJob()) {
            return $this->provider;
        }

        if ($this->isSupply()) {
            return $this->dealer;
        }

        return null;
    }

    /**
     * Calculate the platform fee for the contract.
     *
     * @param float $amount
     * @return float
     */
    public static function calculatePlatformFee(float $amount): float
    {
        // 7.5% platform fee
        return round($amount * 0.075, 2);
    }

    /**
     * Calculate the net amount after platform fee.
     *
     * @param float $amount
     * @return float
     */
    public static function calculateNetAmount(float $amount): float
    {
        return round($amount - self::calculatePlatformFee($amount), 2);
    }

    /**
     * Get the total price including platform fee for display.
     *
     * @return array
     */
    public function getPriceBreakdown(): array
    {
        $amount = (float) $this->amount;
        $fee = (float) $this->platform_fee;
        $net = (float) $this->net_amount;

        return [
            'amount' => $amount,
            'platform_fee' => $fee,
            'fee_percentage' => 7.5,
            'net_amount' => $net,
            'currency' => 'NGN',
        ];
    }

    /**
     * Create a new version of the contract.
     *
     * @param array $data
     * @param User $modifiedBy
     * @return ContractVersion
     */
    public function createNewVersion(array $data, User $modifiedBy): ContractVersion
    {
        $newVersionNumber = $this->current_version + 1;

        $version = $this->versions()->create([
            'contract_id' => $this->id,
            'version_number' => $newVersionNumber,
            'description' => $data['description'] ?? $this->description,
            'amount' => $data['amount'] ?? $this->amount,
            'guarantee_days' => $data['guarantee_days'] ?? $this->guarantee_days,
            'payment_method' => $data['payment_method'] ?? $this->payment_method,
            'modified_by' => $modifiedBy->id,
            'status' => 'draft',
        ]);

        // Update the contract with the new data
        $this->update([
            'description' => $data['description'] ?? $this->description,
            'amount' => $data['amount'] ?? $this->amount,
            'guarantee_days' => $data['guarantee_days'] ?? $this->guarantee_days,
            'payment_method' => $data['payment_method'] ?? $this->payment_method,
            'current_version' => $newVersionNumber,
            'status' => ContractStatus::PENDING_ACCEPTANCE->value,
        ]);

        // Recalculate fees if amount changed
        if (isset($data['amount']) && $data['amount'] != $this->amount) {
            $platformFee = self::calculatePlatformFee($data['amount']);
            $netAmount = self::calculateNetAmount($data['amount']);

            $this->update([
                'platform_fee' => $platformFee,
                'net_amount' => $netAmount,
            ]);
        }

        return $version;
    }

    /**
     * Accept the contract.
     *
     * @return void
     */
    public function accept(): void
    {
        $this->update([
            'status' => ContractStatus::ACTIVE->value,
            'accepted_at' => now(),
        ]);

        // Update the current version to accepted
        if ($this->currentVersion) {
            $this->currentVersion->update([
                'status' => 'accepted',
            ]);
        }
    }

    /**
     * Reject the contract.
     *
     * @param string|null $reason
     * @return void
     */
    public function reject(?string $reason = null): void
    {
        $this->update([
            'status' => ContractStatus::CANCELLED->value,
        ]);

        // Update the current version to rejected
        if ($this->currentVersion) {
            $this->currentVersion->update([
                'status' => 'rejected',
            ]);
        }
    }

    /**
     * Mark the contract as completed.
     *
     * @return void
     */
    public function markComplete(): void
    {
        $this->update([
            'status' => ContractStatus::COMPLETED->value,
            'completed_at' => now(),
        ]);
    }

    /**
     * Confirm completion and move to guarantee.
     *
     * @return void
     */
    public function confirmCompletion(): void
    {
        $this->update([
            'status' => ContractStatus::GUARANTEED->value,
        ]);
    }

    /**
     * Mark the contract as disputed.
     *
     * @return void
     */
    public function markDisputed(): void
    {
        $this->update([
            'status' => ContractStatus::DISPUTED->value,
        ]);
    }

    /**
     * Close the contract.
     *
     * @return void
     */
    public function close(): void
    {
        $this->update([
            'status' => ContractStatus::CLOSED->value,
        ]);
    }
}