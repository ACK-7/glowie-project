# System Architecture Overview

## High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         USER INTERFACE                           │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │              React Frontend (Port 5173)                   │  │
│  │                                                            │  │
│  │  • Home Page          • Quote Form                        │  │
│  │  • Car Listings       • Tracking Page                     │  │
│  │  • Customer Portal    • Admin Dashboard                   │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              ↓ HTTP/REST
┌─────────────────────────────────────────────────────────────────┐
│                      BACKEND API LAYER                           │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │            Laravel Backend (Port 8000)                    │  │
│  │                                                            │  │
│  │  • RESTful API        • Authentication                    │  │
│  │  • Business Logic     • Data Validation                   │  │
│  │  • Database ORM       • File Management                   │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                    ↓                           ↓
        ┌───────────────────┐       ┌──────────────────────┐
        │   MySQL Database  │       │  LangGraph Service   │
        │   (Port 3306)     │       │  (Port 8001)         │
        │                   │       │                      │
        │  • Customers      │       │  AI Orchestration    │
        │  • Shipments      │       │                      │
        │  • Quotes         │       └──────────────────────┘
        │  • Bookings       │                 ↓
        │  • Documents      │       ┌──────────────────────┐
        └───────────────────┘       │   OpenAI GPT-4       │
                                    │   External APIs      │
                                    └──────────────────────┘
```

## AI Service Architecture (LangGraph)

```
┌─────────────────────────────────────────────────────────────────┐
│                    AI SERVICE (Python/FastAPI)                   │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                    API Endpoints                          │  │
│  │  /agents/quote  /agents/route  /agents/document          │  │
│  │  /agents/support  /agents/delay  /health                 │  │
│  └──────────────────────────────────────────────────────────┘  │
│                              ↓                                   │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                  LangGraph Agents                         │  │
│  │                                                            │  │
│  │  ┌────────────┐  ┌────────────┐  ┌────────────┐         │  │
│  │  │   Quote    │  │   Route    │  │  Document  │         │  │
│  │  │   Agent    │  │   Agent    │  │   Agent    │         │  │
│  │  │            │  │            │  │            │         │  │
│  │  │ ✅ WORKING │  │ 🔄 TODO    │  │ 🔄 TODO    │         │  │
│  │  └────────────┘  └────────────┘  └────────────┘         │  │
│  │                                                            │  │
│  │  ┌────────────┐  ┌────────────┐  ┌────────────┐         │  │
│  │  │  Support   │  │   Delay    │  │Notification│         │  │
│  │  │   Agent    │  │   Agent    │  │   Agent    │         │  │
│  │  │            │  │            │  │            │         │  │
│  │  │ 🔄 TODO    │  │ 🔄 TODO    │  │ 🔄 TODO    │         │  │
│  │  └────────────┘  └────────────┘  └────────────┘         │  │
│  └──────────────────────────────────────────────────────────┘  │
│                              ↓                                   │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                      Tools Layer                          │  │
│  │                                                            │  │
│  │  • Laravel API Client    • Google Maps API                │  │
│  │  • OCR Service          • Weather API                     │  │
│  │  • Email Service        • SMS Service                     │  │
│  └──────────────────────────────────────────────────────────┘  │
│                              ↓                                   │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                   Infrastructure                          │  │
│  │                                                            │  │
│  │  • Redis (Caching)      • MySQL (Data)                    │  │
│  │  • Logging (Loguru)     • Monitoring (LangSmith)          │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

## Quote Agent Workflow (LangGraph)

```
                    ┌─────────────────┐
                    │  Quote Request  │
                    └────────┬────────┘
                             ↓
                    ┌─────────────────┐
                    │ Validate Input  │
                    │                 │
                    │ • Check year    │
                    │ • Check make    │
                    │ • Check model   │
                    └────────┬────────┘
                             ↓
                    ┌─────────────────┐
                    │Calculate Base   │
                    │     Cost        │
                    │                 │
                    │ • Country rates │
                    │ • Vehicle type  │
                    │ • Method        │
                    └────────┬────────┘
                             ↓
                    ┌─────────────────┐
                    │  Apply AI       │
                    │   Pricing       │
                    │                 │
                    │ • GPT-4 analysis│
                    │ • Market factors│
                    │ • Reasoning     │
                    └────────┬────────┘
                             ↓
                    ┌─────────────────┐
                    │   Generate      │
                    │   Breakdown     │
                    │                 │
                    │ • Shipping      │
                    │ • Customs       │
                    │ • VAT & Levies  │
                    └────────┬────────┘
                             ↓
                    ┌─────────────────┐
                    │  Save Quote     │
                    │                 │
                    │ • Generate ref  │
                    │ • Store data    │
                    │ • Return result │
                    └────────┬────────┘
                             ↓
                    ┌─────────────────┐
                    │ Quote Response  │
                    │                 │
                    │ • Reference #   │
                    │ • Total cost    │
                    │ • AI reasoning  │
                    │ • Confidence    │
                    └─────────────────┘
```

## Data Flow

### 1. Quote Generation Flow

