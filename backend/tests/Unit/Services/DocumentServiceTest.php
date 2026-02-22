<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\DocumentService;
use App\Services\NotificationService;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use App\Models\Document;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Mockery;
use Carbon\Carbon;

class DocumentServiceTest extends TestCase
{
    use RefreshDatabase;

    private DocumentService $documentService;
    private $documentRepository;
    private $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->documentRepository = Mockery::mock(DocumentRepositoryInterface::class);
        $this->notificationService = Mockery::mock(NotificationService::class);
        
        $this->documentService = new DocumentService(
            $this->documentRepository,
            $this->notificationService
        );
        
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_upload_document_successfully()
    {
        // Arrange
        $customer = Customer::factory()->create();
        $booking = Booking::factory()->create(['customer_id' => $customer->id]);
        
        $file = UploadedFile::fake()->create('test-document.pdf', 1024, 'application/pdf');
        $data = [
            'booking_id' => $booking->id,
            'customer_id' => $customer->id,
            'document_type' => Document::TYPE_PASSPORT,
            'expiry_date' => now()->addYear(),
        ];
        
        $document = new Document(array_merge($data, [
            'id' => 1,
            'file_name' => 'test-document.pdf',
            'file_path' => 'documents/2024/01/1/test-document_20240101120000_abcd1234.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'status' => Document::STATUS_PENDING,
        ]));
        
        $this->documentRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($document);
            
        $this->notificationService
            ->shouldReceive('sendDocumentUploadedNotification')
            ->once()
            ->with($document);

        // Act
        $result = $this->documentService->uploadDocument($file, $data);

        // Assert
        $this->assertInstanceOf(Document::class, $result);
        $this->assertEquals(Document::STATUS_PENDING, $result->status);
        $this->assertEquals('test-document.pdf', $result->file_name);
        $this->assertEquals('application/pdf', $result->mime_type);
    }

    /** @test */
    public function it_validates_file_type_during_upload()
    {
        // Arrange
        $customer = Customer::factory()->create();
        $booking = Booking::factory()->create(['customer_id' => $customer->id]);
        
        $file = UploadedFile::fake()->create('test-document.txt', 1024, 'text/plain');
        $data = [
            'booking_id' => $booking->id,
            'customer_id' => $customer->id,
            'document_type' => Document::TYPE_PASSPORT,
        ];

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid file type');
        
        $this->documentService->uploadDocument($file, $data);
    }

    /** @test */
    public function it_validates_file_size_during_upload()
    {
        // Arrange
        $customer = Customer::factory()->create();
        $booking = Booking::factory()->create(['customer_id' => $customer->id]);
        
        // Create file larger than max size (10MB)
        $file = UploadedFile::fake()->create('large-document.pdf', 11 * 1024, 'application/pdf');
        $data = [
            'booking_id' => $booking->id,
            'customer_id' => $customer->id,
            'document_type' => Document::TYPE_PASSPORT,
        ];

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File size exceeds maximum allowed size');
        
        $this->documentService->uploadDocument($file, $data);
    }

    /** @test */
    public function it_can_approve_document_successfully()
    {
        // Arrange
        $document = Document::factory()->create(['status' => Document::STATUS_PENDING]);
        
        $this->documentRepository
            ->shouldReceive('findOrFail')
            ->with($document->id)
            ->once()
            ->andReturn($document);
            
        $this->notificationService
            ->shouldReceive('sendDocumentApprovedNotification')
            ->once()
            ->with($document, 'Approved by admin');

        // Mock the document approve method
        $document->shouldReceive('approve')
            ->once()
            ->with(null, 'Approved by admin')
            ->andReturn(true);
            
        $document->shouldReceive('fresh')
            ->once()
            ->andReturn($document);

        // Act
        $result = $this->documentService->approveDocument($document->id, 'Approved by admin');

        // Assert
        $this->assertInstanceOf(Document::class, $result);
    }

    /** @test */
    public function it_cannot_approve_non_pending_document()
    {
        // Arrange
        $document = Document::factory()->create(['status' => Document::STATUS_APPROVED]);
        
        $this->documentRepository
            ->shouldReceive('findOrFail')
            ->with($document->id)
            ->once()
            ->andReturn($document);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Document cannot be approved from approved status');
        
        $this->documentService->approveDocument($document->id);
    }

    /** @test */
    public function it_can_reject_document_successfully()
    {
        // Arrange
        $document = Document::factory()->create(['status' => Document::STATUS_PENDING]);
        $reason = 'Document is not clear';
        
        $this->documentRepository
            ->shouldReceive('findOrFail')
            ->with($document->id)
            ->once()
            ->andReturn($document);
            
        $this->notificationService
            ->shouldReceive('sendDocumentRejectedNotification')
            ->once()
            ->with($document, $reason);

        // Mock the document reject method
        $document->shouldReceive('reject')
            ->once()
            ->with($reason, null)
            ->andReturn(true);
            
        $document->shouldReceive('fresh')
            ->once()
            ->andReturn($document);

        // Act
        $result = $this->documentService->rejectDocument($document->id, $reason);

        // Assert
        $this->assertInstanceOf(Document::class, $result);
    }

