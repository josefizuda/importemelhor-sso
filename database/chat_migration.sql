-- ==========================================
-- MIGRATION - CHAT SYSTEM
-- Data: 2025-11-03
-- ==========================================

-- Tabela de conversas (pode ser 1-on-1 ou grupo)
CREATE TABLE IF NOT EXISTS chat_conversations (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255), -- Nome do grupo (null para conversas 1-on-1)
    is_group BOOLEAN DEFAULT FALSE,
    created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de participantes da conversa
CREATE TABLE IF NOT EXISTS chat_participants (
    id SERIAL PRIMARY KEY,
    conversation_id INTEGER REFERENCES chat_conversations(id) ON DELETE CASCADE,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_read_at TIMESTAMP,
    is_muted BOOLEAN DEFAULT FALSE,
    UNIQUE(conversation_id, user_id)
);

-- Tabela de mensagens
CREATE TABLE IF NOT EXISTS chat_messages (
    id SERIAL PRIMARY KEY,
    conversation_id INTEGER REFERENCES chat_conversations(id) ON DELETE CASCADE,
    sender_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    message TEXT,
    message_type VARCHAR(50) DEFAULT 'text', -- text, image, audio, file, link
    file_url TEXT, -- URL do arquivo anexado (imagem, audio, etc)
    file_name VARCHAR(255), -- Nome original do arquivo
    file_size INTEGER, -- Tamanho do arquivo em bytes
    file_mime_type VARCHAR(100), -- Tipo MIME do arquivo
    metadata JSON, -- Dados adicionais (duração de audio, dimensões de imagem, etc)
    reply_to_message_id INTEGER REFERENCES chat_messages(id) ON DELETE SET NULL,
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de leituras de mensagens
CREATE TABLE IF NOT EXISTS chat_message_reads (
    id SERIAL PRIMARY KEY,
    message_id INTEGER REFERENCES chat_messages(id) ON DELETE CASCADE,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(message_id, user_id)
);

-- Índices para performance
CREATE INDEX IF NOT EXISTS idx_chat_participants_user ON chat_participants(user_id);
CREATE INDEX IF NOT EXISTS idx_chat_participants_conversation ON chat_participants(conversation_id);
CREATE INDEX IF NOT EXISTS idx_chat_messages_conversation ON chat_messages(conversation_id);
CREATE INDEX IF NOT EXISTS idx_chat_messages_sender ON chat_messages(sender_id);
CREATE INDEX IF NOT EXISTS idx_chat_messages_created ON chat_messages(created_at);
CREATE INDEX IF NOT EXISTS idx_chat_message_reads_user ON chat_message_reads(user_id);
CREATE INDEX IF NOT EXISTS idx_chat_message_reads_message ON chat_message_reads(message_id);

-- Function: Obter conversas de um usuário
CREATE OR REPLACE FUNCTION sp_get_user_conversations(p_user_id INTEGER)
RETURNS TABLE (
    conversation_id INTEGER,
    conversation_name VARCHAR,
    is_group BOOLEAN,
    last_message TEXT,
    last_message_at TIMESTAMP,
    last_message_sender VARCHAR,
    unread_count BIGINT,
    participant_count BIGINT,
    other_user_id INTEGER,
    other_user_name VARCHAR,
    other_user_photo VARCHAR
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        c.id as conversation_id,
        c.name as conversation_name,
        c.is_group,
        last_msg.message as last_message,
        last_msg.created_at as last_message_at,
        last_sender.name as last_message_sender,
        (
            SELECT COUNT(*)
            FROM chat_messages cm
            LEFT JOIN chat_message_reads cmr ON cm.id = cmr.message_id AND cmr.user_id = p_user_id
            WHERE cm.conversation_id = c.id
            AND cm.sender_id != p_user_id
            AND cmr.id IS NULL
            AND cm.is_deleted = FALSE
        ) as unread_count,
        (SELECT COUNT(*) FROM chat_participants WHERE conversation_id = c.id) as participant_count,
        CASE
            WHEN c.is_group = FALSE THEN (
                SELECT user_id
                FROM chat_participants
                WHERE conversation_id = c.id AND user_id != p_user_id
                LIMIT 1
            )
            ELSE NULL
        END as other_user_id,
        CASE
            WHEN c.is_group = FALSE THEN (
                SELECT u.name
                FROM chat_participants cp
                INNER JOIN users u ON cp.user_id = u.id
                WHERE cp.conversation_id = c.id AND cp.user_id != p_user_id
                LIMIT 1
            )
            ELSE NULL
        END as other_user_name,
        CASE
            WHEN c.is_group = FALSE THEN (
                SELECT u.photo_url
                FROM chat_participants cp
                INNER JOIN users u ON cp.user_id = u.id
                WHERE cp.conversation_id = c.id AND cp.user_id != p_user_id
                LIMIT 1
            )
            ELSE NULL
        END as other_user_photo
    FROM chat_conversations c
    INNER JOIN chat_participants cp ON c.id = cp.conversation_id
    LEFT JOIN LATERAL (
        SELECT message, created_at, sender_id
        FROM chat_messages
        WHERE conversation_id = c.id AND is_deleted = FALSE
        ORDER BY created_at DESC
        LIMIT 1
    ) last_msg ON true
    LEFT JOIN users last_sender ON last_msg.sender_id = last_sender.id
    WHERE cp.user_id = p_user_id
    ORDER BY COALESCE(last_msg.created_at, c.created_at) DESC;
END;
$$ LANGUAGE plpgsql;

-- Function: Obter mensagens de uma conversa
CREATE OR REPLACE FUNCTION sp_get_conversation_messages(
    p_conversation_id INTEGER,
    p_user_id INTEGER,
    p_limit INTEGER DEFAULT 50,
    p_offset INTEGER DEFAULT 0
)
RETURNS TABLE (
    message_id INTEGER,
    sender_id INTEGER,
    sender_name VARCHAR,
    sender_photo VARCHAR,
    message TEXT,
    message_type VARCHAR,
    file_url TEXT,
    file_name VARCHAR,
    file_size INTEGER,
    file_mime_type VARCHAR,
    metadata JSON,
    reply_to_message_id INTEGER,
    reply_to_message TEXT,
    reply_to_sender VARCHAR,
    is_read BOOLEAN,
    created_at TIMESTAMP
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        m.id as message_id,
        m.sender_id,
        u.name as sender_name,
        u.photo_url as sender_photo,
        m.message,
        m.message_type,
        m.file_url,
        m.file_name,
        m.file_size,
        m.file_mime_type,
        m.metadata,
        m.reply_to_message_id,
        reply_msg.message as reply_to_message,
        reply_sender.name as reply_to_sender,
        (mr.id IS NOT NULL) as is_read,
        m.created_at
    FROM chat_messages m
    INNER JOIN users u ON m.sender_id = u.id
    LEFT JOIN chat_messages reply_msg ON m.reply_to_message_id = reply_msg.id
    LEFT JOIN users reply_sender ON reply_msg.sender_id = reply_sender.id
    LEFT JOIN chat_message_reads mr ON m.id = mr.message_id AND mr.user_id = p_user_id
    WHERE m.conversation_id = p_conversation_id
    AND m.is_deleted = FALSE
    ORDER BY m.created_at DESC
    LIMIT p_limit
    OFFSET p_offset;
END;
$$ LANGUAGE plpgsql;

-- Function: Contar mensagens não lidas total
CREATE OR REPLACE FUNCTION sp_count_total_unread_messages(p_user_id INTEGER)
RETURNS INTEGER AS $$
DECLARE
    unread_count INTEGER;
BEGIN
    SELECT COUNT(*) INTO unread_count
    FROM chat_messages m
    INNER JOIN chat_participants cp ON m.conversation_id = cp.conversation_id
    LEFT JOIN chat_message_reads mr ON m.id = mr.message_id AND mr.user_id = p_user_id
    WHERE cp.user_id = p_user_id
    AND m.sender_id != p_user_id
    AND mr.id IS NULL
    AND m.is_deleted = FALSE;

    RETURN unread_count;
END;
$$ LANGUAGE plpgsql;

-- Trigger para atualizar updated_at em conversas
CREATE OR REPLACE FUNCTION update_conversation_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE chat_conversations
    SET updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.conversation_id;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trigger_update_conversation_timestamp ON chat_messages;
CREATE TRIGGER trigger_update_conversation_timestamp
AFTER INSERT ON chat_messages
FOR EACH ROW
EXECUTE FUNCTION update_conversation_timestamp();

-- Comentários
COMMENT ON TABLE chat_conversations IS 'Conversas do chat (1-on-1 ou grupos)';
COMMENT ON TABLE chat_participants IS 'Participantes de cada conversa';
COMMENT ON TABLE chat_messages IS 'Mensagens do chat';
COMMENT ON TABLE chat_message_reads IS 'Controle de leitura de mensagens';
COMMENT ON FUNCTION sp_get_user_conversations IS 'Retorna conversas de um usuário com últimas mensagens';
COMMENT ON FUNCTION sp_get_conversation_messages IS 'Retorna mensagens de uma conversa';
COMMENT ON FUNCTION sp_count_total_unread_messages IS 'Conta total de mensagens não lidas';
