<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ClientProfile;
use App\Models\ProviderProfile;
use App\Models\DealerProfile;
use App\Models\ServiceCategory;
use App\Models\VehicleBrand;
use App\Shared\Enums\UserStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    /**
     * Get current user's profile.
     */
    public function getProfile(Request $request)
    {
        $user = $request->user();
        $profileData = [
            'user' => $this->formatUser($user),
        ];

        if ($user->isClient() && $user->clientProfile) {
            $profileData['profile'] = $this->formatClientProfile($user->clientProfile);
        } elseif ($user->isProvider() && $user->providerProfile) {
            $profileData['profile'] = $this->formatProviderProfile($user->providerProfile);
        } elseif ($user->isDealer() && $user->dealerProfile) {
            $profileData['profile'] = $this->formatDealerProfile($user->dealerProfile);
        }

        return response()->json([
            'success' => true,
            'data' => $profileData,
        ]);
    }

    /**
     * Update Provider Profile.
     */
    public function updateProviderProfile(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user->isProvider()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not a service provider.',
                    'role' => $user->role,
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'business_name' => 'required|string|max:100',
                'business_description' => 'nullable|string',
                'business_address' => 'required|string',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'working_hours' => 'nullable|array',
                'service_radius' => 'nullable|integer|min:1|max:50',
                'bank_account_name' => 'required|string|max:100',
                'bank_account_number' => 'required|string|max:20',
                'bank_name' => 'required|string|max:100',
                'bank_code' => 'required|string|max:10',
                'categories' => 'required|array|min:1',
                'categories.*' => 'exists:service_categories,id',
                'brands' => 'required|array|min:1',
                'brands.*' => 'exists:vehicle_brands,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $profile = $user->providerProfile;

            if (!$profile) {
                $profile = new ProviderProfile();
                $profile->user_id = $user->id;
            }

            $profile->business_name = $request->business_name;
            $profile->business_description = $request->business_description;
            $profile->business_address = $request->business_address;
            $profile->business_location = DB::raw("ST_PointFromText('POINT({$request->latitude} {$request->longitude})')");
            $profile->working_hours = $request->working_hours;
            $profile->service_radius = $request->service_radius ?? 10;
            $profile->bank_account_name = $request->bank_account_name;
            $profile->bank_account_number = $request->bank_account_number;
            $profile->bank_name = $request->bank_name;
            $profile->bank_code = $request->bank_code;
            $profile->bvn = $profile->bvn ?? '00000000000';
            $profile->save();

            $profile->categories()->sync($request->categories);
            $profile->brands()->sync($request->brands);

            $user->update(['profile_complete' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Provider profile updated successfully.',
                'data' => [
                    'profile' => $this->formatProviderProfile($profile),
                    'profile_complete' => $user->profile_complete,
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
     * Update Dealer Profile.
     */
    public function updateDealerProfile(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user->isDealer()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not a dealer.',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'business_name' => 'required|string|max:100',
                'business_description' => 'nullable|string',
                'business_address' => 'required|string',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'working_hours' => 'nullable|array',
                'bank_account_name' => 'required|string|max:100',
                'bank_account_number' => 'required|string|max:20',
                'bank_name' => 'required|string|max:100',
                'bank_code' => 'required|string|max:10',
                'brands' => 'required|array|min:1',
                'brands.*' => 'exists:vehicle_brands,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $profile = $user->dealerProfile;

            if (!$profile) {
                $profile = new DealerProfile();
                $profile->user_id = $user->id;
            }

            $profile->business_name = $request->business_name;
            $profile->business_description = $request->business_description;
            $profile->business_address = $request->business_address;
            $profile->business_location = DB::raw("ST_PointFromText('POINT({$request->latitude} {$request->longitude})')");
            $profile->working_hours = $request->working_hours;
            $profile->bank_account_name = $request->bank_account_name;
            $profile->bank_account_number = $request->bank_account_number;
            $profile->bank_name = $request->bank_name;
            $profile->bank_code = $request->bank_code;
            $profile->bvn = $profile->bvn ?? '00000000000';
            $profile->save();

            $profile->brands()->sync($request->brands);

            $user->update(['profile_complete' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Dealer profile updated successfully.',
                'data' => [
                    'profile' => $this->formatDealerProfile($profile),
                    'profile_complete' => $user->profile_complete,
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
     * Update Client Profile.
     */
    public function updateClientProfile(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user->isClient()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not a client.',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'profile_image' => 'nullable|string|url',
                'notification_preferences' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $profile = $user->clientProfile;

            if (!$profile) {
                $profile = new ClientProfile();
                $profile->user_id = $user->id;
            }

            if ($request->has('profile_image')) {
                $profile->profile_image = $request->profile_image;
            }

            if ($request->has('notification_preferences')) {
                $profile->notification_preferences = $request->notification_preferences;
            }

            $profile->save();

            return response()->json([
                'success' => true,
                'message' => 'Client profile updated successfully.',
                'data' => [
                    'profile' => $this->formatClientProfile($profile),
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
     * Upload profile image.
     */
    public function uploadImage(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpeg,png,webp|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                    'debug' => [
                        'has_file' => $request->hasFile('image'),
                        'all_files' => $request->allFiles(),
                        'all_input' => $request->all(),
                    ],
                ], 422);
            }

            $file = $request->file('image');

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'No file found in request.',
                    'debug' => [
                        'has_file' => $request->hasFile('image'),
                        'all_files' => $request->allFiles(),
                    ],
                ], 400);
            }

            if (!$file->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'File is not valid: ' . $file->getErrorMessage(),
                ], 400);
            }

            $path = $file->store('profiles', 'public');

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully.',
                'data' => [
                    'url' => Storage::url($path),
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
     * Get public profile by user ID.
     */
    public function getPublicProfile($userId)
    {
        try {
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 404);
            }

            if (!$user->isProvider() && !$user->isDealer()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User does not have a public profile.',
                ], 404);
            }

            $profileData = [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'role' => $user->role,
                'verification_badge' => $user->bvn_verified,
                'average_rating' => 0,
                'review_count' => 0,
                'is_open' => true,
            ];

            if ($user->isProvider() && $user->providerProfile) {
                $profile = $user->providerProfile;
                $profileData['business_name'] = $profile->business_name;
                $profileData['business_logo'] = $profile->business_logo;
                $profileData['business_description'] = $profile->business_description;
                $profileData['business_address'] = $profile->business_address;
                $profileData['working_hours'] = $profile->working_hours;
                $profileData['gallery_images'] = $profile->gallery_images;
            }

            if ($user->isDealer() && $user->dealerProfile) {
                $profile = $user->dealerProfile;
                $profileData['business_name'] = $profile->business_name;
                $profileData['business_logo'] = $profile->business_logo;
                $profileData['business_description'] = $profile->business_description;
                $profileData['business_address'] = $profile->business_address;
                $profileData['working_hours'] = $profile->working_hours;
                $profileData['gallery_images'] = $profile->gallery_images;
            }

            return response()->json([
                'success' => true,
                'data' => $profileData,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Format user for response.
     */
    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'phone' => $user->phone,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'phone_verified' => $user->phone_verified,
            'bvn_verified' => $user->bvn_verified,
            'profile_complete' => $user->profile_complete,
            'created_at' => $user->created_at,
        ];
    }

    /**
     * Format client profile for response.
     */
    private function formatClientProfile(ClientProfile $profile): array
    {
        return [
            'id' => $profile->id,
            'user_id' => $profile->user_id,
            'profile_image' => $profile->profile_image,
            'notification_preferences' => $profile->notification_preferences,
            'created_at' => $profile->created_at,
            'updated_at' => $profile->updated_at,
        ];
    }

    /**
     * Format provider profile for response (excludes binary location).
     */
    private function formatProviderProfile(ProviderProfile $profile): array
    {
        return [
            'id' => $profile->id,
            'user_id' => $profile->user_id,
            'business_name' => $profile->business_name,
            'business_logo' => $profile->business_logo,
            'business_description' => $profile->business_description,
            'business_address' => $profile->business_address,
            'working_hours' => $profile->working_hours,
            'service_radius' => $profile->service_radius,
            'bank_account_name' => $profile->bank_account_name,
            'bank_name' => $profile->bank_name,
            'verification_date' => $profile->verification_date,
            'gallery_images' => $profile->gallery_images,
            'created_at' => $profile->created_at,
            'updated_at' => $profile->updated_at,
        ];
    }

    /**
     * Format dealer profile for response (excludes binary location).
     */
    private function formatDealerProfile(DealerProfile $profile): array
    {
        return [
            'id' => $profile->id,
            'user_id' => $profile->user_id,
            'business_name' => $profile->business_name,
            'business_logo' => $profile->business_logo,
            'business_description' => $profile->business_description,
            'business_address' => $profile->business_address,
            'working_hours' => $profile->working_hours,
            'bank_account_name' => $profile->bank_account_name,
            'bank_name' => $profile->bank_name,
            'verification_date' => $profile->verification_date,
            'gallery_images' => $profile->gallery_images,
            'created_at' => $profile->created_at,
            'updated_at' => $profile->updated_at,
        ];
    }
}