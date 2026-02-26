# Postman Testing Guide - AI-Powered Quote System

## Overview
This guide shows you how to test the AI-powered quote generation system using Postman.

---

## Prerequisites

1. **AI Service Running** on port 8001
   ```bash
   cd ai-service
   python main.py
   ```

2. **Laravel Backend Running** on port 8000
   ```bash
   cd backend
   php artisan serve
   ```

3. **Postman Installed** - Download from https://www.postman.com/downloads/

---

## Test 1: Health Check - AI Service

### Request Details
- **Method:** GET
- **URL:** `http://127.0.0.1:8001/health`
- **Headers:** None required

### Expected Response (200 OK)
```json
{
  "status": "healthy",
  "service": "ShipWithGlowie AI Service",
  "version": "1.0.0",
  "timestamp": "2026-02-22T23:14:08.123456"
}
```

### Screenshot Instructions
1. Open Postman
2. Create new request
3. Set method to GET
4. Enter URL: `http://127.0.0.1:8001/health`
5. Click "Send"
6. Verify status is 200 OK

---

## Test 2: Direct AI Quote Generation (AI Service)

### Request Details
- **Method:** POST
- **URL:** `http://127.0.0.1:8001/agents/quote`
- **Headers:**
  ```
  Content-Type: application/json
  ```

### Request Body (JSON)
```json
{
  "vehicle_type": "suv",
  "year": 2020,
  "make": "Toyota",
  "model": "Land Cruiser",
  "engine_size": 4500,
  "origin_country": "japan",
  "origin_port": "Tokyo",
  "destination_country": "Uganda",
  "destination_port": "Port Bell",
  "shipping_method": "roro",
  "customer_email": "test@example.com",
  "customer_name": "John Doe"
}
```

### Expected Response (200 OK)
```json
{
  "success": true,
  "quote_reference": "QTE-20260222231408-3MQJA4",
  "base_cost": 1500.0,
  "adjusted_cost": 1575.0,
  "total_cost": 3143.0,
  "breakdown": {
    "shipping": 1575.0,
    "customs_duty": 800,
    "vat": 418.5,
    "levies": 350,
    "total": 3143.0
  },
  "ai_reasoning": "Based on current market conditions for February, this 2020 Toyota Land Cruiser from Japan represents standard demand. The vehicle's value and route popularity justify a moderate pricing adjustment of +5%. Seasonal factors are neutral this month.",
  "estimated_delivery_days": 45,
  "confidence_score": 0.85,
  "created_at": "2026-02-22T23:14:08.123456"
}
```

### Postman Steps
1. Create new POST request
2. URL: `http://127.0.0.1:8001/agents/quote`
3. Go to "Headers" tab
4. Add: `Content-Type: application/json`
5. Go to "Body" tab
6. Select "raw" and "JSON"
7. Paste the request body above
8. Click "Send"
9. Verify you get AI reasoning and confidence score

---

## Test 3: Full Laravel Quote Creation (Integrated)

### Request Details
- **Method:** POST
- **URL:** `http://localhost:8000/api/quotes`
- **Headers:**
  ```
  Content-Type: application/json
  Accept: application/json
  ```

### Request Body (JSON)
```json
{
  "vehicleType": "suv",
  "year": 2020,
  "make": "Toyota",
  "model": "Land Cruiser",
  "engineSize": 4500,
  "originCountry": "japan",
  "originPort": "Tokyo",
  "shippingMethod": "roro",
  "fullName": "John Doe",
  "email": "john.doe@example.com",
  "phone": "+256700000000",
  "deliveryLocation": "Kampala",
  "additionalInfo": "Please handle with care"
}
```

### Expected Response (201 Created)
```json
{
  "message": "Quote created successfully",
  "quote_id": 123,
  "reference": "QTE-20260222231408-3MQJA4",
  "total_estimated": 3143,
  "ai_powered": true,
  "ai_reasoning": "Based on current market conditions for February, this 2020 Toyota Land Cruiser from Japan represents standard demand. The vehicle's value and route popularity justify a moderate pricing adjustment of +5%. Seasonal factors are neutral this month.",
  "confidence_score": 0.85,
  "breakdown": {
    "shipping": 1575,
    "customs_duty": 800,
    "vat": 418.5,
    "levies": 350,
    "total": 3143
  }
}
```

