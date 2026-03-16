<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shipment Update</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #1e40af, #3b82f6); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f8fafc; padding: 30px; border-radius: 0 0 8px 8px; border: 1px solid #e2e8f0; }
        .status-box { background: #fff; border-left: 4px solid #3b82f6; padding: 20px; margin: 20px 0; border-radius: 0 8px 8px 0; }
        .button { display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 14px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚢 ShipWithGlowie Auto</h1>
        <h2>Shipment Update</h2>
    </div>
    <div class="content">
        <p>Hello {{ $customerName }},</p>

        <div class="status-box">
            <h3 style="margin-top: 0; color: #1e40af;">📦 Tracking: {{ $trackingNumber }}</h3>
            <p><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $newStatus)) }}</p>
            @if(isset($location) && $location)
            <p><strong>Location:</strong> {{ $location }}</p>
            @endif
            @if(isset($estimatedArrival) && $estimatedArrival)
            <p><strong>Estimated Arrival:</strong> {{ $estimatedArrival }}</p>
            @endif
        </div>

        <p>Track your shipment in real-time through our customer portal.</p>

        <p style="text-align: center;">
            <a href="{{ $portalUrl }}" class="button">Track Shipment</a>
        </p>
    </div>
    <div class="footer">
        <p>Thank you for choosing ShipWithGlowie Auto</p>
        <p><small>This is an automated message. Please do not reply to this email.</small></p>
    </div>
</body>
</html>