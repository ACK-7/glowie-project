<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key_name' => 'company_name',
                'value' => 'ShipWithGlowie Auto',
                'data_type' => 'string',
                'description' => 'Company name displayed throughout the system',
                'is_public' => true,
            ],
            [
                'key_name' => 'company_email',
                'value' => 'info@shipwithglowie.com',
                'data_type' => 'string',
                'description' => 'Primary company email address',
                'is_public' => true,
            ],
            [
                'key_name' => 'company_phone',
                'value' => '+256-700-000-000',
                'data_type' => 'string',
                'description' => 'Primary company phone number',
                'is_public' => true,
            ],
            [
                'key_name' => 'default_currency',
                'value' => 'USD',
                'data_type' => 'string',
                'description' => 'Default currency for pricing and payments',
                'is_public' => false,
            ],
            [
                'key_name' => 'quote_validity_days',
                'value' => '30',
                'data_type' => 'integer',
                'description' => 'Default number of days a quote remains valid',
                'is_public' => false,
            ],
            [
                'key_name' => 'max_file_upload_size',
                'value' => '10485760',
                'data_type' => 'integer',
                'description' => 'Maximum file upload size in bytes (10MB)',
                'is_public' => false,
            ],
            [
                'key_name' => 'allowed_document_types',
                'value' => '["pdf", "jpg", "jpeg", "png", "doc", "docx"]',
                'data_type' => 'json',
                'description' => 'Allowed file types for document uploads',
                'is_public' => false,
            ],
            [
                'key_name' => 'email_notifications_enabled',
                'value' => 'true',
                'data_type' => 'boolean',
                'description' => 'Enable or disable email notifications',
                'is_public' => false,
            ],
            [
                'key_name' => 'sms_notifications_enabled',
                'value' => 'true',
                'data_type' => 'boolean',
                'description' => 'Enable or disable SMS notifications',
                'is_public' => false,
            ],
            [
                'key_name' => 'maintenance_mode',
                'value' => 'false',
                'data_type' => 'boolean',
                'description' => 'Enable maintenance mode to restrict access',
                'is_public' => false,
            ],
            [
                'key_name' => 'payment_terms',
                'value' => '{"deposit_percentage": 50, "balance_due_days": 7}',
                'data_type' => 'json',
                'description' => 'Default payment terms configuration',
                'is_public' => false,
            ],
            [
                'key_name' => 'shipping_insurance_rate',
                'value' => '0.02',
                'data_type' => 'string',
                'description' => 'Default insurance rate as percentage of vehicle value',
                'is_public' => false,
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['key_name' => $setting['key_name']],
                array_merge($setting, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}