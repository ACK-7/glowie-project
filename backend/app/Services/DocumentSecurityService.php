<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

/**
 * Document Security Service
 * 
 * Handles secure document storage, encryption, access control,
 * and audit logging for sensitive customer documents
 */
class DocumentSecurityService
{
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/jpg', 
        'image/png',
        'image/gif',
        'image/webp',
    ];

    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

    private const ENCRYPTION_ALGORITHM = 'AES-256-CBC';

    /**
     * Securely upload and encrypt a document
     */
    public function secureUpload(UploadedFile $file, array $metadata): array
    {
        try {
            // Validate file
            $this->validateFile($file);
            
            // Generate secure filename
            $secureFilename = $this->generateSecureFilename($file);
            
            // Create directory structure
            $directory = $this->createSecureDirectory($metadata['customer_id']);
            
            // Encrypt file content
            $encryptedContent = $this->encryptFileContent($file);
            
            // Store encrypted file
            $filePath = $directory . '/' . $secureFilename;
            Storage::disk('local')->put($filePath, $encryptedContent);
            
            // Generate access token
            $accessToken = $this->generateAccessToken();
            
            // Log security event
            $this->logSecurityEvent('document_uploaded', [
                'file_name' => $file->getClientOriginalName(),
                'secure_filename' => $secureFilename,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'customer_id' => $metadata['customer_id'],
                'booking_id' => $metadata['booking_id'] ?? null,
            ]);
            
            return [
                'file_path' => $filePath,
                'secure_filename' => $secureFilename,
                'original_filename' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'access_token' => $accessToken,
                'is_encrypted' => true,
                'encryption_algorithm' => self::ENCRYPTION_ALGORITHM,
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to securely upload document', [
                'error' => $e->getMessage(),
                'file_name' => $file->getClientOriginalName(),
                'customer_id' => $metadata['customer_id'] ?? null,
            ]);
            
            throw $e;
        }
    }

    /**
     * Securely retrieve and decrypt a document
     */
    public function secureRetrieve(Document $document, $user): ?string
    {
        try {
            // Check access permissions
            if (!$this->hasAccessPermission($document, $user)) {
                $this->logSecurityEvent('unauthorized_access_attempt', [
                    'document_id' => $document->id,
                    'user_id' => $user->id,
                    'user_type' => get_class($user),
                ]);
                
                throw new Exception('Unauthorized access to document');
            }
            
            // Check if file exists
            if (!Storage::disk('local')->exists($document->file_path)) {
                throw new Exception('Document file not found');
            }
            
            // Retrieve encrypted content
            $encryptedContent = Storage::disk('local')->get($document->file_path);
            
            // Decrypt content
            $decryptedContent = $this->decryptFileContent($encryptedContent);
            
            // Log access event
            $this->logSecurityEvent('document_accessed', [
                'document_id' => $document->id,
                'user_id' => $user->id,
                'user_type' => get_class($user),
                'file_name' => $document->file_name,
            ]);
            
            return $decryptedContent;
            
        } catch (Exception $e) {
            Log::error('Failed to securely retrieve document', [
                'error' => $e->getMessage(),
                'document_id' => $document->id,
                'user_id' => $user->id ?? null,
            ]);
            
            throw $e;
        }
    }

    /**
     * Securely delete a document
     */
    public function secureDelete(Document $document, $user): bool
    {
        try {
            // Check delete permissions
            if (!$this->hasDeletePermission($document, $user)) {
                throw new Exception('Unauthorized document deletion attempt');
            }
            
            // Delete physical file
            if (Storage::disk('local')->exists($document->file_path)) {
                Storage::disk('local')->delete($document->file_path);
            }
            
            // Log deletion event
            $this->logSecurityEvent('document_deleted', [
                'document_id' => $document->id,
                'user_id' => $user->id,
                'user_type' => get_class($user),
                'file_name' => $document->file_name,
                'file_path' => $document->file_path,
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to securely delete document', [
                'error' => $e->getMessage(),
                'document_id' => $document->id,
                'user_id' => $user->id ?? null,
            ]);
            
            throw $e;
        }
    }

    /**
     * Generate secure download URL with expiration
     */
    public function generateSecureDownloadUrl(Document $document, $user, int $expirationMinutes = 60): string
    {
        try {
            // Check access permissions
            if (!$this->hasAccessPermission($document, $user)) {
                throw new Exception('Unauthorized access to document');
            }
            
            // Generate secure token
            $token = $this->generateSecureToken([
                'document_id' => $document->id,
                'user_id' => $user->id,
                'user_type' => get_class($user),
                'expires_at' => now()->addMinutes($expirationMinutes)->timestamp,
            ]);
            
            // Log URL generation
            $this->logSecurityEvent('secure_url_generated', [
                'document_id' => $document->id,
                'user_id' => $user->id,
                'user_type' => get_class($user),
                'expires_at' => now()->addMinutes($expirationMinutes)->toISOString(),
            ]);
            
            return route('documents.secure-download', [
                'document' => $document->id,
                'token' => $token,
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to generate secure download URL', [
                'error' => $e->getMessage(),
                'document_id' => $document->id,
                'user_id' => $user->id ?? null,
            ]);
            
            throw $e;
        }
    }

    /**
     * Validate secure download token
     */
    public function validateSecureToken(string $token): ?array
    {
        try {
            $decrypted = Crypt::decrypt($token);
            $data = json_decode($decrypted, true);
            
            // Check expiration
            if ($data['expires_at'] < now()->timestamp) {
                $this->logSecurityEvent('expired_token_used', [
                    'token_data' => $data,
                ]);
                
                return null;
            }
            
            return $data;
            
        } catch (Exception $e) {
            $this->logSecurityEvent('invalid_token_used', [
                'token' => substr($token, 0, 20) . '...',
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    /**
     * Scan document for malware (placeholder for future implementation)
     */
    public function scanForMalware(UploadedFile $file): bool
    {
        // Placeholder for malware scanning integration
        // Could integrate with ClamAV, VirusTotal API, etc.
        
        // Basic file type validation for now
        $dangerousExtensions = ['exe', 'bat', 'cmd', 'scr', 'pif', 'com'];
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (in_array($extension, $dangerousExtensions)) {
            $this->logSecurityEvent('malware_detected', [
                'file_name' => $file->getClientOriginalName(),
                'extension' => $extension,
                'reason' => 'dangerous_extension',
            ]);
            
            return false;
        }
        
        return true;
    }

    /**
     * Get document access audit trail
     */
    public function getAccessAuditTrail(Document $document): array
    {
        // This would typically query a dedicated audit log table
        // For now, return a placeholder structure
        
        return [
            'document_id' => $document->id,
            'total_accesses' => 0,
            'unique_users' => 0,
            'last_accessed' => null,
            'access_history' => [],
            'security_events' => [],
        ];
    }

    // Private helper methods

    private function validateFile(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new Exception('File size exceeds maximum allowed size of ' . (self::MAX_FILE_SIZE / 1024 / 1024) . 'MB');
        }
        
        // Check MIME type
        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            throw new Exception('File type not allowed. Allowed types: ' . implode(', ', self::ALLOWED_MIME_TYPES));
        }
        
        // Check for malware
        if (!$this->scanForMalware($file)) {
            throw new Exception('File failed security scan');
        }
    }

    private function generateSecureFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $hash = hash('sha256', $file->getClientOriginalName() . time() . Str::random(32));
        
        return $hash . '.' . $extension;
    }

    private function createSecureDirectory(int $customerId): string
    {
        $directory = 'secure-documents/' . date('Y') . '/' . date('m') . '/' . $customerId;
        
        if (!Storage::disk('local')->exists($directory)) {
            Storage::disk('local')->makeDirectory($directory);
        }
        
        return $directory;
    }

    private function encryptFileContent(UploadedFile $file): string
    {
        $content = file_get_contents($file->getRealPath());
        return Crypt::encrypt($content);
    }

    private function decryptFileContent(string $encryptedContent): string
    {
        return Crypt::decrypt($encryptedContent);
    }

    private function generateAccessToken(): string
    {
        return Str::random(64);
    }

    private function generateSecureToken(array $data): string
    {
        return Crypt::encrypt(json_encode($data));
    }

    private function hasAccessPermission(Document $document, $user): bool
    {
        // Customer can access their own documents
        if ($user instanceof \App\Models\Customer) {
            return $document->customer_id === $user->id;
        }
        
        // Admin users can access all documents
        if ($user instanceof \App\Models\User) {
            return in_array($user->role, ['admin', 'super_admin', 'manager', 'operator']);
        }
        
        return false;
    }

    private function hasDeletePermission(Document $document, $user): bool
    {
        // Only admin users can delete documents
        if ($user instanceof \App\Models\User) {
            return in_array($user->role, ['admin', 'super_admin', 'manager']);
        }
        
        // Customers can delete their own pending documents
        if ($user instanceof \App\Models\Customer) {
            return $document->customer_id === $user->id && $document->status === 'pending';
        }
        
        return false;
    }

    private function logSecurityEvent(string $event, array $data): void
    {
        Log::channel('security')->info("Document Security Event: {$event}", [
            'event' => $event,
            'data' => $data,
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}