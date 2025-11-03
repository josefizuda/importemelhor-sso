-- ==========================================
-- MIGRATION - BANNERS CAROUSEL SYSTEM
-- Data: 2025-11-03
-- ==========================================

-- Tabela de banners para o carousel
CREATE TABLE IF NOT EXISTS banners (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image_url TEXT NOT NULL,
    link_url TEXT,
    link_text VARCHAR(100),
    display_order INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    start_date TIMESTAMP,
    end_date TIMESTAMP,
    created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Índices para otimização
CREATE INDEX IF NOT EXISTS idx_banners_active ON banners(is_active);
CREATE INDEX IF NOT EXISTS idx_banners_order ON banners(display_order);
CREATE INDEX IF NOT EXISTS idx_banners_dates ON banners(start_date, end_date);

-- Trigger para atualizar updated_at automaticamente
CREATE TRIGGER update_banners_updated_at
    BEFORE UPDATE ON banners
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Function: Obter banners ativos
CREATE OR REPLACE FUNCTION sp_get_active_banners()
RETURNS TABLE (
    id INTEGER,
    title VARCHAR(255),
    description TEXT,
    image_url TEXT,
    link_url TEXT,
    link_text VARCHAR(100),
    display_order INTEGER
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        b.id,
        b.title,
        b.description,
        b.image_url,
        b.link_url,
        b.link_text,
        b.display_order
    FROM banners b
    WHERE b.is_active = TRUE
    AND (b.start_date IS NULL OR b.start_date <= CURRENT_TIMESTAMP)
    AND (b.end_date IS NULL OR b.end_date >= CURRENT_TIMESTAMP)
    ORDER BY b.display_order ASC, b.created_at DESC;
END;
$$ LANGUAGE plpgsql;

-- Comentários
COMMENT ON TABLE banners IS 'Banners para o carousel da dashboard - gerenciável pelo admin';
COMMENT ON FUNCTION sp_get_active_banners IS 'Retorna todos os banners ativos baseado em datas e status';

-- ==========================================
-- DADOS DE EXEMPLO (OPCIONAL)
-- ==========================================

-- Banner de boas-vindas padrão
INSERT INTO banners (title, description, image_url, link_url, link_text, display_order, is_active)
VALUES
(
    'Bem-vindo ao Importe Melhor SSO',
    'Acesse todas as suas ferramentas de importação em um só lugar',
    'https://via.placeholder.com/1200x300/0423b2/ffffff?text=Bem-vindo+ao+Importe+Melhor',
    '/dashboard.php?page=apps',
    'Ver Ferramentas',
    1,
    TRUE
),
(
    'Nova Ferramenta: CCA Calculator',
    'Calcule seus custos de importação com facilidade',
    'https://via.placeholder.com/1200x300/83f100/0423b2?text=CCA+Calculator',
    'https://cca.importemelhor.com',
    'Acessar Agora',
    2,
    TRUE
),
(
    'Clean Log - Gerencie seus Logs',
    'Sistema completo de gerenciamento de logs e rastreamento',
    'https://via.placeholder.com/1200x300/021a75/ffffff?text=Clean+Log',
    'https://cleanlog.importemelhor.com',
    'Conhecer',
    3,
    TRUE
);
