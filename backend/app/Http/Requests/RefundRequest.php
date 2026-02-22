<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Payment;

class RefundRequest extends FormRequest
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
            'refund_amount' => 'nullable|numeric|min:0.01',
            'reason' => 'required|string|min:10|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'refund_amount.numeric' => 'The refund amount must be a number.',
            'refund_amount.min' => 'The refund amount must be at least 0.01.',
            'reason.required' => 'A reason for the refund is required.',
            'reason.min' => 'The refund reason must be at least 10 characters.',
            'reason.max' => 'The refund reason may not be greater than 1000 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate payment exists and is refundable
            $paymentId = $this->route('payment') ?? $this->route('id');
            if ($paymentId) {
                $payment = Payment::find($paymentId);
                
                if (!$payment) {
                    $validator->errors()->add('payment', 'Payment not found.');
                    return;
                }

                if (!$payment->is_refundable) {
                    $validator->errors()->add('payment', 'Payment is not eligible for refund.');
                    return;
                }

                // Validate refund amount doesn't exceed original payment
                if ($this->refund_amount && $this->refund_amount > $payment->amount) {
                    $validator->errors()->add('refund_amount', 
                        'Refund amount cannot exceed the original payment amount of ' . 
                        $payment->formatted_amount . '.'
                    );
                }

                // If no refund amount specified, it will be a full refund
                if (!$this->refund_amount) {
                    $this->merge(['refund_amount' => $payment->amount]);
                }
            }
        });
    }
}