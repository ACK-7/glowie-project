# Testing Guide: Seamless Portal Access

## 🎯 Quick Test Scenario

Follow this step-by-step guide to test the complete seamless portal access workflow.

---

## Prerequisites

### 1. Start All Services

**Terminal 1 - Backend (Laravel):**
```bash
cd backend
php artisan serve
```
Should run on: `http://localhost:8000`

**Terminal 2 - Frontend (React):**
```bash
cd frontend
npm run dev
```
Should run on: `http://localhost:5173`

**Terminal 3 - AI Service (Python):**
```bash
cd ai-service
python main.py
```
Should run on: `http://localhost:8001`

### 2. Check Email Configuration

Open `backend/.env` and verify:
```env
MAIL_MAILER=log  # For testing, emails will be logged
# OR configure real SMTP for actual email delivery
```

---

## 🧪 Test Scenario: Complete Workflow

### Test 1: Request Quote as New Customer

#### Step 1: Navigate to Quote Page
1. Open browser: `http://localhost:5173`
2. Click "Get Quote" in navigation
3. Or go directly to: `http://localhost:5173/get-quote`

#### Step 2: Fill Out Quote Form

**Vehicle Information:**
- Vehicle Type: `SUV`
- Year: `2020`
- Make: `Toyota`
- Model: `Land Cruiser`
- Engine Size: `4.5`

**Shipping Information:**
- Origin Country: `Japan`
- Shipping Method: `Container`

**Personal Information:**
- Full Name: `John Doe`
- Email: `john.doe@example.com` (use a unique email)
- Phone: `+256700123456`
- Delivery Location: `Kampala, Uganda`

#### Step 3: Submit Quote
1. Click through all steps
2. Click "Get Quote" on final step
3. Wait for AI-powered quote generation

#### Step 4: Verify Success Page
**Expected Results:**
- ✅ Success message with quote reference (e.g., QT2026010001)
- ✅ AI-powered badge (🤖)
- ✅ AI reasoning section with confidence score
- ✅ Cost breakdown displayed
- ✅ **NEW: Green portal access notification box**
- ✅ Message: "Customer Portal Access Created!"
- ✅ Lists portal features

#### Step 5: Check Backend Logs
Open `backend/storage/logs/laravel.log` and look for:
```
🆕 New customer created
customer_id: 1
email: john.doe@example.com
temporary_password: aB3dE5fG7hJ9

✅ Quote created with credentials notification sent
quote_id: 1
customer_email: john.doe@example.com
has_credentials: true
```

**Copy the temporary password from logs!**

#### Step 6: Check Email (if SMTP configured)
If using real SMTP, check email inbox for:
- **Subject:** "Your Quote is Ready - Portal Access Included"
- **Contains:** Login credentials, portal URL, feature list

If using `MAIL_MAILER=log`, check `backend/storage/logs/laravel.log` for email content.

---

### Test 2: Login to Customer Portal

#### Step 1: Navigate to Portal
1. Go to: `http://localhost:5173/customer-portal`
2. Or click "Customer Portal" in navigation

#### Step 2: Login
- Email: `john.doe@example.com`
- Password: `[temporary password from logs]`
- Click "Login"

#### Step 3: Verify Portal Access
**Expected Results:**
- ✅ Successfully logged in
- ✅ Welcome message: "Welcome back, John Doe"
- ✅ Dashboard shows statistics
- ✅ Navigation tabs visible

#### Step 4: View Quote
1. Click "My Quotes" tab
2. **Expected Results:**
   - ✅ Quote visible with reference number
   - ✅ Status badge: "PENDING" (yellow)
   - ✅ Vehicle details displayed
   - ✅ Total amount shown
   - ✅ Message: "⏳ Awaiting approval..."

---

### Test 3: Admin Approves Quote

#### Step 1: Login to Admin Panel
1. Go to: `http://localhost:5173/admin/login`
2. Login with admin credentials
3. Navigate to "Quotes" section

#### Step 2: Approve Quote
1. Find the quote (QT2026010001)
2. Click "Approve" button
3. Add optional notes
4. Confirm approval

#### Step 3: Check Backend Logs
Look for:
```
✅ Quote approved notification sent
quote_id: 1
customer_email: john.doe@example.com
```

#### Step 4: Check Email
- **Subject:** "Your Quote is Approved - Ready to Accept"
- **Contains:** Approval message, call-to-action button
- **Does NOT contain:** Login credentials (customer already has access)

---

### Test 4: Customer Accepts Quote

#### Step 1: Refresh Customer Portal
1. Go back to customer portal
2. Click "My Quotes" tab
3. Refresh page if needed

#### Step 2: Verify Quote Status Changed
**Expected Results:**
- ✅ Status badge: "APPROVED" (green)
- ✅ "Confirm Booking" button visible
- ✅ "View Details" button visible

