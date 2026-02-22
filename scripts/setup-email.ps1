# Email Configuration Setup Script for ShipWithGlowie (PowerShell)
# This script helps you configure email settings

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "ShipWithGlowie Email Configuration" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

# Check if .env file exists
if (-not (Test-Path .env)) {
    Write-Host "❌ .env file not found!" -ForegroundColor Red
    Write-Host "Please create a .env file first."
    exit 1
}

Write-Host "Select your email provider:"
Write-Host "1) Development (Mailhog - Local Testing)"
Write-Host "2) Gmail"
Write-Host "3) SendGrid"
Write-Host "4) Mailgun"
Write-Host "5) Amazon SES"
Write-Host "6) Postmark"
Write-Host "7) Custom SMTP"
Write-Host ""
$choice = Read-Host "Enter your choice (1-7)"

# Remove existing MAIL_ configuration
$envContent = Get-Content .env | Where-Object { $_ -notmatch '^MAIL_' }

switch ($choice) {
    "1" {
        Write-Host ""
        Write-Host "Configuring for Development (Mailhog)..." -ForegroundColor Yellow
        
        $mailConfig = @"

# Mail Configuration (Development - Mailhog)
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@shipwithglowie.com
MAIL_FROM_NAME=ShipWithGlowie
"@
        
        $envContent + $mailConfig | Set-Content .env
        Write-Host "✅ Configured for Mailhog" -ForegroundColor Green
        Write-Host "📧 View emails at: http://localhost:8025" -ForegroundColor Cyan
    }
    "2" {
        Write-Host ""
        Write-Host "Gmail Configuration" -ForegroundColor Yellow
        Write-Host "-------------------"
        Write-Host "⚠️  You need to generate an App Password:" -ForegroundColor Yellow
        Write-Host "1. Enable 2-Factor Authentication"
        Write-Host "2. Visit: https://myaccount.google.com/apppasswords"
        Write-Host "3. Generate an app password"
        Write-Host ""
        
        $gmailAddress = Read-Host "Enter your Gmail address"
        $gmailPassword = Read-Host "Enter your App Password (16 characters)" -AsSecureString
        $gmailPasswordPlain = [Runtime.InteropServices.Marshal]::PtrToStringAuto(
            [Runtime.InteropServices.Marshal]::SecureStringToBSTR($gmailPassword))
        
        $mailConfig = @"

# Mail Configuration (Gmail)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=$gmailAddress
MAIL_PASSWORD=$gmailPasswordPlain
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=$gmailAddress
MAIL_FROM_NAME=ShipWithGlowie
"@
        
        $envContent + $mailConfig | Set-Content .env
        Write-Host "✅ Gmail configured successfully" -ForegroundColor Green
    }
    "3" {
        Write-Host ""
        Write-Host "SendGrid Configuration" -ForegroundColor Yellow
        Write-Host "----------------------"
        Write-Host "Sign up at: https://sendgrid.com"
        Write-Host ""
        
        $sendgridKey = Read-Host "Enter your SendGrid API Key"
        $fromEmail = Read-Host "Enter your from email address"
        
        $mailConfig = @"

# Mail Configuration (SendGrid)
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=$sendgridKey
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=$fromEmail
MAIL_FROM_NAME=ShipWithGlowie
"@
        
        $envContent + $mailConfig | Set-Content .env
        Write-Host "✅ SendGrid configured successfully" -ForegroundColor Green
    }
    default {
        Write-Host "❌ Invalid choice" -ForegroundColor Red
        exit 1
    }
}

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "Configuration complete!" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next steps:"
Write-Host "1. Restart the backend: docker restart shipwithglowie-backend"
Write-Host "2. Test the email: docker exec shipwithglowie-backend php artisan test:quote-email"
Write-Host ""
