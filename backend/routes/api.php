 <?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\TestApiController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SystemController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Routes
Route::prefix('auth')->group(function () {
    // Customer Authentication
    Route::post('/customer/register', [AuthController::class, 'customerRegister']);
    Route::post('/customer/login', [AuthController::class, 'customerLogin']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/set-password', [AuthController::class, 'setPassword'])->middleware('auth:sanctum');
    
    // Admin Authentication
    Route::post('/admin/login', [AuthController::class, 'adminLogin']);
});

// Car Inventory Public API
Route::prefix('cars')->group(function () {
    Route::get('/', [\App\Http\Controllers\CarInventoryController::class, 'index']);
    Route::get('/featured', [\App\Http\Controllers\CarInventoryController::class, 'featured']);
    Route::get('/brands', [\App\Http\Controllers\CarInventoryController::class, 'brands']);
    Route::get('/categories', [\App\Http\Controllers\CarInventoryController::class, 'categories']);
    Route::get('/stats', [\App\Http\Controllers\CarInventoryController::class, 'stats']);
    Route::get('/search', [\App\Http\Controllers\CarInventoryController::class, 'search']);
    Route::get('/{slug}', [\App\Http\Controllers\CarInventoryController::class, 'show']);
    Route::get('/{slug}/similar', [\App\Http\Controllers\CarInventoryController::class, 'similar']);
    Route::post('/{slug}/inquiry', [\App\Http\Controllers\CarInventoryController::class, 'inquiry']);
});

// Publc Quotes & Bookings
Route::post('/quotes', [QuoteController::class, 'create']);
Route::post('/quotes/lookup', [QuoteController::class, 'lookup']);
Route::post('/bookings/confirm', [BookingController::class, 'create']);

// Public Tracking (no authentication required)
Route::prefix('tracking')->group(function () {
    Route::get('/{trackingNumber}', [TrackingController::class, 'publicTrack']);
    Route::get('/{trackingNumber}/map', [TrackingController::class, 'getPublicTrackingMap']);
    Route::get('/{trackingNumber}/timeline', [TrackingController::class, 'getPublicTrackingTimeline']);
});

// Customer Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/customer/logout', [AuthController::class, 'customerLogout']);
    Route::get('/customer/profile', [AuthController::class, 'getCustomerProfile']);
    Route::put('/customer/profile', [AuthController::class, 'updateCustomerProfile']);
    Route::post('/customer/change-password', [AuthController::class, 'changePassword']);
    
    // Debug endpoint
    Route::get('/debug/user', function (Request $request) {
        $user = $request->user();
        return response()->json([
            'user' => $user,
            'user_type' => $user ? get_class($user) : null,
            'is_customer' => $user instanceof \App\Models\Customer,
            'is_user' => $user instanceof \App\Models\User,
        ]);
    });
    
    // Quotes
    // Route::post('/quotes', [QuoteController::class, 'create']); // Keeping commented to prefer public one
    Route::get('/quotes', [QuoteController::class, 'index']);
    Route::get('/quotes/{id}', [QuoteController::class, 'show']);
    
    // Bookings
    Route::post('/bookings', [BookingController::class, 'create']);
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::put('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
    
    // Documents
    Route::post('/documents', [DocumentController::class, 'upload']);
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::get('/documents/{id}', [DocumentController::class, 'show']);
    Route::delete('/documents/{id}', [DocumentController::class, 'delete']);
    
    // Tracking
    Route::get('/tracking/{referenceNumber}', [TrackingController::class, 'track']);
    
    // Public Map Tracking (no authentication required)
    Route::get('/tracking/{trackingNumber}/map', [TrackingController::class, 'getPublicTrackingMap']);
    
    // Payments
    Route::post('/payments', [PaymentController::class, 'process']);
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::get('/payments/{id}', [PaymentController::class, 'show']);
    
    // Chatbot
    Route::post('/chat', [ChatbotController::class, 'sendMessage']);
    Route::get('/chat/history', [ChatbotController::class, 'getHistory']);
});

