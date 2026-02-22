<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\Route;
use App\Models\Quote;
use App\Models\Booking;
use App\Models\Shipment;
use App\Models\Document;
use App\Models\Payment;
use App\Models\SystemSetting;

class EnhancedDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            SystemSettingsSeeder::class,
            AdminUsersSeeder::class,
            VehicleTypesSeeder::class,
            RoutesSeeder::class,
            CustomersSeeder::class,
            QuotesSeeder::class,
            BookingsSeeder::class,
            ShipmentsSeeder::class,
            DocumentsSeeder::class,
            PaymentsSeeder::class,
        ]);
    }
}