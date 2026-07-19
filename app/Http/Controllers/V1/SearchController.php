<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ProviderProfile;
use App\Models\DealerProfile;
use App\Models\ServiceCategory;
use App\Models\VehicleBrand;
use App\Shared\Enums\UserRole;
use App\Shared\Enums\UserStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    /**
     * Search for Service Providers (Clients only).
     */
    public function searchProviders(Request $request)
    {
        try {
            $user = $request->user();

            // Only clients can search for providers
            if (!$user->isClient()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only clients can search for service providers.',
                ], 403);
            }

            $validator = validator($request->all(), [
                'keyword' => 'nullable|string|max:100',
                'category' => 'nullable|uuid|exists:service_categories,id',
                'brand' => 'nullable|uuid|exists:vehicle_brands,id',
                'distance' => 'nullable|integer|in:2,5,10,20',
                'latitude' => 'required_with:distance|numeric|between:-90,90',
                'longitude' => 'required_with:distance|numeric|between:-180,180',
                'page' => 'nullable|integer|min:1',
                'limit' => 'nullable|integer|min:1|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $keyword = $request->keyword;
            $categoryId = $request->category;
            $brandId = $request->brand;
            $distance = $request->distance ?? 20;
            $latitude = $request->latitude;
            $longitude = $request->longitude;
            $page = $request->page ?? 1;
            $limit = $request->limit ?? 20;

            // Base query for searchable providers
            $query = User::query()
                ->where('role', UserRole::PROVIDER)
                ->where('status', UserStatus::ACTIVE)
                ->where('bvn_verified', true)
                ->where('profile_complete', true)
                ->whereHas('subscription', function ($q) {
                    $q->whereIn('status', ['trial_active', 'paid_active']);
                })
                ->whereHas('providerProfile');

            // Keyword search
            if ($keyword) {
                $query->whereHas('providerProfile', function ($q) use ($keyword) {
                    $q->where('business_name', 'LIKE', "%{$keyword}%")
                        ->orWhere('business_description', 'LIKE', "%{$keyword}%");
                });
            }

            // Category filter
            if ($categoryId) {
                $query->whereHas('providerProfile.categories', function ($q) use ($categoryId) {
                    $q->where('service_categories.id', $categoryId);
                });
            }

            // Brand filter
            if ($brandId) {
                $query->whereHas('providerProfile.brands', function ($q) use ($brandId) {
                    $q->where('vehicle_brands.id', $brandId);
                });
            }

            // Distance filter (using ST_Distance)
            if ($latitude && $longitude) {
                $distanceInKm = $distance * 1000; // Convert to meters
                $query->whereHas('providerProfile', function ($q) use ($latitude, $longitude, $distanceInKm) {
                    $q->whereRaw(
                        "ST_Distance(business_location, ST_GeomFromText(?)) <= ?",
                        ["POINT({$latitude} {$longitude})", $distanceInKm]
                    );
                });
            }

            // Load profiles and calculate distance
            $providers = $query->with('providerProfile')->paginate($limit, ['*'], 'page', $page);

            // Format results
            $results = $providers->map(function ($provider) use ($latitude, $longitude) {
                $profile = $provider->providerProfile;
                
                return [
                    'id' => $provider->id,
                    'business_name' => $profile->business_name,
                    'business_logo' => $profile->business_logo,
                    'business_description' => $profile->business_description,
                    'business_address' => $profile->business_address,
                    'verification_badge' => $provider->bvn_verified,
                    'average_rating' => 0,
                    'review_count' => 0,
                    'is_open' => true,
                    'service_categories' => $profile->categories()->pluck('name'),
                    'vehicle_brands' => $profile->brands()->pluck('name'),
                    'working_hours' => $profile->working_hours,
                    'distance' => $this->calculateDistance($latitude, $longitude, $profile->business_location),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $results,
                'meta' => [
                    'total' => $providers->total(),
                    'current_page' => $providers->currentPage(),
                    'per_page' => $providers->perPage(),
                    'last_page' => $providers->lastPage(),
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
     * Search for Dealers (Clients and Providers).
     */
    public function searchDealers(Request $request)
    {
        try {
            $user = $request->user();

            // Clients and Providers can search for dealers
            if (!$user->isClient() && !$user->isProvider()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only clients and providers can search for dealers.',
                ], 403);
            }

            $validator = validator($request->all(), [
                'keyword' => 'nullable|string|max:100',
                'brand' => 'nullable|uuid|exists:vehicle_brands,id',
                'distance' => 'nullable|integer|in:2,5,10,20',
                'latitude' => 'required_with:distance|numeric|between:-90,90',
                'longitude' => 'required_with:distance|numeric|between:-180,180',
                'page' => 'nullable|integer|min:1',
                'limit' => 'nullable|integer|min:1|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $keyword = $request->keyword;
            $brandId = $request->brand;
            $distance = $request->distance ?? 20;
            $latitude = $request->latitude;
            $longitude = $request->longitude;
            $page = $request->page ?? 1;
            $limit = $request->limit ?? 20;

            // Base query for searchable dealers
            $query = User::query()
                ->where('role', UserRole::DEALER)
                ->where('status', UserStatus::ACTIVE)
                ->where('bvn_verified', true)
                ->where('profile_complete', true)
                ->whereHas('subscription', function ($q) {
                    $q->whereIn('status', ['trial_active', 'paid_active']);
                })
                ->whereHas('dealerProfile');

            // Keyword search
            if ($keyword) {
                $query->whereHas('dealerProfile', function ($q) use ($keyword) {
                    $q->where('business_name', 'LIKE', "%{$keyword}%")
                        ->orWhere('business_description', 'LIKE', "%{$keyword}%");
                });
            }

            // Brand filter
            if ($brandId) {
                $query->whereHas('dealerProfile.brands', function ($q) use ($brandId) {
                    $q->where('vehicle_brands.id', $brandId);
                });
            }

            // Distance filter
            if ($latitude && $longitude) {
                $distanceInKm = $distance * 1000;
                $query->whereHas('dealerProfile', function ($q) use ($latitude, $longitude, $distanceInKm) {
                    $q->whereRaw(
                        "ST_Distance(business_location, ST_GeomFromText(?)) <= ?",
                        ["POINT({$latitude} {$longitude})", $distanceInKm]
                    );
                });
            }

            $dealers = $query->with('dealerProfile')->paginate($limit, ['*'], 'page', $page);

            $results = $dealers->map(function ($dealer) use ($latitude, $longitude) {
                $profile = $dealer->dealerProfile;
                
                return [
                    'id' => $dealer->id,
                    'business_name' => $profile->business_name,
                    'business_logo' => $profile->business_logo,
                    'business_description' => $profile->business_description,
                    'business_address' => $profile->business_address,
                    'verification_badge' => $dealer->bvn_verified,
                    'average_rating' => 0,
                    'review_count' => 0,
                    'is_open' => true,
                    'vehicle_brands' => $profile->brands()->pluck('name'),
                    'working_hours' => $profile->working_hours,
                    'distance' => $this->calculateDistance($latitude, $longitude, $profile->business_location),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $results,
                'meta' => [
                    'total' => $dealers->total(),
                    'current_page' => $dealers->currentPage(),
                    'per_page' => $dealers->perPage(),
                    'last_page' => $dealers->lastPage(),
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
     * Calculate distance between two points.
     */
    private function calculateDistance($lat1, $lon1, $location)
    {
        if (!$lat1 || !$lon1 || !$location) {
            return null;
        }

        try {
            // Extract lat/lon from POINT string (format: POINT(lat lon))
            $point = str_replace(['POINT(', ')', '  '], ['', '', ' '], $location);
            $coords = explode(' ', $point);
            
            if (count($coords) < 2) {
                return null;
            }

            $lat2 = floatval($coords[0]);
            $lon2 = floatval($coords[1]);

            // Haversine formula
            $earthRadius = 6371; // in kilometers
            $dLat = deg2rad($lat2 - $lat1);
            $dLon = deg2rad($lon2 - $lon1);
            $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
            $c = 2 * asin(sqrt($a));
            $distance = $earthRadius * $c;

            return round($distance, 1);

        } catch (\Exception $e) {
            return null;
        }
    }
}