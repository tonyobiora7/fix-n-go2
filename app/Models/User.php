<?php

namespace App\Models;

use App\Shared\Enums\UserRole;
use App\Shared\Enums\UserStatus;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'full_name',
        'phone',
        'email',
        'password_hash',
        'role',
        'status',
        'phone_verified',
        'bvn_verified',
        'bvn_verification_date',
        'bvn_verification_status',
        'profile_complete',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    /**
     * Get the casts for the model.
     *
     * @return array
     */
    protected function casts(): array
    {
        return [
            'phone_verified' => 'boolean',
            'bvn_verified' => 'boolean',
            'profile_complete' => 'boolean',
            'last_login_at' => 'datetime',
            'bvn_verification_date' => 'datetime',
            'deleted_at' => 'datetime',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * Check if user is a Client.
     */
    public function isClient(): bool
    {
        $role = $this->role;
        if ($role === null) {
            return false;
        }
        return $role === UserRole::CLIENT || $role === 'client' || (is_object($role) && $role->value === 'client');
    }

    /**
     * Check if user is a Service Provider.
     */
    public function isProvider(): bool
    {
        $role = $this->role;
        if ($role === null) {
            return false;
        }
        return $role === UserRole::PROVIDER || $role === 'provider' || (is_object($role) && $role->value === 'provider');
    }

    /**
     * Check if user is a Dealer.
     */
    public function isDealer(): bool
    {
        $role = $this->role;
        if ($role === null) {
            return false;
        }
        return $role === UserRole::DEALER || $role === 'dealer' || (is_object($role) && $role->value === 'dealer');
    }

    /**
     * Check if user is an Admin.
     */
    public function isAdmin(): bool
    {
        $role = $this->role;
        if ($role === null) {
            return false;
        }
        return $role === UserRole::ADMIN || $role === 'admin' || (is_object($role) && $role->value === 'admin');
    }

    /**
     * Check if user is searchable.
     */
    public function isSearchable(): bool
    {
        $role = $this->role;
        if ($role === null) {
            return false;
        }

        $roleIsSearchable = false;
        if (is_object($role) && method_exists($role, 'isSearchable')) {
            $roleIsSearchable = $role->isSearchable();
        } else {
            $roleIsSearchable = in_array($role, ['provider', 'dealer']);
        }

        return $roleIsSearchable
            && $this->status === UserStatus::ACTIVE
            && $this->bvn_verified
            && $this->profile_complete
            && $this->hasActiveSubscription();
    }

    /**
     * Check if user has an active subscription.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscription()
            ->whereIn('status', ['trial_active', 'paid_active'])
            ->exists();
    }

    /**
     * Get the client profile.
     */
    public function clientProfile(): HasOne
    {
        return $this->hasOne(ClientProfile::class);
    }

    /**
     * Get the provider profile.
     */
    public function providerProfile(): HasOne
    {
        return $this->hasOne(ProviderProfile::class);
    }

    /**
     * Get the dealer profile.
     */
    public function dealerProfile(): HasOne
    {
        return $this->hasOne(DealerProfile::class);
    }

    /**
     * Get the subscription.
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latest();
    }

    /**
     * Get the vehicles.
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Get the chats created.
     */
    public function createdChats(): HasMany
    {
        return $this->hasMany(Chat::class, 'creator_id');
    }

    /**
     * Get the chats received.
     */
    public function receivedChats(): HasMany
    {
        return $this->hasMany(Chat::class, 'recipient_id');
    }

    /**
     * Get the messages.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Get the notifications.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}