<?php
session_start();

// Carrega variáveis de ambiente
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// Configurações do Azure AD
define('CLIENT_ID', $_ENV['AZURE_CLIENT_ID'] ?? 'SEU_CLIENT_ID');
define('CLIENT_SECRET', $_ENV['AZURE_CLIENT_SECRET'] ?? 'SEU_CLIENT_SECRET');
define('TENANT_ID', $_ENV['AZURE_TENANT_ID'] ?? 'SEU_TENANT_ID');
define('REDIRECT_URI', ($_ENV['APP_URL'] ?? 'https://auth.importemelhor.com') . '/callback.php');

// URLs da Microsoft
define('AUTHORITY', 'https://login.microsoftonline.com/' . TENANT_ID);
define('AUTHORIZE_ENDPOINT', AUTHORITY . '/oauth2/v2.0/authorize');
define('TOKEN_ENDPOINT', AUTHORITY . '/oauth2/v2.0/token');
define('USER_ENDPOINT', 'https://graph.microsoft.com/v1.0/me');
define('PHOTO_ENDPOINT', 'https://graph.microsoft.com/v1.0/me/photo/$value');
define('SCOPES', 'openid profile email User.Read');

// Configurações do Banco (PostgreSQL)
define('DB_HOST', $_ENV['DB_HOST'] ?? 'bd-sso');
define('DB_PORT', $_ENV['DB_PORT'] ?? '5432');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'importemelhor_sso');
define('DB_USER', $_ENV['DB_USER'] ?? 'sso_user');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// Configurações de Cookie
define('COOKIE_DOMAIN', $_ENV['COOKIE_DOMAIN'] ?? '.importemelhor.com');
define('COOKIE_SECURE', filter_var($_ENV['COOKIE_SECURE'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('COOKIE_HTTPONLY', true);
define('COOKIE_SAMESITE', 'Lax');
define('SESSION_LIFETIME', 60 * 60 * 24 * 7); // 7 dias

// Database Singleton para PostgreSQL
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = sprintf(
                "pgsql:host=%s;port=%s;dbname=%s;options='--client_encoding=UTF8'",
                DB_HOST,
                DB_PORT,
                DB_NAME
            );
            
            $this->connection = new PDO(
                $dsn,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            die("Erro de conexão com o banco de dados. Tente novamente mais tarde.");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
}

// Auth Class adaptada para PostgreSQL
class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generateSessionToken() {
        return bin2hex(random_bytes(32));
    }
    
    public function upsertUser($microsoft_id, $email, $name, $photo_url = null, $job_title = null, $department = null) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM sp_upsert_user($1, $2, $3, $4, $5, $6)");
            $stmt->execute([$microsoft_id, $email, $name, $photo_url, $job_title, $department]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error upserting user: " . $e->getMessage());
            return false;
        }
    }
    
    public function createSession($user_id, $access_token, $refresh_token = null) {
        try {
            $session_token = $this->generateSessionToken();
            $expires_at = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt = $this->db->prepare("SELECT * FROM sp_create_session($1, $2, $3, $4, $5, $6, $7)");
            $stmt->execute([
                $user_id,
                $session_token,
                $access_token,
                $expires_at,
                $ip_address,
                $user_agent,
                $refresh_token
            ]);
            
            return $session_token;
        } catch (PDOException $e) {
            error_log("Error creating session: " . $e->getMessage());
            return false;
        }
    }
    
    public function validateSession($session_token) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM sp_validate_session($1)");
            $stmt->execute([$session_token]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error validating session: " . $e->getMessage());
            return false;
        }
    }
    
    public function checkAppAccess($user_id, $app_slug) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM sp_check_app_access($1, $2)");
            $stmt->execute([$user_id, $app_slug]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error checking app access: " . $e->getMessage());
            return false;
        }
    }
    
    public function getUserApplications($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT a.* 
                FROM applications a
                INNER JOIN user_app_access uaa ON a.id = uaa.app_id
                WHERE uaa.user_id = $1 AND a.is_active = TRUE
                ORDER BY a.app_name
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting user applications: " . $e->getMessage());
            return [];
        }
    }
    
    public function logAudit($user_id, $action, $app_id = null, $details = null) {
        try {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $details_json = $details ? json_encode($details) : null;
            
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs (user_id, action, app_id, ip_address, user_agent, details)
                VALUES ($1, $2, $3, $4, $5, $6::jsonb)
            ");
            $stmt->execute([$user_id, $action, $app_id, $ip_address, $user_agent, $details_json]);
        } catch (PDOException $e) {
            error_log("Error logging audit: " . $e->getMessage());
        }
    }
    
    public function destroySession($session_token) {
        try {
            $stmt = $this->db->prepare("DELETE FROM sessions WHERE session_token = $1");
            return $stmt->execute([$session_token]);
        } catch (PDOException $e) {
            error_log("Error destroying session: " . $e->getMessage());
            return false;
        }
    }
    
    public function setSessionCookie($session_token) {
        setcookie('sso_token', $session_token, [
            'expires' => time() + SESSION_LIFETIME,
            'path' => '/',
            'domain' => COOKIE_DOMAIN,
            'secure' => COOKIE_SECURE,
            'httponly' => COOKIE_HTTPONLY,
            'samesite' => COOKIE_SAMESITE
        ]);
    }
    
    public function clearSessionCookie() {
        setcookie('sso_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => COOKIE_DOMAIN,
            'secure' => COOKIE_SECURE,
            'httponly' => COOKIE_HTTPONLY,
            'samesite' => COOKIE_SAMESITE
        ]);
    }
}
