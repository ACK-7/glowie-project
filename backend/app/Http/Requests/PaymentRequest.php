<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Payment;

class PaymentRequest extends FormRequest
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
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'payment_method' => 'required|in:' . implode(',', Payment::VALID_METHODS),
            'payment_gateway' => 'nullable|string|max:50',
            'transaction_id' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'metadata' => 'nullable|array',
        ];

        // For updates, make some fields optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['booking_id'] = 'sometimes|exists:bookings,id';
            $rules['customer_id'] = 'sometimes|exists:customers,id';
            $rules['amount'] = 'sometimes|numeric|min:0.01';
            $rules['currency'] = 'sometimes|string|size:3';
            $rules['payment_method'] = 'sometimes|in:' . implode(',', Payment::VALID_METHODS);
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'booking_id.required' => 'A booking ID is required.',
            'booking_id.exists' => 'The selected booking does not exist.',
            'customer_id.required' => 'A customer ID is required.',
            'customer_id.exists' => 'The selected customer does not exist.',
            'amount.required' => 'A payment amount is required.',
            'amount.numeric' => 'The payment amount must be a number.',
            'amount.min' => 'The payment amount must be at least 0.01.',
            'currency.required' => 'A currency is required.',
            'currency.size' => 'The currency must be exactly 3 characters (e.g., USD, EUR).',
            'payment_method.required' => 'A payment method is required.',
            'payment_method.in' => 'The payment method must be one of: ' . implode(', ', Payment::VALID_METHODS),
            'payment_gateway.max' => 'The payment gateway may not be greater than 50 characters.',
            'transaction_id.max' => 'The transaction ID may not be greater than 100 characters.',
            'notes.max' => 'The notes may not be greater than 1000 characters.',
            'metadata.array' => 'The metadata must be an array.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate customer belongs to booking
            if ($this->booking_id && $this->customer_id) {
                $booking = \App\Models\Booking::find($this->booking_id);
                if ($booking && $booking->customer_id != $this->customer_id) {
                    $validator->errors()->add('customer_id', 
                        'The customer does not match the booking customer.'
                    );
                }
            }

            // Validate payment amount doesn't exceed booking total
            if ($this->booking_id && $this->amount) {
                $booking = \App\Models\Booking::find($this->booking_id);
                if ($booking) {
                    $totalPaid = $booking->paid_amount + $this->amount;
                    if ($totalPaid > $booking->total_amount) {
                        $validator->errors()->add('amount', 
                            'Payment amount would exceed the booking total amount.'
                        );
                    }
                }
            }

            // Validate transaction_id is unique if provided
            if ($this->transaction_id) {
                $existingPayment = Payment::where('transaction_id', $this->transaction_id);
                
                // Exclude current payment for updates
                if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
                    $paymentId = $this->route('payment') ?? $this->route('id');
                    if ($paymentId) {
                        $existingPayment->where('id', '!=', $paymentId);
                    }
                }
                
                if ($existingPayment->exists()) {
                    $validator->errors()->add('transaction_id', 
                        'The transaction ID has already been used.'
                    );
                }
            }
        });
    }
}