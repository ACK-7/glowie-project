<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentSecurityService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Secure Document Controller
 * 
 * Handles secure document downloads with token-based authentication
 * and audit logging
 */
class SecureDocumentController extends Controller
{
    public function __construct(
        private DocumentSecurityService $documentSecurityService
    ) {}

    /**
     * Secure document download with token validation
     */
    public function secureDownload(Request $request, Document $document, string $token)
    {
        try {
            // Validate token
            $tokenData = $this->documentSecurityService->validateSecureToken($token);
            
            if (!$tokenData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired download token',
                ], 403);
            }
            
            // Verify token matches document
            if ($tokenData['document_id'] !== $document->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token does not match document',
                ], 403);
            }
            
            // Get user from token data
            $userClass = $tokenData['user_type'];
            $user = $userClass::find($tokenData['user_id']);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }
            
            // Retrieve and decrypt document
            $decryptedContent = $this->documentSecurityService->secureRetrieve($document, $user);
            
            // Return file response
            return response($decryptedContent)
                ->header('Content-Type', $document->mime_type)
                ->header('Content-Disposition', 'attachment; filename="' . $document->file_name . '"')
                ->header('Content-Length', strlen($decryptedContent))
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
            
        } catch (\Exception $e) {
            Log::error('Secure document download failed', [
                'document_id' => $document->id,
                'token' => substr($token, 0, 20) . '...',
                'error' => $e->getMessage(),
                'ip_address' => $request->ip(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to download document',
            ], 500);
        }
    }

    /**
     * Generate secure download URL for a document
     */
    public function generateDownloadUrl(Request $request, Document $document)
    {
        try {
            $user = $request->user();
            $expirationMinutes = $request->get('expiration_minutes', 60);
            
            $secureUrl = $this->documentSecurityService->generateSecureDownloadUrl(
                $document, 
                $user, 
                $expirationMinutes
            );
            
            return response()->json([
                'success' => true,
                'data' => [
                    'download_url' => $secureUrl,
                    'expires_at' => now()->addMinutes($expirationMinutes)->toISOString(),
                    'expiration_minutes' => $expirationMinutes,
                ],
                'message' => 'Secure download URL generated successfully',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to generate secure download URL', [
                'document_id' => $document->id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate download URL',
            ], 500);
        }
    }

    /**
     * Get document access audit trail
     */
    public function getAuditTrail(Request $request, Document $document)
    {
        try {
            $user = $request->user();
            
            // Check if user has permission to view audit trail
            if (!$this->canViewAuditTrail($document, $user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view audit trail',
                ], 403);
            }
            
            $auditTrail = $this->documentSecurityService->getAccessAuditTrail($document);
            
            return response()->json([
                'success' => true,
                'data' => $auditTrail,
                'message' => 'Audit trail retrieved successfully',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve document audit trail', [
                'document_id' => $document->id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve audit trail',
            ], 500);
        }
    }

    /**
     * Check if user can view audit trail
     */
    private function canViewAuditTrail(Document $document, $user): bool
    {
        // Only admin users can view audit trails
        if ($user instanceof \App\Models\User) {
            return in_array($user->role, ['admin', 'super_admin', 'manager']);
        }
        
        return false;
    }
}