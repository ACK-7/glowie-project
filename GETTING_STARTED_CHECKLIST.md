# 🚀 Getting Started Checklist

Follow this checklist to get your AI-powered car shipping platform running!

## Prerequisites

- [ ] Python 3.11 or higher installed
- [ ] Node.js 16+ installed
- [ ] PHP 8.1+ installed
- [ ] MySQL running
- [ ] OpenAI API account created

---

## Step 1: Get OpenAI API Key

- [ ] Go to https://platform.openai.com/api-keys
- [ ] Create new API key
- [ ] Copy the key (starts with `sk-`)
- [ ] Keep it safe - you'll need it in Step 4

---

## Step 2: Backend Setup

```bash
cd backend
```

- [ ] Copy `.env.example` to `.env` (if not exists)
- [ ] Update database credentials in `.env`
- [ ] Run `composer install`
- [ ] Run `php artisan key:generate`
- [ ] Run `php artisan migrate`
- [ ] Run `php artisan db:seed`
- [ ] Start server: `php artisan serve`
- [ ] Verify: http://localhost:8000/api/health

---

## Step 3: Frontend Setup

```bash
cd frontend
```

- [ ] Verify `.env` file exists
- [ ] Check `VITE_API_BASE_URL=http://localhost:8000/api`
- [ ] Run `npm install`
- [ ] Start dev server: `npm run dev`
- [ ] Verify: http://localhost:5173

---

## Step 4: AI Service Setup

```bash
cd ai-service
```

- [ ] Run setup script: `python setup.py`
- [ ] Create virtual environment: `python -m venv venv`
- [ ] Activate venv:
  - Windows: `venv\Scripts\activate`
  - Linux/Mac: `source venv/bin/activate`
- [ ] Install dependencies: `pip install -r requirements.txt`
- [ ] Edit `.env` file
- [ ] **Add your OpenAI API key**: `OPENAI_API_KEY=sk-your-key-here`
- [ ] Start service: `uvicorn main:app --reload --port 8001`
- [ ] Verify: http://localhost:8001/health

---

## Step 5: Test Everything

### Test Backend
- [ ] Visit http://localhost:8000/api/health
- [ ] Should see: `{"status":"ok"}`

### Test Frontend
- [ ] Visit http://localhost:5173
- [ ] Homepage loads correctly
- [ ] Navigate to "Get Quote" page
- [ ] Form displays properly

### Test AI Service
- [ ] Visit http://localhost:8001/health
- [ ] Should see: `{"status":"healthy"}`
- [ ] Visit http://localhost:8001/docs
- [ ] Interactive API docs load

### Test Quote Generation
```bash
curl -X POST http://localhost:8001/agents/quote \
  -H "Content-Type: application/json" \
  -d '{
    "vehicle_type": "sedan",
    "year": 2020,
    "make": "Toyota",
    "model": "Camry",
    "origin_country": "Japan",
    "shipping_method": "roro"
  }'
```

- [ ] Quote generated successfully
- [ ] Response includes `quote_reference`
- [ ] Response includes `total_cost`
- [ ] Response includes `ai_reasoning`

---

## Step 6: Test Integration

### Test Frontend → Backend → AI

1. [ ] Open frontend: http://localhost:5173
2. [ ] Go to "Get Quote" page
3. [ ] Fill in vehicle details:
   - Vehicle Type: Sedan
   - Year: 2020
   - Make: Toyota
   - Model: Camry
4. [ ] Fill in shipping details:
   - Origin: Japan
   - Method: RoRo
5. [ ] Fill in your details
6. [ ] Click "Calculate Quote"
7. [ ] Quote should be generated with AI reasoning
8. [ ] Check backend logs for AI service call
9. [ ] Check AI service logs for request processing

---

## Step 7: Test Vehicle Tracking

1. [ ] Go to "Track Shipment" page
2. [ ] Enter tracking number: `TRK00000003`
3. [ ] Click "Track"
4. [ ] Shipment details should load
5. [ ] No CORS errors in console

---

## Step 8: Test Vehicle Pre-fill

1. [ ] Go to "Cars" page
2. [ ] Click "Get Quote" on any vehicle
3. [ ] Quote form should open
4. [ ] Vehicle details should be pre-filled
5. [ ] Year, Make, Model should match the car

---

## Troubleshooting

### Backend Issues

**Port 8000 already in use:**
```bash
php artisan serve --port=8001
# Update frontend .env: VITE_API_BASE_URL=http://localhost:8001/api
```

**Database connection error:**
- Check MySQL is running
- Verify credentials in `backend/.env`
- Run `php artisan migrate`

### Frontend Issues

**Blank page:**
- Check browser console for errors
- Verify backend is running
- Check `.env` file exists

**CORS errors:**
- Restart backend server
- Clear browser cache
- Check CORS middleware in `backend/app/Http/Kernel.php`

### AI Service Issues

**Module not found:**
```bash
# Make sure virtual environment is activated
venv\Scripts\activate  # Windows
pip install -r requirements.txt
```

**OpenAI API error:**
- Check API key is correct in `.env`
- Verify you have credits: https://platform.openai.com/usage
- Check rate limits

**Port 8001 already in use:**
```bash
uvicorn main:app --reload --port 8002
# Update backend .env: LANGGRAPH_SERVICE_URL=http://localhost:8002
```

---

## Optional: Docker Setup

If you prefer Docker:

```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f

# Stop all services
docker-compose down
```

- [ ] All containers running
- [ ] Backend accessible
- [ ] Frontend accessible
- [ ] AI service accessible

---

## Verification Checklist

### All Services Running
- [ ] Backend: http://localhost:8000
- [ ] Frontend: http://localhost:5173
- [ ] AI Service: http://localhost:8001

### Core Features Working
- [ ] User can browse cars
- [ ] User can request quote
- [ ] Quote is generated with AI
- [ ] User can track shipment
- [ ] Vehicle details pre-fill in quote form

### AI Features Working
- [ ] Quote agent generates quotes
- [ ] AI provides pricing reasoning
- [ ] Confidence scores included
- [ ] Cost breakdown accurate

---

## Next Steps After Setup

### Immediate
1. [ ] Test with different vehicle types
2. [ ] Test with different origins
3. [ ] Review AI reasoning quality
4. [ ] Check response times

### This Week
1. [ ] Set up LangSmith monitoring (optional)
2. [ ] Deploy to staging environment
3. [ ] Test with real customer data
4. [ ] Gather user feedback

### This Month
1. [ ] Implement Route Optimization Agent
2. [ ] Implement Document Processing Agent
3. [ ] Implement Customer Support Agent
4. [ ] Production deployment

---

## Success! 🎉

When all checkboxes are ticked, you have:

✅ A fully functional car shipping platform
✅ AI-powered quote generation
✅ Real-time shipment tracking
✅ Modern React frontend
✅ Robust Laravel backend
✅ Intelligent Python AI service

**You're ready to revolutionize car shipping! 🚀**

---

## Need Help?

### Documentation
- `README.md` - Project overview
- `AUTOMATION_PLAN_LANGGRAPH.md` - AI implementation plan
- `AI_SERVICE_SETUP_COMPLETE.md` - AI service details
- `ai-service/README.md` - AI service documentation
- `ai-service/QUICKSTART.md` - Quick start guide

### Logs
- Backend: `backend/storage/logs/laravel.log`
- AI Service: `ai-service/logs/ai-service.log`
- Frontend: Browser console

### API Documentation
- Backend: http://localhost:8000/api/documentation
- AI Service: http://localhost:8001/docs

---

**Happy Shipping! 🚢**