#### Step 3: Accept Quote
1. Click "Confirm Booking" button
2. Confirm action in popup
3. Wait for processing

#### Step 4: Verify Booking Created
**Expected Results:**
- ✅ Success message: "Quote confirmed successfully"
- ✅ Quote status changes to "CONVERTED" (blue)
- ✅ New booking appears in "Manage Booking" tab
- ✅ Booking confirmation email sent

---

### Test 5: Verify Complete Workflow

#### Customer Portal Checks:
- [ ] Profile tab shows customer information
- [ ] Quotes tab shows all quotes with correct statuses
- [ ] Manage Booking tab shows converted booking
- [ ] Tracking tab available (if shipment created)
- [ ] Documents tab accessible
- [ ] Payments tab accessible

#### Admin Panel Checks:
- [ ] Quote shows as "CONVERTED"
- [ ] Booking created with correct details
- [ ] Customer account exists and is active
- [ ] Activity logs recorded

---

## 🔍 Edge Cases to Test

### Test 6: Existing Customer Requests New Quote

#### Scenario:
Customer who already has an account requests another quote.

#### Steps:
1. Use same email as Test 1: `john.doe@example.com`
2. Fill out new quote form with different vehicle
3. Submit quote

#### Expected Results:
- ✅ Quote created successfully
- ✅ **NO new credentials sent** (customer already has password)
- ✅ Standard quote notification sent
- ✅ Customer can login with existing password
- ✅ Both quotes visible in portal

#### Check Logs:
```
🔄 Existing customer - no credential generation
customer_id: 1
email: john.doe@example.com
```

---

### Test 7: Customer with Temporary Password Requests New Quote

#### Scenario:
Customer has temporary password but hasn't changed it yet.

#### Steps:
1. Use email with temporary password
2. Request new quote

#### Expected Results:
- ✅ New temporary password generated
- ✅ Credentials sent in email
- ✅ Old password invalidated
- ✅ Customer must use new password

---

## 📊 Success Criteria

### ✅ All Tests Pass If:

1. **Quote Creation:**
   - New customers receive credentials immediately
   - Existing customers don't receive duplicate credentials
   - Quote reference generated correctly
   - AI-powered quote works

2. **Portal Access:**
   - Customers can login immediately after quote request
   - Portal shows correct quote status
   - All portal features accessible

3. **Quote Approval:**
   - Admin can approve quotes
   - Simple approval email sent (no credentials)
   - Customer notified of approval

4. **Quote Acceptance:**
   - Customer can accept approved quotes
   - Booking created automatically
   - Status updates correctly

5. **Emails:**
   - Quote created email has credentials (new customers)
   - Quote approved email is simple (no credentials)
   - Booking confirmation email sent
   - All emails properly formatted

---

## 🐛 Troubleshooting

### Issue: No Email Received

**Solution:**
- Check `backend/.env` for `MAIL_MAILER=log`
- Check `backend/storage/logs/laravel.log` for email content
- Verify SMTP settings if using real email

### Issue: Cannot Login to Portal

**Solution:**
- Check backend logs for temporary password
- Verify email address is correct
- Try password reset feature
- Check customer table in database

### Issue: Quote Not Showing in Portal

**Solution:**
- Verify customer is logged in
- Check quote was created (check database)
- Refresh page
- Check browser console for errors

### Issue: AI Quote Not Working

**Solution:**
- Verify AI service is running on port 8001
- Check `ai-service/.env` for valid Mistral API key
- Check AI service logs
- Fallback pricing should still work

### Issue: Approve Button Not Working

**Solution:**
- Verify admin is logged in
- Check quote status is "pending"
- Check browser console for errors
- Verify backend API is accessible

---

## 📝 Test Results Template

```
Date: _______________
Tester: _______________

Test 1: Request Quote as New Customer
[ ] Quote created successfully
[ ] Portal access notification shown
[ ] Credentials logged/emailed
[ ] Notes: _______________

Test 2: Login to Customer Portal
[ ] Login successful
[ ] Quote visible with PENDING status
[ ] Portal features accessible
[ ] Notes: _______________

Test 3: Admin Approves Quote
[ ] Approval successful
[ ] Email sent
[ ] Status updated
[ ] Notes: _______________

Test 4: Customer Accepts Quote
[ ] Booking created
[ ] Status changed to CONVERTED
[ ] Confirmation sent
[ ] Notes: _______________

Test 5: Complete Workflow
[ ] All features working
[ ] No errors in console
[ ] Logs look correct
[ ] Notes: _______________

Test 6: Existing Customer
[ ] No duplicate credentials
[ ] Quote created
[ ] Portal access maintained
[ ] Notes: _______________

Overall Result: [ ] PASS [ ] FAIL
Comments: _______________
```

---

## 🎉 Ready to Test!

Your seamless portal access implementation is complete and ready for comprehensive testing. Follow this guide step-by-step to verify all functionality works as expected.

**Good luck with testing! 🚀**
