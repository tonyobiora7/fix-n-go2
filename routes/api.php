<?php

use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\ProfileController;
use App\Http\Controllers\V1\SubscriptionController;
use App\Http\Controllers\V1\VehicleController;
use App\Http\Controllers\V1\SearchController;
use App\Http\Controllers\V1\ChatController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Test route
Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is working!'
    ]);
});

Route::prefix('v1')->group(function () {

    // Public routes - no authentication required
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

    // Public profile
    Route::get('/profiles/{userId}', [ProfileController::class, 'getPublicProfile']);

    // Public vehicle brands/models
    Route::get('/vehicle-brands', [VehicleController::class, 'brands']);
    Route::get('/vehicle-models/{brandId}', [VehicleController::class, 'models']);

    // Protected routes - require authentication
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Profile
        Route::get('/profile', [ProfileController::class, 'getProfile']);
        Route::put('/profile/client', [ProfileController::class, 'updateClientProfile']);
        Route::put('/profile/provider', [ProfileController::class, 'updateProviderProfile']);
        Route::put('/profile/dealer', [ProfileController::class, 'updateDealerProfile']);
        Route::post('/profile/upload-image', [ProfileController::class, 'uploadImage']);

        // Subscription
        Route::get('/subscription/status', [SubscriptionController::class, 'getStatus']);
        Route::post('/subscription/purchase', [SubscriptionController::class, 'purchase']);
        Route::post('/subscription/renew', [SubscriptionController::class, 'renew']);
        Route::post('/subscription/activate-trial', [SubscriptionController::class, 'activateTrial']);

        // Vehicle Garage
        Route::get('/vehicles', [VehicleController::class, 'index']);
        Route::get('/vehicles/archived', [VehicleController::class, 'archived']);
        Route::post('/vehicles', [VehicleController::class, 'store']);
        Route::put('/vehicles/{id}', [VehicleController::class, 'update']);
        Route::delete('/vehicles/{id}', [VehicleController::class, 'destroy']);
        Route::post('/vehicles/{id}/restore', [VehicleController::class, 'restore']);

        // Search
        Route::get('/search/providers', [SearchController::class, 'searchProviders']);
        Route::get('/search/dealers', [SearchController::class, 'searchDealers']);

        // Chats
        Route::get('/chats', [ChatController::class, 'index']);
        Route::post('/chats', [ChatController::class, 'store']);
        Route::get('/chats/{chatId}', [ChatController::class, 'show']);
        Route::get('/chats/{chatId}/messages', [ChatController::class, 'messages']);
        Route::post('/chats/{chatId}/messages', [ChatController::class, 'sendMessage']);
        Route::post('/chats/{chatId}/read', [ChatController::class, 'markAsRead']);
    });

});