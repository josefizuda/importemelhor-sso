-- Update all existing roles to have chat access by default
-- This should be run after adding the can_access_chat column

-- First, check if the column exists
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'user_roles'
        AND column_name = 'can_access_chat'
    ) THEN
        RAISE EXCEPTION 'Column can_access_chat does not exist. Please run the chat_permission migration first.';
    END IF;
END $$;

-- Update all existing roles to have chat access enabled
UPDATE user_roles
SET can_access_chat = TRUE
WHERE can_access_chat IS NULL OR can_access_chat = FALSE;

-- Show the results
SELECT id, name, slug, can_access_chat
FROM user_roles
ORDER BY id;