### Postman Steps
1. Create new POST request
2. URL: `http://localhost:8000/api/quotes`
3. Go to "Headers" tab
4. Add:
   - `Content-Type: application/json`
   - `Accept: application/json`
5. Go to "Body" tab
6. Select "raw" and "JSON"
7. Paste the request body above
8. Click "Send"
9. Verify response includes:
   - ✅ `ai_powered: true`
   - ✅ `ai_reasoning` with explanation
   - ✅ `confidence_score` (0-1)
   - ✅ Complete `breakdown` object

---

## Test 4: Fallback Test (AI Service Down)

### Purpose
Test that the system gracefully handles AI service failure.

### Steps
1. **Stop the AI service** (Ctrl+C in AI service terminal)
2. **Send the same request** from Test 3
3. **Verify fallback response:**

### Expected Response (201 Created)
```json
{
  "message": "Quote created successfully",
  "quote_id": 124,
  "reference": "QTE-20260222231510-5XYZAB",
  "total_estimated": 2968,
  "ai_powered": false,
  "ai_reasoning": "Standard pricing applied (AI service unavailable)",
  "confidence_score": 0.5,
  "breakdown": {
    "shipping": 1500,
    "customs_duty": 800,
    "vat": 414,
    "levies": 350,
    "total": 2968
  }
}
```

### Key Observations
- ✅ Quote still created successfully
- ✅ `ai_powered: false`
- ✅ Fallback reasoning message
- ✅ Lower confidence score (0.5)
- ✅ Manual calculation used

---

## Test 5: Different Vehicle Types

Test AI pricing adjustments for different vehicle types.

### Test 5a: Sedan (Lower Cost)
```json
{
  "vehicleType": "sedan",
  "year": 2018,
  "make": "Honda",
  "model": "Accord",
  "engineSize": 2000,
  "originCountry": "japan",
  "shippingMethod": "roro",
  "fullName": "Jane Smith",
  "email": "jane@example.com",
  "phone": "+256700000001",
  "deliveryLocation": "Entebbe"
}
```

**Expected:** Lower shipping cost (sedan multiplier: 1.0)

### Test 5b: Luxury Car (Higher Cost)
```json
{
  "vehicleType": "luxury",
  "year": 2022,
  "make": "Mercedes-Benz",
  "model": "S-Class",
  "engineSize": 3000,
  "originCountry": "uk",
  "shippingMethod": "container",
  "fullName": "Robert Johnson",
  "email": "robert@example.com",
  "phone": "+256700000002",
  "deliveryLocation": "Kampala"
}
```

**Expected:** Higher shipping cost (luxury multiplier: 1.5, container +$500)

### Test 5c: Motorcycle (Lowest Cost)
```json
{
  "vehicleType": "motorcycle",
  "year": 2021,
  "make": "Yamaha",
  "model": "R1",
  "engineSize": 1000,
  "originCountry": "uae",
  "shippingMethod": "roro",
  "fullName": "Mike Wilson",
  "email": "mike@example.com",
  "phone": "+256700000003",
  "deliveryLocation": "Jinja"
}
```

**Expected:** Lowest shipping cost (motorcycle multiplier: 0.7)

---

## Test 6: Different Origin Countries

### Test 6a: Japan (Standard)
```json
{
  "originCountry": "japan",
  "shippingMethod": "roro"
}
```
**Expected Base Rate:** $1,500 (RoRo) or $2,200 (Container)

### Test 6b: UK (Higher)
```json
{
  "originCountry": "uk",
  "shippingMethod": "roro"
}
```
**Expected Base Rate:** $1,800 (RoRo) or $2,800 (Container)

### Test 6c: UAE (Lower)
```json
{
  "originCountry": "uae",
  "shippingMethod": "roro"
}
```
**Expected Base Rate:** $1,100 (RoRo) or $1,600 (Container)

