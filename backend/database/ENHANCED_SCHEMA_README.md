# Enhanced Database Schema for Admin Dashboard System

This document describes the enhanced database schema implemented for the ShipWithGlowie Auto Admin Dashboard System, following the specifications in the design document.

## Overview

The enhanced schema provides comprehensive data management for a vehicle shipping service that facilitates car imports from international markets (Japan, UK, UAE) to Uganda. The system includes complete booking lifecycle management, shipment tracking, document verification, financial oversight, and business analytics.

## Schema Enhancements

### New Tables Added

1. **activity_logs** - Comprehensive audit trail for all system actions
2. **system_settings** - Configurable system parameters and business rules

### Enhanced Existing Tables

All existing tables have been enhanced with:
- Proper indexes for performance optimization
- Foreign key constraints for data integrity
- Additional fields as specified in the design document
- Updated enums and data types for consistency

## Table Specifications

### Core Business Tables

#### bookings
Enhanced with:
- `booking_reference` (unique identifier)
- `total_amount` and `paid_amount` (financial tracking)
- `currency` (multi-currency support)
- `notes` (admin notes)
- `created_by` and `updated_by` (audit trail)
- Proper foreign key to `routes` table
- Performance indexes on key fields

#### quotes
Enhanced with:
- `quote_reference` (unique identifier)
- `vehicle_details` (JSON field for flexible vehicle data)
- `additional_fees` (JSON field for fee breakdown)
- `valid_until` (quote expiry date)
- `notes` (admin notes)
- `created_by`, `approved_by`, `approved_at` (approval workflow)
- Updated status enum with 'converted' and 'expired' states

#### shipments
Enhanced with:
- `tracking_number` (unique tracking identifier)
- `carrier_name` (shipping company)
- `tracking_updates` (JSON field for tracking history)
- Updated status enum matching design requirements
- Performance indexes for tracking queries

#### documents
Enhanced with:
- Updated document types enum
- `expiry_date` for document lifecycle management
- Proper foreign key constraints
- Performance indexes for document queries

#### payments
Enhanced with:
- `payment_reference` (unique payment identifier)
- `payment_gateway` (gateway information)
- `payment_date` (actual payment timestamp)
- `notes` (payment notes)
- Updated enums for payment methods and statuses

#### users (Admin Users)
Enhanced with:
- Combined `name` field (replacing separate first/last names)
- `email_verified_at` (email verification tracking)
- Updated role enum with proper admin hierarchy
- Performance indexes for authentication queries

### System Management Tables

#### activity_logs
Comprehensive audit trail with:
- User action tracking
- Model change logging
- IP address and user agent capture
- JSON change details
- Performance indexes for audit queries

#### system_settings
Configurable system parameters with:
- Key-value storage with data type specification
- Public/private setting classification
- Description field for documentation
- Support for string, integer, boolean, and JSON data types

## Indexes and Performance

### Primary Indexes
All tables have optimized indexes for:
- Primary key lookups
- Foreign key relationships
- Status-based queries
- Date range queries
- Search operations

### Composite Indexes
Strategic composite indexes for:
- Customer + status queries
- Date + status combinations
- Model type + model ID (for activity logs)
- Multi-field search operations

## Foreign Key Relationships

### Referential Integrity
- All foreign keys properly defined with appropriate cascade/restrict rules
- Customer deletions restricted to prevent data loss
- Booking deletions cascade to related shipments and documents
- User deletions set to null for audit trail preservation

### Relationship Mapping
```
customers (1) -> (n) bookings
customers (1) -> (n) quotes
customers (1) -> (n) documents
customers (1) -> (n) payments

bookings (1) -> (1) shipments
bookings (1) -> (n) documents
bookings (1) -> (n) payments

quotes (1) -> (0..1) bookings
routes (1) -> (n) quotes
routes (1) -> (n) bookings
vehicles (1) -> (n) bookings

users (1) -> (n) activity_logs
users (1) -> (n) quotes (created_by)
users (1) -> (n) bookings (created_by, updated_by)
```

## Data Validation Rules

### Business Rules Enforced
- Booking references must be unique across the system
- Quote validity periods must be future dates
- Payment amounts cannot exceed booking totals
- Document expiry dates trigger system alerts
- Status transitions follow defined state machines

### Data Integrity
- Email addresses must be unique per table
- Phone numbers follow international format
- Currency codes follow ISO 4217 standard
- File paths and sizes validated for documents
- JSON fields validated for proper structure

## Seeded Data

### Admin Users
- Super Administrator (full system access)
- Operations Manager (booking and shipment management)
- Customer Service Admin (customer and quote management)
- Data Entry Operator (read-only access)

### System Settings
- Company information and contact details
- Default currency and payment terms
- File upload limits and allowed types
- Notification preferences
- Business rule parameters

### Test Data
- 20+ sample customers with varied profiles
- 50+ quotes in different statuses
- 30+ bookings with complete lifecycle data
- Shipments with realistic tracking information
- Documents with various verification states
- Payment records with multiple methods

## Migration Strategy

### Safe Migration Process
1. **Backup existing data** before running migrations
2. **Run migrations in order** to maintain dependencies
3. **Verify foreign key constraints** after completion
4. **Test data integrity** with sample queries
5. **Validate indexes** for performance optimization

### Rollback Support
- All migrations include proper `down()` methods
- Foreign key constraints can be safely removed
- Index changes are reversible
- Data transformations preserve original values where possible

## Usage Instructions

### Setup Commands
```bash
# Fresh installation (drops all tables)
php artisan db:setup-enhanced --fresh

# Update existing database (preserves data)
php artisan db:setup-enhanced

# Run only migrations
php artisan migrate

# Run only seeders
php artisan db:seed --class=EnhancedDatabaseSeeder
```

### Verification Queries
```sql
-- Check table counts
SELECT 
    table_name,
    table_rows
FROM information_schema.tables 
WHERE table_schema = DATABASE();

-- Verify foreign key constraints
SELECT 
    constraint_name,
    table_name,
    referenced_table_name
FROM information_schema.referential_constraints
WHERE constraint_schema = DATABASE();

-- Check index usage
SHOW INDEX FROM bookings;
SHOW INDEX FROM quotes;
SHOW INDEX FROM shipments;
```

## Performance Considerations

### Query Optimization
- Use indexes for all WHERE clauses
- Leverage composite indexes for multi-field queries
- Consider pagination for large result sets
- Use appropriate JOIN types for relationships

### Maintenance Tasks
- Regular index analysis and optimization
- Periodic cleanup of old activity logs
- Archive completed bookings and shipments
- Monitor foreign key constraint performance

## Security Features

### Data Protection
- Sensitive data encrypted at application level
- Audit trail for all data modifications
- User action logging with IP tracking
- Secure password hashing for all user types

### Access Control
- Role-based permissions in user table
- Activity logging for security monitoring
- Foreign key constraints prevent orphaned records
- Soft deletes preserve audit trails

## Future Enhancements

### Planned Improvements
- Partitioning for large tables (activity_logs, payments)
- Full-text search indexes for document content
- Materialized views for analytics queries
- Archive tables for historical data

### Scalability Considerations
- Database sharding strategies for multi-tenant support
- Read replicas for reporting queries
- Caching layers for frequently accessed data
- Queue-based processing for heavy operations

---

**Note**: This enhanced schema is designed to support the complete Admin Dashboard System as specified in the design document. All tables, relationships, and constraints have been implemented according to the requirements for comprehensive vehicle shipping management.