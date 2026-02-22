<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: #f8fafc;
            padding: 30px;
            border-radius: 0 0 8px 8px;
            border: 1px solid #e2e8f0;
        }
        .credentials-box {
            background: #fff;
            border: 2px solid #3b82f6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .credentials-box h3 {
            color: #1e40af;
            margin-top: 0;
        }
        .credential-item {
            margin: 10px 0;
            font-family: monospace;
            background: #f1f5f9;
            padding: 8px;
            border-radius: 4px;
        }
        .button {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name', 'ShipWithGlowie Auto') }}</h1>
        <h2>{{ $title }}</h2>
    </div>
    
    <div class="content">
        <p>Hello {{ $customerName }},</p>
        
        {!! nl2br(e($message)) !!}
        
        @if(isset($credentials) && $credentials)
        <div class="credentials-box">
            <h3>🔐 Your Login Credentials</h3>
            <div class="credential-item">
                <strong>Email:</strong> {{ $customerEmail }}
            </div>
            <div class="credential-item">
                <strong>Password:</strong> {{ $temporaryPassword }}
            </div>
            <p><small>⚠️ This is a temporary password. Please log in and set a permanent password for security.</small></p>
        </div>
        @endif
        
        @if(isset($portalUrl) && $portalUrl)
        <p style="text-align: center;">
            <a href="{{ $portalUrl }}" class="button">Access Customer Portal</a>
        </p>
        @endif
        
        @if(isset($additionalInfo) && $additionalInfo)
        <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0;">
            <strong>Additional Information:</strong><br>
            {!! nl2br(e($additionalInfo)) !!}
        </div>
        @endif
    </div>
    
    <div class="footer">
        <p>Thank you for choosing {{ config('app.name', 'ShipWithGlowie Auto') }}</p>
        <p>If you have any questions, please contact our support team.</p>
        <p><small>This is an automated message. Please do not reply to this email.</small></p>
    </div>
</body>
</html>