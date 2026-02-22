<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Users matching the AdminLogin page credentials
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@shipwithglowie.com',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'permissions' => json_encode([
                    'users.manage',
                    'system.configure',
                    'bookings.manage',
                    'quotes.manage',
                    'customers.manage',
                    'shipments.manage',
                    'documents.manage',
                    'payments.manage',
                    'analytics.view',
                    'reports.export'
                ]),
                'is_active' => true,
                'phone' => '+256-700-000-001',
            ],
            [
                'name' => 'Manager User',
                'email' => 'manager@shipwithglowie.com',
                'email_verified_at' => now(),
                'password' => Hash::make('manager123'),
                'role' => 'manager',
                'permissions' => json_encode([
                    'bookings.manage',
                    'quotes.manage',
                    'customers.manage',
                    'shipments.manage',
                    'documents.manage',
                    'payments.view',
                    'analytics.view',
                    'reports.export'
                ]),
                'is_active' => true,
                'phone' => '+256-700-000-002',
            ],
            [
                'name' => 'Support User',
                'email' => 'support@shipwithglowie.com',
                'email_verified_at' => now(),
                'password' => Hash::make('support123'),
                'role' => 'support',
                'permissions' => json_encode([
                    'bookings.view',
                    'bookings.edit',
                    'quotes.manage',
                    'customers.manage',
                    'shipments.view',
                    'documents.view',
                    'payments.view'
                ]),
                'is_active' => true,
                'phone' => '+256-700-000-003',
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                array_merge($user, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}