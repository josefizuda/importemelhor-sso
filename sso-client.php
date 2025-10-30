<?php
/**
 * SSO Client Library - Importe Melhor (PostgreSQL)
 * 
 * Adicione este arquivo em TODAS as suas aplicações
 * e use no início de cada página protegida
 * 
 * Uso:
 * require_once 'sso-client.php';
 * $sso = new SSOClient('slug-da-app');
 * $user = $sso->getUser();
 */

class SSOClient {
    private $auth_url = 'https://auth.importemelhor.com';
    private $db;
    private $current_app_slug;
    
    public function __construct($app_slug) {
        $this->current_app_slug = $app_slug;
        $this->connectDatabase();
    }
    
    private function connectDatabase() {
        try {
            // Carrega variáveis de ambiente se disponível
            if (file_exists(__DIR__ . '/.env')) {
                $env = parse_ini_file(__DIR__ . '/.env');
                $db_host = $env['DB_HOST'] ?? 'bd-sso';
                $db_port = $env['DB_PORT'] ?? '5432';
                $db_name = $env['DB_NAME'] ?? 'importemelhor_sso';
                $db_user = $env['DB_USER'] ?? 'sso_user';
                $db_pass = $env['DB_PASS'] ?? '';
            } else {
                // Valores padrão se não tiver .env
                $db_host = 'bd-sso';
                $db_port = '5432';
                $db_name = 'importemelhor_sso';
                $db_user = 'sso_user';
                $db_pass = 'SUA_SENHA_AQUI'; // Substitua pela senha real
            }
            
            $dsn = "pgsql:host={$db_host};port={$db_port};dbname={$db_name};options='--client_encoding=UTF8'";
            
            $this->db = new PDO(
                $dsn,
                $db_user,
                $db_pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch(PDOException $e) {
            error_log("SSO Database error: " . $e->getMessage());
            $this->showError("Erro de conexão SSO. Contate o administrador.");
        }
    }
    
    public function checkAuth() {
        // Verifica cookie
        if (!isset($_COOKIE['sso_token'])) {
            $this->redirectToLogin();
        }
        
        // Valida sessão
        try {
            $stmt = $this->db->prepare("SELECT * FROM sp_validate_session($1)");
            $stmt->execute([$_COOKIE['sso_token']]);
            $session = $stmt->fetch();
            
            if (!$session) {
                $this->clearCookie();
                $this->redirectToLogin();
            }
            
            // Verifica acesso à aplicação
            $stmt = $this->db->prepare("SELECT * FROM sp_check_app_access($1, $2)");
            $stmt->execute([$session['user_id'], $this->current_app_slug]);
            $access = $stmt->fetch();
            
            if (!$access || !$access['has_access']) {
                $this->showNoAccess();
            }
            
            return $session;
            
        } catch (PDOException $e) {
            error_log("SSO validation error: " . $e->getMessage());
            $this->redirectToLogin();
        }
    }
    
    private function redirectToLogin() {
        $current_url = $this->getCurrentUrl();
        $login_url = $this->auth_url . '/index.php?return_url=' . urlencode($current_url);
        header('Location: ' . $login_url);
        exit;
    }
    
    private function getCurrentUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        return $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
    
    private function clearCookie() {
        setcookie('sso_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '.importemelhor.com',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    
    private function showNoAccess() {
        http_response_code(403);
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Acesso Negado</title>
            <style>
                body { 
                    font-family: 'Segoe UI', sans-serif; 
                    display: flex; 
                    justify-content: center; 
                    align-items: center; 
                    min-height: 100vh; 
                    margin: 0; 
                    background: #f5f7fa; 
                }
                .container { 
                    text-align: center; 
                    padding: 40px; 
                    background: white; 
                    border-radius: 12px; 
                    box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
                    max-width: 500px;
                }
                .icon { font-size: 64px; margin-bottom: 20px; }
                h1 { color: #333; margin-bottom: 10px; }
                p { color: #666; margin-bottom: 30px; line-height: 1.6; }
                a { 
                    background: #667eea; 
                    color: white; 
                    padding: 12px 24px; 
                    border-radius: 6px; 
                    text-decoration: none; 
                    display: inline-block;
                    transition: all 0.3s;
                }
                a:hover { 
                    background: #5568d3; 
                    transform: translateY(-2px);
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="icon">🔒</div>
                <h1>Acesso Negado</h1>
                <p>Você não tem permissão para acessar esta ferramenta.<br>
                   Entre em contato com o administrador para solicitar acesso.</p>
                <a href="<?php echo $this->auth_url; ?>/dashboard.php">Voltar ao Dashboard</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
    
    private function showError($message) {
        http_response_code(500);
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <title>Erro</title>
            <style>
                body { 
                    font-family: 'Segoe UI', sans-serif; 
                    display: flex; 
                    justify-content: center; 
                    align-items: center; 
                    min-height: 100vh; 
                    margin: 0; 
                    background: #f5f7fa; 
                }
                .container { 
                    text-align: center; 
                    padding: 40px; 
                    background: white; 
                    border-radius: 12px; 
                    box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
                }
                .icon { font-size: 64px; margin-bottom: 20px; }
                h1 { color: #dc3545; }
                p { color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="icon">⚠️</div>
                <h1>Erro</h1>
                <p><?php echo htmlspecialchars($message); ?></p>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
    
    public function getUser() {
        return $this->checkAuth();
    }
    
    public function getUserId() {
        return $this->getUser()['user_id'];
    }
    
    public function getUserEmail() {
        return $this->getUser()['email'];
    }
    
    public function getUserName() {
        return $this->getUser()['name'];
    }
}
