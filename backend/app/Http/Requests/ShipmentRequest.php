<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Shipment;

class ShipmentRequest extends FormRequest
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
        $rules = [
            'booking_id' => 'required|exists:bookings,id',
            'carrier_name' => 'nullable|string|max:100',
            'vessel_name' => 'nullable|string|max:100',
            'container_number' => 'nullable|string|max:50',
            'current_location' => 'nullable|string|max:255',
            'status' => 'nullable|in:' . implode(',', Shipment::VALID_STATUSES),
            'departure_port' => 'nullable|string|max:100',
            'arrival_port' => 'nullable|string|max:100',
            'departure_date' => 'nullable|date|after_or_equal:today',
            'estimated_arrival' => 'nullable|date|after:departure_date',
            'actual_arrival' => 'nullable|date|after:departure_date',
        ];

        // For updates, make booking_id optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['booking_id'] = 'sometimes|exists:bookings,id';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'booking_id.required' => 'A booking ID is required to create a shipment.',
            'booking_id.exists' => 'The selected booking does not exist.',
            'status.in' => 'The status must be one of: ' . implode(', ', Shipment::VALID_STATUSES),
            'departure_date.after_or_equal' => 'The departure date must be today or in the future.',
            'estimated_arrival.after' => 'The estimated arrival must be after the departure date.',
            'actual_arrival.after' => 'The actual arrival must be after the departure date.',
            'carrier_name.max' => 'The carrier name may not be greater than 100 characters.',
            'vessel_name.max' => 'The vessel name may not be greater than 100 characters.',
            'container_number.max' => 'The container number may not be greater than 50 characters.',
            'current_location.max' => 'The current location may not be greater than 255 characters.',
            'departure_port.max' => 'The departure port may not be greater than 100 characters.',
            'arrival_port.max' => 'The arrival port may not be greater than 100 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation for date consistency
            if ($this->departure_date && $this->estimated_arrival) {
                if (strtotime($this->estimated_arrival) <= strtotime($this->departure_date)) {
                    $validator->errors()->add('estimated_arrival', 'The estimated arrival must be after the departure date.');
                }
            }

            // Validate that booking doesn't already have a shipment (for creation)
            if ($this->isMethod('POST') && $this->booking_id) {
                $existingShipment = \App\Models\Shipment::where('booking_id', $this->booking_id)->first();
                if ($existingShipment) {
                    $validator->errors()->add('booking_id', 'This booking already has a shipment.');
                }
            }
        });
    }
}