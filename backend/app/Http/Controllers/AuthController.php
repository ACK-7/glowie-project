<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Customer Registration
     */
    public function customerRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:customers,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $customer = Customer::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'country' => $request->country,
            'city' => $request->city,
            'address' => $request->address,
        ]);

        $token = $customer->createToken('customer-auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Customer registered successfully',
            'customer' => $customer,
            'token' => $token,
        ], 201);
    }

    /**
     * Customer Login
     */
    public function customerLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $customer = Customer::where('email', $request->email)->first();

        if (!$customer || !Hash::check($request->password, $customer->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$customer->is_active) {
            return response()->json(['message' => 'Account is deactivated. Please contact support.'], 403);
        }

        // Update last login
        $customer->last_login_at = now();
        $customer->save();

        $token = $customer->createToken('customer-auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'customer' => $customer,
            'token' => $token,
        ]);
    }

    /**
     * Admin Login
     */
    public function adminLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Account is deactivated. Please contact administrator.'], 403);
        }

        // Update last login
        $user->last_login_at = now();
        $user->save();

        $token = $user->createToken('admin-auth-token', ['admin'])->plainTextToken;

        return response()->json([
            'message' => 'Admin login successful',
            'user' => $user,
            'token' => $token,
            'role' => $user->role,
        ]);
    }

    /**
     * Customer Logout
     */
    public function customerLogout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Admin Logout
     */
    public function adminLogout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Get Customer Profile
     */
    public function getCustomerProfile(Request $request)
    {
        $customer = $request->user();
        
        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'Profile retrieved successfully'
        ]);
    }

    /**
     * Update Customer Profile
     */
    public function updateCustomerProfile(Request $request)
    {
        $customer = $request->user();

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'phone' => 'sometimes|string|max:20',
            'country' => 'sometimes|string|max:100',
            'city' => 'sometimes|string|max:100',
            'address' => 'sometimes|string',
            'postal_code' => 'sometimes|string|max:20',
            'date_of_birth' => 'sometimes|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $customer->update($request->only([
            'first_name', 'last_name', 'phone', 
            'country', 'city', 'address', 'postal_code', 'date_of_birth'
        ]));

        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'Profile updated successfully',
        ]);
    }

    /**
     * Change Password
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password changed successfully']);
    }

    /**
     * Request Password Reset
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $customer = Customer::where('email', $request->email)->first();

        if ($customer) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $customer->reset_token = $token;
            $customer->reset_token_expires_at = now()->addHours(2);
            $customer->save();

            // Send email with reset link
            $notificationService = app(\App\Services\NotificationService::class);
            $notificationService->sendPasswordResetInstructions($customer, $token);

            return response()->json(['message' => 'Password reset link sent to your email']);
        }

        return response()->json(['message' => 'If an account with that email exists, a reset link has been sent.']);
    }

    /**
     * Reset Password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $customer = Customer::where('reset_token', $request->token)
            ->where('reset_token_expires_at', '>', now())
            ->first();

        if (!$customer) {
            return response()->json(['message' => 'Invalid or expired reset token'], 422);
        }

        $customer->password = Hash::make($request->password);
        $customer->password_is_temporary = false; // Mark as permanent password
        $customer->reset_token = null;
        $customer->reset_token_expires_at = null;
        $customer->save();

        return response()->json(['message' => 'Password reset successfully']);
    }

    /**
     * Set permanent password (for customers with temporary passwords)
     */
    public function setPassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $customer = $request->user();

        if (!Hash::check($request->current_password, $customer->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 422);
        }

        $customer->password = Hash::make($request->new_password);
        $customer->password_is_temporary = false; // Mark as permanent password
        $customer->save();

        return response()->json(['message' => 'Password set successfully']);
    }
}