    /** @test */
    public function it_can_detect_missing_documents_for_booking()
    {
        // Arrange
        $booking = Booking::factory()->create();
        
        // Mock booking with relationships
        $booking->shouldReceive('getAttribute')
            ->with('documents')
            ->andReturn(collect([
                (object)['document_type' => Document::TYPE_PASSPORT]
            ]));
            
        $booking->shouldReceive('getAttribute')
            ->with('route')
            ->andReturn((object)['origin_country' => 'Japan']);
            
        $booking->shouldReceive('getAttribute')
            ->with('vehicle')
            ->andReturn((object)['type' => 'car']);

        // Act
        $missingDocuments = $this->documentService->detectMissingDocuments($booking->id);

        // Assert
        $this->assertIsArray($missingDocuments);
        // Should be missing license and invoice at minimum
        $this->assertArrayHasKey(Document::TYPE_LICENSE, $missingDocuments);
        $this->assertArrayHasKey(Document::TYPE_INVOICE, $missingDocuments);
    }

    /** @test */
    public function it_can_process_expired_documents()
    {
        // Arrange
        $expiredDocument = Document::factory()->create([
            'status' => Document::STATUS_APPROVED,
            'expiry_date' => now()->subDay()
        ]);
        
        $this->notificationService
            ->shouldReceive('sendDocumentExpiredNotification')
            ->once()
            ->with(Mockery::type(Document::class));

        // Act
        $results = $this->documentService->processExpiredDocuments();

        // Assert
        $this->assertIsArray($results);
        $this->assertArrayHasKey('processed', $results);
        $this->assertArrayHasKey('notifications_sent', $results);
        $this->assertArrayHasKey('errors', $results);
    }

    /** @test */
    public function it_can_update_document_expiry()
    {
        // Arrange
        $document = Document::factory()->create([
            'expiry_date' => now()->addMonth()
        ]);
        $newExpiryDate = now()->addYear();
        
        $this->documentRepository
            ->shouldReceive('findOrFail')
            ->with($document->id)
            ->once()
            ->andReturn($document);
            
        $this->notificationService
            ->shouldReceive('sendDocumentExpiryUpdatedNotification')
            ->once()
            ->with($document, Mockery::type(Carbon::class), $newExpiryDate);

        // Mock the document updateExpiry method
        $document->shouldReceive('updateExpiry')
            ->once()
            ->with($newExpiryDate)
            ->andReturn(true);
            
        $document->shouldReceive('fresh')
            ->once()
            ->andReturn($document);

        // Act
        $result = $this->documentService->updateDocumentExpiry($document->id, $newExpiryDate);

        // Assert
        $this->assertInstanceOf(Document::class, $result);
    }

    /** @test */
    public function it_cannot_update_expiry_to_past_date()
    {
        // Arrange
        $document = Document::factory()->create();
        $pastDate = now()->subDay();
        
        $this->documentRepository
            ->shouldReceive('findOrFail')
            ->with($document->id)
            ->once()
            ->andReturn($document);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Expiry date cannot be in the past');
        
        $this->documentService->updateDocumentExpiry($document->id, $pastDate);
    }

    /** @test */
    public function it_can_get_documents_requiring_verification()
    {
        // Arrange
        $documents = collect([
            Document::factory()->make(['status' => Document::STATUS_PENDING]),
            Document::factory()->make(['status' => Document::STATUS_PENDING])
        ]);
        
        $this->documentRepository
            ->shouldReceive('getRequiringVerification')
            ->once()
            ->andReturn($documents);

        // Act
        $result = $this->documentService->getDocumentsRequiringVerification();

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals(Document::STATUS_PENDING, $result->first()->status);
    }

    /** @test */
    public function it_can_get_documents_expiring_soon()
    {
        // Arrange
        $documents = collect([
            Document::factory()->make(['expiry_date' => now()->addDays(15)]),
            Document::factory()->make(['expiry_date' => now()->addDays(20)])
        ]);
        
        $this->documentRepository
            ->shouldReceive('getExpiringWithin')
            ->with(30)
            ->once()
            ->andReturn($documents);

        // Act
        $result = $this->documentService->getDocumentsExpiringSoon(30);

        // Assert
        $this->assertCount(2, $result);
    }

    /** @test */
    public function it_can_search_documents()
    {
        // Arrange
        $query = 'passport';
        $documents = collect([
            Document::factory()->make(['document_type' => Document::TYPE_PASSPORT])
        ]);
        
        $this->documentRepository
            ->shouldReceive('searchDocuments')
            ->with($query)
            ->once()
            ->andReturn($documents);

        // Act
        $result = $this->documentService->searchDocuments($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(Document::TYPE_PASSPORT, $result->first()->document_type);
    }
}