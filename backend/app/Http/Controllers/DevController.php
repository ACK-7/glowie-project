<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Development Controller
 * 
 * ONLY FOR DEVELOPMENT - Contains utilities for development and testing
 */
class DevController extends Controller
{
    /**
     * Get all customer credentials for development
     * 
     * @return JsonResponse
     */
    public function getCustomerCredentials(): JsonResponse
    {
        if (config('app.env') !== 'local') {
            return response()->json(['error' => 'This endpoint is only available in development'], 403);
        }

        $customers = Customer::select('id', 'first_name', 'last_name', 'email', 'password_is_temporary', 'created_at', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->first_name . ' ' . $customer->last_name,
                    'email' => $customer->email,
                    'has_temporary_password' => $customer->password_is_temporary,
                    'created_at' => $customer->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $customer->updated_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'message' => 'Customer credentials (Development Only)',
            'customers' => $customers,
            'note' => 'Check Laravel logs for temporary passwords when quotes are approved'
        ]);
    }

    /**
     * Get recent notifications for development
     * 
     * @return JsonResponse
     */
    public function getRecentNotifications(): JsonResponse
    {
        if (config('app.env') !== 'local') {
            return response()->json(['error' => 'This endpoint is only available in development'], 403);
        }

        $notifications = Notification::with(['notifiable'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($notification) {
                $customer = null;
                if ($notification->notifiable_type === 'App\\Models\\Customer' && $notification->notifiable) {
                    $customer = [
                        'name' => $notification->notifiable->first_name . ' ' . $notification->notifiable->last_name,
                        'email' => $notification->notifiable->email
                    ];
                }

                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'customer' => $customer,
                    'data' => $notification->data,
                    'created_at' => $notification->created_at->format('Y-m-d H:i:s')
                ];
            });

        return response()->json([
            'message' => 'Recent notifications (Development Only)',
            'notifications' => $notifications
        ]);
    }

    /**
     * Reset customer password for development
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function resetCustomerPassword(Request $request): JsonResponse
    {
        if (config('app.env') !== 'local') {
            return response()->json(['error' => 'This endpoint is only available in development'], 403);
        }

        $request->validate([
            'email' => 'required|email|exists:customers,email',
            'password' => 'required|string|min:6'
        ]);

        $customer = Customer::where('email', $request->email)->first();
        $customer->password = bcrypt($request->password);
        $customer->password_is_temporary = false;
        $customer->save();

        return response()->json([
            'message' => 'Customer password reset successfully',
            'customer' => [
                'name' => $customer->first_name . ' ' . $customer->last_name,
                'email' => $customer->email,
                'new_password' => $request->password
            ]
        ]);
    }
}