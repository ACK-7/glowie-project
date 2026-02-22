<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Shipment;

class UpdateShipmentStatusRequest extends FormRequest
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
        return [
            'status' => 'required|in:' . implode(',', Shipment::VALID_STATUSES),
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'status.required' => 'A status is required to update the shipment.',
            'status.in' => 'The status must be one of: ' . implode(', ', Shipment::VALID_STATUSES),
            'location.max' => 'The location may not be greater than 255 characters.',
            'notes.max' => 'The notes may not be greater than 1000 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate status transition is allowed
            $shipmentId = $this->route('shipment') ?? $this->route('id');
            if ($shipmentId && $this->status) {
                $shipment = Shipment::find($shipmentId);
                if ($shipment && !$shipment->canTransitionTo($this->status)) {
                    $validator->errors()->add('status', 
                        "Cannot transition from {$shipment->status} to {$this->status}."
                    );
                }
            }
        });
    }
}