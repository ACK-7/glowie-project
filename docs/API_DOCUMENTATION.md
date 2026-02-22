# ShipWithGlowie Auto - API Documentation

## Overview

This document provides comprehensive API documentation for the ShipWithGlowie Auto platform.

## Authentication

All API endpoints require authentication except for registration and login.

### Headers
```
Authorization: Bearer {token}
Content-Type: application/json
```

## Endpoints

### Authentication
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout
- `POST /api/auth/refresh-token` - Refresh JWT token

### Quotes
- `POST /api/quotes` - Get quote
- `GET /api/quotes` - List user quotes
- `GET /api/quotes/:id` - Get quote details

### Bookings
- `POST /api/bookings` - Create booking
- `GET /api/bookings` - List user bookings
- `GET /api/bookings/:id` - Get booking details
- `PUT /api/bookings/:id` - Update booking
- `PUT /api/bookings/:id/cancel` - Cancel booking

### Documents
- `POST /api/documents` - Upload document
- `GET /api/documents` - List documents
- `GET /api/documents/:id` - Get document details
- `DELETE /api/documents/:id` - Delete document

### Tracking
- `GET /api/shipments/:id` - Get shipment tracking
- `GET /api/shipments/:id/history` - Get tracking history

### Admin
- `GET /api/admin/bookings` - List all bookings
- `PUT /api/admin/bookings/:id` - Update booking status
- `GET /api/admin/users` - List users
- `GET /api/admin/reports` - Get system reports

## Error Responses

```json
{
  "error": "Error message",
  "code": "ERROR_CODE"
}
```

## Rate Limiting

API endpoints are rate limited to prevent abuse.
