-- Create table for system settings/configurations
CREATE TABLE IF NOT EXISTS system_settings (
    id SERIAL PRIMARY KEY,
    setting_key VARCHAR(255) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(50) DEFAULT 'string', -- string, number, boolean, json
    description TEXT,
    is_sensitive BOOLEAN DEFAULT FALSE, -- for passwords, API keys, etc
    category VARCHAR(100) DEFAULT 'general', -- general, analytics, advertising, security
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_by INTEGER REFERENCES users(id)
);

-- Create indexes
CREATE INDEX IF NOT EXISTS idx_system_settings_key ON system_settings(setting_key);
CREATE INDEX IF NOT EXISTS idx_system_settings_category ON system_settings(category);

-- Insert default settings for all integrations
INSERT INTO system_settings (setting_key, setting_value, setting_type, description, is_sensitive, category)
VALUES
    -- Google Analytics
    ('ga4_property_id', NULL, 'string', 'Google Analytics 4 Property ID (ex: G-XXXXXXXXXX)', FALSE, 'analytics'),
    ('ga4_measurement_id', NULL, 'string', 'Google Analytics 4 Measurement ID', FALSE, 'analytics'),
    ('ga4_api_secret', NULL, 'string', 'Google Analytics 4 API Secret para Measurement Protocol', TRUE, 'analytics'),
    ('ga4_service_account_json', NULL, 'json', 'Google Service Account JSON para Google Analytics Data API', TRUE, 'analytics'),
    ('analytics_enabled', 'false', 'boolean', 'Habilitar integração com Google Analytics', FALSE, 'analytics'),

    -- Facebook Pixel
    ('facebook_pixel_id', NULL, 'string', 'Facebook Pixel ID', FALSE, 'advertising'),
    ('facebook_pixel_enabled', 'false', 'boolean', 'Habilitar Facebook Pixel', FALSE, 'advertising'),
    ('facebook_access_token', NULL, 'string', 'Facebook Access Token para Conversions API', TRUE, 'advertising'),

    -- Google Ads
    ('google_ads_id', NULL, 'string', 'Google Ads Conversion ID (ex: AW-XXXXXXXXXX)', FALSE, 'advertising'),
    ('google_ads_label', NULL, 'string', 'Google Ads Conversion Label', FALSE, 'advertising'),
    ('google_ads_enabled', 'false', 'boolean', 'Habilitar Google Ads Conversion Tracking', FALSE, 'advertising'),

    -- reCAPTCHA
    ('recaptcha_site_key', NULL, 'string', 'Google reCAPTCHA Site Key (v3)', FALSE, 'security'),
    ('recaptcha_secret_key', NULL, 'string', 'Google reCAPTCHA Secret Key (v3)', TRUE, 'security'),
    ('recaptcha_enabled', 'false', 'boolean', 'Habilitar reCAPTCHA no login', FALSE, 'security'),
    ('recaptcha_threshold', '0.5', 'number', 'Score mínimo do reCAPTCHA (0.0 a 1.0)', FALSE, 'security')
ON CONFLICT (setting_key) DO NOTHING;

COMMENT ON TABLE system_settings IS 'System-wide settings and configurations';
COMMENT ON COLUMN system_settings.is_sensitive IS 'Sensitive data (passwords, API keys) - should be encrypted';
COMMENT ON COLUMN system_settings.category IS 'Setting category for grouping (general, analytics, advertising, security)';
