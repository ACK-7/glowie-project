# AI Service Restart Guide

## Issue Fixed
The document extraction endpoint now properly accepts multipart/form-data with the `document_type` field.

## Changes Made
1. Updated `main.py` to import `Form` from FastAPI
2. Changed document endpoint parameter from `document_type: str = "bill_of_lading"` to `document_type: str = Form("bill_of_lading")`
3. Added better error logging in backend DocumentController

## How to Restart the AI Service

### Option 1: If running in terminal
1. Stop the current process (Ctrl+C)
2. Restart with:
```bash
cd ai-service
python main.py
```

### Option 2: If running as background process
```bash
# Find the process
tasklist | findstr python

# Kill the process (replace PID with actual process ID)
taskkill /PID <PID> /F

# Restart
cd ai-service
python main.py
```

### Option 3: Using uvicorn directly
```bash
cd ai-service
uvicorn main:app --host 0.0.0.0 --port 8001 --reload
```

## Verify It's Working

### Test 1: Health Check
```bash
curl http://localhost:8001/health
```

Expected response:
```json
{
  "status": "healthy",
  "service": "ShipWithGlowie AI Service",
  "version": "1.0.0"
}
```

### Test 2: Document Endpoint Test
```bash
cd ai-service
python test_document_endpoint.py
```

Expected output:
```
✅ SUCCESS! AI service is working correctly
```

## Troubleshooting

### Error: "python-multipart not installed"
```bash
cd ai-service
pip install python-multipart
```

### Error: "Module not found"
Make sure you're in the virtual environment:
```bash
cd ai-service
# Activate venv
venv\Scripts\activate  # Windows
source venv/bin/activate  # Linux/Mac

# Install dependencies
pip install -r requirements.txt
```

### Error: "Port 8001 already in use"
```bash
# Find and kill the process using port 8001
netstat -ano | findstr :8001
taskkill /PID <PID> /F
```

## Testing the Full Flow

1. Start AI service (port 8001)
2. Start Laravel backend (port 8000)
3. Start React frontend (port 5173)
4. Login as admin
5. Go to Document Manager
6. Upload a document
7. Click the three-dot menu on the document
8. Click "Extract Data (AI)" with robot icon
9. Should see extracted data with confidence score

## Expected Behavior

When you click "Extract Data (AI)":
1. Frontend sends POST to `http://localhost:8000/api/documents/{id}/extract`
2. Backend reads the document file from storage
3. Backend sends multipart request to `http://localhost:8001/agents/document`
4. AI service extracts text using OCR (pytesseract)
5. AI service uses Mistral AI to structure the data
6. Backend returns extracted data to frontend
7. Frontend displays data in a modal with confidence score

## Logs to Check

### AI Service Logs
Look for:
```
Processing document: filename.pdf, type: passport
Extracted 1234 characters from document
Successfully parsed JSON with 8 fields
```

### Backend Logs
Check `storage/logs/laravel.log` for:
```
AI service request failed
Document processing error
```