---

## Test 7: Container vs RoRo Shipping

### Test 7a: RoRo Shipping
```json
{
  "shippingMethod": "roro",
  "originCountry": "japan"
}
```
**Expected:** Base rate only ($1,500)

### Test 7b: Container Shipping
```json
{
  "shippingMethod": "container",
  "originCountry": "japan"
}
```
**Expected:** Base rate + $500 markup ($2,000)

---

## Postman Collection Setup

### Create a Collection
1. Click "New" → "Collection"
2. Name it: "ShipWithGlowie AI Quote System"
3. Add all tests above as requests

### Environment Variables
Create environment with these variables:

| Variable | Value |
|----------|-------|
| `ai_service_url` | `http://127.0.0.1:8001` |
| `backend_url` | `http://localhost:8000` |
| `test_email` | `test@example.com` |

### Use Variables in Requests
- AI Service: `{{ai_service_url}}/agents/quote`
- Backend: `{{backend_url}}/api/quotes`

---

## Validation Checklist

For each test, verify:

### AI Service Direct Test
- [ ] Status code is 200
- [ ] Response includes `success: true`
- [ ] `ai_reasoning` is present and meaningful
- [ ] `confidence_score` is between 0 and 1
- [ ] `quote_reference` follows format: QTE-YYYYMMDDHHMMSS-XXXXX
- [ ] `breakdown` object has all cost components
- [ ] `estimated_delivery_days` is reasonable (30-50 days)

### Laravel Integration Test
- [ ] Status code is 201
- [ ] Response includes `quote_id`
- [ ] `reference` is generated
- [ ] `ai_powered` is true (when AI service is up)
- [ ] `ai_reasoning` explains pricing decision
- [ ] `confidence_score` is present
- [ ] `breakdown` shows all fees
- [ ] Total matches sum of breakdown components

### Fallback Test
- [ ] Status code is still 201 (success)
- [ ] `ai_powered` is false
- [ ] Fallback reasoning message appears
- [ ] Manual calculation is used
- [ ] Quote is still saved to database

---

## Common Issues & Solutions

### Issue 1: Connection Refused (AI Service)
**Error:** `Failed to connect to 127.0.0.1:8001`

**Solution:**
```bash
# Check if AI service is running
curl http://127.0.0.1:8001/health

# If not, start it
cd ai-service
python main.py
```

### Issue 2: Route Not Found (Laravel)
**Error:** `404 Not Found`

**Solution:**
```bash
# Check Laravel routes
cd backend
php artisan route:list | grep quotes

# Verify backend is running
curl http://localhost:8000/api/health
```

### Issue 3: Validation Errors
**Error:** `422 Unprocessable Entity`

**Solution:** Check required fields:
- `vehicleType`, `year`, `make` (vehicle info)
- `originCountry`, `shippingMethod` (shipping)
- `fullName`, `email`, `phone` (contact)

### Issue 4: AI Service Timeout
**Error:** `Request timeout`

**Solution:**
- Check Mistral API key in `ai-service/.env`
- Verify internet connection
- Check AI service logs for errors

### Issue 5: Database Error
**Error:** `SQLSTATE[HY000]`

**Solution:**
```bash
# Run migrations
cd backend
php artisan migrate

# Seed routes if needed
php artisan db:seed --class=RouteSeeder
```

---

## Expected AI Reasoning Examples

The AI should provide reasoning like:

### Example 1: Standard Pricing
```
"Based on current market conditions for February, this 2020 Toyota Land Cruiser 
from Japan represents standard demand. The vehicle's value and route popularity 
justify a moderate pricing adjustment of +5%. Seasonal factors are neutral this month."
```

### Example 2: High Demand
```
"This luxury Mercedes-Benz S-Class from UK requires premium handling and insurance. 
Current high demand for luxury vehicles from Europe warrants a +15% adjustment. 
Container shipping provides optimal protection for this high-value vehicle."
```

### Example 3: Low Season
```
"February typically sees lower shipping volumes from UAE. This Honda Accord benefits 
from a -10% seasonal discount. The sedan category and efficient engine size contribute 
to competitive pricing."
```

