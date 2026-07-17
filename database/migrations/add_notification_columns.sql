-- Run this migration if your notifications table was created from an older schema.

ALTER TABLE notifications
    ADD COLUMN IF NOT EXISTS type VARCHAR(50) DEFAULT 'system' AFTER message,
    ADD COLUMN IF NOT EXISTS reference_type VARCHAR(50) DEFAULT NULL AFTER type,
    ADD COLUMN IF NOT EXISTS reference_id INT DEFAULT NULL AFTER reference_type,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- MySQL versions without IF NOT EXISTS support: run these one at a time and ignore duplicate-column errors.
-- ALTER TABLE notifications ADD COLUMN type VARCHAR(50) DEFAULT 'system' AFTER message;
-- ALTER TABLE notifications ADD COLUMN reference_type VARCHAR(50) DEFAULT NULL AFTER type;
-- ALTER TABLE notifications ADD COLUMN reference_id INT DEFAULT NULL AFTER reference_type;
-- ALTER TABLE notifications ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at;
