<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Repository Contracts
use App\Repositories\Contracts\BaseRepositoryInterface;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\QuoteRepositoryInterface;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Contracts\ShipmentRepositoryInterface;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use App\Repositories\Contracts\AnalyticsRepositoryInterface;

// Repository Implementations
use App\Repositories\BaseRepository;
use App\Repositories\BookingRepository;
use App\Repositories\QuoteRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\ShipmentRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\DocumentRepository;
use App\Repositories\AnalyticsRepository;

// Models
use App\Models\Booking;
use App\Models\Quote;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\Payment;
use App\Models\Document;

/**
 * Repository Service Provider
 * 
 * Binds repository interfaces to their implementations
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind repository interfaces to implementations
        $this->app->bind(BookingRepositoryInterface::class, function ($app) {
            return new BookingRepository($app->make(Booking::class));
        });

        $this->app->bind(QuoteRepositoryInterface::class, function ($app) {
            return new QuoteRepository($app->make(Quote::class));
        });

        $this->app->bind(CustomerRepositoryInterface::class, function ($app) {
            return new CustomerRepository($app->make(Customer::class));
        });

        $this->app->bind(ShipmentRepositoryInterface::class, function ($app) {
            return new ShipmentRepository($app->make(Shipment::class));
        });

        $this->app->bind(PaymentRepositoryInterface::class, function ($app) {
            return new PaymentRepository($app->make(Payment::class));
        });

        $this->app->bind(DocumentRepositoryInterface::class, function ($app) {
            return new DocumentRepository($app->make(Document::class));
        });

        $this->app->bind(AnalyticsRepositoryInterface::class, function ($app) {
            return new AnalyticsRepository();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}