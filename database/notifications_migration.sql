-- ==========================================
-- MIGRATION - NOTIFICATIONS SYSTEM
-- Data: 2025-11-03
-- ==========================================

-- Tabela de notificações
CREATE TABLE IF NOT EXISTS notifications (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'info', -- info, success, warning, error
    target_type VARCHAR(50) NOT NULL, -- user, department, all
    target_value TEXT, -- user_id, department name, or NULL for all
    created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP
);

-- Tabela de leitura de notificações
CREATE TABLE IF NOT EXISTS notification_reads (
    id SERIAL PRIMARY KEY,
    notification_id INTEGER REFERENCES notifications(id) ON DELETE CASCADE,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(notification_id, user_id)
);

-- Índices
CREATE INDEX IF NOT EXISTS idx_notifications_target ON notifications(target_type, target_value);
CREATE INDEX IF NOT EXISTS idx_notifications_created_at ON notifications(created_at);
CREATE INDEX IF NOT EXISTS idx_notification_reads_user ON notification_reads(user_id);
CREATE INDEX IF NOT EXISTS idx_notification_reads_notification ON notification_reads(notification_id);

-- Function: Obter notificações não lidas de um usuário
CREATE OR REPLACE FUNCTION sp_get_user_notifications(
    p_user_id INTEGER,
    p_include_read BOOLEAN DEFAULT FALSE
)
RETURNS TABLE (
    id INTEGER,
    title VARCHAR,
    message TEXT,
    type VARCHAR,
    created_by INTEGER,
    created_by_name VARCHAR,
    created_at TIMESTAMP,
    is_read BOOLEAN
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        n.id,
        n.title,
        n.message,
        n.type,
        n.created_by,
        u.name as created_by_name,
        n.created_at,
        (nr.id IS NOT NULL) as is_read
    FROM notifications n
    LEFT JOIN users u ON n.created_by = u.id
    LEFT JOIN notification_reads nr ON n.id = nr.notification_id AND nr.user_id = p_user_id
    LEFT JOIN users target_user ON target_user.id = p_user_id
    WHERE (n.expires_at IS NULL OR n.expires_at > CURRENT_TIMESTAMP)
    AND (
        n.target_type = 'all'
        OR (n.target_type = 'user' AND n.target_value = p_user_id::TEXT)
        OR (n.target_type = 'department' AND target_user.department = n.target_value)
    )
    AND (p_include_read = TRUE OR nr.id IS NULL)
    ORDER BY n.created_at DESC;
END;
$$ LANGUAGE plpgsql;

-- Function: Contar notificações não lidas
CREATE OR REPLACE FUNCTION sp_count_unread_notifications(p_user_id INTEGER)
RETURNS INTEGER AS $$
DECLARE
    unread_count INTEGER;
BEGIN
    SELECT COUNT(*) INTO unread_count
    FROM notifications n
    LEFT JOIN notification_reads nr ON n.id = nr.notification_id AND nr.user_id = p_user_id
    LEFT JOIN users target_user ON target_user.id = p_user_id
    WHERE (n.expires_at IS NULL OR n.expires_at > CURRENT_TIMESTAMP)
    AND (
        n.target_type = 'all'
        OR (n.target_type = 'user' AND n.target_value = p_user_id::TEXT)
        OR (n.target_type = 'department' AND target_user.department = n.target_value)
    )
    AND nr.id IS NULL;

    RETURN unread_count;
END;
$$ LANGUAGE plpgsql;

-- Comentários
COMMENT ON TABLE notifications IS 'Notificações do sistema';
COMMENT ON TABLE notification_reads IS 'Controle de leitura de notificações';
COMMENT ON FUNCTION sp_get_user_notifications IS 'Retorna notificações de um usuário';
COMMENT ON FUNCTION sp_count_unread_notifications IS 'Conta notificações não lidas';
