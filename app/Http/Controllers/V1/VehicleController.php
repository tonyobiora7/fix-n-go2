<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
{
    /**
     * List all vehicles for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $vehicles = $user->vehicles()
            ->where('is_archived', false)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $vehicles,
        ]);
    }

    /**
     * List all archived vehicles for the authenticated user.
     */
    public function archived(Request $request)
    {
        $user = $request->user();
        
        $vehicles = $user->vehicles()
            ->where('is_archived', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $vehicles,
        ]);
    }

    /**
     * Add a new vehicle to the garage.
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();

            // Only clients can add vehicles
            if (!$user->isClient()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only clients can add vehicles.',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'make' => 'required|string|max:50',
                'model' => 'required|string|max:50',
                'year' => 'required|integer|min:1900|max:' . date('Y') + 1,
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $vehicle = Vehicle::create([
                'user_id' => $user->id,
                'make' => $request->make,
                'model' => $request->model,
                'year' => $request->year,
                'is_archived' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Vehicle added successfully.',
                'data' => $vehicle,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a vehicle.
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();
            $vehicle = Vehicle::find($id);

            if (!$vehicle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vehicle not found.',
                ], 404);
            }

            // Check ownership
            if ($vehicle->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not own this vehicle.',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'make' => 'sometimes|string|max:50',
                'model' => 'sometimes|string|max:50',
                'year' => 'sometimes|integer|min:1900|max:' . date('Y') + 1,
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $vehicle->update($request->only(['make', 'model', 'year']));

            return response()->json([
                'success' => true,
                'message' => 'Vehicle updated successfully.',
                'data' => $vehicle,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete or archive a vehicle.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            $vehicle = Vehicle::find($id);

            if (!$vehicle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vehicle not found.',
                ], 404);
            }

            // Check ownership
            if ($vehicle->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not own this vehicle.',
                ], 403);
            }

            // Archive instead of delete (per SRS)
            $vehicle->update(['is_archived' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Vehicle archived successfully.',
                'data' => [
                    'vehicle_id' => $vehicle->id,
                    'is_archived' => true,
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
     * Restore an archived vehicle.
     */
    public function restore(Request $request, $id)
    {
        try {
            $user = $request->user();
            $vehicle = Vehicle::find($id);

            if (!$vehicle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vehicle not found.',
                ], 404);
            }

            // Check ownership
            if ($vehicle->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not own this vehicle.',
                ], 403);
            }

            $vehicle->update(['is_archived' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Vehicle restored successfully.',
                'data' => $vehicle,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get vehicle brands list.
     */
    public function brands()
    {
        $brands = VehicleBrand::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => $brands,
        ]);
    }

    /**
     * Get vehicle models for a brand.
     */
    public function models($brandId)
    {
        $models = VehicleModel::where('brand_id', $brandId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($models->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No models found for this brand.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $models,
        ]);
    }
}