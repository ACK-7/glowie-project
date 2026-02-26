<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Quote is Ready</title>
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
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
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
        .greeting {
            font-size: 18px;
            color: #1e3a8a;
            margin-bottom: 20px;
        }
        .quote-box {
            background: #f8fafc;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .quote-box h3 {
            margin: 0 0 15px;
            color: #1e3a8a;
            font-size: 18px;
        }
        .quote-detail {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .quote-detail:last-child {
            border-bottom: none;
        }
        .quote-detail strong {
            color: #1e3a8a;
        }
        .credentials-box {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .credentials-box h3 {
            margin: 0 0 15px;
            color: #92400e;
            font-size: 18px;
            display: flex;
            align-items: center;
        }
        .credentials-box h3::before {
            content: "🔐";
            margin-right: 10px;
            font-size: 24px;
        }
        .credential-item {
            background: white;
            padding: 12px;
            margin: 10px 0;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
        }
        .credential-label {
            font-size: 12px;
            color: #92400e;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .credential-value {
            font-size: 16px;
            color: #1e3a8a;
            font-weight: 600;
            word-break: break-all;
        }
        .features-box {
            background: #f0fdf4;
            border-left: 4px solid #10b981;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .features-box h3 {
            margin: 0 0 15px;
            color: #065f46;
            font-size: 16px;
        }
        .feature-item {
            padding: 8px 0;
            color: #065f46;
        }
        .feature-item::before {
            content: "✓";
            color: #10b981;
            font-weight: bold;
            margin-right: 10px;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
            text-align: center;
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);
        }
        .cta-button:hover {
            background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
        }
        .warning {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            font-size: 14px;
            color: #991b1b;
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
            <h1>🚗 Your Quote is Ready!</h1>
            <p>ShipWithGlowie Auto - Reliable Car Shipping</p>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Hello {{ $customer->first_name ?? 'Valued Customer' }},
            </div>

            <p>
                Thank you for requesting a quote with <strong>ShipWithGlowie Auto</strong>! 
                We're excited to help you ship your vehicle safely and efficiently.
            </p>

            <!-- Quote Details -->
            <div class="quote-box">
                <h3>📊 Your Quote Details</h3>
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
                    <strong style="color: #f59e0b;">Pending Approval</strong>
                </div>
            </div>

            <p>
                Your quote is currently being reviewed by our team. We'll notify you as soon as it's approved!
            </p>

            @if($temporaryPassword)
            <!-- Login Credentials -->
            <div class="credentials-box">
                <h3>Your Customer Portal Access</h3>
                <p style="margin: 0 0 15px; color: #92400e;">
                    We've created a customer portal account for you. Use these credentials to log in:
                </p>
                
                <div class="credential-item">
                    <div class="credential-label">Email Address</div>
                    <div class="credential-value">{{ $customer->email }}</div>
                </div>
                
                <div class="credential-item">
                    <div class="credential-label">Temporary Password</div>
                    <div class="credential-value">{{ $temporaryPassword }}</div>
                </div>
            </div>

            <!-- Portal Features -->
            <div class="features-box">
                <h3>What You Can Do in Your Portal:</h3>
                <div class="feature-item">View your quote status in real-time</div>
                <div class="feature-item">Accept your quote when approved</div>
                <div class="feature-item">Track your shipment progress</div>
                <div class="feature-item">Upload required documents</div>
                <div class="feature-item">Manage your bookings</div>
                <div class="feature-item">View payment history</div>
            </div>

            <!-- CTA Button -->
            <div style="text-align: center;">
                <a href="{{ $portalUrl }}" class="cta-button">
                    Access Your Portal Now →
                </a>
            </div>

            <!-- Security Warning -->
            <div class="warning">
                <strong>⚠️ Important Security Notice:</strong><br>
                Please change your password immediately after your first login for security purposes. 
                Never share your password with anyone.
            </div>
            @endif

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