---

## Performance Benchmarks

Expected response times:

| Endpoint | Expected Time | Acceptable Max |
|----------|---------------|----------------|
| AI Service Health | < 100ms | 500ms |
| AI Quote Generation | 2-5 seconds | 10 seconds |
| Laravel Quote (with AI) | 3-6 seconds | 15 seconds |
| Laravel Quote (fallback) | < 1 second | 3 seconds |

---

## Postman Tests (Automated)

Add these test scripts in Postman:

### Test Script for Laravel Quote
```javascript
// Test status code
pm.test("Status code is 201", function () {
    pm.response.to.have.status(201);
});

// Test AI powered
pm.test("Quote is AI powered", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.ai_powered).to.be.true;
});

// Test AI reasoning exists
pm.test("AI reasoning is present", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.ai_reasoning).to.be.a('string');
    pm.expect(jsonData.ai_reasoning.length).to.be.above(10);
});

// Test confidence score
pm.test("Confidence score is valid", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.confidence_score).to.be.a('number');
    pm.expect(jsonData.confidence_score).to.be.within(0, 1);
});

// Test breakdown exists
pm.test("Breakdown is complete", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.breakdown).to.have.property('shipping');
    pm.expect(jsonData.breakdown).to.have.property('customs_duty');
    pm.expect(jsonData.breakdown).to.have.property('vat');
    pm.expect(jsonData.breakdown).to.have.property('levies');
    pm.expect(jsonData.breakdown).to.have.property('total');
});

// Test reference format
pm.test("Reference has correct format", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.reference).to.match(/^QTE-\d{14}-[A-Z0-9]{6}$/);
});
```

---

## Export/Import Postman Collection

### Export Collection
1. Click "..." next to collection name
2. Select "Export"
3. Choose "Collection v2.1"
4. Save as `ShipWithGlowie_AI_Quotes.postman_collection.json`

### Import Collection
1. Click "Import" button
2. Drag and drop the JSON file
3. All requests will be imported

---

## Quick Test Sequence

Run these tests in order:

1. ✅ AI Service Health Check
2. ✅ Direct AI Quote (verify AI works)
3. ✅ Laravel Quote - Japan RoRo SUV
4. ✅ Laravel Quote - UK Container Luxury
5. ✅ Laravel Quote - UAE RoRo Sedan
6. ✅ Stop AI Service
7. ✅ Laravel Quote - Fallback Test
8. ✅ Restart AI Service
9. ✅ Laravel Quote - Verify AI restored

---

## Success Criteria

Your integration is working correctly if:

- ✅ All health checks return 200
- ✅ AI service generates quotes with reasoning
- ✅ Laravel creates quotes with AI data
- ✅ Confidence scores are reasonable (0.7-0.9)
- ✅ AI reasoning is meaningful and specific
- ✅ Fallback works when AI is down
- ✅ Different vehicle types get different pricing
- ✅ Different origins affect base rates
- ✅ Container shipping adds markup
- ✅ All quotes save to database

---

## Next Steps After Testing

1. **Monitor Logs:**
   - AI Service: Check console output
   - Laravel: `tail -f backend/storage/logs/laravel.log`

2. **Database Verification:**
   ```sql
   SELECT id, quote_reference, total_amount, vehicle_details 
   FROM quotes 
   ORDER BY created_at DESC 
   LIMIT 5;
   ```

3. **Frontend Testing:**
   - Open http://localhost:5173/get-quote
   - Submit form and verify AI reasoning displays

4. **Production Checklist:**
   - [ ] Set proper MISTRAL_API_KEY
   - [ ] Configure LANGGRAPH_URL for production
   - [ ] Set up monitoring/alerting
   - [ ] Test fallback mechanism
   - [ ] Load test AI service

---

## Support

If you encounter issues:
1. Check service logs
2. Verify environment variables
3. Test each service independently
4. Review `AI_INTEGRATION_COMPLETE.md`
5. Check Laravel logs for detailed errors

Happy Testing! 🚀
