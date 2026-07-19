<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use App\Shared\Enums\SubscriptionStatus;
use App\Shared\Enums\SubscriptionType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    /**
     * Get current user's subscription status.
     */
    public function getStatus(Request $request)
    {
        $user = $request->user();
        $subscription = $user->subscription;

        if (!$subscription) {
            return response()->json([
                'success' => true,
                'data' => [
                    'has_subscription' => false,
                    'status' => null,
                    'type' => null,
                    'start_date' => null,
                    'end_date' => null,
                    'grace_end_date' => null,
                    'days_remaining' => 0,
                    'is_searchable' => false,
                    'is_in_grace_period' => false,
                    'is_active' => false,
                    'is_expired' => false,
                ],
            ]);
        }

        $statusString = $subscription->status instanceof SubscriptionStatus 
            ? $subscription->status->value 
            : $subscription->status;
        
        $isActive = in_array($statusString, ['trial_active', 'paid_active']);
        $isExpired = $statusString === 'expired';
        $isInGracePeriod = $statusString === 'grace_period';

        $endDate = $subscription->end_date;
        if ($endDate && !$isExpired) {
            $endDateCarbon = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
            if ($endDateCarbon->isPast()) {
                $isExpired = true;
                $isActive = false;
            }
        }

        $daysRemaining = 0;
        if (($isActive || $isInGracePeriod) && $endDate) {
            $endDateCarbon = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
            $daysRemaining = (int) Carbon::now()->diffInDays($endDateCarbon, false);
            if ($daysRemaining < 0) {
                $daysRemaining = 0;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'has_subscription' => true,
                'status' => $statusString,
                'type' => $subscription->type instanceof SubscriptionType 
                    ? $subscription->type->value 
                    : $subscription->type,
                'start_date' => $subscription->start_date,
                'end_date' => $subscription->end_date,
                'grace_end_date' => $subscription->grace_end_date,
                'days_remaining' => $daysRemaining,
                'is_searchable' => $this->isSearchable($user),
                'is_in_grace_period' => $isInGracePeriod,
                'is_active' => $isActive,
                'is_expired' => $isExpired,
            ],
        ]);
    }

    /**
     * Purchase a paid subscription (extends existing if active).
     */
    public function purchase(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'payment_reference' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $existingSubscription = $user->subscription;
            $isFirstPaid = $this->isFirstPaidSubscription($user);
            $durationDays = $isFirstPaid ? SubscriptionType::firstPaidDurationDays() : SubscriptionType::PAID->durationDays();

            if ($existingSubscription) {
                $statusString = $existingSubscription->status instanceof SubscriptionStatus 
                    ? $existingSubscription->status->value 
                    : $existingSubscription->status;

                // Check if subscription is active or in grace period
                $isActive = in_array($statusString, ['trial_active', 'paid_active']);
                $isInGracePeriod = $statusString === 'grace_period';

                if ($isActive || $isInGracePeriod) {
                    // EXTEND existing subscription
                    $currentEndDate = $existingSubscription->end_date;
                    $currentEndDateCarbon = $currentEndDate instanceof Carbon 
                        ? $currentEndDate 
                        : Carbon::parse($currentEndDate);

                    // If end date is in the past, start from now
                    if ($currentEndDateCarbon->isPast()) {
                        $newEndDate = Carbon::now()->addDays($durationDays);
                    } else {
                        $newEndDate = $currentEndDateCarbon->addDays($durationDays);
                    }

                    $existingSubscription->update([
                        'end_date' => $newEndDate,
                        'status' => SubscriptionStatus::PAID_ACTIVE,
                        'type' => SubscriptionType::PAID,
                        'amount_paid' => $this->getSubscriptionPrice($durationDays),
                        'payment_reference' => $request->payment_reference,
                        'grace_end_date' => null,
                    ]);

                    $existingSubscription->refresh();

                    return response()->json([
                        'success' => true,
                        'message' => 'Subscription extended successfully.',
                        'data' => [
                            'subscription' => [
                                'id' => $existingSubscription->id,
                                'type' => $existingSubscription->type instanceof SubscriptionType 
                                    ? $existingSubscription->type->value 
                                    : $existingSubscription->type,
                                'status' => $existingSubscription->status instanceof SubscriptionStatus 
                                    ? $existingSubscription->status->value 
                                    : $existingSubscription->status,
                                'start_date' => $existingSubscription->start_date,
                                'end_date' => $existingSubscription->end_date,
                                'days_remaining' => $this->calculateDaysRemaining($existingSubscription),
                                'amount_paid' => $existingSubscription->amount_paid,
                            ],
                            'is_searchable' => $this->isSearchable($user),
                        ],
                    ]);
                }
            }

            // No existing subscription or it's expired - create new
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'type' => SubscriptionType::PAID,
                'status' => SubscriptionStatus::PAID_ACTIVE,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addDays($durationDays),
                'amount_paid' => $this->getSubscriptionPrice($durationDays),
                'payment_reference' => $request->payment_reference,
            ]);

            $subscription->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Subscription purchased successfully.',
                'data' => [
                    'subscription' => [
                        'id' => $subscription->id,
                        'type' => $subscription->type instanceof SubscriptionType 
                            ? $subscription->type->value 
                            : $subscription->type,
                        'status' => $subscription->status instanceof SubscriptionStatus 
                            ? $subscription->status->value 
                            : $subscription->status,
                        'start_date' => $subscription->start_date,
                        'end_date' => $subscription->end_date,
                        'days_remaining' => $this->calculateDaysRemaining($subscription),
                        'amount_paid' => $subscription->amount_paid,
                    ],
                    'is_searchable' => $this->isSearchable($user),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 500);
        }
    }

    /**
     * Renew subscription (extends existing if active).
     */
    public function renew(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'payment_reference' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $subscription = $user->subscription;

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'No subscription found to renew.',
                ], 404);
            }

            $durationDays = SubscriptionType::PAID->durationDays();

            // EXTEND the existing subscription
            $currentEndDate = $subscription->end_date;
            $currentEndDateCarbon = $currentEndDate instanceof Carbon 
                ? $currentEndDate 
                : Carbon::parse($currentEndDate);

            // If end date is in the past, start from now
            if ($currentEndDateCarbon->isPast()) {
                $newEndDate = Carbon::now()->addDays($durationDays);
            } else {
                $newEndDate = $currentEndDateCarbon->addDays($durationDays);
            }

            $subscription->update([
                'end_date' => $newEndDate,
                'status' => SubscriptionStatus::PAID_ACTIVE,
                'type' => SubscriptionType::PAID,
                'amount_paid' => $this->getSubscriptionPrice($durationDays),
                'payment_reference' => $request->payment_reference,
                'grace_end_date' => null,
            ]);

            $subscription->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Subscription renewed and extended successfully.',
                'data' => [
                    'subscription' => [
                        'id' => $subscription->id,
                        'type' => $subscription->type instanceof SubscriptionType 
                            ? $subscription->type->value 
                            : $subscription->type,
                        'status' => $subscription->status instanceof SubscriptionStatus 
                            ? $subscription->status->value 
                            : $subscription->status,
                        'start_date' => $subscription->start_date,
                        'end_date' => $subscription->end_date,
                        'days_remaining' => $this->calculateDaysRemaining($subscription),
                        'amount_paid' => $subscription->amount_paid,
                    ],
                    'is_searchable' => $this->isSearchable($user),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activate trial subscription (for BVN verification success).
     */
    public function activateTrial(Request $request)
    {
        try {
            $user = $request->user();

            // Check if user already has a subscription
            $existingSubscription = $user->subscription;
            if ($existingSubscription) {
                $statusString = $existingSubscription->status instanceof SubscriptionStatus 
                    ? $existingSubscription->status->value 
                    : $existingSubscription->status;
                if (in_array($statusString, ['trial_active', 'paid_active'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User already has an active subscription.',
                    ], 400);
                }
            }

            // Activate trial
            $subscription = Subscription::activateTrial($user);
            $subscription->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Trial subscription activated successfully.',
                'data' => [
                    'subscription' => [
                        'id' => $subscription->id,
                        'type' => $subscription->type instanceof SubscriptionType 
                            ? $subscription->type->value 
                            : $subscription->type,
                        'status' => $subscription->status instanceof SubscriptionStatus 
                            ? $subscription->status->value 
                            : $subscription->status,
                        'start_date' => $subscription->start_date,
                        'end_date' => $subscription->end_date,
                        'days_remaining' => $this->calculateDaysRemaining($subscription),
                    ],
                    'is_searchable' => $this->isSearchable($user),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if user is searchable.
     */
    private function isSearchable(User $user): bool
    {
        return $user->isSearchable();
    }

    /**
     * Check if this is the user's first paid subscription.
     */
    private function isFirstPaidSubscription(User $user): bool
    {
        $hasPaidSubscription = Subscription::where('user_id', $user->id)
            ->where('type', SubscriptionType::PAID)
            ->whereIn('status', [
                SubscriptionStatus::PAID_ACTIVE,
                SubscriptionStatus::EXPIRED,
                SubscriptionStatus::GRACE_PERIOD
            ])
            ->exists();

        return !$hasPaidSubscription;
    }

    /**
     * Get subscription price based on duration.
     */
    private function getSubscriptionPrice(int $durationDays): float
    {
        return match($durationDays) {
            60 => 15000.00,
            30 => 10000.00,
            7 => 0.00,
            default => 10000.00,
        };
    }

    /**
     * Calculate days remaining for a subscription.
     */
    private function calculateDaysRemaining(Subscription $subscription): int
    {
        $statusString = $subscription->status instanceof SubscriptionStatus 
            ? $subscription->status->value 
            : $subscription->status;

        $isActive = in_array($statusString, ['trial_active', 'paid_active']);
        $isInGracePeriod = $statusString === 'grace_period';

        if (!$isActive && !$isInGracePeriod) {
            return 0;
        }

        $endDate = $isInGracePeriod ? $subscription->grace_end_date : $subscription->end_date;
        
        if (!$endDate) {
            return 0;
        }

        $endDateCarbon = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
        $days = (int) Carbon::now()->diffInDays($endDateCarbon, false);
        
        return $days < 0 ? 0 : $days;
    }
}