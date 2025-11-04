-- Create table for system settings/configurations
CREATE TABLE IF NOT EXISTS system_settings (
    id SERIAL PRIMARY KEY,
    setting_key VARCHAR(255) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(50) DEFAULT 'string', -- string, number, boolean, json
    description TEXT,
    is_sensitive BOOLEAN DEFAULT FALSE, -- for passwords, API keys, etc
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_by INTEGER REFERENCES users(id)
);

-- Create index
CREATE INDEX IF NOT EXISTS idx_system_settings_key ON system_settings(setting_key);

-- Insert default Google Analytics settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description, is_sensitive)
VALUES
    ('ga4_property_id', NULL, 'string', 'Google Analytics 4 Property ID (ex: G-XXXXXXXXXX)', FALSE),
    ('ga4_measurement_id', NULL, 'string', 'Google Analytics 4 Measurement ID', FALSE),
    ('ga4_api_secret', NULL, 'string', 'Google Analytics 4 API Secret para Measurement Protocol', TRUE),
    ('ga4_service_account_json', NULL, 'json', 'Google Service Account JSON para Google Analytics Data API', TRUE),
    ('analytics_enabled', 'false', 'boolean', 'Habilitar integração com Google Analytics', FALSE)
ON CONFLICT (setting_key) DO NOTHING;

COMMENT ON TABLE system_settings IS 'System-wide settings and configurations';
COMMENT ON COLUMN system_settings.is_sensitive IS 'Sensitive data (passwords, API keys) - should be encrypted';
