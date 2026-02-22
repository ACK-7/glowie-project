<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class DocumentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Get existing bookings and customers
        $bookings = DB::table('bookings')->get()->toArray();
        $users = DB::table('users')->pluck('id')->toArray();

        if (empty($bookings)) {
            return; // Skip if no bookings exist
        }

        $documentTypes = ['passport', 'license', 'invoice', 'insurance', 'customs', 'other'];
        $statuses = ['pending', 'verified', 'rejected', 'requires_revision'];
        $mimeTypes = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword'];

        foreach ($bookings as $booking) {
            // Each booking should have 2-5 documents
            $numDocuments = $faker->numberBetween(2, 5);
            
            for ($i = 0; $i < $numDocuments; $i++) {
                $documentType = $faker->randomElement($documentTypes);
                $createdAt = $faker->dateTimeBetween($booking->created_at, 'now');
                $status = $faker->randomElement($statuses);
                
                $verifiedBy = null;
                $verifiedAt = null;
                $rejectionReason = null;
                $expiryDate = null;
                
                if (in_array($status, ['verified', 'rejected'])) {
                    $verifiedBy = $faker->randomElement($users);
                    $verifiedAt = $faker->dateTimeBetween($createdAt, 'now');
                    
                    if ($status === 'rejected') {
                        $rejectionReason = $faker->randomElement([
                            'Document is not clear/readable',
                            'Document has expired',
                            'Wrong document type uploaded',
                            'Document does not match customer information',
                            'Additional verification required'
                        ]);
                    }
                }
                
                // Set expiry dates for certain document types
                if (in_array($documentType, ['passport', 'license', 'insurance'])) {
                    $expiryDate = $faker->dateTimeBetween('+6 months', '+5 years');
                    
                    // Some documents might be expired (set as requires_revision)
                    if ($faker->boolean(10)) {
                        $expiryDate = $faker->dateTimeBetween('-2 years', '-1 day');
                        $status = 'requires_revision';
                    }
                }
                
                $mimeType = $faker->randomElement($mimeTypes);
                $extension = match($mimeType) {
                    'application/pdf' => 'pdf',
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'application/msword' => 'doc',
                    default => 'pdf'
                };
                
                $fileName = $documentType . '_' . $booking->booking_reference . '_' . ($i + 1) . '.' . $extension;

                DB::table('documents')->insert([
                    'booking_id' => $booking->id,
                    'customer_id' => $booking->customer_id,
                    'document_type' => $documentType,
                    'file_name' => $fileName,
                    'file_path' => 'documents/' . $booking->customer_id . '/' . $fileName,
                    'file_size' => $faker->numberBetween(50000, 5000000), // 50KB to 5MB
                    'mime_type' => $mimeType,
                    'status' => $status,
                    'verified_by' => $verifiedBy,
                    'verified_at' => $verifiedAt,
                    'rejection_reason' => $rejectionReason,
                    'expiry_date' => $expiryDate,
                    'created_at' => $createdAt,
                    'updated_at' => $faker->dateTimeBetween($createdAt, 'now'),
                ]);
            }
        }
    }
}