# ShipWithGlowie AI Service

AI-powered automation service using LangGraph for car shipping logistics.

## Features

- **Quote Generation Agent**: AI-powered shipping cost estimation
- **Route Optimization Agent**: Intelligent route planning (Coming soon)
- **Document Processing Agent**: OCR and validation (Coming soon)
- **Delay Prediction Agent**: ML-based delay forecasting (Coming soon)
- **Customer Support Agent**: RAG-based support chatbot (Coming soon)
- **Notification Agent**: Smart notification routing (Coming soon)

## Tech Stack

- **Framework**: FastAPI
- **AI Orchestration**: LangGraph
- **LLM**: OpenAI GPT-4
- **Caching**: Redis
- **Database**: MySQL (via Laravel API)

## Setup

### 1. Install Dependencies

```bash
# Create virtual environment
python -m venv venv

# Activate virtual environment
# Windows
venv\Scripts\activate
# Linux/Mac
source venv/bin/activate

# Install dependencies
pip install -r requirements.txt
```

### 2. Configure Environment

```bash
# Copy example env file
cp .env.example .env

# Edit .env and add your API keys
# Required: OPENAI_API_KEY
```

### 3. Run Service

```bash
# Development mode
uvicorn main:app --reload --port 8001

# Production mode
uvicorn main:app --host 0.0.0.0 --port 8001
```

### 4. Using Docker

```bash
# Build and run
docker-compose up --build

# Run in background
docker-compose up -d

# View logs
docker-compose logs -f ai-service

# Stop
docker-compose down
```

## API Endpoints

### Health Check
```
GET /health
```

### Quote Generation
```
POST /agents/quote
Content-Type: application/json

{
  "vehicle_type": "sedan",
  "year": 2020,
  "make": "Toyota",
  "model": "Camry",
  "origin_country": "Japan",
  "shipping_method": "roro"
}
```

### Route Optimization
```
POST /agents/route
Content-Type: application/json

{
  "shipment_id": 123,
  "origin": "Tokyo",
  "destination": "Kampala"
}
```

### Document Processing
```
POST /agents/document
Content-Type: multipart/form-data

file: [document file]
document_type: "bill_of_lading"
```

### Customer Support
```
POST /agents/support
Content-Type: application/json

{
  "query": "Where is my shipment?",
  "customer_id": 456
}
```

## Development

### Project Structure

```
ai-service/
├── agents/           # LangGraph agents
│   ├── quote_agent.py
│   ├── route_agent.py
│   ├── document_agent.py
│   ├── support_agent.py
│   ├── delay_agent.py
│   └── notification_agent.py
├── config/           # Configuration
│   └── settings.py
├── models/           # Pydantic schemas
│   └── schemas.py
├── tools/            # External API clients
│   └── laravel_api.py
├── utils/            # Utilities
│   ├── database.py
│   ├── redis_client.py
│   ├── logger.py
│   └── helpers.py
├── tests/            # Tests
├── main.py           # FastAPI app
├── requirements.txt
└── Dockerfile
```

### Running Tests

```bash
pytest tests/ -v
```

### Code Quality

```bash
# Format code
black .

# Lint
flake8 .

# Type checking
mypy .
```

## Integration with Laravel

The AI service communicates with the Laravel backend via REST API.

### Laravel Service Class

```php
// app/Services/LangGraphService.php
$langGraph = new LangGraphService();
$quote = $langGraph->generateQuote($data);
```

See `AUTOMATION_PLAN_LANGGRAPH.md` for detailed integration guide.

## Monitoring

### LangSmith (Optional)

Enable LangSmith for agent monitoring:

```env
LANGCHAIN_TRACING_V2=true
LANGCHAIN_API_KEY=your_key_here
LANGCHAIN_PROJECT=shipwithglowie
```

View traces at: https://smith.langchain.com

## Cost Optimization

- Responses are cached in Redis
- Use GPT-3.5 for simple tasks
- Batch similar requests
- Monitor token usage via LangSmith

## Roadmap

- [x] Quote Generation Agent
- [ ] Route Optimization Agent
- [ ] Document Processing Agent
- [ ] Delay Prediction Agent
- [ ] Customer Support Agent
- [ ] Notification Agent
- [ ] ML Model Training Pipeline
- [ ] Advanced Analytics Dashboard

## Support

For issues or questions, contact the development team.

## License

Proprietary - ShipWithGlowie
