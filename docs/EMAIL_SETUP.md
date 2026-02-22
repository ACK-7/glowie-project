# Email Configuration Guide

This guide will help you set up email sending for ShipWithGlowie.

## Quick Start

### Option 1: Use the Setup Script (Recommended)

**Windows (PowerShell):**
```powershell
.\scripts\setup-email.ps1
```

**Linux/Mac:**
```bash
chmod +x scripts/setup-email.sh
./scripts/setup-email.sh
```

### Option 2: Manual Configuration

1. Copy the desired configuration from `.env.mail.example`
2. Add it to your `.env` file
3. Restart the backend: `docker restart shipwithglowie-backend`
4. Test: `docker exec shipwithglowie-backend php artisan test:quote-email`

## Email Providers

### 1. Development Mode (Default)

Uses Mailhog for local testing. No real emails are sent.

- **View emails:** http://localhost:8025
- **No setup required** - works out of the box

### 2. Gmail (Quick Testing)

**Pros:** Easy to set up, free
**Cons:** Daily sending limits, requires app password

**Setup:**
1. Enable 2-Factor Authentication on your Gmail account
2. Generate App Password: https://myaccount.google.com/apppasswords
3. Use the 16-character app password (not your regular password)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-16-char-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME=ShipWithGlowie
```

### 3. SendGrid (Recommended for Production)

**Pros:** Free tier (100 emails/day), reliable, easy setup
**Cons:** Requires account verification

**Setup:**
1. Sign up at https://sendgrid.com
2. Create API Key: Settings > API Keys > Create API Key
3. Choose "Full Access" or "Mail Send" permission

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@shipwithglowie.com
MAIL_FROM_NAME=ShipWithGlowie
```

### 4. Mailgun

**Pros:** Good for high volume, flexible pricing
**Cons:** Requires domain verification

**Setup:**
1. Sign up at https://mailgun.com
2. Add and verify your domain
3. Get SMTP credentials from: Sending > Domain Settings > SMTP

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@your-domain.mailgun.org
MAIL_PASSWORD=your-mailgun-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@shipwithglowie.com
MAIL_FROM_NAME=ShipWithGlowie
```

### 5. Amazon SES

**Pros:** Very cost-effective at scale, reliable
**Cons:** More complex setup, requires AWS account

**Setup:**
1. Sign up for AWS and enable SES
2. Verify your domain or email address
3. Create SMTP credentials in SES Console
4. Move out of sandbox mode for production

```env
MAIL_MAILER=smtp
MAIL_HOST=email-smtp.us-east-1.amazonaws.com
MAIL_PORT=587
MAIL_USERNAME=your-ses-smtp-username
MAIL_PASSWORD=your-ses-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@shipwithglowie.com
MAIL_FROM_NAME=ShipWithGlowie
```

## Testing Email Configuration

After configuring your email settings:

1. **Restart the backend:**
   ```bash
   docker restart shipwithglowie-backend
   ```

2. **Send a test email:**
   ```bash
   docker exec shipwithglowie-backend php artisan test:quote-email
   ```

3. **Check the output:**
   - For Mailhog: Visit http://localhost:8025
   - For real SMTP: Check the recipient's inbox (and spam folder)

## Troubleshooting

### Emails not sending

1. **Check logs:**
   ```bash
   docker exec shipwithglowie-backend tail -f storage/logs/laravel.log
   ```

2. **Verify credentials:**
   - Double-check username and password
   - Ensure no extra spaces in .env file

3. **Check firewall:**
   - Ensure port 587 is not blocked
   - Try port 465 with SSL encryption

### Emails going to spam

1. **Verify sender domain:**
   - Add SPF, DKIM, and DMARC records
   - Use a verified domain with your email provider

2. **Use professional from address:**
   - Avoid generic addresses like noreply@gmail.com
   - Use your own domain

3. **Warm up your sending:**
   - Start with low volume
   - Gradually increase sending rate

### Gmail-specific issues

- **"Less secure app" error:** Use App Password instead
- **Daily limit reached:** Gmail limits to ~500 emails/day
- **Blocked sign-in:** Check Gmail security alerts

## Email Templates

Email templates are located in:
```
backend/resources/views/emails/
```

Current templates:
- `quote-approved.blade.php` - Sent when a quote is approved

To customize templates, edit the Blade files and restart the backend.

## Production Checklist

Before going live:

- [ ] Switch from Mailhog to real SMTP service
- [ ] Verify sender domain (SPF, DKIM, DMARC)
- [ ] Test email delivery to multiple providers (Gmail, Outlook, Yahoo)
- [ ] Check spam score using mail-tester.com
- [ ] Set up email monitoring and alerts
- [ ] Configure bounce and complaint handling
- [ ] Review and update email templates
- [ ] Test all email triggers (quote approval, booking confirmation, etc.)

## Support

For issues or questions:
- Check Laravel logs: `docker exec shipwithglowie-backend tail -f storage/logs/laravel.log`
- Review email provider documentation
- Test with Mailhog first to isolate SMTP issues
