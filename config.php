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
            WHERE uaa.user_id = ? AND a.is_active = TRUE
            ORDER BY a.app_name
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetchAll();
        
        error_log("🔍 Apps para user_id $user_id: " . count($result));
        
        return $result;
    } catch (PDOException $e) {
        error_log("❌ Error getting user applications: " . $e->getMessage());
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

    // Banner Management Functions
    public function getActiveBanners() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM sp_get_active_banners()");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting active banners: " . $e->getMessage());
            return [];
        }
    }

    public function getAllBanners() {
        try {
            $stmt = $this->db->prepare("
                SELECT b.*, u.name as created_by_name
                FROM banners b
                LEFT JOIN users u ON b.created_by = u.id
                ORDER BY b.display_order ASC, b.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting all banners: " . $e->getMessage());
            return [];
        }
    }

    public function createBanner($title, $description, $image_url, $link_url, $link_text, $display_order, $user_id, $start_date = null, $end_date = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO banners (title, description, image_url, link_url, link_text, display_order, created_by, start_date, end_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                RETURNING *
            ");
            $stmt->execute([$title, $description, $image_url, $link_url, $link_text, $display_order, $user_id, $start_date, $end_date]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error creating banner: " . $e->getMessage());
            return false;
        }
    }

    public function updateBanner($id, $title, $description, $image_url, $link_url, $link_text, $display_order, $is_active, $start_date = null, $end_date = null) {
        try {
            $stmt = $this->db->prepare("
                UPDATE banners
                SET title = ?, description = ?, image_url = ?, link_url = ?, link_text = ?,
                    display_order = ?, is_active = ?, start_date = ?, end_date = ?
                WHERE id = ?
                RETURNING *
            ");
            $stmt->execute([$title, $description, $image_url, $link_url, $link_text, $display_order, $is_active, $start_date, $end_date, $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error updating banner: " . $e->getMessage());
            return false;
        }
    }

    public function deleteBanner($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM banners WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error deleting banner: " . $e->getMessage());
            return false;
        }
    }

    public function toggleBannerStatus($id) {
        try {
            $stmt = $this->db->prepare("
                UPDATE banners
                SET is_active = NOT is_active
                WHERE id = ?
                RETURNING is_active
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error toggling banner status: " . $e->getMessage());
            return false;
        }
    }

    // User Management Functions
    public function getAllUsers() {
        try {
            $stmt = $this->db->prepare("
                SELECT u.*,
                    COUNT(DISTINCT s.id) as active_sessions,
                    COUNT(DISTINCT uaa.app_id) as apps_count
                FROM users u
                LEFT JOIN sessions s ON u.id = s.user_id AND s.expires_at > NOW()
                LEFT JOIN user_app_access uaa ON u.id = uaa.user_id
                GROUP BY u.id
                ORDER BY u.last_login DESC NULLS LAST, u.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting all users: " . $e->getMessage());
            return [];
        }
    }

    public function toggleUserStatus($user_id) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users
                SET is_active = NOT is_active
                WHERE id = ?
                RETURNING is_active
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error toggling user status: " . $e->getMessage());
            return false;
        }
    }

    public function getUserAppAccess($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*,
                    CASE WHEN uaa.id IS NOT NULL THEN TRUE ELSE FALSE END as has_access,
                    uaa.granted_at,
                    u.name as granted_by_name
                FROM applications a
                LEFT JOIN user_app_access uaa ON a.id = uaa.app_id AND uaa.user_id = ?
                LEFT JOIN users u ON uaa.granted_by = u.id
                WHERE a.is_active = TRUE
                ORDER BY a.app_name
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting user app access: " . $e->getMessage());
            return [];
        }
    }

    public function grantAppAccess($user_id, $app_id, $granted_by) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO user_app_access (user_id, app_id, granted_by)
                VALUES (?, ?, ?)
                ON CONFLICT (user_id, app_id) DO NOTHING
                RETURNING *
            ");
            $stmt->execute([$user_id, $app_id, $granted_by]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error granting app access: " . $e->getMessage());
            return false;
        }
    }

    public function revokeAppAccess($user_id, $app_id) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM user_app_access
                WHERE user_id = ? AND app_id = ?
            ");
            return $stmt->execute([$user_id, $app_id]);
        } catch (PDOException $e) {
            error_log("Error revoking app access: " . $e->getMessage());
            return false;
        }
    }

    // Role Management Functions
    public function getAllRoles() {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*,
                    COUNT(DISTINCT u.id) as users_count
                FROM user_roles r
                LEFT JOIN users u ON r.id = u.role_id
                GROUP BY r.id
                ORDER BY r.id
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting all roles: " . $e->getMessage());
            return [];
        }
    }

    public function getUserRole($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*
                FROM user_roles r
                INNER JOIN users u ON r.id = u.role_id
                WHERE u.id = ?
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error getting user role: " . $e->getMessage());
            return false;
        }
    }

    public function updateUserRole($user_id, $role_id) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users
                SET role_id = ?
                WHERE id = ?
            ");
            return $stmt->execute([$role_id, $user_id]);
        } catch (PDOException $e) {
            error_log("Error updating user role: " . $e->getMessage());
            return false;
        }
    }

    public function checkPermission($user_id, $permission) {
        try {
            $stmt = $this->db->prepare("SELECT sp_check_user_permission(?, ?)");
            $stmt->execute([$user_id, $permission]);
            $result = $stmt->fetch();
            return $result ? $result['sp_check_user_permission'] : false;
        } catch (PDOException $e) {
            error_log("Error checking permission: " . $e->getMessage());
            return false;
        }
    }

    public function createRole($name, $slug, $description, $permissions) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO user_roles (name, slug, description, is_admin, can_manage_users, can_manage_banners, can_manage_apps, can_access_external_sites)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                RETURNING *
            ");
            $stmt->execute([
                $name,
                $slug,
                $description,
                $permissions['is_admin'] ?? false,
                $permissions['can_manage_users'] ?? false,
                $permissions['can_manage_banners'] ?? false,
                $permissions['can_manage_apps'] ?? false,
                $permissions['can_access_external_sites'] ?? false
            ]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error creating role: " . $e->getMessage());
            return false;
        }
    }

    public function updateRole($role_id, $name, $description, $permissions) {
        try {
            $stmt = $this->db->prepare("
                UPDATE user_roles
                SET name = ?, description = ?, is_admin = ?, can_manage_users = ?, can_manage_banners = ?, can_manage_apps = ?, can_access_external_sites = ?
                WHERE id = ?
                RETURNING *
            ");
            $stmt->execute([
                $name,
                $description,
                $permissions['is_admin'] ?? false,
                $permissions['can_manage_users'] ?? false,
                $permissions['can_manage_banners'] ?? false,
                $permissions['can_manage_apps'] ?? false,
                $permissions['can_access_external_sites'] ?? false,
                $role_id
            ]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error updating role: " . $e->getMessage());
            return false;
        }
    }

    // Notification Management Functions
    public function createNotification($title, $message, $type, $target_type, $target_value, $created_by, $expires_at = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (title, message, type, target_type, target_value, created_by, expires_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                RETURNING *
            ");
            $stmt->execute([$title, $message, $type, $target_type, $target_value, $created_by, $expires_at]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error creating notification: " . $e->getMessage());
            return false;
        }
    }

    public function getUserNotifications($user_id, $include_read = false) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM sp_get_user_notifications(?, ?)");
            $stmt->execute([$user_id, $include_read]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting user notifications: " . $e->getMessage());
            return [];
        }
    }

    public function getUnreadNotificationCount($user_id) {
        try {
            $stmt = $this->db->prepare("SELECT sp_count_unread_notifications(?)");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            return $result ? (int)$result['sp_count_unread_notifications'] : 0;
        } catch (PDOException $e) {
            error_log("Error counting unread notifications: " . $e->getMessage());
            return 0;
        }
    }

    public function markNotificationAsRead($notification_id, $user_id) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notification_reads (notification_id, user_id)
                VALUES (?, ?)
                ON CONFLICT (notification_id, user_id) DO NOTHING
                RETURNING *
            ");
            $stmt->execute([$notification_id, $user_id]);
            return true;
        } catch (PDOException $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }

    public function getAllNotifications() {
        try {
            $stmt = $this->db->prepare("
                SELECT n.*, u.name as created_by_name,
                    COUNT(DISTINCT nr.id) as read_count
                FROM notifications n
                LEFT JOIN users u ON n.created_by = u.id
                LEFT JOIN notification_reads nr ON n.id = nr.notification_id
                GROUP BY n.id, u.name
                ORDER BY n.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting all notifications: " . $e->getMessage());
            return [];
        }
    }

    public function deleteNotification($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM notifications WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error deleting notification: " . $e->getMessage());
            return false;
        }
    }

    // User Edit Functions
    public function updateUser($user_id, $name, $job_title, $department) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users
                SET name = ?, job_title = ?, department = ?
                WHERE id = ?
                RETURNING *
            ");
            $stmt->execute([$name, $job_title, $department, $user_id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error updating user: " . $e->getMessage());
            return false;
        }
    }

    public function getUserById($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, r.name as role_name
                FROM users u
                LEFT JOIN user_roles r ON u.role_id = r.id
                WHERE u.id = ?
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error getting user by id: " . $e->getMessage());
            return false;
        }
    }

    // Check if user is admin
    public function isAdmin($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT r.is_admin
                FROM users u
                LEFT JOIN user_roles r ON u.role_id = r.id
                WHERE u.id = ?
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            return $result ? (bool)$result['is_admin'] : false;
        } catch (PDOException $e) {
            error_log("Error checking if user is admin: " . $e->getMessage());
            return false;
        }
    }
}
