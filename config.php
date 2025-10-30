<?php
session_start();

define('CLIENT_ID', getenv('AZURE_CLIENT_ID') ?: 'ac66d4b8-04a0-4534-9e02-3a7a497784f8');
define('CLIENT_SECRET', getenv('AZURE_CLIENT_SECRET') ?: '');
define('TENANT_ID', getenv('AZURE_TENANT_ID') ?: '');
define('REDIRECT_URI', getenv('APP_URL') . '/callback.php');

define('AUTHORITY', 'https://login.microsoftonline.com/' . TENANT_ID);
define('AUTHORIZE_ENDPOINT', AUTHORITY . '/oauth2/v2.0/authorize');
define('TOKEN_ENDPOINT', AUTHORITY . '/oauth2/v2.0/token');
define('USER_ENDPOINT', 'https://graph.microsoft.com/v1.0/me');
define('SCOPES', 'openid profile email User.Read');

define('DB_HOST', getenv('DB_HOST') ?: 'bd-sso');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: 'importemelhor_sso');
define('DB_USER', getenv('DB_USER') ?: 'sso_user');
define('DB_PASS', getenv('DB_PASS') ?: '');

define('COOKIE_DOMAIN', getenv('COOKIE_DOMAIN') ?: '.importemelhor.com.br');
define('COOKIE_SECURE', true);
define('COOKIE_HTTPONLY', true);
define('COOKIE_SAMESITE', 'Lax');
define('SESSION_LIFETIME', 60 * 60 * 24 * 7);

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = sprintf(
                "pgsql:host=%s;port=%s;dbname=%s",
                DB_HOST,
                DB_PORT,
                DB_NAME
            );
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch(PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            die("Erro de conexão. Verifique as configurações do banco de dados.");
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
        // SQL direto ao invés de stored procedure
        $stmt = $this->db->prepare("
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
            WHERE s.session_token = ?
            AND s.expires_at > NOW()
            AND u.is_active = TRUE
        ");
        $stmt->execute([$session_token]);
        $result = $stmt->fetch();
        
        error_log("🔍 Validação sessão: " . ($result ? "VÁLIDA" : "INVÁLIDA"));
        if ($result) {
            error_log("✅ Usuário: " . $result['email']);
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("❌ Error validating session: " . $e->getMessage());
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
