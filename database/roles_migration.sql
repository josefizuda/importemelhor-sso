-- ==========================================
-- MIGRATION - USER ROLES SYSTEM
-- Data: 2025-11-03
-- ==========================================

-- Tabela de roles/tipos de usuários
CREATE TABLE IF NOT EXISTS user_roles (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    is_admin BOOLEAN DEFAULT FALSE,
    can_manage_users BOOLEAN DEFAULT FALSE,
    can_manage_banners BOOLEAN DEFAULT FALSE,
    can_manage_apps BOOLEAN DEFAULT FALSE,
    can_access_external_sites BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Adicionar coluna role_id na tabela users
ALTER TABLE users ADD COLUMN IF NOT EXISTS role_id INTEGER REFERENCES user_roles(id) ON DELETE SET NULL;

-- Índices
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role_id);
CREATE INDEX IF NOT EXISTS idx_user_roles_slug ON user_roles(slug);

-- Inserir roles padrão
INSERT INTO user_roles (name, slug, description, is_admin, can_manage_users, can_manage_banners, can_manage_apps, can_access_external_sites) VALUES
('Administrador', 'admin', 'Acesso total ao sistema', TRUE, TRUE, TRUE, TRUE, TRUE),
('Gerente', 'manager', 'Pode gerenciar usuários e banners', FALSE, TRUE, TRUE, FALSE, TRUE),
('Usuário', 'user', 'Usuário padrão com acesso às ferramentas', FALSE, FALSE, FALSE, FALSE, FALSE)
ON CONFLICT (slug) DO NOTHING;

-- Atualizar usuário admin existente
UPDATE users
SET role_id = (SELECT id FROM user_roles WHERE slug = 'admin')
WHERE email = 'app@importemelhor.com.br';

-- Atualizar outros usuários para role 'user'
UPDATE users
SET role_id = (SELECT id FROM user_roles WHERE slug = 'user')
WHERE role_id IS NULL;

-- Function: Verificar permissão do usuário
CREATE OR REPLACE FUNCTION sp_check_user_permission(
    p_user_id INTEGER,
    p_permission VARCHAR
)
RETURNS BOOLEAN AS $$
DECLARE
    has_permission BOOLEAN;
BEGIN
    SELECT
        CASE p_permission
            WHEN 'admin' THEN r.is_admin
            WHEN 'manage_users' THEN r.can_manage_users
            WHEN 'manage_banners' THEN r.can_manage_banners
            WHEN 'manage_apps' THEN r.can_manage_apps
            WHEN 'access_external_sites' THEN r.can_access_external_sites
            ELSE FALSE
        END INTO has_permission
    FROM users u
    INNER JOIN user_roles r ON u.role_id = r.id
    WHERE u.id = p_user_id;

    RETURN COALESCE(has_permission, FALSE);
END;
$$ LANGUAGE plpgsql;

-- Comentários
COMMENT ON TABLE user_roles IS 'Tipos de usuários e suas permissões';
COMMENT ON FUNCTION sp_check_user_permission IS 'Verifica se um usuário tem uma permissão específica';
