<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Booking Confirmation</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #1e40af, #3b82f6); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f8fafc; padding: 30px; border-radius: 0 0 8px 8px; border: 1px solid #e2e8f0; }
        .detail-box { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
        .detail-label { color: #64748b; font-size: 14px; }
        .detail-value { font-weight: bold; font-size: 14px; }
        .button { display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 14px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚢 ShipWithGlowie Auto</h1>
        <h2>Booking Confirmed!</h2>
    </div>
    <div class="content">
        <p>Hello {{ $customerName }},</p>
        <p>Your booking has been created successfully. Here are the details:</p>

        <div class="detail-box">
            <div class="detail-row">
                <span class="detail-label">Booking Reference</span>
                <span class="detail-value">{{ $bookingReference }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Total Amount</span>
                <span class="detail-value">${{ number_format($totalAmount, 2) }} {{ $currency }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value">{{ ucfirst($status) }}</span>
            </div>
        </div>

        <p>You can view your booking details and make payments through the customer portal.</p>

        <p style="text-align: center;">
            <a href="{{ $portalUrl }}" class="button">View Booking</a>
        </p>
    </div>
    <div class="footer">
        <p>Thank you for choosing ShipWithGlowie Auto</p>
        <p><small>This is an automated message. Please do not reply to this email.</small></p>
    </div>
</body>
</html>