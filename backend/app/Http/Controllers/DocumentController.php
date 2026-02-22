<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseApiController;
use App\Services\DocumentService;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use App\Http\Requests\DocumentUploadRequest;
use App\Http\Requests\DocumentVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Document Controller
 * 
 * Handles document management with verification workflows,
 * missing document detection, and automated expiry management.
 * 
 * Requirements: 6.1, 6.2, 6.3, 6.4, 6.5
 */
class DocumentController extends BaseApiController
{
    public function __construct(
        private DocumentService $documentService,
        private DocumentRepositoryInterface $documentRepository,
        private \App\Services\DocumentSecurityService $documentSecurityService
    ) {}

    /**
     * Display a listing of documents with filtering options
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // If accessed by authenticated customer, filter by their ID
            $user = $request->user();
            if ($user && $user instanceof \App\Models\Customer) {
                $filters = ['customer_id' => $user->id];
                
                $documents = $this->documentRepository->getFilteredPaginated(
                    $filters,
                    100, // Get all customer documents
                    ['booking.customer', 'customer', 'verifiedBy'],
                    'created_at',
                    'desc'
                );
                
                return $this->successResponse([
                    'data' => $documents->items(),
                    'meta' => [
                        'total' => $documents->total(),
                        'customer_id' => $user->id
                    ]
                ], 'Documents retrieved successfully');
            }
            
            // For admin users, use full filtering
            $filters = $request->only([
                'status', 'document_type', 'customer_id', 'booking_id',
                'expiring_soon', 'verified_by', 'mime_type', 'search'
            ]);
            
            $perPage = $request->get('per_page', 15);
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            $documents = $this->documentRepository->getFilteredPaginated(
                $filters,
                $perPage,
                ['booking.customer', 'customer', 'verifiedBy'],
                $sortBy,
                $sortOrder
            );
            
            return $this->paginatedResponse($documents, 'Documents retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve documents', [
                'error' => $e->getMessage(),
                'filters' => $filters ?? []
            ]);
            
            return $this->errorResponse('Failed to retrieve documents', 500);
        }
    }

    /**
     * Upload a new document
     * 
     * @param DocumentUploadRequest $request
     * @return JsonResponse
     */
    public function upload(DocumentUploadRequest $request): JsonResponse
    {
        try {
            $file = $request->file('file');
            $data = $request->validated();
            
            $document = $this->documentService->uploadDocument($file, $data);
            
            return $this->successResponse(
                $document->load(['booking.customer', 'customer']),
                'Document uploaded successfully',
                201
            );
            
        } catch (Exception $e) {
            Log::error('Failed to upload document', [
                'error' => $e->getMessage(),
                'data' => $request->except(['file'])
            ]);
            
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Display the specified document
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $document = $this->documentRepository->findWithRelations($id, [
                'booking.customer', 'customer', 'verifiedBy', 'booking.route'
            ]);
            
            if (!$document) {
                return $this->errorResponse('Document not found', 404);
            }
            
            // Add additional document information
            $documentData = [
                'document' => $document,
                'type_requirements' => $document::getTypeRequirements($document->document_type),
                'is_required' => $document->getRequiredForBooking(),
                'file_info' => [
                    'file_url' => $document->file_url,
                    'file_size_human' => $document->file_size_human,
                    'is_image' => $document->is_image,
                    'is_pdf' => $document->is_pdf,
                ],
                'expiry_info' => [
                    'is_expired' => $document->is_expired,
                    'days_until_expiry' => $document->days_until_expiry,
                    'expiry_date' => $document->expiry_date,
                ]
            ];
            
            return $this->successResponse($documentData, 'Document details retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve document details', [
                'document_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve document details', 500);
        }
    }

    /**
     * Update the specified document
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'document_type' => 'sometimes|in:' . implode(',', \App\Models\Document::VALID_TYPES),
            'expiry_date' => 'sometimes|nullable|date|after:today',
        ]);
        
        try {
            $document = $this->documentRepository->update($id, $request->validated());
            
            return $this->successResponse(
                $document->load(['booking.customer', 'customer']),
                'Document updated successfully'
            );
            
        } catch (Exception $e) {
            Log::error('Failed to update document', [
                'document_id' => $id,
                'error' => $e->getMessage(),
                'data' => $request->validated()
            ]);
            
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Approve a document
     * 
     * @param DocumentVerificationRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function approve(DocumentVerificationRequest $request, int $id): JsonResponse
    {
        try {
            $document = $this->documentService->approveDocument($id, $request->notes);
            
            return $this->successResponse(
                $document->load(['booking.customer', 'verifiedBy']),
                'Document approved successfully'
            );
            
        } catch (Exception $e) {
            Log::error('Failed to approve document', [
                'document_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Reject a document
     * 
     * @param DocumentVerificationRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function reject(DocumentVerificationRequest $request, int $id): JsonResponse
    {
        try {
            $document = $this->documentService->rejectDocument($id, $request->reason);
            
            return $this->successResponse(
                $document->load(['booking.customer', 'verifiedBy']),
                'Document rejected successfully'
            );
            
        } catch (Exception $e) {
            Log::error('Failed to reject document', [
                'document_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Update document expiry date
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateExpiry(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'expiry_date' => 'required|date|after:today'
        ]);
        
        try {
            $document = $this->documentService->updateDocumentExpiry(
                $id,
                \Carbon\Carbon::parse($request->expiry_date)
            );
            
            return $this->successResponse(
                $document->load(['booking.customer']),
                'Document expiry updated successfully'
            );
            
        } catch (Exception $e) {
            Log::error('Failed to update document expiry', [
                'document_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get documents requiring verification
     * 
     * @return JsonResponse
     */
    public function requiresVerification(): JsonResponse
    {
        try {
            $documents = $this->documentService->getDocumentsRequiringVerification();
            
            return $this->successResponse($documents, 'Documents requiring verification retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve documents requiring verification', [
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve documents requiring verification', 500);
        }
    }

    /**
     * Get documents expiring soon
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function expiringSoon(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'nullable|integer|min:1|max:365'
        ]);
        
        try {
            $days = $request->get('days', 30);
            $documents = $this->documentService->getDocumentsExpiringSoon($days);
            
            return $this->successResponse($documents, 'Documents expiring soon retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve documents expiring soon', [
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve documents expiring soon', 500);
        }
    }

    /**
     * Get expired documents
     * 
     * @return JsonResponse
     */
    public function expired(): JsonResponse
    {
        try {
            $documents = $this->documentService->getExpiredDocuments();
            
            return $this->successResponse($documents, 'Expired documents retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve expired documents', [
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve expired documents', 500);
        }
    }

    /**
     * Process expired documents automatically
     * 
     * @return JsonResponse
     */
    public function processExpired(): JsonResponse
    {
        try {
            $results = $this->documentService->processExpiredDocuments();
            
            return $this->successResponse($results, 'Expired documents processed successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to process expired documents', [
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to process expired documents', 500);
        }
    }

    /**
     * Detect missing documents for a booking
     * 
     * @param int $bookingId
     * @return JsonResponse
     */
    public function detectMissing(int $bookingId): JsonResponse
    {
        try {
            $missingDocuments = $this->documentService->detectMissingDocuments($bookingId);
            
            return $this->successResponse([
                'booking_id' => $bookingId,
                'missing_documents' => $missingDocuments,
                'missing_count' => count($missingDocuments)
            ], 'Missing documents detected successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to detect missing documents', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Send missing document requests to customer
     * 
     * @param int $bookingId
     * @return JsonResponse
     */
    public function requestMissing(int $bookingId): JsonResponse
    {
        try {
            $success = $this->documentService->sendMissingDocumentRequests($bookingId);
            
            if ($success) {
                return $this->successResponse(null, 'Missing document requests sent successfully');
            } else {
                return $this->errorResponse('No missing documents found', 400);
            }
            
        } catch (Exception $e) {
            Log::error('Failed to send missing document requests', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get documents by booking
     * 
     * @param int $bookingId
     * @return JsonResponse
     */
    public function byBooking(int $bookingId): JsonResponse
    {
        try {
            $documents = $this->documentRepository->getByBooking($bookingId);
            $missingDocuments = $this->documentService->detectMissingDocuments($bookingId);
            
            return $this->successResponse([
                'documents' => $documents,
                'missing_documents' => $missingDocuments,
                'total_uploaded' => $documents->count(),
                'total_missing' => count($missingDocuments)
            ], 'Booking documents retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve booking documents', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve booking documents', 500);
        }
    }

    /**
     * Get documents by customer
     * 
     * @param int $customerId
     * @return JsonResponse
     */
    public function byCustomer(int $customerId): JsonResponse
    {
        try {
            $documents = $this->documentRepository->getByCustomer($customerId);
            
            return $this->successResponse($documents, 'Customer documents retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve customer documents', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve customer documents', 500);
        }
    }

    /**
     * Get document statistics
     * 
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->documentService->getDocumentStatistics();
            
            return $this->successResponse($statistics, 'Document statistics retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve document statistics', [
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve document statistics', 500);
        }
    }

    /**
     * Search documents across multiple fields
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2|max:100'
        ]);
        
        try {
            $documents = $this->documentService->searchDocuments($request->query);
            
            return $this->successResponse($documents, 'Search results retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to search documents', [
                'query' => $request->query,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to search documents', 500);
        }
    }

    /**
     * Get recent documents
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function recent(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);
            $documents = $this->documentRepository->getRecent($limit);
            
            return $this->successResponse($documents, 'Recent documents retrieved successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve recent documents', [
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to retrieve recent documents', 500);
        }
    }

    /**
     * Download a document file
     * 
     * @param int $id
     * @return mixed
     */
    public function download(int $id)
    {
        try {
            $document = $this->documentRepository->findOrFail($id);
            
            if (!$document->file_path || !\Illuminate\Support\Facades\Storage::disk('public')->exists($document->file_path)) {
                return $this->errorResponse('Document file not found', 404);
            }
            
            // Create audit trail for download
            \App\Models\ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'document_downloaded',
                'model_type' => \App\Models\Document::class,
                'model_id' => $document->id,
                'changes' => [
                    'downloaded_by' => auth()->id(),
                    'downloaded_at' => now(),
                    'file_name' => $document->file_name
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            
            return \Illuminate\Support\Facades\Storage::disk('public')->download(
                $document->file_path,
                $document->file_name
            );
            
        } catch (Exception $e) {
            Log::error('Failed to download document', [
                'document_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to download document', 500);
        }
    }

    /**
     * Remove the specified document
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);
        
        try {
            $success = $this->documentService->deleteDocument($id, $request->reason);
            
            if ($success) {
                return $this->successResponse(null, 'Document deleted successfully');
            } else {
                return $this->errorResponse('Failed to delete document', 400);
            }
            
        } catch (Exception $e) {
            Log::error('Failed to delete document', [
                'document_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Bulk approve documents
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkApprove(Request $request): JsonResponse
    {
        $request->validate([
            'document_ids' => 'required|array|min:1',
            'document_ids.*' => 'integer|exists:documents,id',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            $documentIds = $request->document_ids;
            $notes = $request->notes ?? 'Bulk approval';
            $approvedCount = 0;
            $errors = [];

            foreach ($documentIds as $documentId) {
                try {
                    $this->documentService->approveDocument($documentId, $notes);
                    $approvedCount++;
                } catch (Exception $e) {
                    $errors[] = "Document ID {$documentId}: " . $e->getMessage();
                }
            }

            $message = "Successfully approved {$approvedCount} documents";
            if (!empty($errors)) {
                $message .= ". Errors: " . implode(', ', $errors);
            }

            return $this->successResponse([
                'approved_count' => $approvedCount,
                'total_requested' => count($documentIds),
                'errors' => $errors
            ], $message);

        } catch (Exception $e) {
            Log::error('Failed to bulk approve documents', [
                'document_ids' => $request->document_ids ?? [],
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Bulk reject documents
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkReject(Request $request): JsonResponse
    {
        $request->validate([
            'document_ids' => 'required|array|min:1',
            'document_ids.*' => 'integer|exists:documents,id',
            'reason' => 'required|string|min:10|max:1000'
        ]);

        try {
            $documentIds = $request->document_ids;
            $reason = $request->reason;
            $rejectedCount = 0;
            $errors = [];

            foreach ($documentIds as $documentId) {
                try {
                    $this->documentService->rejectDocument($documentId, $reason);
                    $rejectedCount++;
                } catch (Exception $e) {
                    $errors[] = "Document ID {$documentId}: " . $e->getMessage();
                }
            }

            $message = "Successfully rejected {$rejectedCount} documents";
            if (!empty($errors)) {
                $message .= ". Errors: " . implode(', ', $errors);
            }

            return $this->successResponse([
                'rejected_count' => $rejectedCount,
                'total_requested' => count($documentIds),
                'errors' => $errors
            ], $message);

        } catch (Exception $e) {
            Log::error('Failed to bulk reject documents', [
                'document_ids' => $request->document_ids ?? [],
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
