<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Receipt</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #059669, #10b981); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f8fafc; padding: 30px; border-radius: 0 0 8px 8px; border: 1px solid #e2e8f0; }
        .receipt-box { background: #fff; border: 2px solid #10b981; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
        .detail-label { color: #64748b; font-size: 14px; }
        .detail-value { font-weight: bold; font-size: 14px; }
        .total-row { font-size: 18px; color: #059669; border-top: 2px solid #059669; padding-top: 12px; margin-top: 8px; }
        .button { display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 14px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚢 ShipWithGlowie Auto</h1>
        <h2>✅ Payment Receipt</h2>
    </div>
    <div class="content">
        <p>Hello {{ $customerName }},</p>
        <p>Your payment has been received and confirmed. Here is your receipt:</p>

        <div class="receipt-box">
            <div class="detail-row">
                <span class="detail-label">Payment Reference</span>
                <span class="detail-value">{{ $paymentReference }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Booking Reference</span>
                <span class="detail-value">{{ $bookingReference }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment Method</span>
                <span class="detail-value">{{ ucfirst(str_replace('_', ' ', $paymentMethod)) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date</span>
                <span class="detail-value">{{ $paymentDate }}</span>
            </div>
            <div class="detail-row total-row">
                <span>Amount Paid</span>
                <span>${{ number_format($amount, 2) }} {{ $currency }}</span>
            </div>
        </div>

        <p>You can view your full payment history and download receipts from the customer portal.</p>

        <p style="text-align: center;">
            <a href="{{ $portalUrl }}" class="button">View Payments</a>
        </p>
    </div>
    <div class="footer">
        <p>Thank you for your payment!</p>
        <p>ShipWithGlowie Auto — AI-Powered Vehicle Shipping & Logistics</p>
        <p><small>This is an automated message. Please do not reply to this email.</small></p>
    </div>
</body>
</html>