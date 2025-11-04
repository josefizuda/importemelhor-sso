-- Add chat permission control per user
-- This allows admins to enable/disable chat for specific users, overriding role permissions

-- Add column to users table
ALTER TABLE users ADD COLUMN IF NOT EXISTS can_access_chat BOOLEAN DEFAULT NULL;

-- NULL = use role permission (default behavior)
-- TRUE = user can access chat (override role)
-- FALSE = user cannot access chat (override role)

COMMENT ON COLUMN users.can_access_chat IS 'User-specific chat permission override. NULL uses role permission, TRUE/FALSE overrides it.';

-- Create index for better performance
CREATE INDEX IF NOT EXISTS idx_users_can_access_chat ON users(can_access_chat) WHERE can_access_chat IS NOT NULL;
