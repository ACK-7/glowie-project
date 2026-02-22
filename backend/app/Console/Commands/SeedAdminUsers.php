<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SeedAdminUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:seed-users {--fresh : Clear existing users first}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed admin users for the admin dashboard';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting admin user seeding...');

        // Clear existing users if --fresh flag is set
        if ($this->option('fresh')) {
            $this->warn('⚠️  Clearing existing users...');
            
            // Disable foreign key checks temporarily
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            
            // Delete all users (can't truncate due to foreign keys)
            $deleted = DB::table('users')->delete();
            
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
            $this->info("✓ Cleared {$deleted} existing user(s)");
        }

        // Users matching the AdminLogin page
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@shipwithglowie.com',
                'password' => 'admin123',
                'role' => 'admin',
                'phone' => '+256-700-000-001',
            ],
            [
                'name' => 'Manager User',
                'email' => 'manager@shipwithglowie.com',
                'password' => 'manager123',
                'role' => 'manager',
                'phone' => '+256-700-000-002',
            ],
            [
                'name' => 'Support User',
                'email' => 'support@shipwithglowie.com',
                'password' => 'support123',
                'role' => 'support',
                'phone' => '+256-700-000-003',
            ],
        ];

        $this->info('📝 Creating admin users...');

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make($userData['password']),
                    'role' => $userData['role'],
                    'phone' => $userData['phone'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $this->info("  ✓ Created/Updated: {$user->email} ({$user->role})");
        }

        $this->newLine();
        $this->info('✅ Admin users seeded successfully!');
        $this->newLine();
        $this->table(
            ['Email', 'Password', 'Role'],
            [
                ['admin@shipwithglowie.com', 'admin123', 'Admin'],
                ['manager@shipwithglowie.com', 'manager123', 'Manager'],
                ['support@shipwithglowie.com', 'support123', 'Support'],
            ]
        );
        $this->newLine();
        $this->info('🎉 You can now login at: http://localhost:5173/admin/login');

        return Command::SUCCESS;
    }
}
