<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Logging Middleware
 * 
 * Logs API requests and responses for monitoring and debugging
 */
class ApiLoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Log incoming request
        $this->logRequest($request);

        $response = $next($request);

        // Log outgoing response
        $this->logResponse($request, $response, $startTime);

        return $response;
    }

    /**
     * Log incoming API request
     *
     * @param Request $request
     * @return void
     */
    private function logRequest(Request $request): void
    {
        $logData = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
            'headers' => $this->sanitizeHeaders($request->headers->all()),
        ];

        // Log request body for non-GET requests (excluding sensitive data)
        if (!$request->isMethod('GET')) {
            $logData['body'] = $this->sanitizeRequestBody($request->all());
        }

        Log::info('API Request', $logData);
    }

    /**
     * Log outgoing API response
     *
     * @param Request $request
     * @param Response $response
     * @param float $startTime
     * @return void
     */
    private function logResponse(Request $request, Response $response, float $startTime): void
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2); // in milliseconds

        $logData = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'user_id' => auth()->id(),
        ];

        // Log response body for errors (4xx, 5xx status codes)
        if ($response->getStatusCode() >= 400) {
            $logData['response_body'] = $this->sanitizeResponseBody($response->getContent());
        }

        $logLevel = $this->getLogLevel($response->getStatusCode());
        Log::log($logLevel, 'API Response', $logData);

        // Log slow requests (> 1 second) as warnings
        if ($duration > 1000) {
            Log::warning('Slow API Request', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'duration_ms' => $duration,
                'user_id' => auth()->id(),
            ]);
        }
    }

    /**
     * Sanitize request headers (remove sensitive information)
     *
     * @param array $headers
     * @return array
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key'];
        
        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['[REDACTED]'];
            }
        }

        return $headers;
    }

    /**
     * Sanitize request body (remove sensitive information)
     *
     * @param array $body
     * @return array
     */
    private function sanitizeRequestBody(array $body): array
    {
        $sensitiveFields = [
            'password', 'password_confirmation', 'current_password',
            'token', 'api_key', 'secret', 'private_key',
            'credit_card', 'card_number', 'cvv', 'ssn'
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($body[$field])) {
                $body[$field] = '[REDACTED]';
            }
        }

        return $body;
    }

    /**
     * Sanitize response body (limit size and remove sensitive data)
     *
     * @param string $content
     * @return string
     */
    private function sanitizeResponseBody(string $content): string
    {
        // Limit response body size in logs (max 1KB)
        if (strlen($content) > 1024) {
            $content = substr($content, 0, 1024) . '... [TRUNCATED]';
        }

        return $content;
    }

    /**
     * Get appropriate log level based on HTTP status code
     *
     * @param int $statusCode
     * @return string
     */
    private function getLogLevel(int $statusCode): string
    {
        if ($statusCode >= 500) {
            return 'error';
        } elseif ($statusCode >= 400) {
            return 'warning';
        } else {
            return 'info';
        }
    }
}