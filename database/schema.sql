-- ==========================================
-- SCHEMA DO BANCO DE DADOS - SSO Importe Melhor
-- PostgreSQL Version
-- ==========================================

-- Criar database (execute como superuser)
-- CREATE DATABASE importemelhor_sso WITH ENCODING 'UTF8' LC_COLLATE='pt_BR.UTF-8' LC_CTYPE='pt_BR.UTF-8';

-- Conectar ao banco
\c importemelhor_sso;

-- Habilitar extensão UUID (opcional, mas útil)
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ==========================================
-- TABELAS
-- ==========================================

-- Tabela de usuários
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    microsoft_id VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    photo_url TEXT,
    job_title VARCHAR(255),
    department VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_microsoft_id ON users(microsoft_id);
CREATE INDEX idx_users_active ON users(is_active);

-- Tabela de sessões
CREATE TABLE sessions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    refresh_token TEXT,
    access_token TEXT,
    expires_at TIMESTAMP NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_sessions_token ON sessions(session_token);
CREATE INDEX idx_sessions_user_id ON sessions(user_id);
CREATE INDEX idx_sessions_expires ON sessions(expires_at);

-- Tabela de aplicações
CREATE TABLE applications (
    id SERIAL PRIMARY KEY,
    app_name VARCHAR(100) NOT NULL,
    app_slug VARCHAR(100) UNIQUE NOT NULL,
    app_url VARCHAR(255) NOT NULL,
    app_description TEXT,
    icon_emoji VARCHAR(10) DEFAULT '📱',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_applications_slug ON applications(app_slug);
CREATE INDEX idx_applications_active ON applications(is_active);

-- Tabela de permissões de acesso
CREATE TABLE user_app_access (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    app_id INTEGER NOT NULL REFERENCES applications(id) ON DELETE CASCADE,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    granted_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE(user_id, app_id)
);

CREATE INDEX idx_user_app_access_user ON user_app_access(user_id);
CREATE INDEX idx_user_app_access_app ON user_app_access(app_id);

-- Tabela de logs de auditoria
CREATE TABLE audit_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(100) NOT NULL,
    app_id INTEGER REFERENCES applications(id) ON DELETE SET NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    details JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_audit_logs_user ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_action ON audit_logs(action);
CREATE INDEX idx_audit_logs_created ON audit_logs(created_at);
CREATE INDEX idx_audit_logs_details ON audit_logs USING gin(details);

-- ==========================================
-- FUNCTIONS (equivalente a Stored Procedures)
-- ==========================================

-- Trigger para atualizar updated_at automaticamente
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER update_users_updated_at 
    BEFORE UPDATE ON users
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Trigger para atualizar last_activity em sessions
CREATE OR REPLACE FUNCTION update_session_activity()
RETURNS TRIGGER AS $$
BEGIN
    NEW.last_activity = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER update_sessions_activity
    BEFORE UPDATE ON sessions
    FOR EACH ROW
    EXECUTE FUNCTION update_session_activity();

-- Function: Criar ou atualizar usuário
CREATE OR REPLACE FUNCTION sp_upsert_user(
    p_microsoft_id VARCHAR(255),
    p_email VARCHAR(255),
    p_name VARCHAR(255),
    p_photo_url TEXT DEFAULT NULL,
    p_job_title VARCHAR(255) DEFAULT NULL,
    p_department VARCHAR(255) DEFAULT NULL
)
RETURNS TABLE (
    id INTEGER,
    microsoft_id VARCHAR(255),
    email VARCHAR(255),
    name VARCHAR(255),
    photo_url TEXT,
    job_title VARCHAR(255),
    department VARCHAR(255),
    is_active BOOLEAN,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    last_login TIMESTAMP
) AS $$
BEGIN
    RETURN QUERY
    INSERT INTO users (microsoft_id, email, name, photo_url, job_title, department, last_login)
    VALUES (p_microsoft_id, p_email, p_name, p_photo_url, p_job_title, p_department, CURRENT_TIMESTAMP)
    ON CONFLICT (microsoft_id) 
    DO UPDATE SET
        email = EXCLUDED.email,
        name = EXCLUDED.name,
        photo_url = EXCLUDED.photo_url,
        job_title = EXCLUDED.job_title,
        department = EXCLUDED.department,
        last_login = CURRENT_TIMESTAMP,
        updated_at = CURRENT_TIMESTAMP
    RETURNING users.*;
END;
$$ LANGUAGE plpgsql;

-- Function: Criar sessão
CREATE OR REPLACE FUNCTION sp_create_session(
    p_user_id INTEGER,
    p_session_token VARCHAR(255),
    p_access_token TEXT,
    p_expires_at TIMESTAMP,
    p_ip_address VARCHAR(45),
    p_user_agent TEXT,
    p_refresh_token TEXT DEFAULT NULL
)
RETURNS TABLE (
    id INTEGER,
    user_id INTEGER,
    session_token VARCHAR(255),
    refresh_token TEXT,
    access_token TEXT,
    expires_at TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP,
    last_activity TIMESTAMP
) AS $$
BEGIN
    RETURN QUERY
    INSERT INTO sessions (user_id, session_token, access_token, refresh_token, expires_at, ip_address, user_agent)
    VALUES (p_user_id, p_session_token, p_access_token, p_refresh_token, p_expires_at, p_ip_address, p_user_agent)
    RETURNING sessions.*;
END;
$$ LANGUAGE plpgsql;

-- Function: Validar sessão
CREATE OR REPLACE FUNCTION sp_validate_session(p_session_token VARCHAR(255))
RETURNS TABLE (
    id INTEGER,
    user_id INTEGER,
    session_token VARCHAR(255),
    expires_at TIMESTAMP,
    email VARCHAR(255),
    name VARCHAR(255),
    photo_url TEXT,
    is_active BOOLEAN
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        s.id,
        s.user_id,
        s.session_token,
        s.expires_at,
        u.email,
        u.name,
        u.photo_url,
        u.is_active
    FROM sessions s
    INNER JOIN users u ON s.user_id = u.id
    WHERE s.session_token = p_session_token
    AND s.expires_at > CURRENT_TIMESTAMP
    AND u.is_active = TRUE;
END;
$$ LANGUAGE plpgsql;

-- Function: Verificar acesso a aplicação
CREATE OR REPLACE FUNCTION sp_check_app_access(
    p_user_id INTEGER,
    p_app_slug VARCHAR(100)
)
RETURNS TABLE (
    id INTEGER,
    app_name VARCHAR(100),
    app_slug VARCHAR(100),
    app_url VARCHAR(255),
    has_access BOOLEAN
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        a.id,
        a.app_name,
        a.app_slug,
        a.app_url,
        CASE 
            WHEN uaa.id IS NOT NULL THEN TRUE
            ELSE FALSE
        END as has_access
    FROM applications a
    LEFT JOIN user_app_access uaa ON a.id = uaa.app_id AND uaa.user_id = p_user_id
    WHERE a.app_slug = p_app_slug
    AND a.is_active = TRUE;
END;
$$ LANGUAGE plpgsql;

-- Function: Limpar sessões expiradas
CREATE OR REPLACE FUNCTION sp_cleanup_expired_sessions()
RETURNS INTEGER AS $$
DECLARE
    deleted_count INTEGER;
BEGIN
    DELETE FROM sessions WHERE expires_at < CURRENT_TIMESTAMP;
    GET DIAGNOSTICS deleted_count = ROW_COUNT;
    RETURN deleted_count;
END;
$$ LANGUAGE plpgsql;

-- ==========================================
-- VIEWS
-- ==========================================

-- View de usuários ativos
CREATE OR REPLACE VIEW vw_active_users AS
SELECT 
    u.id,
    u.email,
    u.name,
    u.department,
    u.last_login,
    COUNT(DISTINCT s.id) as active_sessions,
    MAX(s.last_activity) as last_activity
FROM users u
LEFT JOIN sessions s ON u.id = s.user_id AND s.expires_at > CURRENT_TIMESTAMP
WHERE u.is_active = TRUE
GROUP BY u.id, u.email, u.name, u.department, u.last_login;

-- View de resumo de acesso por aplicação
CREATE OR REPLACE VIEW vw_app_access_summary AS
SELECT 
    a.id as app_id,
    a.app_name,
    a.app_slug,
    COUNT(DISTINCT uaa.user_id) as total_users_with_access,
    COUNT(DISTINCT CASE WHEN s.expires_at > CURRENT_TIMESTAMP THEN s.user_id END) as active_users
FROM applications a
LEFT JOIN user_app_access uaa ON a.id = uaa.app_id
LEFT JOIN sessions s ON uaa.user_id = s.user_id
WHERE a.is_active = TRUE
GROUP BY a.id, a.app_name, a.app_slug;

-- ==========================================
-- DADOS INICIAIS
-- ==========================================

-- Inserir aplicações padrão
INSERT INTO applications (app_name, app_slug, app_url, app_description, icon_emoji) VALUES
('Dashboard Principal', 'dashboard', 'https://auth.importemelhor.com/dashboard.php', 'Painel principal', '🏠'),
('CCA Calculator', 'cca-calc', 'https://cca.importemelhor.com', 'Calculadora de CCA', '🧮'),
('Clean Log', 'cleanlog', 'https://cleanlog.importemelhor.com', 'Sistema de logs', '📋'),
('ECF Canton Fair', 'ecf-canton-fair', 'https://ecf.importemelhor.com', 'Sistema ECF', '🎪');

-- ==========================================
-- COMENTÁRIOS E DOCUMENTAÇÃO
-- ==========================================

COMMENT ON TABLE users IS 'Tabela de usuários autenticados via Microsoft Azure AD';
COMMENT ON TABLE sessions IS 'Sessões ativas dos usuários com tokens de acesso';
COMMENT ON TABLE applications IS 'Aplicações disponíveis no sistema SSO';
COMMENT ON TABLE user_app_access IS 'Controle de permissões de acesso por aplicação';
COMMENT ON TABLE audit_logs IS 'Logs de auditoria de todas as ações do sistema';

COMMENT ON FUNCTION sp_upsert_user IS 'Cria ou atualiza um usuário após autenticação Microsoft';
COMMENT ON FUNCTION sp_create_session IS 'Cria uma nova sessão para um usuário';
COMMENT ON FUNCTION sp_validate_session IS 'Valida se uma sessão é válida e retorna dados do usuário';
COMMENT ON FUNCTION sp_check_app_access IS 'Verifica se um usuário tem acesso a uma aplicação específica';
COMMENT ON FUNCTION sp_cleanup_expired_sessions IS 'Remove sessões expiradas do banco de dados';

-- ==========================================
-- QUERIES ÚTEIS PARA ADMINISTRAÇÃO
-- ==========================================

-- Para executar estas queries, descomente conforme necessário:

-- Ver todas as tabelas
-- SELECT table_name FROM information_schema.tables WHERE table_schema = 'public';

-- Ver todas as functions
-- SELECT routine_name FROM information_schema.routines WHERE routine_schema = 'public';

-- Testar function de upsert user
-- SELECT * FROM sp_upsert_user('test123', 'test@example.com', 'Test User', NULL, NULL, NULL);

-- Limpar sessões expiradas manualmente
-- SELECT sp_cleanup_expired_sessions();

-- Ver estatísticas de uso
-- SELECT * FROM vw_active_users;
-- SELECT * FROM vw_app_access_summary;

-- Ver logs recentes
-- SELECT u.name, al.action, al.created_at 
-- FROM audit_logs al 
-- LEFT JOIN users u ON al.user_id = u.id 
-- ORDER BY al.created_at DESC 
-- LIMIT 50;
