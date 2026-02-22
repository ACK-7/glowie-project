# ShipWithGlowie Auto - Architecture Overview

## System Architecture

ShipWithGlowie Auto is built using a microservices architecture with Docker containers.

### Components

#### Frontend (React.js)
- **Framework**: React.js with Vite
- **Styling**: Tailwind CSS
- **State Management**: React Context API
- **Routing**: React Router
- **HTTP Client**: Axios

#### Backend (Laravel)
- **Framework**: Laravel (PHP)
- **Database**: MySQL
- **Cache**: Redis
- **Authentication**: JWT
- **API**: RESTful API

#### Database (MySQL)
- **Version**: MySQL 8.0
- **Schema**: Relational database with foreign key constraints
- **Migrations**: Laravel migrations for schema management

#### Cache (Redis)
- **Purpose**: Session storage, caching, queues
- **Version**: Redis 7 Alpine

#### AI Services (n8n)
- **Purpose**: Workflow automation, AI integrations
- **Version**: Latest n8n

## Directory Structure

```
ShipWithGlowie-Auto/
├── frontend/          # React.js application
├── backend/           # Laravel API
├── docker/            # Docker configurations
├── docs/              # Documentation
├── scripts/           # Utility scripts
├── n8n-workflows/     # AI workflows
└── .github/           # CI/CD workflows
```

## Data Flow

1. User interacts with React frontend
2. Frontend makes API calls to Laravel backend
3. Backend processes requests and interacts with MySQL database
4. Redis is used for caching and session management
5. n8n handles AI-powered features and automations
6. Email notifications and other services are triggered as needed

## Security

- JWT authentication for API access
- Role-based access control (Customer/Admin)
- Input validation and sanitization
- CORS configuration
- Rate limiting
- SQL injection prevention

## Scalability

- Docker containerization for easy scaling
- Horizontal scaling support for backend services
- Database connection pooling
- Redis clustering capability
- Load balancer ready
