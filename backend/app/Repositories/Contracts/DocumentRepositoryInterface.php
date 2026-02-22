<?php

namespace App\Repositories\Contracts;

use App\Models\Document;
use Illuminate\Database\Eloquent\Collection;

/**
 * Document Repository Interface
 */
interface DocumentRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get documents by status
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get pending documents
     */
    public function getPending(): Collection;

    /**
     * Get approved documents
     */
    public function getApproved(): Collection;

    /**
     * Get rejected documents
     */
    public function getRejected(): Collection;

    /**
     * Get expired documents
     */
    public function getExpired(): Collection;

    /**
     * Get documents by type
     */
    public function getByType(string $type): Collection;

    /**
     * Get documents by customer
     */
    public function getByCustomer(int $customerId): Collection;

    /**
     * Get documents by booking
     */
    public function getByBooking(int $bookingId): Collection;

    /**
     * Get documents expiring within days
     */
    public function getExpiringWithin(int $days = 30): Collection;

    /**
     * Get documents requiring verification
     */
    public function getRequiringVerification(): Collection;

    /**
     * Get document statistics
     */
    public function getDocumentStatistics(): array;

    /**
     * Search documents
     */
    public function searchDocuments(string $query): Collection;

    /**
     * Get recent documents
     */
    public function getRecent(int $limit = 10): Collection;

    /**
     * Get missing documents by booking
     */
    public function getMissingDocumentsByBooking(int $bookingId): array;
}