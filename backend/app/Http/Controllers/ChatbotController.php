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
