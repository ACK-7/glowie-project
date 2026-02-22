<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentVerificationRequest extends FormRequest
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
        $action = $this->route()->getActionMethod();
        
        if ($action === 'reject') {
            return [
                'reason' => 'required|string|min:10|max:1000',
                'notes' => 'nullable|string|max:500',
            ];
        } else {
            // For approve action
            return [
                'notes' => 'nullable|string|max:500',
                'reason' => 'nullable|string|max:1000', // Optional for approve
            ];
        }
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'A reason is required when rejecting a document.',
            'reason.min' => 'The rejection reason must be at least 10 characters.',
            'reason.max' => 'The rejection reason may not be greater than 1000 characters.',
            'notes.max' => 'The notes may not be greater than 500 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate document exists and is in pending status
            $documentId = $this->route('document') ?? $this->route('id');
            if ($documentId) {
                $document = \App\Models\Document::find($documentId);
                if (!$document) {
                    $validator->errors()->add('document', 'Document not found.');
                } elseif ($document->status !== \App\Models\Document::STATUS_PENDING) {
                    $validator->errors()->add('document', 
                        "Document cannot be verified from {$document->status} status."
                    );
                }
            }
        });
    }
}