<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin
        User::updateOrCreate(
            ['email' => 'admin@shipwithglowie.com'],
            [
                'name' => 'Admin User',
                'phone' => '+256700000001',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'is_active' => true,
                'permissions' => null,
            ]
        );

        // Create Manager
        User::updateOrCreate(
            ['email' => 'manager@shipwithglowie.com'],
            [
                'name' => 'Manager User',
                'phone' => '+256700000002',
                'password' => Hash::make('manager123'),
                'role' => 'manager',
                'is_active' => true,
                'permissions' => [
                    'bookings.view',
                    'bookings.create',
                    'bookings.edit',
                    'customers.view',
                    'customers.create',
                    'customers.edit',
                    'shipments.view',
                    'shipments.create',
                    'shipments.edit',
                    'quotes.view',
                    'quotes.create',
                    'quotes.edit',
                    'quotes.approve',
                    'documents.view',
                    'documents.verify',
                ],
            ]
        );

        // Create Support User
        User::updateOrCreate(
            ['email' => 'support@shipwithglowie.com'],
            [
                'name' => 'Support User',
                'phone' => '+256700000003',
                'password' => Hash::make('support123'),
                'role' => 'operator',
                'is_active' => true,
                'permissions' => [
                    'bookings.view',
                    'customers.view',
                    'shipments.view',
                    'documents.view',
                ],
            ]
        );

        $this->command->info('Admin users created successfully!');
        $this->command->info('-----------------------------------');
        $this->command->info('Admin: admin@shipwithglowie.com / admin123');
        $this->command->info('Manager: manager@shipwithglowie.com / manager123');
        $this->command->info('Support: support@shipwithglowie.com / support123');
    }
}