```
User (Frontend)
    ↓ Submit quote form
Laravel Backend
    ↓ Validate & prepare data
    ↓ Call LangGraphService
AI Service (Python)
    ↓ Quote Agent workflow
    ↓ LangGraph state machine
    ↓ Call OpenAI GPT-4
OpenAI API
    ↓ AI analysis & reasoning
AI Service
    ↓ Generate quote
    ↓ Return response
Laravel Backend
    ↓ Save to database
    ↓ Send email notification
User (Frontend)
    ↓ Display quote
```

### 2. Shipment Tracking Flow

```
User (Frontend)
    ↓ Enter tracking number
Laravel Backend
    ↓ Query database
    ↓ Get shipment details
    ↓ Return JSON
User (Frontend)
    ↓ Display tracking info
    ↓ Show map & timeline
```

### 3. Vehicle Pre-fill Flow

```
User (Frontend - Cars Page)
    ↓ Click "Get Quote" on vehicle
Frontend
    ↓ Navigate to /quote?vehicle=slug
    ↓ Extract vehicle slug from URL
    ↓ Fetch vehicle details from API
Laravel Backend
    ↓ Return vehicle data
Frontend
    ↓ Pre-fill quote form
User
    ↓ Complete remaining fields
    ↓ Submit quote
```

## Technology Stack

### Frontend
- **Framework**: React 18
- **Build Tool**: Vite
- **Routing**: React Router
- **HTTP Client**: Axios
- **Styling**: Tailwind CSS
- **Icons**: React Icons

### Backend
- **Framework**: Laravel 10
- **Database**: MySQL 8
- **Authentication**: Laravel Sanctum
- **API**: RESTful
- **Caching**: Redis (optional)

### AI Service
- **Framework**: FastAPI
- **AI Orchestration**: LangGraph
- **LLM**: OpenAI GPT-4
- **Language**: Python 3.11
- **Caching**: Redis
- **Logging**: Loguru
- **Validation**: Pydantic

### Infrastructure
- **Containerization**: Docker
- **Orchestration**: Docker Compose
- **Web Server**: Nginx (production)
- **Process Manager**: Supervisor (production)

## Security Layers

```
┌─────────────────────────────────────────┐
│         Security Measures                │
├─────────────────────────────────────────┤
│ 1. HTTPS/TLS Encryption                 │
│ 2. CORS Configuration                   │
│ 3. API Authentication (Sanctum)         │
│ 4. Rate Limiting                        │
│ 5. Input Validation (Pydantic)          │
│ 6. SQL Injection Prevention (ORM)       │
│ 7. XSS Protection                       │
│ 8. CSRF Protection                      │
│ 9. Environment Variables                │
│ 10. API Key Management                  │
└─────────────────────────────────────────┘
```

## Scalability Strategy

### Horizontal Scaling
- **Frontend**: CDN + Multiple instances
- **Backend**: Load balancer + Multiple Laravel instances
- **AI Service**: Multiple Python instances
- **Database**: Read replicas

### Vertical Scaling
- **Database**: Increase resources
- **AI Service**: GPU instances for ML models
- **Caching**: Redis cluster

### Performance Optimization
- **Caching**: Redis for frequent queries
- **CDN**: Static assets
- **Database**: Indexes & query optimization
- **API**: Response caching
- **AI**: Prompt caching & model selection

## Monitoring & Observability

```
┌─────────────────────────────────────────┐
│         Monitoring Stack                 │
├─────────────────────────────────────────┤
│ • LangSmith (AI agent tracing)          │
│ • Laravel Logs (backend errors)         │
│ • Loguru (AI service logs)              │
│ • Health Check Endpoints                │
│ • Performance Metrics                   │
│ • Error Tracking                        │
│ • API Usage Analytics                   │
└─────────────────────────────────────────┘
```

## Deployment Architecture

### Development
```
Local Machine
├── Backend (localhost:8000)
├── Frontend (localhost:5173)
└── AI Service (localhost:8001)
```

### Production
```
Cloud Infrastructure (AWS/Azure/DO)
├── Load Balancer
├── Frontend (CDN + Static hosting)
├── Backend (Multiple instances)
├── AI Service (Multiple instances)
├── Database (Primary + Replicas)
├── Redis Cluster
└── File Storage (S3/Blob)
```

## Cost Structure

### Development
- **Total**: $50-100/month
  - OpenAI API: $50-100
  - Infrastructure: $0 (local)

### Production
- **Total**: $915-2330/month
  - OpenAI API: $750-2000
  - Infrastructure: $165-330
    - Compute: $100-200
    - Database: $30-50
    - Redis: $15-30
    - Storage: $10-20
    - Bandwidth: $10-30

## Future Enhancements

### Phase 2 (Months 2-3)
- Route Optimization Agent
- Document Processing Agent
- Advanced caching strategies

### Phase 3 (Months 4-6)
- Customer Support Agent (RAG)
- Delay Prediction Agent (ML)
- Real-time notifications

### Phase 4 (Months 7-12)
- Custom ML models
- Multi-language support
- Advanced analytics dashboard
- Mobile app integration

---

**This architecture provides a solid foundation for a scalable, intelligent car shipping platform!**
