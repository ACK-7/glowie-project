<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * LangGraph AI Service Integration
 * 
 * Communicates with the Python AI service for intelligent automation
 */
class LangGraphService
{
    private string $baseUrl;
    private int $timeout;
    
    public function __construct()
    {
        $this->baseUrl = config('services.langgraph.url', 'http://localhost:8001');
        $this->timeout = config('services.langgraph.timeout', 60);
    }
    
    /**
     * Generate shipping quote using AI
     * 
     * @param array $data Quote request data
     * @return array AI-generated quote
     * @throws Exception
     */
    public function generateQuote(array $data): array
    {
        try {
            Log::info('Requesting AI quote generation', ['data' => $data]);
            
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/agents/quote", [
                    'vehicle_type' => $data['vehicleType'] ?? $data['vehicle_type'],
                    'year' => (int) $data['year'],
                    'make' => $data['make'],
                    'model' => $data['model'],
                    'engine_size' => $data['engineSize'] ?? $data['engine_size'] ?? null,
                    'origin_country' => $data['originCountry'] ?? $data['origin_country'],
                    'origin_port' => $data['originPort'] ?? $data['origin_port'] ?? null,
                    'destination_country' => 'Uganda',
                    'destination_port' => 'Port Bell',
                    'shipping_method' => $data['shippingMethod'] ?? $data['shipping_method'],
                    'customer_email' => $data['email'] ?? null,
                    'customer_name' => $data['fullName'] ?? $data['full_name'] ?? null,
                ]);
            
            if ($response->successful()) {
                $result = $response->json();
                Log::info('AI quote generated successfully', [
                    'reference' => $result['quote_reference'] ?? null
                ]);
                return $result;
            }
            
            throw new Exception('AI service returned error: ' . $response->body());
            
        } catch (Exception $e) {
            Log::error('AI quote generation failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }
    
    /**
     * Optimize shipping route using AI
     * 
     * @param int $shipmentId Shipment ID
     * @param array $options Additional options
     * @return array Optimized route data
     * @throws Exception
     */
    public function optimizeRoute(int $shipmentId, array $options = []): array
    {
        try {
            Log::info('Requesting route optimization', ['shipment_id' => $shipmentId]);
            
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/agents/route", [
                    'shipment_id' => $shipmentId,
                    'priority' => $options['priority'] ?? 'standard',
                ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            throw new Exception('Route optimization failed: ' . $response->body());
            
        } catch (Exception $e) {
            Log::error('Route optimization failed', [
                'error' => $e->getMessage(),
                'shipment_id' => $shipmentId
            ]);
            throw $e;
        }
    }
    
    /**
     * Process document using AI OCR
     * 
     * @param string $filePath Path to document file
     * @param string $documentType Type of document
     * @return array Extracted document data
     * @throws Exception
     */
    public function processDocument(string $filePath, string $documentType): array
    {
        try {
            Log::info('Requesting document processing', [
                'file' => basename($filePath),
                'type' => $documentType
            ]);
            
            $response = Http::timeout($this->timeout)
                ->attach('file', file_get_contents($filePath), basename($filePath))
                ->post("{$this->baseUrl}/agents/document", [
                    'document_type' => $documentType
                ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            throw new Exception('Document processing failed: ' . $response->body());
            
        } catch (Exception $e) {
            Log::error('Document processing failed', [
                'error' => $e->getMessage(),
                'file' => basename($filePath)
            ]);
            throw $e;
        }
    }
    
    /**
     * Get AI support response for customer query
     * 
     * @param string $query Customer query
     * @param int $customerId Customer ID
     * @param int|null $shipmentId Optional shipment ID
     * @return array AI support response
     * @throws Exception
     */
    public function getSupportResponse(string $query, int $customerId, ?int $shipmentId = null): array
    {
        try {
            Log::info('Requesting AI support response', [
                'customer_id' => $customerId,
                'query_length' => strlen($query)
            ]);
            
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/agents/support", [
                    'query' => $query,
                    'customer_id' => $customerId,
                    'shipment_id' => $shipmentId,
                ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            throw new Exception('Support query failed: ' . $response->body());
            
        } catch (Exception $e) {
            Log::error('AI support query failed', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId
            ]);
            throw $e;
        }
    }
    
    /**
     * Predict potential shipment delays
     * 
     * @param int $shipmentId Shipment ID
     * @return array Delay prediction data
     * @throws Exception
     */
    public function predictDelays(int $shipmentId): array
    {
        try {
            Log::info('Requesting delay prediction', ['shipment_id' => $shipmentId]);
            
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/agents/delay-prediction", [
                    'shipment_id' => $shipmentId,
                ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            throw new Exception('Delay prediction failed: ' . $response->body());
            
        } catch (Exception $e) {
            Log::error('Delay prediction failed', [
                'error' => $e->getMessage(),
                'shipment_id' => $shipmentId
            ]);
            throw $e;
        }
    }
    
    /**
     * Check if AI service is healthy
     * 
     * @return bool
     */
    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/health");
            return $response->successful();
        } catch (Exception $e) {
            Log::warning('AI service health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Get AI service status
     * 
     * @return array Service status information
     */
    public function getStatus(): array
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/health");
            
            if ($response->successful()) {
                return [
                    'available' => true,
                    'data' => $response->json()
                ];
            }
            
            return [
                'available' => false,
                'error' => 'Service unavailable'
            ];
            
        } catch (Exception $e) {
            return [
                'available' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
