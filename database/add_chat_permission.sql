-- Add chat permission to roles
ALTER TABLE user_roles ADD COLUMN IF NOT EXISTS can_access_chat BOOLEAN DEFAULT TRUE;

-- Update existing roles to have chat access
UPDATE user_roles SET can_access_chat = TRUE WHERE TRUE;

COMMENT ON COLUMN user_roles.can_access_chat IS 'Permissão para acessar o sistema de chat';
