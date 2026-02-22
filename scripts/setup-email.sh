#!/bin/bash

# Email Configuration Setup Script for ShipWithGlowie
# This script helps you configure email settings

echo "=========================================="
echo "ShipWithGlowie Email Configuration"
echo "=========================================="
echo ""

# Check if .env file exists
if [ ! -f .env ]; then
    echo "❌ .env file not found!"
    echo "Please create a .env file first."
    exit 1
fi

echo "Select your email provider:"
echo "1) Development (Mailhog - Local Testing)"
echo "2) Gmail"
echo "3) SendGrid"
echo "4) Mailgun"
echo "5) Amazon SES"
echo "6) Postmark"
echo "7) Custom SMTP"
echo ""
read -p "Enter your choice (1-7): " choice

case $choice in
    1)
        echo ""
        echo "Configuring for Development (Mailhog)..."
        sed -i.bak '/^MAIL_/d' .env
        cat >> .env << EOF

# Mail Configuration (Development - Mailhog)
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@shipwithglowie.com
MAIL_FROM_NAME=ShipWithGlowie
EOF
        echo "✅ Configured for Mailhog"
        echo "📧 View emails at: http://localhost:8025"
        ;;
    2)
        echo ""
        echo "Gmail Configuration"
        echo "-------------------"
        echo "⚠️  You need to generate an App Password:"
        echo "1. Enable 2-Factor Authentication"
        echo "2. Visit: https://myaccount.google.com/apppasswords"
        echo "3. Generate an app password"
        echo ""
        read -p "Enter your Gmail address: " gmail_address
        read -sp "Enter your App Password (16 characters): " gmail_password
        echo ""
        
        sed -i.bak '/^MAIL_/d' .env
        cat >> .env << EOF

# Mail Configuration (Gmail)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=$gmail_address
MAIL_PASSWORD=$gmail_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=$gmail_address
MAIL_FROM_NAME=ShipWithGlowie
EOF
        echo "✅ Gmail configured successfully"
        ;;
    3)
        echo ""
        echo "SendGrid Configuration"
        echo "----------------------"
        echo "Sign up at: https://sendgrid.com"
        echo ""
        read -p "Enter your SendGrid API Key: " sendgrid_key
        read -p "Enter your from email address: " from_email
        
        sed -i.bak '/^MAIL_/d' .env
        cat >> .env << EOF

# Mail Configuration (SendGrid)
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=$sendgrid_key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=$from_email
MAIL_FROM_NAME=ShipWithGlowie
EOF
        echo "✅ SendGrid configured successfully"
        ;;
    *)
        echo "❌ Invalid choice"
        exit 1
        ;;
esac

echo ""
echo "=========================================="
echo "Configuration complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Restart the backend: docker restart shipwithglowie-backend"
echo "2. Test the email: docker exec shipwithglowie-backend php artisan test:quote-email"
echo ""
