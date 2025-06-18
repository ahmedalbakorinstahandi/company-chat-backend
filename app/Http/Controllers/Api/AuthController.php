<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ImageService;
use App\Services\MessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:20',
            'last_name' => 'required|string|max:20',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:8',
            'phone_number' => 'required|string|max:15',
            'role' => 'required|in:manager,employee',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            $avatar = ImageService::storeImage($request->file('avatar'), 'avatars');
        } else {
            $avatar = 'none';
        }

        // $table->boolean('is_verified')->default(false);
        // $table->string('otp', 10)->nullable();
        // $table->timestamp('otp_expire_at')->nullable();

        $otp = rand(100000, 999999);
        $otp_expire_at = now()->addMinutes(5);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'role' => $request->role,
            'avatar' => $avatar,
            'otp' => $otp,
            'otp_expire_at' => $otp_expire_at,
        ]);


        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'device_token' => 'nullable|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            MessageService::abort(401, 'The provided credentials are incorrect.');
        }

        if (!$user->is_active) {
            MessageService::abort(401, 'Your account is inactive.');
        }

        if ($request->device_token) {
            $user->update(['device_token' => $request->device_token]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Logged in successfully',
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|exists:users',
        ]);

        $user = User::where('email', $request->email)->first();
        $otp = rand(100000, 999999);
        $user->update([
            'otp' => $otp,
            'otp_expire_at' => now()->addMinutes(5),
        ]);

        // Send OTP via email
        // TODO: Implement email sending

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully',
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|exists:users',
            'otp' => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->email)
            // ->where('otp', $request->otp)
            ->where('otp_expire_at', '>', now())
            ->first();

        if (!$user) {
            MessageService::abort(401, 'Invalid or expired OTP.');
        }

        $user->update([
            'is_verified' => true,
            'otp' => null,
            'otp_expire_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|exists:users',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('otp_expire_at', '>', now())
            ->first();

        if (!$user) {
            MessageService::abort(401, 'Invalid or expired OTP.');
        }

        $user->update([
            'password' => Hash::make($request->password),
            'otp' => null,
            'otp_expire_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $request->user(),
        ]);
    }
}
