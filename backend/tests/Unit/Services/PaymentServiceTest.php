<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PaymentService;
use App\Services\NotificationService;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Models\Payment;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Carbon\Carbon;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $paymentService;
    private $paymentRepository;
    private $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
        $this->notificationService = Mockery::mock(NotificationService::class);
        
        $this->paymentService = new PaymentService(
            $this->paymentRepository,
            $this->notificationService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_create_payment_successfully()
    {
        // Arrange
        $customer = Customer::factory()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'total_amount' => 1000.00,
            'paid_amount' => 0.00
        ]);
        
        $data = [
            'booking_id' => $booking->id,
            'customer_id' => $customer->id,
            'amount' => 500.00,
            'currency' => 'USD',
            'payment_method' => Payment::METHOD_BANK_TRANSFER,
        ];
        
        $payment = new Payment(array_merge($data, [
            'id' => 1,
            'payment_reference' => 'PAY202401000001',
            'status' => Payment::STATUS_PENDING,
        ]));
        
        $this->paymentRepository
            ->shouldReceive('create')
            ->once()
            ->with(array_merge($data, ['status' => Payment::STATUS_PENDING]))
            ->andReturn($payment);
            
        $this->notificationService
            ->shouldReceive('sendPaymentCreatedNotification')
            ->once()
            ->with($payment);

        // Act
        $result = $this->paymentService->createPayment($data);

        // Assert
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals(Payment::STATUS_PENDING, $result->status);
        $this->assertEquals(500.00, $result->amount);
        $this->assertEquals(Payment::METHOD_BANK_TRANSFER, $result->payment_method);
    }

    /** @test */
    public function it_validates_payment_amount_during_creation()
    {
        // Arrange
        $customer = Customer::factory()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'total_amount' => 1000.00,
            'paid_amount' => 0.00
        ]);
        
        $data = [
            'booking_id' => $booking->id,
            'customer_id' => $customer->id,
            'amount' => 0.00, // Invalid amount
            'payment_method' => Payment::METHOD_BANK_TRANSFER,
        ];

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Payment amount must be greater than zero');
        
        $this->paymentService->createPayment($data);
    }

    /** @test */
    public function it_validates_payment_does_not_exceed_booking_total()
    {
        // Arrange
        $customer = Customer::factory()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'total_amount' => 1000.00,
            'paid_amount' => 800.00
        ]);
        
        $data = [
            'booking_id' => $booking->id,
            'customer_id' => $customer->id,
            'amount' => 300.00, // Would exceed total
            'payment_method' => Payment::METHOD_BANK_TRANSFER,
        ];

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Payment amount would exceed booking total');
        
        $this->paymentService->createPayment($data);
    }

    /** @test */
    public function it_can_complete_payment_successfully()
    {
        // Arrange
        $booking = Booking::factory()->create([
            'total_amount' => 1000.00,
            'paid_amount' => 0.00
        ]);
        
        $payment = Payment::factory()->create([
            'booking_id' => $booking->id,
            'amount' => 500.00,
            'status' => Payment::STATUS_PENDING
        ]);
        
        $transactionId = 'TXN123456789';
        $metadata = ['gateway' => 'stripe', 'charge_id' => 'ch_123'];
        
        $this->paymentRepository
            ->shouldReceive('findOrFail')
            ->with($payment->id)
            ->once()
            ->andReturn($payment);
            
        $this->notificationService
            ->shouldReceive('sendPaymentCompletedNotification')
            ->once()
            ->with($payment);

        // Mock payment methods
        $payment->shouldReceive('canTransitionTo')
            ->with(Payment::STATUS_COMPLETED)
            ->once()
            ->andReturn(true);
            
        $payment->shouldReceive('complete')
            ->with($transactionId, $metadata)
            ->once()
            ->andReturn(true);
            
        $payment->shouldReceive('getAttribute')
            ->with('booking')
            ->andReturn($booking);
            
        $payment->shouldReceive('fresh')
            ->once()
            ->andReturn($payment);

        // Mock booking increment
        $booking->shouldReceive('increment')
            ->with('paid_amount', 500.00)
            ->once();

        // Act
        $result = $this->paymentService->completePayment($payment->id, $transactionId, $metadata);

        // Assert
        $this->assertInstanceOf(Payment::class, $result);
    }

    /** @test */
    public function it_cannot_complete_non_pending_payment()
    {
        // Arrange
        $payment = Payment::factory()->create(['status' => Payment::STATUS_COMPLETED]);
        
        $this->paymentRepository
            ->shouldReceive('findOrFail')
            ->with($payment->id)
            ->once()
            ->andReturn($payment);

        $payment->shouldReceive('canTransitionTo')
            ->with(Payment::STATUS_COMPLETED)
            ->once()
            ->andReturn(false);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Payment cannot be completed from completed status');
        
        $this->paymentService->completePayment($payment->id);
    }

    /** @test */
    public function it_can_process_refund_successfully()
    {
        // Arrange
        $payment = Payment::factory()->create([
            'amount' => 500.00,
            'status' => Payment::STATUS_COMPLETED,
            'payment_date' => now()->subDays(10)
        ]);
        
        $refundAmount = 300.00;
        $reason = 'Customer requested partial refund';
        
        $refundPayment = Payment::factory()->create([
            'amount' => -300.00,
            'status' => Payment::STATUS_COMPLETED
        ]);
        
        $this->paymentRepository
            ->shouldReceive('findOrFail')
            ->with($payment->id)
            ->once()
            ->andReturn($payment);
            
        $this->notificationService
            ->shouldReceive('sendRefundProcessedNotification')
            ->once()
            ->with($payment, $refundPayment, $reason);

        // Mock payment methods
        $payment->shouldReceive('getAttribute')
            ->with('is_refundable')
            ->andReturn(true);
            
        $payment->shouldReceive('refund')
            ->with($refundAmount, $reason)
            ->once()
            ->andReturn($refundPayment);

        // Act
        $result = $this->paymentService->processRefund($payment->id, $refundAmount, $reason);

        // Assert
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals(-300.00, $result->amount);
    }

    /** @test */
    public function it_cannot_refund_non_refundable_payment()
    {
        // Arrange
        $payment = Payment::factory()->create([
            'status' => Payment::STATUS_PENDING
        ]);
        
        $this->paymentRepository
            ->shouldReceive('findOrFail')
            ->with($payment->id)
            ->once()
            ->andReturn($payment);

        $payment->shouldReceive('getAttribute')
            ->with('is_refundable')
            ->andReturn(false);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Payment is not eligible for refund');
        
        $this->paymentService->processRefund($payment->id);
    }

    /** @test */
    public function it_can_process_overdue_payments()
    {
        // Arrange
        $overduePayments = collect([
            Payment::factory()->make([
                'id' => 1,
                'status' => Payment::STATUS_PENDING,
                'created_at' => now()->subDays(5)
            ]),
            Payment::factory()->make([
                'id' => 2,
                'status' => Payment::STATUS_PENDING,
                'created_at' => now()->subDays(10)
            ]),
            Payment::factory()->make([
                'id' => 3,
                'status' => Payment::STATUS_PENDING,
                'created_at' => now()->subDays(20)
            ])
        ]);
        
        $this->paymentRepository
            ->shouldReceive('getOverdue')
            ->with(30)
            ->once()
            ->andReturn($overduePayments);

        // Mock days_overdue attribute for each payment
        $overduePayments[0]->shouldReceive('getAttribute')
            ->with('days_overdue')
            ->andReturn(5);
            
        $overduePayments[1]->shouldReceive('getAttribute')
            ->with('days_overdue')
            ->andReturn(10);
            
        $overduePayments[2]->shouldReceive('getAttribute')
            ->with('days_overdue')
            ->andReturn(20);

        // Mock notification calls
        $this->notificationService
            ->shouldReceive('sendPaymentReminderNotification')
            ->with($overduePayments[0], 'gentle')
            ->once();
            
        $this->notificationService
            ->shouldReceive('sendPaymentReminderNotification')
            ->with($overduePayments[1], 'urgent')
            ->once();
            
        $this->notificationService
            ->shouldReceive('sendPaymentEscalationNotification')
            ->with($overduePayments[2])
            ->once();

        // Act
        $results = $this->paymentService->processOverduePayments(30);

        // Assert
        $this->assertIsArray($results);
        $this->assertEquals(3, $results['processed']);
        $this->assertEquals(3, $results['notifications_sent']);
        $this->assertEquals(1, $results['escalations']);
        $this->assertEmpty($results['errors']);
    }

    /** @test */
    public function it_can_get_payment_statistics()
    {
        // Arrange
        $expectedStats = [
            'total_payments' => 100,
            'completed_payments' => 80,
            'pending_payments' => 15,
            'failed_payments' => 5,
            'total_revenue' => 50000.00,
            'success_rate' => 80.0
        ];
        
        $this->paymentRepository
            ->shouldReceive('getPaymentStatistics')
            ->once()
            ->andReturn($expectedStats);

        // Act
        $result = $this->paymentService->getPaymentStatistics();

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals(100, $result['total_payments']);
        $this->assertEquals(80.0, $result['success_rate']);
        $this->assertEquals(50000.00, $result['total_revenue']);
    }

    /** @test */
    public function it_can_search_payments()
    {
        // Arrange
        $query = 'PAY2024';
        $payments = collect([
            Payment::factory()->make(['payment_reference' => 'PAY202401000001']),
            Payment::factory()->make(['payment_reference' => 'PAY202401000002'])
        ]);
        
        $this->paymentRepository
            ->shouldReceive('searchPayments')
            ->with($query)
            ->once()
            ->andReturn($payments);

        // Act
        $result = $this->paymentService->searchPayments($query);

        // Assert
        $this->assertCount(2, $result);
        $this->assertStringContains('PAY2024', $result->first()->payment_reference);
    }

    /** @test */
    public function it_can_calculate_payment_fees()
    {
        // Arrange
        $amount = 1000.00;
        $method = Payment::METHOD_CREDIT_CARD;

        // Act
        $fees = $this->paymentService->calculatePaymentFees($amount, $method);

        // Assert
        $this->assertIsArray($fees);
        $this->assertArrayHasKey('processing_fee', $fees);
        $this->assertArrayHasKey('gateway_fee', $fees);
        $this->assertArrayHasKey('total_fees', $fees);
        $this->assertArrayHasKey('net_amount', $fees);
        
        // For credit card: 2.9% + $0.30
        $expectedProcessingFee = $amount * 0.029;
        $expectedGatewayFee = 0.30;
        $expectedTotalFees = $expectedProcessingFee + $expectedGatewayFee;
        
        $this->assertEquals($expectedProcessingFee, $fees['processing_fee']);
        $this->assertEquals($expectedGatewayFee, $fees['gateway_fee']);
        $this->assertEquals($expectedTotalFees, $fees['total_fees']);
        $this->assertEquals($amount - $expectedTotalFees, $fees['net_amount']);
    }

    /** @test */
    public function it_can_get_payment_instructions()
    {
        // Arrange
        $payment = Payment::factory()->create([
            'payment_method' => Payment::METHOD_BANK_TRANSFER,
            'payment_reference' => 'PAY202401000001'
        ]);
        
        $expectedInstructions = [
            'method' => 'Bank Transfer',
            'steps' => [
                'Transfer the amount to our bank account',
                'Use payment reference as transfer description',
                'Send proof of payment to our support team',
            ],
            'details' => [
                'account_name' => 'ShipWithGlowie Auto Ltd',
                'account_number' => '1234567890',
                'bank_name' => 'Example Bank',
                'reference' => 'PAY202401000001',
            ],
        ];
        
        $this->paymentRepository
            ->shouldReceive('findOrFail')
            ->with($payment->id)
            ->once()
            ->andReturn($payment);

        $payment->shouldReceive('getPaymentInstructions')
            ->once()
            ->andReturn($expectedInstructions);

        // Act
        $result = $this->paymentService->getPaymentInstructions($payment->id);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals('Bank Transfer', $result['method']);
        $this->assertArrayHasKey('steps', $result);
        $this->assertArrayHasKey('details', $result);
        $this->assertEquals('PAY202401000001', $result['details']['reference']);
    }
}