// Admin Protected Routes
Route::middleware(['auth:sanctum', 'ability:admin'])->prefix('admin')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'adminLogout']);
    
    // Dashboard - New dedicated dashboard endpoints
    Route::prefix('dashboard')->group(function () {
        Route::get('/test', [DashboardController::class, 'test']);
        Route::get('/statistics', [DashboardController::class, 'getStatistics']);
        Route::get('/kpis', [DashboardController::class, 'getKPIMetrics']);
        Route::get('/recent-activity', [DashboardController::class, 'getRecentActivity']);
        Route::get('/revenue-analytics', [DashboardController::class, 'getRevenueAnalytics']);
        Route::get('/operational-metrics', [DashboardController::class, 'getOperationalMetrics']);
        Route::get('/chart-data', [DashboardController::class, 'getChartData']);
    });
    
    // Analytics - Advanced analytics and reporting endpoints
    Route::prefix('analytics')->group(function () {
        Route::get('/dashboard', [AnalyticsController::class, 'getDashboardAnalytics']);
        Route::get('/revenue', [AnalyticsController::class, 'getRevenueAnalytics']);
        Route::get('/bookings', [AnalyticsController::class, 'getBookingAnalytics']);
        Route::get('/customers', [AnalyticsController::class, 'getCustomerAnalytics']);
        Route::get('/operational', [AnalyticsController::class, 'getOperationalAnalytics']);
        Route::get('/trends', [AnalyticsController::class, 'getTrendAnalysis']);
        Route::get('/comparative', [AnalyticsController::class, 'getComparativeAnalysis']);
        Route::get('/predictive', [AnalyticsController::class, 'getPredictiveAnalytics']);
        Route::post('/export', [AnalyticsController::class, 'exportReport']);
    });
    
    // Enhanced CRUD Controllers
    
    // Bookings CRUD Management
    Route::prefix('crud/bookings')->group(function () {
        Route::get('/', [BookingController::class, 'index']);
        Route::post('/', [BookingController::class, 'store']);
        Route::get('/{id}', [BookingController::class, 'show']);
        Route::put('/{id}', [BookingController::class, 'update']);
        Route::delete('/{id}', [BookingController::class, 'destroy']);
        Route::patch('/{id}/status', [BookingController::class, 'updateStatus']);
        Route::get('/statistics', [BookingController::class, 'statistics']);
        Route::get('/requires-attention', [BookingController::class, 'requiresAttention']);
        Route::get('/search', [BookingController::class, 'search']);
        Route::post('/{id}/payment', [BookingController::class, 'processPayment']);
        Route::get('/analytics', [BookingController::class, 'analytics']);
    });

    // Routes and vehicles for dropdowns (create booking, etc.)
    Route::get('crud/routes', [RouteController::class, 'index']);
    Route::get('crud/vehicles', [VehicleController::class, 'index']);
    
    // Quotes CRUD Management
    Route::prefix('crud/quotes')->group(function () {
        Route::get('/', [QuoteController::class, 'index']);
        Route::post('/', [QuoteController::class, 'store']);
        Route::get('/requires-approval', [QuoteController::class, 'requiresApproval']);
        Route::get('/expiring-soon', [QuoteController::class, 'expiringSoon']);
        Route::get('/statistics', [QuoteController::class, 'statistics']);
        Route::get('/search', [QuoteController::class, 'search']);
        Route::get('/analytics', [QuoteController::class, 'analytics']);
        Route::post('/process-expired', [QuoteController::class, 'processExpiredQuotes']);
        Route::get('/{id}', [QuoteController::class, 'show']);
        Route::put('/{id}', [QuoteController::class, 'update']);
        Route::patch('/{id}/approve', [QuoteController::class, 'approve']);
        Route::patch('/{id}/reject', [QuoteController::class, 'reject']);
        Route::post('/{id}/convert', [QuoteController::class, 'convertToBooking']);
        Route::patch('/{id}/extend', [QuoteController::class, 'extendValidity']);
    });
    
    // Customers CRUD Management
    Route::prefix('crud/customers')->group(function () {
        Route::get('/', [\App\Http\Controllers\CustomerController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\CustomerController::class, 'store']);
        Route::get('/search', [\App\Http\Controllers\CustomerController::class, 'search']);
        Route::get('/statistics', [\App\Http\Controllers\CustomerController::class, 'statistics']);
        Route::get('/requires-attention', [\App\Http\Controllers\CustomerController::class, 'requiresAttention']);
        Route::get('/tier/{tier}', [\App\Http\Controllers\CustomerController::class, 'byTier']);
        Route::post('/export', [\App\Http\Controllers\CustomerController::class, 'export']);
        Route::get('/{id}', [\App\Http\Controllers\CustomerController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\CustomerController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\CustomerController::class, 'destroy']);
        Route::patch('/{id}/status', [\App\Http\Controllers\CustomerController::class, 'updateStatus']);
        Route::patch('/{id}/verify', [\App\Http\Controllers\CustomerController::class, 'verify']);
        Route::post('/{id}/reset-password', [\App\Http\Controllers\CustomerController::class, 'resetPassword']);
        Route::get('/{id}/bookings', [\App\Http\Controllers\CustomerController::class, 'bookingHistory']);
        Route::get('/{id}/communications', [\App\Http\Controllers\CustomerController::class, 'communicationHistory']);
    });
    
    // Payments CRUD Management (New comprehensive payment management)
    Route::prefix('crud/payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index']);
        Route::post('/', [PaymentController::class, 'store']);
        Route::get('/{id}', [PaymentController::class, 'show']);
        Route::put('/{id}', [PaymentController::class, 'update']);
        Route::delete('/{id}', [PaymentController::class, 'destroy']);
        Route::patch('/{id}/complete', [PaymentController::class, 'complete']);
        Route::post('/{id}/refund', [PaymentController::class, 'refund']);
        Route::get('/requires-attention', [PaymentController::class, 'requiresAttention']);
        Route::get('/overdue', [PaymentController::class, 'overdue']);
        Route::post('/process-overdue', [PaymentController::class, 'processOverdue']);
        Route::get('/statistics', [PaymentController::class, 'statistics']);
        Route::get('/revenue-analytics', [PaymentController::class, 'revenueAnalytics']);
        Route::post('/calculate-fees', [PaymentController::class, 'calculateFees']);
        Route::get('/{id}/instructions', [PaymentController::class, 'instructions']);
        Route::get('/search', [PaymentController::class, 'search']);
        Route::get('/recent', [PaymentController::class, 'recent']);
        Route::get('/booking/{bookingId}', [PaymentController::class, 'byBooking']);
        Route::get('/customer/{customerId}', [PaymentController::class, 'byCustomer']);
        Route::post('/export', [PaymentController::class, 'export']);
    });
    
    // Legacy Dashboard (keeping for backward compatibility)
    Route::get('/dashboard/stats', [AdminController::class, 'getDashboardStats']);
    Route::get('/dashboard/recent-bookings', [AdminController::class, 'getRecentBookings']);
    
    // Bookings Management (Legacy)
    Route::get('/bookings', [AdminController::class, 'getBookings']);
    Route::get('/bookings/{id}', [AdminController::class, 'getBookingDetails']);
    Route::put('/bookings/{id}/status', [AdminController::class, 'updateBookingStatus']);
    
    // Customers Management (Legacy)
    Route::get('/customers', [AdminController::class, 'getCustomers']);
    Route::get('/customers/{id}', [AdminController::class, 'getCustomerDetails']);
    Route::put('/customers/{id}', [AdminController::class, 'updateCustomer']);
    
    // Shipments Tracking
    Route::get('/shipments', [AdminController::class, 'getShipments']);
    Route::get('/shipments/{id}', [AdminController::class, 'getShipmentDetails']);
    Route::put('/shipments/{id}', [AdminController::class, 'updateShipment']);
    
    // Shipments CRUD Management (New comprehensive shipment management)
    Route::prefix('crud/shipments')->group(function () {
        Route::get('/', [TrackingController::class, 'index']);
        Route::post('/', [TrackingController::class, 'store']);
        Route::get('/{id}', [TrackingController::class, 'show']);
        Route::put('/{id}', [TrackingController::class, 'update']);
        Route::delete('/{id}', [TrackingController::class, 'destroy']);
        Route::patch('/{id}/status', [TrackingController::class, 'updateStatus']);
        Route::patch('/{id}/estimated-arrival', [TrackingController::class, 'updateEstimatedArrival']);
        Route::get('/track/{trackingNumber}', [TrackingController::class, 'track']);
        Route::get('/requires-attention', [TrackingController::class, 'requiresAttention']);
        Route::get('/statistics', [TrackingController::class, 'statistics']);
        Route::post('/process-delayed', [TrackingController::class, 'processDelayed']);
        Route::get('/delivery-performance', [TrackingController::class, 'deliveryPerformance']);
        Route::get('/carrier-performance', [TrackingController::class, 'carrierPerformance']);
        Route::get('/search', [TrackingController::class, 'search']);
        Route::get('/recent', [TrackingController::class, 'recent']);
        Route::get('/trends', [TrackingController::class, 'trends']);
        
        // Map Tracking Endpoints
        Route::get('/{id}/map', [TrackingController::class, 'getTrackingMap']);
        Route::get('/live-tracking', [TrackingController::class, 'getLiveTrackingData']);
        Route::patch('/{id}/location', [TrackingController::class, 'updateLocation']);
        Route::post('/route', [TrackingController::class, 'getRoute']);
        Route::post('/geocode', [TrackingController::class, 'geocodeLocation']);
    });
    
    // Finance
    Route::get('/finance/stats', [AdminController::class, 'getFinanceStats']);
    Route::get('/invoices', [AdminController::class, 'getInvoices']);
    
    // Documents CRUD Management (New comprehensive document management)
    Route::prefix('crud/documents')->group(function () {
        Route::get('/', [DocumentController::class, 'index']);
        Route::post('/upload', [DocumentController::class, 'upload']);
        Route::get('/{id}', [DocumentController::class, 'show']);
        Route::put('/{id}', [DocumentController::class, 'update']);
        Route::delete('/{id}', [DocumentController::class, 'destroy']);
        Route::patch('/{id}/approve', [DocumentController::class, 'approve']);
        Route::patch('/{id}/reject', [DocumentController::class, 'reject']);
        Route::patch('/bulk-approve', [DocumentController::class, 'bulkApprove']);
        Route::patch('/bulk-reject', [DocumentController::class, 'bulkReject']);
        Route::patch('/{id}/expiry', [DocumentController::class, 'updateExpiry']);
        Route::get('/requires-verification', [DocumentController::class, 'requiresVerification']);
        Route::get('/expiring-soon', [DocumentController::class, 'expiringSoon']);
        Route::get('/expired', [DocumentController::class, 'expired']);
        Route::post('/process-expired', [DocumentController::class, 'processExpired']);
        Route::get('/booking/{bookingId}/missing', [DocumentController::class, 'detectMissing']);
        Route::post('/booking/{bookingId}/request-missing', [DocumentController::class, 'requestMissing']);
        Route::get('/booking/{bookingId}', [DocumentController::class, 'byBooking']);
        Route::get('/customer/{customerId}', [DocumentController::class, 'byCustomer']);
        Route::get('/statistics', [DocumentController::class, 'statistics']);
        Route::get('/search', [DocumentController::class, 'search']);
        Route::get('/recent', [DocumentController::class, 'recent']);
        Route::get('/{id}/download', [DocumentController::class, 'download']);
    });
    
    // Legacy Documents Management
    Route::get('/documents', [AdminController::class, 'getDocuments']);
    Route::put('/documents/{id}/verify', [AdminController::class, 'verifyDocument']);
    
    // Reports
    Route::get('/reports/revenue', [AdminController::class, 'getRevenueReport']);
    Route::get('/reports/operational', [AdminController::class, 'getOperationalReport']);
    
    // Messages
    Route::get('/messages', [AdminController::class, 'getMessages']);
    Route::post('/messages/{id}/reply', [AdminController::class, 'replyToMessage']);
    
    // Settings
    Route::get('/settings', [AdminController::class, 'getSettings']);
    Route::put('/settings', [AdminController::class, 'updateSettings']);
    
    // User Management
    Route::get('/profile', [AdminController::class, 'getAdminProfile']);
    Route::get('/users', [AdminController::class, 'getUsers']);
    Route::post('/users', [AdminController::class, 'createUser']);
    Route::get('/users/{id}', [AdminController::class, 'getUserDetails']);
    Route::put('/users/{id}', [AdminController::class, 'updateUser']);
    Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
    
    // Enhanced User Management CRUD
    Route::prefix('crud/users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
        Route::patch('/{id}/activate', [UserController::class, 'activate']);
        Route::patch('/{id}/deactivate', [UserController::class, 'deactivate']);
        Route::patch('/{id}/permissions', [UserController::class, 'updatePermissions']);
        Route::get('/{id}/activity', [UserController::class, 'activityLogs']);
        Route::get('/roles-permissions', [UserController::class, 'rolesAndPermissions']);
    });
    
    // Car Inventory Management CRUD
    Route::prefix('inventory')->group(function () {
        // Cars
        Route::get('/cars', [\App\Http\Controllers\AdminCarInventoryController::class, 'getCars']);
        Route::post('/cars', [\App\Http\Controllers\AdminCarInventoryController::class, 'storeCar']);
        Route::put('/cars/{id}', [\App\Http\Controllers\AdminCarInventoryController::class, 'updateCar']);
        Route::delete('/cars/{id}', [\App\Http\Controllers\AdminCarInventoryController::class, 'deleteCar']);
        
        // Brands
        Route::get('/brands', [\App\Http\Controllers\AdminCarInventoryController::class, 'getBrands']);
        Route::post('/brands', [\App\Http\Controllers\AdminCarInventoryController::class, 'storeBrand']);
        Route::put('/brands/{id}', [\App\Http\Controllers\AdminCarInventoryController::class, 'updateBrand']);
        Route::delete('/brands/{id}', [\App\Http\Controllers\AdminCarInventoryController::class, 'deleteBrand']);
        
        // Categories
        Route::get('/categories', [\App\Http\Controllers\AdminCarInventoryController::class, 'getCategories']);
        Route::post('/categories', [\App\Http\Controllers\AdminCarInventoryController::class, 'storeCategory']);
        Route::put('/categories/{id}', [\App\Http\Controllers\AdminCarInventoryController::class, 'updateCategory']);
        Route::delete('/categories/{id}', [\App\Http\Controllers\AdminCarInventoryController::class, 'deleteCategory']);
        
        // Images
        Route::post('/cars/{id}/images', [\App\Http\Controllers\AdminCarInventoryController::class, 'uploadCarImage']);
        Route::delete('/images/{id}', [\App\Http\Controllers\AdminCarInventoryController::class, 'deleteCarImage']);
    });
    
    // System Configuration Management
    Route::prefix('system')->group(function () {
        Route::get('/settings', [SystemController::class, 'settings']);
        Route::get('/settings/{key}', [SystemController::class, 'getSetting']);
        Route::put('/settings', [SystemController::class, 'updateSettings']);
        Route::post('/settings/reset', [SystemController::class, 'resetToDefaults']);
        Route::post('/settings/initialize', [SystemController::class, 'initializeDefaults']);
        Route::get('/health', [SystemController::class, 'health']);
        Route::get('/metrics', [SystemController::class, 'metrics']);
        Route::post('/cache/clear', [SystemController::class, 'clearCache']);
        Route::get('/configuration/history', [SystemController::class, 'configurationHistory']);
    });
});

