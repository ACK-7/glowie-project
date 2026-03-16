<?php

namespace App\Http\Controllers;

use App\Services\LangGraphService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class ChatbotController extends BaseApiController
{
    public function __construct(
        private LangGraphService $langGraphService
    ) {}

    /**
     * Handle chatbot query
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function chat(Request $request): JsonResponse
    {
        try {
            $validatedData = $this->validateRequest($request, [
                'query' => 'required|string|max:1000',
                'context' => 'nullable|string',
                'customer_id' => 'nullable|integer',
                'shipment_id' => 'nullable|integer'
            ]);

            Log::info('Chatbot query received', [
                'query' => $validatedData['query'],
                'context' => $validatedData['context'] ?? 'general'
            ]);

            // Call AI service
            $response = $this->langGraphService->getSupportResponse(
                $validatedData['query'],
                $validatedData['customer_id'] ?? 0,
                $validatedData['shipment_id'] ?? null
            );

            return $this->successResponse([
                'response' => $response['response'] ?? $response['message'] ?? 'I apologize, but I couldn\'t process your request.',
                'confidence' => $response['confidence'] ?? 0.8,
                'suggestions' => $response['suggestions'] ?? []
            ]);

        } catch (Exception $e) {
            Log::error('Chatbot error', [
                'error' => $e->getMessage(),
                'query' => $request->input('query')
            ]);

            // Fallback response
            return $this->successResponse([
                'response' => $this->getFallbackResponse($request->input('query')),
                'confidence' => 0.5,
                'suggestions' => []
            ]);
        }
    }

    /**
     * Handle admin AI assistant query with business context
     */
    public function adminAssistant(Request $request): JsonResponse
    {
        try {
            $validatedData = $this->validateRequest($request, [
                'message' => 'required|string|max:2000',
                'context' => 'nullable|string',
            ]);

            $query = $validatedData['message'];
            $queryLower = strtolower($query);

            // Gather live business metrics for AI context
            $metrics = $this->getBusinessMetrics($queryLower);

            $contextPrefix = "[ADMIN ASSISTANT] Business Context:\n" . $metrics . "\n\nAdmin Question: ";

            try {
                $response = $this->langGraphService->getSupportResponse(
                    $contextPrefix . $query,
                    0,
                    null
                );

                return $this->successResponse([
                    'response' => $response['response'] ?? $response['message'] ?? $this->getAdminFallbackResponse($query, $metrics),
                ]);
            } catch (\Exception $aiError) {
                Log::warning('AI service unavailable for admin assistant', ['error' => $aiError->getMessage()]);

                return $this->successResponse([
                    'response' => $this->getAdminFallbackResponse($query, $metrics),
                ]);
            }
        } catch (Exception $e) {
            Log::error('Admin AI assistant error', ['error' => $e->getMessage()]);

            return $this->successResponse([
                'response' => 'I encountered an error processing your request. Please try again.',
            ]);
        }
    }

    /**
     * Gather live business metrics for AI context
     */
    private function getBusinessMetrics(string $queryLower): string
    {
        $metrics = [];

        try {
            $bookingModel = new \App\Models\Booking();
            $paymentModel = new \App\Models\Payment();
            $shipmentModel = new \App\Models\Shipment();
            $customerModel = new \App\Models\Customer();

            $totalBookings = $bookingModel->count();
            $pendingBookings = $bookingModel->where('status', 'pending')->count();
            $activeShipments = $shipmentModel->whereNotIn('status', ['delivered', 'cancelled'])->count();
            $delayedShipments = $shipmentModel->where('status', 'delayed')->count();
            $totalRevenue = $paymentModel->where('status', 'completed')->sum('amount');
            $pendingPayments = $paymentModel->where('status', 'pending')->sum('amount');
            $totalCustomers = $customerModel->count();
            $recentBookings = $bookingModel->where('created_at', '>=', now()->subDays(30))->count();

            $metrics[] = "Total Bookings: {$totalBookings} ({$recentBookings} in last 30 days)";
            $metrics[] = "Pending Bookings: {$pendingBookings}";
            $metrics[] = "Active Shipments: {$activeShipments}";
            $metrics[] = "Delayed Shipments: {$delayedShipments}";
            $metrics[] = "Total Revenue (Completed): $" . number_format($totalRevenue, 2);
            $metrics[] = "Pending Payments: $" . number_format($pendingPayments, 2);
            $metrics[] = "Total Customers: {$totalCustomers}";
        } catch (\Exception $e) {
            $metrics[] = "Unable to fetch some metrics: " . $e->getMessage();
        }

        return implode("\n", $metrics);
    }

    /**
     * Admin-specific fallback with live data
     */
    private function getAdminFallbackResponse(string $query, string $metrics): string
    {
        $queryLower = strtolower($query);

        if (str_contains($queryLower, 'revenue') || str_contains($queryLower, 'financial') || str_contains($queryLower, 'money') || str_contains($queryLower, 'payment')) {
            return "📊 **Financial Overview**\n\nHere are your current financial metrics:\n\n{$metrics}\n\nFor detailed breakdowns, visit the Finance Dashboard. You can export payment reports as CSV or PDF from there.";
        }

        if (str_contains($queryLower, 'delay') || str_contains($queryLower, 'late') || str_contains($queryLower, 'overdue')) {
            return "⚠️ **Shipment Delay Report**\n\n{$metrics}\n\n**Recommendations:**\n• Review delayed shipments in the Shipment Management page\n• Contact carriers for updated ETAs\n• Notify affected customers proactively\n• Consider alternative routing for pending shipments";
        }

        if (str_contains($queryLower, 'improve') || str_contains($queryLower, 'suggest') || str_contains($queryLower, 'optimize') || str_contains($queryLower, 'insight')) {
            return "💡 **Operational Insights**\n\n{$metrics}\n\n**Suggestions:**\n• Focus on converting pending bookings to confirmed\n• Follow up on pending payments to improve cash flow\n• Monitor delayed shipments and update customers\n• Review carrier performance for optimization\n• Consider promotional offers for repeat customers";
        }

        if (str_contains($queryLower, 'customer') || str_contains($queryLower, 'client')) {
            return "👥 **Customer Summary**\n\n{$metrics}\n\n**Actions:**\n• Check customers requiring attention in the Customers page\n• Follow up with customers who have pending documents\n• Send reminders for outstanding payments";
        }

        if (str_contains($queryLower, 'report') || str_contains($queryLower, 'summary')) {
            return "📋 **Business Summary**\n\n{$metrics}\n\nFor detailed reports, visit the Reports Hub where you can generate:\n• Revenue Reports\n• Operational Metrics\n• Customer Analytics\n• Shipment Performance";
        }

        return "📊 **Current Business Metrics**\n\n{$metrics}\n\nI can help you with:\n• **Revenue & payments** — financial summaries and analysis\n• **Shipment delays** — identify and resolve delays\n• **Operational insights** — suggestions for improvement\n• **Customer analytics** — customer trends and actions\n• **Reports** — generate business summaries\n\nWhat would you like to know more about?";
    }

    /**
     * Get fallback response when AI is unavailable
     *
     * @param string $query
     * @return string
     */
    private function getFallbackResponse(string $query): string
    {
        $queryLower = strtolower($query);

        // Shipping cost questions
        if (str_contains($queryLower, 'cost') || str_contains($queryLower, 'price') || str_contains($queryLower, 'how much')) {
            return "Shipping costs vary based on vehicle type, origin country, and shipping method. You can get an instant quote by visiting our Quote page. Generally, prices range from $2,500 to $4,500 for standard vehicles from Japan, UK, or UAE to Uganda.";
        }

        // Shipping time questions
        if (str_contains($queryLower, 'long') || str_contains($queryLower, 'time') || str_contains($queryLower, 'duration')) {
            return "Shipping typically takes 30-50 days depending on the origin country:\n• Japan: 40-45 days\n• UK: 30-35 days\n• UAE: 25-30 days\n\nThis includes ocean freight and customs clearance.";
        }

        // Document questions
        if (str_contains($queryLower, 'document') || str_contains($queryLower, 'paper')) {
            return "You'll need:\n• Vehicle registration/title\n• Bill of sale\n• Valid ID/Passport\n• Import permit (we can help arrange this)\n• Insurance documents\n\nWe'll guide you through the entire documentation process.";
        }

        // Tracking questions
        if (str_contains($queryLower, 'track') || str_contains($queryLower, 'status') || str_contains($queryLower, 'where')) {
            return "Yes! You can track your shipment in real-time using your tracking number on our Track Shipment page. You'll receive updates via email and SMS at every milestone.";
        }

        // Payment questions
        if (str_contains($queryLower, 'pay') || str_contains($queryLower, 'payment')) {
            return "We accept multiple payment methods:\n• Bank transfer\n• Mobile money\n• Credit/Debit cards\n• PayPal\n\nPayment is typically split: 50% deposit to start, 50% before delivery.";
        }

        // Insurance questions
        if (str_contains($queryLower, 'insurance') || str_contains($queryLower, 'insure')) {
            return "Yes, all shipments are fully insured during transit. Insurance covers damage, loss, or theft. The cost is included in your shipping quote.";
        }

        // General greeting
        if (str_contains($queryLower, 'hello') || str_contains($queryLower, 'hi') || str_contains($queryLower, 'hey')) {
            return "Hello! 👋 I'm here to help with any questions about shipping your vehicle to Uganda. What would you like to know?";
        }

        // Default response
        return "Thank you for your question! For detailed information, please:\n• Visit our FAQ page\n• Request a quote for specific pricing\n• Contact our support team at support@shipwithglowie.com\n• Call us at +256 700 000 000\n\nHow else can I assist you?";
    }
}
