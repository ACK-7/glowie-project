<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Document;

class DocumentUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $maxSizeMB = Document::MAX_FILE_SIZE / 1048576;
        
        return [
            'file' => [
                'required',
                'file',
                "max:{$maxSizeMB}",
                'mimes:pdf,jpeg,jpg,png,gif,webp',
            ],
            'booking_id' => 'required|exists:bookings,id',
            'customer_id' => 'required|exists:customers,id',
            'document_type' => 'required|in:' . implode(',', Document::VALID_TYPES),
            'expiry_date' => 'nullable|date|after:today',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        $maxSizeMB = Document::MAX_FILE_SIZE / 1048576;
        
        return [
            'file.required' => 'A file is required for document upload.',
            'file.file' => 'The uploaded file is not valid.',
            'file.max' => "The file size may not be greater than {$maxSizeMB}MB.",
            'file.mimes' => 'The file must be a PDF, JPEG, JPG, PNG, GIF, or WebP file.',
            'booking_id.required' => 'A booking ID is required.',
            'booking_id.exists' => 'The selected booking does not exist.',
            'customer_id.required' => 'A customer ID is required.',
            'customer_id.exists' => 'The selected customer does not exist.',
            'document_type.required' => 'A document type is required.',
            'document_type.in' => 'The document type must be one of: ' . implode(', ', Document::VALID_TYPES),
            'expiry_date.date' => 'The expiry date must be a valid date.',
            'expiry_date.after' => 'The expiry date must be in the future.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check if document type already exists for this booking
            if ($this->booking_id && $this->document_type) {
                $existingDocument = Document::where('booking_id', $this->booking_id)
                    ->where('document_type', $this->document_type)
                    ->where('status', '!=', Document::STATUS_REJECTED)
                    ->first();
                    
                if ($existingDocument) {
                    $validator->errors()->add('document_type', 
                        "A {$this->document_type} document already exists for this booking."
                    );
                }
            }

            // Validate expiry date is required for certain document types
            $requiresExpiry = [Document::TYPE_PASSPORT, Document::TYPE_LICENSE, Document::TYPE_INSURANCE];
            if (in_array($this->document_type, $requiresExpiry) && !$this->expiry_date) {
                $validator->errors()->add('expiry_date', 
                    "An expiry date is required for {$this->document_type} documents."
                );
            }

            // Validate customer belongs to booking
            if ($this->booking_id && $this->customer_id) {
                $booking = \App\Models\Booking::find($this->booking_id);
                if ($booking && $booking->customer_id != $this->customer_id) {
                    $validator->errors()->add('customer_id', 
                        'The customer does not match the booking customer.'
                    );
                }
            }
        });
    }
}