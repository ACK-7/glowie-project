@component('mail::message')
# Quote Approved - Welcome to ShipWithGlowie!

Dear {{ $customer->first_name }} {{ $customer->last_name }},

Great news! Your shipping quote **{{ $quote->quote_reference }}** has been approved.

## Quote Details

- **Vehicle:** {{ $quote->vehicle_details['year'] ?? '' }} {{ $quote->vehicle_details['make'] ?? '' }} {{ $quote->vehicle_details['model'] ?? '' }}
- **Route:** {{ $quote->route->origin_country ?? 'N/A' }} → {{ $quote->route->destination_country ?? 'N/A' }}
- **Total Amount:** ${{ number_format($quote->total_amount, 2) }} {{ $quote->currency }}
- **Valid Until:** {{ $quote->valid_until ? $quote->valid_until->format('F d, Y') : 'N/A' }}

## Access Your Customer Portal

We've created a customer portal account for you where you can:
- View your quote details
- Track your shipment
- Upload required documents
- Communicate with our team
- Manage your bookings

### Your Login Credentials

**Email:** {{ $customer->email }}
@if($temporaryPassword)
**Temporary Password:** {{ $temporaryPassword }}

⚠️ **Important:** Please change your password after your first login for security purposes.
@else
**Password:** Use your existing password or reset it if you've forgotten.
@endif

@component('mail::button', ['url' => $customerPortalUrl])
Access Customer Portal
@endcomponent

## Next Steps

1. Log in to your customer portal using the credentials above
2. Review your quote details
3. Upload any required documents (ID, vehicle logbook, etc.)
4. Our team will contact you to finalize the booking

If you have any questions or need assistance, please don't hesitate to contact us.

Thank you for choosing ShipWithGlowie!

Best regards,<br>
The ShipWithGlowie Team

---

<small>
This is an automated email. Please do not reply directly to this message.
If you did not request this quote, please contact us immediately at support@shipwithglowie.com
</small>
@endcomponent
