# 🚀 Quick Reference Card

## Start Services

```bash
# Terminal 1 - Backend
cd backend && php artisan serve

# Terminal 2 - Frontend  
cd frontend && npm run dev

# Terminal 3 - AI Service
cd ai-service && python main.py
```

## URLs

| Service | URL | Status |
|---------|-----|--------|
| Frontend | http://localhost:5173 | ✅ |
| Backend API | http://localhost:8000/api | ✅ |
| AI Service | http://localhost:8001 | ✅ |
| Customer Portal | http://localhost:5173/customer-portal | ✅ |
| Admin Panel | http://localhost:5173/admin/login | ✅ |

## Test Workflow

1. **Request Quote** → http://localhost:5173/get-quote
2. **Check Logs** → `backend/storage/logs/laravel.log` (find password)
3. **Login Portal** → http://localhost:5173/customer-portal
4. **View Quote** → Status: PENDING
5. **Admin Approve** → http://localhost:5173/admin/login
6. **Customer Accept** → Click "Confirm Booking"
7. **Done!** → Booking created ✅

## Find Credentials

```bash
# In backend logs
grep "temporary_password" backend/storage/logs/laravel.log

# Last 20 lines
tail -20 backend/storage/logs/laravel.log
```

## Test Emails

```bash
# View email content
grep -A 50 "EMAIL NOTIFICATION" backend/storage/logs/laravel.log
```

## Quick Database Check

```bash
cd backend
php artisan tinker

# Check last customer
>>> \App\Models\Customer::latest()->first()

# Check last quote
>>> \App\Models\Quote::latest()->first()

# Check last booking
>>> \App\Models\Booking::latest()->first()
```

## Health Checks

```bash
# Backend
curl http://localhost:8000/api/health

# AI Service
curl http://localhost:8001/health

# Frontend (open in browser)
http://localhost:5173
```

## Common Commands

```bash
# Clear Laravel cache
cd backend
php artisan cache:clear
php artisan config:clear

# View logs in real-time
tail -f backend/storage/logs/laravel.log

# Restart AI service
cd ai-service
python main.py
```

## Test Data

**New Customer:**
- Name: John Doe
- Email: john.doe@example.com
- Phone: +256700123456

**Vehicle:**
- Type: SUV
- Year: 2020
- Make: Toyota
- Model: Land Cruiser

**Shipping:**
- Origin: Japan
- Method: Container

## Key Features

✅ Immediate portal access
✅ AI-powered quotes
✅ Self-service acceptance
✅ Global chatbot
✅ Real-time tracking
✅ Document upload
✅ Email notifications

## Documentation

- `SYSTEM_READY_FOR_TESTING.md` - Overview
- `TESTING_SEAMLESS_PORTAL_ACCESS.md` - Detailed testing
- `SEAMLESS_PORTAL_ACCESS_IMPLEMENTATION.md` - Technical details
- `POSTMAN_TESTING_GUIDE.md` - API testing

## Status: ✅ READY FOR TESTING!