// Test routes for API infrastructure
Route::prefix('test')->group(function () {
    Route::get('/success', [TestApiController::class, 'testSuccess']);
    Route::get('/error', [TestApiController::class, 'testError']);
    Route::post('/validation', [TestApiController::class, 'testValidation']);
    Route::get('/collection', [TestApiController::class, 'testCollection']);
    Route::get('/pagination', [TestApiController::class, 'testPagination']);
    
    // Protected routes
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/auth', [TestApiController::class, 'testAuth']);
        Route::get('/logging', [TestApiController::class, 'testLogging']);
        
        // Admin only routes
        Route::middleware(['api.admin'])->group(function () {
            Route::get('/admin', [TestApiController::class, 'testAdmin']);
        });
    });
});

// Development routes (only available in local environment)
Route::prefix('dev')->group(function () {
    Route::get('/customers', [\App\Http\Controllers\DevController::class, 'getCustomerCredentials']);
    Route::get('/notifications', [\App\Http\Controllers\DevController::class, 'getRecentNotifications']);
    Route::post('/reset-password', [\App\Http\Controllers\DevController::class, 'resetCustomerPassword']);
});

// Health check endpoint
Route::get('/health', [HealthController::class, 'check']);

// Secure document routes
Route::get('/documents/secure-download/{document}/{token}', [\App\Http\Controllers\SecureDocumentController::class, 'secureDownload'])
    ->name('documents.secure-download');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/documents/{document}/generate-download-url', [\App\Http\Controllers\SecureDocumentController::class, 'generateDownloadUrl']);
    Route::get('/documents/{document}/audit-trail', [\App\Http\Controllers\SecureDocumentController::class, 'getAuditTrail']);
});

// Broadcasting endpoints
Route::post('/broadcasting/auth', [\App\Http\Controllers\BroadcastController::class, 'authenticate'])
    ->middleware('auth:sanctum');

Route::get('/broadcasting/dashboard-stats', [\App\Http\Controllers\BroadcastController::class, 'getDashboardStats'])
    ->middleware(['auth:sanctum', 'ability:admin']);

Route::post('/broadcasting/refresh-dashboard-stats', [\App\Http\Controllers\BroadcastController::class, 'refreshDashboardStats'])
    ->middleware(['auth:sanctum', 'ability:admin']);

Route::post('/broadcasting/test', [\App\Http\Controllers\BroadcastController::class, 'testBroadcast'])
    ->middleware(['auth:sanctum', 'ability:admin']);

// Test route
Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});

// Test dashboard stats (no auth for testing)
Route::get('/test/dashboard-stats-simple', function () {
    try {
        $quotesCount = \App\Models\Quote::count();
        return response()->json(['success' => true, 'quotes' => $quotesCount]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()]);
    }
});
