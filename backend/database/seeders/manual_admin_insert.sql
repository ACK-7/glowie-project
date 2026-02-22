-- Insert Admin Users Manually
-- Run this SQL in your MySQL database if the seeder is not working

-- Admin User
INSERT INTO `users` (`first_name`, `last_name`, `email`, `phone`, `password`, `role`, `is_active`, `permissions`, `created_at`, `updated_at`) 
VALUES (
    'Admin',
    'User',
    'admin@shipwithglowie.com',
    '+256700000001',
    '$2y$12$LQv3c1yytN/Zu7twqwhqQOV9LjK6nTWzXh0vgK5qF4YB.x8yOUMV2', -- password: admin123
    'admin',
    1,
    NULL,
    NOW(),
    NOW()
);

-- Manager User
INSERT INTO `users` (`first_name`, `last_name`, `email`, `phone`, `password`, `role`, `is_active`, `permissions`, `created_at`, `updated_at`) 
VALUES (
    'Manager',
    'User',
    'manager@shipwithglowie.com',
    '+256700000002',
    '$2y$12$LQv3c1yytN/Zu7twqwhqQOV9LjK6nTWzXh0vgK5qF4YB.x8yOUMV2', -- password: manager123
    'manager',
    1,
    '["view_bookings","edit_bookings","view_customers","edit_customers","view_shipments","edit_shipments","view_quotes","send_quotes","view_documents","verify_documents"]',
    NOW(),
    NOW()
);

-- Support User
INSERT INTO `users` (`first_name`, `last_name`, `email`, `phone`, `password`, `role`, `is_active`, `permissions`, `created_at`, `updated_at`) 
VALUES (
    'Support',
    'User',
    'support@shipwithglowie.com',
    '+256700000003',
    '$2y$12$LQv3c1yytN/Zu7twqwhqQOV9LjK6nTWzXh0vgK5qF4YB.x8yOUMV2', -- password: support123
    'support',
    1,
    '["view_bookings","view_customers","view_shipments","view_messages","reply_messages"]',
    NOW(),
    NOW()
);
