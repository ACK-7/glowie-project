<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;
use App\Models\Customer;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Default Laravel user channel
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Customer private channels
Broadcast::channel('customer.{customerId}', function ($user, $customerId) {
    // Allow customers to listen to their own channel
    if ($user instanceof Customer) {
        return (int) $user->id === (int) $customerId;
    }
    
    // Allow admin users to listen to any customer channel
    if ($user instanceof User && in_array($user->role, ['admin', 'super_admin', 'manager'])) {
        return true;
    }
    
    return false;
});

// Admin channels - only for authenticated admin users
Broadcast::channel('admin.dashboard', function ($user) {
    return $user instanceof User && in_array($user->role, ['admin', 'super_admin', 'manager', 'operator']);
});

Broadcast::channel('admin.quotes', function ($user) {
    return $user instanceof User && in_array($user->role, ['admin', 'super_admin', 'manager', 'operator']);
});

Broadcast::channel('admin.bookings', function ($user) {
    return $user instanceof User && in_array($user->role, ['admin', 'super_admin', 'manager', 'operator']);
});

Broadcast::channel('admin.shipments', function ($user) {
    return $user instanceof User && in_array($user->role, ['admin', 'super_admin', 'manager', 'operator']);
});

Broadcast::channel('admin.payments', function ($user) {
    return $user instanceof User && in_array($user->role, ['admin', 'super_admin', 'manager']);
});

Broadcast::channel('admin.documents', function ($user) {
    return $user instanceof User && in_array($user->role, ['admin', 'super_admin', 'manager', 'operator']);
});

Broadcast::channel('admin.analytics', function ($user) {
    return $user instanceof User && in_array($user->role, ['admin', 'super_admin', 'manager']);
});

// Public tracking channels - no authentication required
// These are handled as public channels in the events
