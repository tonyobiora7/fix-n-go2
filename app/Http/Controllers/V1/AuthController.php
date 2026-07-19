<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\OTP;
use App\Shared\Enums\UserRole;
use App\Shared\Enums\UserStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'role' => 'required|in:client,provider,dealer',
                'full_name' => 'required|string|min:2|max:100',
                'phone' => 'required|string|unique:users,phone|regex:/^[0-9]{11}$/',
                'email' => 'required|email|unique:users,email|max:100',
                'password' => 'required|string|min:8|confirmed',
                'terms_accepted' => 'required|boolean|accepted',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Determine status based on role
            $status = match($request->role) {
                'client' => UserStatus::PENDING_VERIFICATION->value,
                'provider' => UserStatus::AWAITING_BVN->value,
                'dealer' => UserStatus::AWAITING_BVN->value,
                default => UserStatus::PENDING_VERIFICATION->value,
            };

            $user = User::create([
                'full_name' => $request->full_name,
                'phone' => $request->phone,
                'email' => $request->email,
                'password_hash' => Hash::make($request->password),
                'role' => $request->role,
                'status' => $status,
            ]);

            $response = [
                'success' => true,
                'message' => 'Registration successful.',
                'data' => [
                    'user_id' => $user->id,
                ],
            ];

            // Different response based on role
            if ($request->role === 'client') {
                // Send OTP for Client
                $this->sendOtp($user);
                $response['data']['requires_otp'] = true;
                $response['message'] = 'Registration successful. Please verify your phone.';
            } else {
                // Provider or Dealer - requires BVN
                $response['data']['requires_bvn'] = true;
                $response['message'] = 'Registration successful. Please complete BVN verification.';
            }

            return response()->json($response, 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify OTP (Client only).
     */
    public function verifyOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|string|exists:users,id',
                'otp' => 'required|string|size:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $otp = OTP::where('user_id', $request->user_id)
                ->where('code', $request->otp)
                ->where('expires_at', '>', Carbon::now())
                ->whereNull('used_at')
                ->first();

            if (!$otp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired OTP.',
                ], 400);
            }

            $otp->update(['used_at' => Carbon::now()]);

            $user = User::find($request->user_id);
            $user->update([
                'status' => UserStatus::ACTIVE->value,
                'phone_verified' => true,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Phone verified successfully.',
                'data' => [
                    'token' => $token,
                    'user' => $this->formatUser($user),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Verification failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login user.
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'login' => 'required|string',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = User::where('phone', $request->login)
                ->orWhere('email', $request->login)
                ->first();

            if (!$user || !Hash::check($request->password, $user->password_hash)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials.',
                ], 401);
            }

            if ($user->status === UserStatus::SUSPENDED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been suspended. Please contact support.',
                ], 403);
            }

            if ($user->status === UserStatus::CLOSED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been closed.',
                ], 403);
            }

            $user->update(['last_login_at' => Carbon::now()]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful.',
                'data' => [
                    'token' => $token,
                    'user' => $this->formatUser($user),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout user.
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current user.
     */
    public function me(Request $request)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->formatUser($request->user()),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send OTP for password reset.
     */
    public function forgotPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'login' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = User::where('phone', $request->login)
                ->orWhere('email', $request->login)
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 404);
            }

            $this->sendOtp($user, 'password_reset');

            return response()->json([
                'success' => true,
                'message' => 'OTP sent to your email.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset password.
     */
    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'login' => 'required|string',
                'otp' => 'required|string|size:6',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = User::where('phone', $request->login)
                ->orWhere('email', $request->login)
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 404);
            }

            $otp = OTP::where('user_id', $user->id)
                ->where('code', $request->otp)
                ->where('type', 'password_reset')
                ->where('expires_at', '>', Carbon::now())
                ->whereNull('used_at')
                ->first();

            if (!$otp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired OTP.',
                ], 400);
            }

            $otp->update(['used_at' => Carbon::now()]);

            $user->update([
                'password_hash' => Hash::make($request->password),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Password reset failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send OTP to user.
     */
    private function sendOtp(User $user, string $type = 'registration')
    {
        $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        OTP::where('user_id', $user->id)->whereNull('used_at')->delete();

        OTP::create([
            'user_id' => $user->id,
            'code' => $code,
            'type' => $type,
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        // Log OTP for testing
        \Log::info("OTP for {$user->email}: {$code}");
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
}