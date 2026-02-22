# Customer Portal Testing Guide

## Quick Test

### 1. Run Automated Test
```bash
docker-compose exec backend php test_customer_portal.php
```

Expected output:
```
🎉 All tests passed! Customer portal is fully functional.
```

---

## Manual Testing

### 1. Get Customer Credentials
```bash
docker-compose exec backend php artisan tinker --execute="
\$customer = \App\Models\Customer::first();
echo 'Email: ' . \$customer->email . PHP_EOL;
echo 'ID: ' . \$customer->id . PHP_EOL;
"
```

### 2. Reset Customer Password (if needed)
```bash
docker-compose exec backend php artisan tinker --execute="
\$customer = \App\Models\Customer::where('email', 'john.doe@example.com')->first();
\$customer->password = bcrypt('password123');
\$customer->password_is_temporary = false;
\$customer->save();
echo 'Password reset to: password123' . PHP_EOL;
"
```

### 3. Login to Customer Portal
1. Open browser: http://localhost:5173
2. Click "Customer Portal" or navigate to login
3. Enter credentials:
   - Email: `john.doe@example.com`
   - Password: `password123`

### 4. Test Each Tab

#### Profile Tab
- [ ] Customer name displays correctly
- [ ] Email and phone visible
- [ ] Member since date shown
- [ ] Account statistics accurate

#### Dashboard Tab
- [ ] Active shipments count correct
- [ ] Balance due displayed
- [ ] Current bookings list populated
- [ ] Status badges showing correctly

#### My Quotes Tab
- [ ] All quotes listed
- [ ] Status badges correct (Approved, Pending, Rejected)
- [ ] Prices displayed properly
- [ ] "Confirm Booking" button visible for approved quotes

#### Manage Booking Tab
- [ ] Search functionality works
- [ ] Booking details display
- [ ] Status tracking visible

#### Tracking Tab
- [ ] Shipment selection dropdown populated
- [ ] Map loads (if tracking data available)
- [ ] Timeline shows status updates
- [ ] Route information correct

#### Contracts Tab
- [ ] UI loads without errors
- [ ] Contract list displays (if contracts exist)

#### Documents Tab
- [ ] Document list populated
- [ ] Upload button functional
- [ ] Document types shown
- [ ] Status indicators correct

#### Payments Tab
- [ ] Payment history listed
- [ ] Amounts and dates correct
- [ ] Payment status shown
- [ ] Booking references linked

---

## API Testing with cURL

### Get Customer Token
```bash
curl -X POST http://localhost:8000/api/auth/customer/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john.doe@example.com",
    "password": "password123"
  }'
```

Save the token from response.

### Test Profile Endpoint
```bash
curl -X GET http://localhost:8000/api/customer/profile \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### Test Quotes Endpoint
```bash
curl -X GET http://localhost:8000/api/quotes \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### Test Bookings Endpoint
```bash
curl -X GET http://localhost:8000/api/bookings \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### Test Documents Endpoint
```bash
curl -X GET http://localhost:8000/api/documents \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### Test Payments Endpoint
```bash
curl -X GET http://localhost:8000/api/payments \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

---

## Troubleshooting

### Issue: "Unauthorized" Error
**Solution:** 
1. Check if token is valid
2. Verify token is in Authorization header
3. Ensure customer is authenticated

### Issue: No Data Showing
**Solution:**
1. Check if customer has data in database
2. Verify API endpoints are returning data
3. Check browser console for errors
4. Run automated test script

### Issue: "Connection Refused"
**Solution:**
1. Ensure all services are running: `docker-compose ps`
2. Check frontend is on port 5173: `curl http://localhost:5173`
3. Check backend is on port 8000: `curl http://localhost:8000/api/health`

### Issue: Data Not Filtered by Customer
**Solution:**
1. Check controller implementations
2. Verify customer ID is being used in queries
3. Review logs: `docker-compose exec backend tail -f storage/logs/laravel.log`

---

## Test Data

### Test Customer
- **ID:** 1
- **Name:** John Doe
- **Email:** john.doe@example.com
- **Quotes:** 4 (various statuses)
- **Bookings:** 14 (various statuses)
- **Documents:** 15
- **Payments:** 2

### Creating Additional Test Data

#### Create Test Quote
```bash
docker-compose exec backend php artisan tinker --execute="
\$quote = new \App\Models\Quote([
    'customer_id' => 1,
    'quote_reference' => 'QT' . str_pad(rand(1, 9999), 6, '0', STR_PAD_LEFT),
    'status' => 'pending',
    'total_amount' => 2500.00,
    'valid_until' => now()->addDays(30),
]);
\$quote->save();
echo 'Quote created: ' . \$quote->quote_reference . PHP_EOL;
"
```

#### Create Test Booking
```bash
docker-compose exec backend php artisan tinker --execute="
\$booking = new \App\Models\Booking([
    'customer_id' => 1,
    'booking_reference' => 'BK' . str_pad(rand(1, 9999), 6, '0', STR_PAD_LEFT),
    'status' => 'pending',
    'total_amount' => 3500.00,
]);
\$booking->save();
echo 'Booking created: ' . \$booking->booking_reference . PHP_EOL;
"
```

---

## Performance Testing

### Load Time Expectations
- Profile load: < 500ms
- Quotes load: < 1s
- Bookings load: < 1s
- Documents load: < 1s
- Payments load: < 1s

### Monitoring
```bash
# Watch API logs
docker-compose exec backend tail -f storage/logs/laravel.log | grep "API Response"

# Check response times
docker-compose exec backend grep "duration_ms" storage/logs/laravel.log | tail -20
```

---

## Checklist

### Before Testing
- [ ] All Docker containers running
- [ ] Database seeded with test data
- [ ] Frontend accessible at http://localhost:5173
- [ ] Backend accessible at http://localhost:8000

### During Testing
- [ ] All tabs load without errors
- [ ] Data displays correctly
- [ ] No console errors in browser
- [ ] API responses are fast (< 1s)
- [ ] Authentication works properly

### After Testing
- [ ] Document any issues found
- [ ] Verify fixes with automated test
- [ ] Update test data if needed
- [ ] Clear browser cache if necessary

---

## Automated Testing Schedule

### Daily
- Run automated test script
- Check API response times
- Monitor error logs

### Weekly
- Full manual testing of all tabs
- Performance testing
- Security audit

### Monthly
- Load testing with multiple users
- Data integrity checks
- Backup verification

---

**Last Updated:** January 27, 2026
