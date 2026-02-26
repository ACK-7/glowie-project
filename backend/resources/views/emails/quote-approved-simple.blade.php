<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote Approved</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .header p {
            margin: 10px 0 0;
            font-size: 16px;
            opacity: 0.9;
        }
        .content {
            padding: 30px;
        }
        .success-icon {
            text-align: center;
            font-size: 64px;
            margin: 20px 0;
        }
        .quote-box {
            background: #f0fdf4;
            border-left: 4px solid #10b981;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .quote-box h3 {
            margin: 0 0 15px;
            color: #065f46;
            font-size: 18px;
        }
        .quote-detail {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #d1fae5;
        }
        .quote-detail:last-child {
            border-bottom: none;
        }
        .quote-detail strong {
            color: #065f46;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
            text-align: center;
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);
        }
        .cta-button:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }
        .info-box {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .footer {
            background: #f8fafc;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }
        .footer a {
            color: #3b82f6;
            text-decoration: none;
        }
        @media only screen and (max-width: 600px) {
            .container {
                margin: 0;
                border-radius: 0;
            }
            .content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>✅ Quote Approved!</h1>
            <p>Your quote is ready to accept</p>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="success-icon">
                🎉
            </div>

            <p style="text-align: center; font-size: 18px; color: #065f46; font-weight: 600;">
                Great news, {{ $customer->first_name ?? 'Valued Customer' }}!
            </p>

            <p style="text-align: center;">
                Your quote has been reviewed and approved by our team. 
                You can now proceed to accept it and complete your booking.
            </p>

            <!-- Quote Details -->
            <div class="quote-box">
                <h3>📊 Approved Quote Details</h3>
                <div class="quote-detail">
                    <span>Quote Reference:</span>
                    <strong>{{ $quoteReference }}</strong>
                </div>
                <div class="quote-detail">
                    <span>Total Amount:</span>
                    <strong>{{ $currency }} {{ $totalAmount }}</strong>
                </div>
                <div class="quote-detail">
                    <span>Valid Until:</span>
                    <strong>{{ $validUntil }}</strong>
                </div>
                <div class="quote-detail">
                    <span>Status:</span>
                    <strong style="color: #10b981;">✓ APPROVED</strong>
                </div>
            </div>

            <!-- CTA Button -->
            <div style="text-align: center;">
                <a href="{{ $portalUrl }}" class="cta-button">
                    Accept Quote & Complete Booking →
                </a>
            </div>

            <!-- Info Box -->
            <div class="info-box">
                <strong>💡 Next Steps:</strong><br>
                1. Log in to your customer portal<br>
                2. Review your approved quote<br>
                3. Click "Confirm Booking" to proceed<br>
                4. Upload required documents<br>
                5. Track your shipment in real-time
            </div>

            <p style="margin-top: 30px;">
                If you have any questions or need assistance, please don't hesitate to contact our support team.
            </p>

            <p style="margin-top: 20px; color: #64748b; font-size: 14px;">
                Best regards,<br>
                <strong style="color: #1e3a8a;">The ShipWithGlowie Auto Team</strong>
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>
                This email was sent to {{ $customer->email }}<br>
                <a href="{{ config('app.frontend_url', 'http://localhost:5173') }}">Visit Our Website</a> | 
                <a href="mailto:support@shipwithglowie.com">Contact Support</a>
            </p>
            <p style="margin-top: 10px; font-size: 12px;">
                © {{ date('Y') }} ShipWithGlowie Auto. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
