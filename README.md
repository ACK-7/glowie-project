< align="center">

# 🚢 ShipWithGlowie

## A comprehensive, full-stack vehicle shipping logistics platform with AI-powered quoting, intelligent route optimization, real-time shipment tracking, and an integrated customer portal.

## 📋 Table of Contents

- [Overview](#-overview)
- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Project Structure](#-project-structure)
- [Getting Started](#-getting-started)
- [Environment Variables](#-environment-variables)
- [Docker Setup](#-docker-setup)
- [Architecture](#-architecture)
- [API Reference](#-api-reference)
- [AI Service](#-ai-service)
- [Key Workflows](#-key-workflows)
- [Development Guide](#-development-guide)
- [Documentation](#-documentation)
- [Contributing](#-contributing)
- [License](#-license)

---

## 🌐 Overview

**ShipWithGlowie** is an enterprise-grade vehicle shipping and logistics management platform designed to streamline the entire lifecycle of car shipping — from initial quote generation through booking, shipment tracking, document management, and payment processing.

The platform leverages **AI agents** powered by LangChain and Mistral AI to provide intelligent quote generation, route optimization, delay prediction, document OCR processing, and customer support chatbot capabilities.

### Key Highlights

- 🤖 **AI-Powered Operations** — Intelligent quoting, route optimization, delay prediction, and document processing
- 📊 **Admin Dashboard** — Comprehensive analytics, KPIs, revenue tracking, and operational metrics
- 🚗 **Car Inventory** — Browse, search, compare vehicles with brand/category filtering
- 👤 **Customer Portal** — Self-service bookings, payments, document uploads, and shipment tracking
- 🗺️ **Real-Time Tracking** — Live shipment tracking with map visualization and timeline updates
- 💳 **Payment Processing** — Multi-method payment with admin confirmation workflow
- 📄 **Document Management** — Upload, OCR extraction, verification, and approval workflows
- 🐳 **Fully Dockerized** — One-command setup with Docker Compose

---

## ✨ Features

### Public Website

| Feature               | Description                                                   |
| --------------------- | ------------------------------------------------------------- |
| **Homepage**          | Hero section, service highlights, featured cars, testimonials |
| **Car Inventory**     | Browse vehicles by brand, category, search with filters       |
| **Car Comparison**    | Side-by-side vehicle comparison tool                          |
| **Favorites**         | Save and manage favorite vehicles                             |
| **Quote Request**     | AI-assisted shipping quote generation                         |
| **Shipment Tracking** | Public tracking by tracking number with map visualization     |
| **Services Pages**    | Inland transport, customs clearance, how it works             |
| **About & News**      | Company story, certifications, news articles                  |
| **FAQ & Contact**     | Frequently asked questions, contact form                      |

### Customer Portal

| Feature                | Description                                                             |
| ---------------------- | ----------------------------------------------------------------------- |
| **Dashboard**          | Booking stats, outstanding balance, recent activity                     |
| **Booking Management** | View, create, and cancel bookings                                       |
| **Payment Submission** | Pay via bank transfer, mobile money, or cash with transaction reference |
| **Payment History**    | Track all payments with status indicators                               |
| **Document Uploads**   | Upload shipping documents with AI-powered OCR extraction                |
| **Profile Management** | Edit profile, change password                                           |
| **AI Chatbot**         | Intelligent support assistant for shipping queries                      |
| **Notifications**      | Real-time notification bell with unread counts                          |

### Admin Dashboard

| Feature                 | Description                                                               |
| ----------------------- | ------------------------------------------------------------------------- |
| **Analytics Dashboard** | Revenue analytics, booking trends, operational KPIs, chart visualizations |
| **Booking Management**  | Full CRUD, status updates, payment tracking, attention flags              |
| **Quote Management**    | Approve/reject quotes, convert to bookings, extend validity               |
| **Shipment Management** | Track shipments, update locations, carrier performance metrics            |
| **Customer Management** | Customer profiles, tier management, verification, communication history   |
| **Finance Dashboard**   | Revenue overview, payment confirmation, summary cards, pagination         |
| **Document Manager**    | Verify/approve documents, bulk operations, expiry tracking                |
| **User Management**     | Admin users, roles, permissions, activity logs                            |
| **Car Inventory**       | Manage vehicles, brands, categories, images                               |
| **Message Center**      | Internal messaging and customer communications                            |
| **Reports Hub**         | Generate and export operational reports                                   |
| **System Settings**     | Application configuration, cache management, health monitoring            |

### AI Features

| Feature                       | Description                                                      |
| ----------------------------- | ---------------------------------------------------------------- |
| **Smart Quoting**             | AI-generated shipping quotes based on vehicle details and routes |
| **Route Optimization**        | Intelligent route planning with cost/time optimization           |
| **Delay Prediction**          | Proactive delay forecasting based on historical data             |
| **Document OCR**              | Automated text extraction from shipping documents                |
| **Vehicle Suggestions**       | AI-powered vehicle detail completion and validation              |
| **Customer Support Bot**      | Natural language chatbot for shipping inquiries                  |
| **Notification Intelligence** | Smart notification generation and delivery                       |

---

## 🛠 Tech Stack

### Frontend

| Technology   | Version | Purpose                 |
| ------------ | ------- | ----------------------- |
| React        | 18.x    | UI framework            |
| Vite         | 5.x     | Build tool & dev server |
| Tailwind CSS | 3.x     | Utility-first styling   |
| React Router | 6.x     | Client-side routing     |
| Axios        | 1.x     | HTTP client             |
| Chart.js     | 4.x     | Data visualization      |
| React Icons  | 5.x     | Icon library            |
| SweetAlert2  | 11.x    | Alert dialogs           |

### Backend

| Technology      | Version | Purpose                    |
| --------------- | ------- | -------------------------- |
| PHP             | 8.2+    | Runtime                    |
| Laravel         | 10.x    | API framework              |
| Laravel Sanctum | 3.x     | Token-based authentication |
| MySQL           | 8.0     | Primary database           |
| Redis           | 7.x     | Caching & sessions         |
| Guzzle          | 7.x     | HTTP client for AI service |

### AI Service

| Technology            | Purpose                       |
| --------------------- | ----------------------------- |
| Python / FastAPI      | Async API server              |
| LangChain + LangGraph | Agent orchestration framework |
| Mistral AI            | Large language model          |
| Tesseract / PDF2Image | Document OCR processing       |
| SQLAlchemy            | Database ORM                  |
| Redis                 | Caching layer                 |
| Loguru                | Structured logging            |

### Infrastructure

| Technology     | Purpose                     |
| -------------- | --------------------------- |
| Docker Compose | Container orchestration     |
| n8n            | AI workflow automation      |
| Mailhog        | Email testing (development) |
| phpMyAdmin     | Database management UI      |

---

## 🚀 Getting Started

### Prerequisites

- [Docker](https://docs.docker.com/get-docker/) & [Docker Compose](https://docs.docker.com/compose/install/) (recommended)
- **OR** for manual setup:
  - PHP 8.2+, Composer
  - Node.js 18+, npm
  - Python 3.10+
  - MySQL 8.0
  - Redis 7.x

### Quick Start with Docker (Recommended)

```bash
# 1. Clone the repository
git clone https://github.com/your-org/shipwithglowie.git
cd shipwithglowie

# 2. Copy environment file
cp .env.example .env

# 3. Start all services
docker-compose up -d --build

# 4. Run database migrations and seeders
./scripts/docker-migrate.sh
./scripts/docker-seed.sh

# 5. Access the application
# Frontend:    http://localhost:5173
# Backend API: http://localhost:8000/api
# phpMyAdmin:  http://localhost:8080
# n8n:         http://localhost:5678
# Mailhog:     http://localhost:8025
```

### Manual Setup

#### Backend (Laravel)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8000
```

#### Frontend (React)

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

#### AI Service (Python)

```bash
cd ai-service
pip install -r requirements.txt
uvicorn main:app --host 0.0.0.0 --port 8001
```

---

## 🔐 Environment Variables

Create a `.env` file in the project root based on `.env.example`:

```env
# ── Database ──────────────────────────────────
DB_ROOT_PASSWORD=your_root_password
DB_DATABASE=shipwithglowie
DB_USERNAME=shipuser
DB_PASSWORD=your_db_password
DB_PORT=3306

# ── Laravel Backend ───────────────────────────
APP_ENV=local
APP_DEBUG=true
APP_KEY=                       # Generated via php artisan key:generate

# ── Redis ─────────────────────────────────────
REDIS_PORT=6379

# ── Frontend ──────────────────────────────────
VITE_API_BASE_URL=http://localhost:8000/api
VITE_APP_NAME=ShipWithGlowie Auto
FRONTEND_PORT=5173

# ── Backend Port ──────────────────────────────
BACKEND_PORT=8000

# ── Auto Migration & Seeding ─────────────────
DB_MIGRATION_AUTO=true
DB_SEED_AUTO=true

# ── Mail (SMTP) ──────────────────────────────
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_NAME=ShipWithGlowie

# ── n8n AI Workflows ─────────────────────────
N8N_PORT=5678
N8N_BASIC_AUTH_USER=admin
N8N_BASIC_AUTH_PASSWORD=admin123
```

---

## 🐳 Docker Setup

### Services

| Service        | Port                    | Description                 |
| -------------- | ----------------------- | --------------------------- |
| **Frontend**   | 5173                    | React app (Vite dev server) |
| **Backend**    | 8000                    | Laravel API server          |
| **MySQL**      | 3306                    | Primary database            |
| **Redis**      | 6379                    | Cache & session store       |
| **phpMyAdmin** | 8080                    | Database management UI      |
| **n8n**        | 5678                    | AI workflow automation      |
| **Mailhog**    | 8025 (UI) / 1025 (SMTP) | Email testing               |

### Common Commands

```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# Rebuild containers
docker-compose up -d --build

# View logs
docker-compose logs -f [service_name]

# Access backend shell
docker-compose exec backend bash

# Run Laravel artisan commands
docker-compose exec backend php artisan migrate
docker-compose exec backend php artisan db:seed

# Access MySQL shell
docker-compose exec mysql mysql -u shipuser -p shipwithglowie
```

### Production

```bash
# Use production compose file
docker-compose -f docker-compose.prod.yml up -d --build
```

---

## 🏗 Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         Client Browser                          │
└────────────────────────────────┬────────────────────────────────┘
                                 │
                    ┌────────────▼────────────┐
                    │   React Frontend (SPA)  │
                    │   Vite + Tailwind CSS   │
                    │   Port: 5173            │
                    └────────────┬────────────┘
                                 │ Axios HTTP
                    ┌────────────▼────────────┐
                    │   Laravel Backend API   │
                    │   Sanctum Auth          │
                    │   Port: 8000            │
                    └──┬─────────┬─────────┬──┘
                       │         │         │
              ┌────────▼──┐  ┌──▼──────┐  ┌▼────────────┐
              │  MySQL 8  │  │  Redis  │  │  AI Service  │
              │  Port:3306│  │ Port:   │  │  FastAPI     │
              │           │  │  6379   │  │  LangChain   │
              └───────────┘  └─────────┘  │  Mistral AI  │
                                          └──────────────┘
                    ┌─────────────────────────┐
                    │   n8n Workflows         │
                    │   Port: 5678            │
                    └─────────────────────────┘
```

### Design Patterns

- **Repository Pattern** — Data access abstracted through repository classes (`BaseRepository`, `BookingRepository`, etc.)
- **Service Layer** — Business logic encapsulated in service classes (`ApiResponseService`, etc.)
- **Eloquent Model Events** — Automatic side effects on model state changes (e.g., payment completion updates booking)
- **Computed Accessors** — Derived fields (`payment_status`, `balance_amount`, `full_name`) via Laravel `$appends`
- **Token Authentication** — Laravel Sanctum with ability-based authorization (`admin` ability)
- **AI Agent Architecture** — LangChain agents with LangGraph orchestration for multi-step AI workflows

---

## 📡 API Reference

### Authentication

| Method | Endpoint                      | Description            |
| ------ | ----------------------------- | ---------------------- |
| `POST` | `/api/auth/customer/register` | Register new customer  |
| `POST` | `/api/auth/customer/login`    | Customer login         |
| `POST` | `/api/auth/admin/login`       | Admin login            |
| `POST` | `/api/auth/forgot-password`   | Request password reset |
| `POST` | `/api/auth/reset-password`    | Reset password         |

### Public Endpoints

| Method | Endpoint                         | Description                |
| ------ | -------------------------------- | -------------------------- |
| `GET`  | `/api/cars`                      | List vehicles with filters |
| `GET`  | `/api/cars/featured`             | Featured vehicles          |
| `GET`  | `/api/cars/brands`               | All brands                 |
| `GET`  | `/api/cars/search`               | Search vehicles            |
| `GET`  | `/api/cars/{slug}`               | Vehicle details            |
| `POST` | `/api/quotes`                    | Create shipping quote      |
| `GET`  | `/api/tracking/{trackingNumber}` | Public shipment tracking   |
| `POST` | `/api/chatbot`                   | AI chatbot interaction     |

### Customer Endpoints (Authenticated)

| Method | Endpoint                      | Description            |
| ------ | ----------------------------- | ---------------------- |
| `GET`  | `/api/customer/profile`       | Get customer profile   |
| `PUT`  | `/api/customer/profile`       | Update profile         |
| `GET`  | `/api/bookings`               | List customer bookings |
| `POST` | `/api/bookings`               | Create booking         |
| `POST` | `/api/payments`               | Submit payment         |
| `GET`  | `/api/payments`               | Payment history        |
| `POST` | `/api/documents`              | Upload document        |
| `POST` | `/api/documents/{id}/extract` | OCR extraction         |

---

## 🤖 AI Service

The AI microservice is built with **FastAPI** and uses **LangChain** agents orchestrated via **LangGraph** for multi-step reasoning.

### Agent Endpoints

| Endpoint                         | Agent              | Description                                     |
| -------------------------------- | ------------------ | ----------------------------------------------- |
| `POST /agents/quote`             | Quote Agent        | Generate intelligent shipping quotes            |
| `POST /agents/route`             | Route Agent        | Optimize shipping routes                        |
| `POST /agents/document`          | Document Agent     | OCR extraction & document processing            |
| `POST /agents/support`           | Support Agent      | Customer support chatbot                        |
| `POST /agents/delay-prediction`  | Delay Agent        | Predict shipment delays                         |
| `POST /agents/notify`            | Notification Agent | Generate smart notifications                    |
| `POST /agents/parse-description` | Vehicle Parser     | Parse vehicle descriptions into structured data |
| `POST /agents/suggest-vehicle`   | Vehicle Suggester  | Auto-complete vehicle details                   |
| `POST /agents/quote-preview`     | Quote Preview      | Real-time quote estimates                       |
| `POST /agents/validate-vehicle`  | Vehicle Validator  | Validate vehicle information                    |

### Technologies

- **LLM**: Mistral AI (via LangChain integration)
- **Agent Framework**: LangChain + LangGraph for multi-step agent workflows
- **OCR**: Tesseract + PDF2Image for document text extraction
- **Data**: SQLAlchemy + Redis for persistence and caching
- **Logging**: Loguru for structured logging

---

## 🔄 Key Workflows

### Quote → Booking → Shipment → Delivery

```
1. Customer requests quote (with vehicle & route details)
         ↓
2. AI generates quote (pricing, timeline, route)
         ↓
3. Admin reviews & approves quote
         ↓
4. Customer confirms → Booking created
         ↓
5. Customer submits payment (bank/mobile/cash)
         ↓
6. Admin confirms payment → Booking marked as paid
         ↓
7. Shipment created with tracking number
         ↓
8. Real-time tracking updates (pickup → transit → delivery)
         ↓
9. Delivery confirmed → Shipment completed
```

### Payment Flow

```
1. Customer selects booking & payment method
         ↓
2. Transaction reference auto-generated (matches booking number)
         ↓
3. Payment submitted with status "pending"
         ↓
4. Admin reviews in Finance Dashboard
         ↓
5. Admin confirms → Payment status "completed"
         ↓
6. Booking paid_amount incremented (via Eloquent model events)
         ↓
7. Booking payment_status auto-computed (paid/partial/unpaid)
         ↓
8. Customer portal reflects updated balance in real-time
```

### Document Processing

```
1. Customer uploads document (PDF/image)
         ↓
2. AI OCR agent extracts text & structured data
         ↓
3. Document stored with extracted metadata
         ↓
4. Admin reviews & approves/rejects
         ↓
5. Approved documents linked to booking
```

---

## 🧑‍💻 Development Guide

### Frontend Development

```bash
cd frontend
npm install
npm run dev          # Start dev server (http://localhost:5173)
npm run build        # Production build
npm run preview      # Preview production build
```

### Backend Development

```bash
cd backend
composer install
php artisan serve    # Start API server (http://localhost:8000)
php artisan migrate  # Run migrations
php artisan db:seed  # Seed database
php artisan tinker   # Interactive REPL
```

### AI Service Development

```bash
cd ai-service
pip install -r requirements.txt
uvicorn main:app --reload --host 0.0.0.0 --port 8001
```

## 🤝 Contributing

1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/amazing-feature`)
3. **Commit** your changes (`git commit -m 'Add amazing feature'`)
4. **Push** to the branch (`git push origin feature/amazing-feature`)
5. **Open** a Pull Request

### Coding Standards

- **Frontend**: ESLint, Prettier, Tailwind CSS conventions
- **Backend**: PSR-12, Laravel conventions
- **AI Service**: PEP 8, type hints
- **Git**: Conventional commits recommended

---

## 📄 License

This project is proprietary software. All rights reserved.

---

<div align="center">

**Built with ❤️ by the ShipWithGlowie Team**

</div>
