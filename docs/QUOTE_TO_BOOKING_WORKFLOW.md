# Quote to Booking Workflow

## Overview
This document explains the workflow for converting quotes to bookings and when customer notification emails are sent.

## Workflow Steps

### 1. Quote Approval (Admin Action)
**Endpoint:** `PATCH /api/admin/crud/quotes/{id}/approve`

**What Happens:**
- Quote status changes from `pending` to `approved`
- Temporary password is generated for the customer (if needed)
- Password is stored in session for later use
- **NO EMAIL IS SENT YET**

**Response Message:**
```
"Quote approved successfully. Customer will be notified when converted to booking."
```

### 2. Convert to Booking (Admin Action)
**Endpoint:** `POST /api/admin/crud/quotes/{id}/convert`

**What Happens:**
- Quote is converted to a booking
- Quote status changes from `approved` to `converted`
- Booking record is created
- **EMAIL IS SENT NOW** with:
  - Quote details
  - Login credentials (email + temporary password)
  - Customer portal access link
  - Next steps instructions

**Response Message:**
```
"Quote converted to booking successfully. Customer has been notified with login credentials."
```

## Email Content

The email sent to customers includes:

1. **Quote Information:**
   - Quote reference number
   - Total amount
   - Route details (origin → destination)

2. **Login Credentials:**
   - Email address
   - Temporary password
   - Customer portal URL

3. **Next Steps:**
   - Instructions to log in
   - Guidance on tracking shipment
   - Contact information for support

## Email Template

Location: `backend/resources/views/emails/quote-approved.blade.php`

## Testing

### Test the Complete Workflow:

1. **Approve a pending quote:**
   ```bash
   curl -X PATCH http://localhost:8000/api/admin/crud/quotes/1/approve \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json"
   ```
   ✅ No email should be sent

2. **Convert approved quote to booking:**
   ```bash
   curl -X POST http://localhost:8000/api/admin/crud/quotes/1/convert \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json"
   ```
   ✅ Email should be sent now

### Check Logs:

```bash
docker-compose exec backend tail -f storage/logs/laravel.log | grep "Quote Approved Email"
```

Look for:
```
[timestamp] local.INFO: 📧 Quote Approved Email Sent on Booking Conversion
```

## Configuration

### Email Provider Setup

The system uses SendGrid by default. To configure:

1. Update `.env` file:
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.sendgrid.net
   MAIL_PORT=587
   MAIL_USERNAME=apikey
   MAIL_PASSWORD=your_sendgrid_api_key
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=your-email@domain.com
   MAIL_FROM_NAME="ShipWithGlowie Auto"
   ```

2. Restart backend container:
   ```bash
   docker-compose restart backend
   ```

3. Clear config cache:
   ```bash
   docker-compose exec backend php artisan config:clear
   ```

## Troubleshooting

### Email Not Sending

1. **Check SendGrid Credits:**
   - Error: "451 Authentication failed: Maximum credits exceeded"
   - Solution: Upgrade SendGrid plan or wait for credit reset

2. **DNS Resolution Issues:**
   - Error: "Connection could not be established with host smtp.sendgrid.net"
   - Solution: Ensure Docker container has DNS configured (Google DNS: 8.8.8.8)

3. **Check Logs:**
   ```bash
   docker-compose exec backend grep -i "email\|mail" storage/logs/laravel.log
   ```

### Password Not Generated

If customer already has a password and it's not temporary, a new password won't be generated. To force generation:

```php
// In QuoteController@convertToBooking
$temporaryPassword = Str::random(12);
$customer->password = Hash::make($temporaryPassword);
$customer->password_is_temporary = true;
$customer->save();
```

## Related Files

- **Controller:** `backend/app/Http/Controllers/QuoteController.php`
- **Service:** `backend/app/Services/NotificationService.php`
- **Email Class:** `backend/app/Mail/QuoteApprovedMail.php`
- **Email Template:** `backend/resources/views/emails/quote-approved.blade.php`
- **Routes:** `backend/routes/api.php`

## Notes

- Temporary passwords are 12 characters long (alphanumeric)
- Passwords are stored in session between approval and conversion
- Session is cleared after email is sent
- Email includes both quote details and login credentials
- Customer portal URL is configurable via `FRONTEND_URL` environment variable
