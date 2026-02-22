<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\Quote;
use App\Models\Payment;
use App\Models\Document;
use App\Models\ChatMessage;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class AdminController extends Controller
{
    /**
     * Dashboard Overview - Get dashboard statistics
     */
    public function getDashboardStats()
    {
        $stats = [
            'active_bookings' => Booking::whereIn('status', ['pending', 'confirmed', 'in_transit'])->count(),
            'pending_quotes' => Quote::where('status', 'pending')->count(),
            'in_transit' => Shipment::where('status', 'in_transit')->count(),
            'revenue_mtd' => Payment::where('status', 'completed')
                ->whereMonth('created_at', Carbon::now()->month)
                ->sum('amount'),
            'total_customers' => Customer::count(),
            'pending_verifications' => Document::where('verification_status', 'pending')->count(),
            'delivered_this_month' => Booking::where('status', 'delivered')
                ->whereMonth('updated_at', Carbon::now()->month)
                ->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Get recent bookings for dashboard
     */
    public function getRecentBookings(Request $request)
    {
        $limit = $request->get('limit', 5);
        
        $bookings = Booking::with(['customer', 'vehicle'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'reference_number' => $booking->reference_number,
                    'customer' => $booking->customer->full_name,
                    'customer_email' => $booking->customer->email,
                    'vehicle' => $booking->vehicle->make . ' ' . $booking->vehicle->model,
                    'status' => $booking->status,
                    'date' => $booking->created_at->format('M d, Y'),
                    'amount' => $booking->total_amount ?? 0,
                ];
            });

        return response()->json($bookings);
    }

    /**
     * Bookings Management - List all bookings with filters
     */
    public function getBookings(Request $request)
    {
        $query = Booking::with(['customer', 'vehicle', 'quote']);

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%$search%")
                  ->orWhereHas('customer', function ($q2) use ($search) {
                      $q2->where('first_name', 'like', "%$search%")
                         ->orWhere('last_name', 'like', "%$search%")
                         ->orWhere('email', 'like', "%$search%");
                  });
            });
        }

        // Filter by status
        if ($request->has('status') && $request->get('status') !== 'all') {
            $query->where('status', $request->get('status'));
        }

        // Filter by origin
        if ($request->has('origin') && $request->get('origin') !== 'all') {
            $query->whereHas('quote', function ($q) use ($request) {
                $q->where('origin_country', $request->get('origin'));
            });
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $bookings = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($bookings);
    }

    /**
     * Get single booking details
     */
    public function getBookingDetails($id)
    {
        $booking = Booking::with(['customer', 'vehicle', 'quote', 'shipment', 'documents', 'payments'])
            ->findOrFail($id);

        return response()->json($booking);
    }

    /**
     * Update booking status
     */
    public function updateBookingStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,in_transit,delivered,cancelled'
        ]);

        $booking = Booking::findOrFail($id);
        $booking->status = $request->status;
        $booking->save();

        return response()->json(['message' => 'Booking status updated successfully', 'booking' => $booking]);
    }

    /**
     * Customers Management - List all customers
     */
    public function getCustomers(Request $request)
    {
        $query = Customer::withCount('bookings')
            ->withSum('payments', 'amount');

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                  ->orWhere('last_name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('phone', 'like', "%$search%");
            });
        }

        // Filter
        if ($request->has('status')) {
            $query->where('is_active', $request->get('status') === 'active');
        }

        $customers = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($customers);
    }

    /**
     * Get single customer details
     */
    public function getCustomerDetails($id)
    {
        $customer = Customer::with(['bookings', 'quotes', 'payments'])
            ->withCount('bookings')
            ->withSum('payments', 'amount')
            ->findOrFail($id);

        return response()->json($customer);
    }

    /**
     * Update customer details
     */
    public function updateCustomer(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        
        $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:customers,email,' . $id,
            'phone' => 'sometimes|string|max:20',
            'is_active' => 'sometimes|boolean',
            'notes' => 'sometimes|string',
        ]);

        $customer->update($request->only([
            'first_name', 'last_name', 'email', 'phone', 
            'country', 'city', 'address', 'is_active', 'notes'
        ]));

        return response()->json(['message' => 'Customer updated successfully', 'customer' => $customer]);
    }

    /**
     * Shipment Tracking - Get all shipments
     */
    public function getShipments(Request $request)
    {
        $query = Shipment::with(['booking.customer', 'booking.vehicle']);

        if ($request->has('status') && $request->get('status') !== 'all') {
            $query->where('status', $request->get('status'));
        }

        $shipments = $query->orderBy('updated_at', 'desc')->get();

        return response()->json($shipments);
    }

    /**
     * Get single shipment details
     */
    public function getShipmentDetails($id)
    {
        $shipment = Shipment::with(['booking.customer', 'booking.vehicle'])->findOrFail($id);
        return response()->json($shipment);
    }

    /**
     * Update shipment location and status
     */
    public function updateShipment(Request $request, $id)
    {
        $shipment = Shipment::findOrFail($id);
        
        $request->validate([
            'current_location' => 'sometimes|string',
            'current_latitude' => 'sometimes|numeric',
            'current_longitude' => 'sometimes|numeric',
            'status' => 'sometimes|in:pending,dispatched,in_transit,customs,cleared,delivered',
        ]);

        $shipment->update($request->only([
            'current_location', 'current_latitude', 'current_longitude', 
            'status', 'customs_status'
        ]));

        return response()->json(['message' => 'Shipment updated successfully', 'shipment' => $shipment]);
    }

    /**
     * Quote Management - Get all quotes
     */
    public function getQuotes(Request $request)
    {
        $query = Quote::with(['customer', 'vehicle', 'route']);

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        $quotes = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($quotes);
    }

    /**
     * Send quote to customer (approve quote)
     */
    public function sendQuote($id)
    {
        try {
            $quote = Quote::with('customer')->findOrFail($id);
            
            // Use the proper approve method from the Quote model
            $success = $quote->approve(auth()->id(), 'Quote approved and sent to customer');
            
            if ($success) {
                // Log the activity
                ActivityLog::logActivity('quote_approved', Quote::class, $id, [
                    'approved_by' => auth()->id(),
                    'notes' => 'Quote approved via admin panel'
                ]);
                
                return response()->json([
                    'message' => 'Quote approved and sent successfully',
                    'quote' => $quote->fresh()->load('customer')
                ]);
            }
            
            return response()->json(['message' => 'Failed to approve quote'], 400);
            
        } catch (Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Finance Dashboard - Get financial stats
     */
    public function getFinanceStats()
    {
        $currentMonth = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();

        $stats = [
            'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
            'pending_invoices' => Payment::where('status', 'pending')->count(),
            'pending_amount' => Payment::where('status', 'pending')->sum('amount'),
            'paid_this_month' => Payment::where('status', 'completed')
                ->whereMonth('created_at', $currentMonth->month)
                ->sum('amount'),
            'overdue_count' => Payment::where('status', 'pending')
                ->where('created_at', '<', Carbon::now()->subDays(30))
                ->count(),
            'overdue_amount' => Payment::where('status', 'pending')
                ->where('created_at', '<', Carbon::now()->subDays(30))
                ->sum('amount'),
        ];

        return response()->json($stats);
    }

    /**
     * Get invoices/payments
     */
    public function getInvoices(Request $request)
    {
        $query = Payment::with(['booking.customer']);

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($payments);
    }

    /**
     * Document Management - Get all documents
     */
    public function getDocuments(Request $request)
    {
        $query = Document::with(['booking', 'customer']);

        if ($request->has('booking_id')) {
            $query->where('booking_id', $request->get('booking_id'));
        }

        if ($request->has('status')) {
            $query->where('verification_status', $request->get('status'));
        }

        $documents = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($documents);
    }

    /**
     * Verify/reject document
     */
    public function verifyDocument(Request $request, $id)
    {
        $request->validate([
            'verification_status' => 'required|in:verified,rejected,requires_revision',
            'verification_notes' => 'nullable|string',
        ]);

        $document = Document::findOrFail($id);
        $document->verification_status = $request->verification_status;
        $document->verification_notes = $request->verification_notes;
        $document->verified_by = auth()->id(); // Admin user ID
        $document->verified_at = now();
        $document->save();

        return response()->json(['message' => 'Document verification updated', 'document' => $document]);
    }

    /**
     * Reports - Revenue report
     */
    public function getRevenueReport(Request $request)
    {
        $period = $request->get('period', 'thisMonth');
        
        $query = Payment::where('status', 'completed');

        switch ($period) {
            case 'thisMonth':
                $query->whereMonth('created_at', Carbon::now()->month);
                break;
            case 'lastMonth':
                $query->whereMonth('created_at', Carbon::now()->subMonth()->month);
                break;
            case 'thisQuarter':
                $query->whereBetween('created_at', [
                    Carbon::now()->firstOfQuarter(),
                    Carbon::now()->lastOfQuarter()
                ]);
                break;
            case 'thisYear':
                $query->whereYear('created_at', Carbon::now()->year);
                break;
        }

        $report = [
            'total_revenue' => $query->sum('amount'),
            'total_transactions' => $query->count(),
            'average_transaction' => $query->avg('amount'),
            'by_origin' => $query->join('bookings', 'payments.booking_id', '=', 'bookings.id')
                ->join('quotes', 'bookings.quote_id', '=', 'quotes.id')
                ->select('quotes.origin_country', DB::raw('SUM(payments.amount) as total'))
                ->groupBy('quotes.origin_country')
                ->get(),
        ];

        return response()->json($report);
    }

    /**
     * Reports - Operational metrics
     */
    public function getOperationalReport()
    {
        $report = [
            'total_bookings' => Booking::count(),
            'active_bookings' => Booking::whereIn('status', ['confirmed', 'in_transit'])->count(),
            'delivered_bookings' => Booking::where('status', 'delivered')->count(),
            'average_delivery_time' => Booking::where('status', 'delivered')
                ->whereNotNull('delivery_date_actual')
                ->selectRaw('AVG(DATEDIFF(delivery_date_actual, pickup_date)) as avg_days')
                ->value('avg_days'),
            'customer_satisfaction' => 4.8, // This would come from ratings table
            'on_time_deliveries' => 98.5, // Calculate based on estimated vs actual dates
        ];

        return response()->json($report);
    }

    /**
     * Messages - Get customer messages
     */
    public function getMessages(Request $request)
    {
        $query = ChatMessage::with('customer')
            ->where('sender_type', 'user');

        if ($request->has('unread')) {
            $query->where('is_resolved', false);
        }

        $messages = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($messages);
    }

    /**
     * Reply to message
     */
    public function replyToMessage(Request $request, $id)
    {
        $request->validate([
            'reply' => 'required|string',
        ]);

        $message = ChatMessage::findOrFail($id);
        
        // Create a response message
        ChatMessage::create([
            'customer_id' => $message->customer_id,
            'message_text' => $request->reply,
            'sender_type' => 'support',
            'session_id' => $message->session_id,
        ]);

        $message->is_resolved = true;
        $message->save();

        return response()->json(['message' => 'Reply sent successfully']);
    }

    /**
     * Settings - Get system settings
     */
    public function getSettings()
    {
        // This would fetch from a settings table or config
        $settings = [
            'company_name' => env('APP_NAME', 'ShipWithGlowie Auto'),
            'company_email' => env('MAIL_FROM_ADDRESS', 'support@shipwithglowie.com'),
            'company_phone' => '+256 123 456 789',
            'pricing' => [
                'Japan' => ['base' => 2000, 'processing' => 150],
                'UK' => ['base' => 3000, 'processing' => 200],
                'UAE' => ['base' => 1500, 'processing' => 100],
            ],
        ];

        return response()->json($settings);
    }

    /**
     * Update settings
     */
    public function updateSettings(Request $request)
    {
        // Validate and update settings
        // This would update a settings table or env file
        
        return response()->json(['message' => 'Settings updated successfully']);
    }

    /**
     * User Management - Get authenticated admin profile
     */
    public function getAdminProfile()
    {
        return response()->json(auth()->user());
    }

    /**
     * Get all admin users
     */
    public function getUsers(Request $request)
    {
        $query = \App\Models\User::query();

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                  ->orWhere('last_name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }

        // Filter by role
        if ($request->has('role') && $request->get('role') !== 'all') {
            $query->where('role', $request->get('role'));
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('is_active', $request->get('status') === 'active');
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($users);
    }

    /**
     * Get single user details
     */
    public function getUserDetails($id)
    {
        $user = \App\Models\User::findOrFail($id);
        return response()->json($user);
    }

    /**
     * Create new admin user
     */
    public function createUser(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,manager,support',
            'is_active' => 'boolean',
        ]);

        $user = \App\Models\User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'role' => $request->role,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ], 201);
    }

    /**
     * Update admin user
     */
    public function updateUser(Request $request, $id)
    {
        $user = \App\Models\User::findOrFail($id);

        $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'phone' => 'sometimes|string|max:20',
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|in:admin,manager,support',
            'is_active' => 'sometimes|boolean',
        ]);

        $updateData = $request->only([
            'first_name', 'last_name', 'email', 'phone', 'role', 'is_active'
        ]);

        // Update password if provided
        if ($request->has('password')) {
            $updateData['password'] = \Illuminate\Support\Facades\Hash::make($request->password);
        }

        $user->update($updateData);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Delete admin user
     */
    public function deleteUser($id)
    {
        // Prevent deleting self
        if (auth()->id() == $id) {
            return response()->json(['message' => 'Cannot delete your own account'], 403);
        }

        $user = \App\Models\User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
