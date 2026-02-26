# Quick Start Guide

Get the AI service running in 5 minutes!

## Prerequisites

- Python 3.11 or higher
- OpenAI API key
- Redis (optional, for caching)

## Step 1: Setup

```bash
cd ai-service

# Run setup script
python setup.py

# Create virtual environment
python -m venv venv

# Activate virtual environment
# Windows:
venv\Scripts\activate
# Linux/Mac:
source venv/bin/activate

# Install dependencies
pip install -r requirements.txt
```

## Step 2: Configure

Edit `.env` file and add your OpenAI API key:

```env
OPENAI_API_KEY=sk-your-key-here
```

## Step 3: Run

```bash
# Start the service
uvicorn main:app --reload --port 8001
```

The service will be available at: http://localhost:8001

## Step 4: Test

Open your browser and visit:
- Health check: http://localhost:8001/health
- API docs: http://localhost:8001/docs

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

## Step 5: Integrate with Laravel

Add to your Laravel `.env`:

```env
LANGGRAPH_SERVICE_URL=http://localhost:8001
```

Create Laravel service:

```php
// app/Services/LangGraphService.php
$langGraph = new LangGraphService();
$quote = $langGraph->generateQuote($data);
```

## Troubleshooting

### Port already in use
```bash
# Use different port
uvicorn main:app --reload --port 8002
```

### OpenAI API errors
- Check your API key is valid
- Ensure you have credits in your OpenAI account
- Check rate limits

### Redis connection errors
- Redis is optional for development
- Service will work without Redis (no caching)

## Next Steps

1. Read `README.md` for full documentation
2. Check `AUTOMATION_PLAN_LANGGRAPH.md` for implementation roadmap
3. Implement remaining agents (Route, Document, etc.)
4. Set up monitoring with LangSmith

## Support

For issues, check the logs in `logs/ai-service.log`
