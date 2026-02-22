# Database Schema Update: Separate Customer and Admin Tables

## Overview
Separated the customer and admin user management into two distinct tables for better data separation, security, and scalability.

---

## Table Structure

### 1. **customers** Table (NEW)
**Purpose:** Store customer account information

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| first_name | VARCHAR(100) | Customer's first name |
| last_name | VARCHAR(100) | Customer's last name |
| email | VARCHAR | Unique email address |
| phone | VARCHAR(20) | Contact phone number |
| password | VARCHAR | Hashed password |
| country | VARCHAR(100) | Customer's country |
| city | VARCHAR(100) | Customer's city |
| address | TEXT | Full address |
| postal_code | VARCHAR(20) | Postal/ZIP code |
| profile_image_url | VARCHAR | Profile photo URL |
| date_of_birth | DATE | Date of birth |
| id_number | VARCHAR(50) | National ID or Passport number |
| id_type | ENUM | Type: national_id, passport, drivers_license |
| is_verified | BOOLEAN | Email verification status |
| is_active | BOOLEAN | Account active status |
| email_verified_at | TIMESTAMP | Email verification timestamp |
| verification_token | VARCHAR | Email verification token |
| reset_token | VARCHAR | Password reset token |
| reset_token_expires_at | TIMESTAMP | Reset token expiry |
| notes | TEXT | Admin notes about customer |
| total_bookings | INTEGER | Total bookings count |
| total_spent | DECIMAL(12,2) | Total amount spent |
| last_login_at | TIMESTAMP | Last login timestamp |
| preferred_language | VARCHAR(10) | UI language preference |
| newsletter_subscribed | BOOLEAN | Newsletter subscription status |
| created_at | TIMESTAMP | Account creation date |
| updated_at | TIMESTAMP | Last update |
| deleted_at | TIMESTAMP | Soft delete |

**Indexes:**
- email
- phone
- is_active
- created_at

---

### 2. **users** Table (UPDATED - Admin/Staff Only)
**Purpose:** Store admin and staff accounts

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| first_name | VARCHAR(100) | Staff first name |
| last_name | VARCHAR(100) | Staff last name |
| email | VARCHAR | Unique email address |
| phone | VARCHAR(20) | Contact phone |
| password | VARCHAR | Hashed password |
| role | ENUM | Role: admin, manager, support |
| profile_image_url | VARCHAR | Profile photo URL |
| is_active | BOOLEAN | Account status |
| last_login_at | TIMESTAMP | Last login |
| permissions | JSON | Custom permissions object |
| created_at | TIMESTAMP | Account creation |
| updated_at | TIMESTAMP | Last update |
| deleted_at | TIMESTAMP | Soft delete |

**Indexes:**
- email
- role
- is_active

---

## Foreign Key Updates

All customer-related tables now reference `customers` table instead of `users`:

### Updated Tables:
1. **bookings** - `customer_id` → `customers.id`
2. **quotes** - `customer_id` → `customers.id`
3. **documents** - `customer_id` → `customers.id`
4. **payments** - `customer_id` → `customers.id`
5. **chat_messages** - `customer_id` → `customers.id`
6. **notifications** - `customer_id` → `customers.id`

### Admin References:
- **documents.verified_by** → `users.id` (admin who verified)
- **audit_logs.user_id** → `users.id` (admin actions)

---

## Authentication Flow

### Customer Registration/Login:
```
POST /api/auth/customer/register → Creates record in customers table
POST /api/auth/customer/login → Authenticates against customers table
```

### Admin Login:
```
POST /api/auth/admin/login → Authenticates against users table
```

---

## Customer Model Features

### Relationships:
- `bookings()` - HasMany
- `quotes()` - HasMany
- `documents()` - HasMany
- `payments()` - HasMany
- `chatMessages()` - HasMany
- `notifications()` - HasMany

### Accessors:
- `full_name` - Returns "First Last"

### Scopes:
- `active()` - Only active customers
- `verified()` - Only verified customers

### Helper Methods:
- `incrementBookingCount()` - Auto-increment total bookings
- `addToTotalSpent($amount)` - Add to total spent amount

---

## Benefits of This Approach

✅ **Better Security** - Admin credentials completely separated from customer data  
✅ **Clearer Data Model** - Each table serves a distinct purpose  
✅ **Easier Auditing** - Track admin actions vs customer actions  
✅ **Scalability** - Can add customer-specific fields without affecting admin table  
✅ **Performance** - Smaller tables, better indexing  
✅ **Compliance** - Easier GDPR/data privacy implementation  

---

## Migration Steps

1. Run migration: `php artisan migrate`
2. Update all foreign keys in related tables
3. Update AuthController to handle both customer and admin authentication
4. Update API routes to distinguish between customer and admin endpoints
5. Test registration and login flows

---

## File Locations

**Migration:** `backend/database/migrations/2025_01_11_create_customers_table.php`  
**Model:** `backend/app/Models/Customer.php`  
**Updated Migration:** `backend/database/migrations/2025_01_01_create_users_table.php`
