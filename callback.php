<?php
require_once 'config.php';

$auth = new Auth();

define('AZURE_CLIENT_ID', getenv('AZURE_CLIENT_ID'));
define('AZURE_CLIENT_SECRET', getenv('AZURE_CLIENT_SECRET'));
define('AZURE_TENANT_ID', getenv('AZURE_TENANT_ID'));
define('AZURE_REDIRECT_URI', getenv('AZURE_REDIRECT_URI'));
define('BASE_URL', getenv('APP_URL'));

function redirectWithError($message) {
    error_log("SSO Error: " . $message);
    header('Location: ' . BASE_URL . '/index.php?error=' . urlencode($message));
    exit();
}

function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => $response
    ];
}

if (!AZURE_CLIENT_SECRET || !AZURE_TENANT_ID) {
    redirectWithError('Configurações do Azure não definidas.');
}

if (isset($_GET['error'])) {
    $errorDescription = $_GET['error_description'] ?? 'Erro desconhecido';
    redirectWithError('Erro: ' . $errorDescription);
}

if (!isset($_GET['code'])) {
    redirectWithError('Código não recebido');
}

$tokenUrl = "https://login.microsoftonline.com/" . AZURE_TENANT_ID . "/oauth2/v2.0/token";

$tokenData = [
    'client_id' => AZURE_CLIENT_ID,
    'client_secret' => AZURE_CLIENT_SECRET,
    'code' => $_GET['code'],
    'redirect_uri' => AZURE_REDIRECT_URI,
    'grant_type' => 'authorization_code',
    'scope' => 'openid profile email User.Read'
];

$response = makeRequest($tokenUrl, 'POST', http_build_query($tokenData), [
    'Content-Type: application/x-www-form-urlencoded'
]);

if ($response['code'] !== 200) {
    error_log('Azure Token Error: ' . $response['body']);
    redirectWithError('Erro ao obter token');
}

$tokenResponse = json_decode($response['body'], true);

if (!isset($tokenResponse['access_token'])) {
    redirectWithError('Token não recebido');
}

$accessToken = $tokenResponse['access_token'];
$refreshToken = $tokenResponse['refresh_token'] ?? null;
$idToken = $tokenResponse['id_token'] ?? null;

if (!$idToken) {
    redirectWithError('ID Token não recebido');
}

$idTokenParts = explode('.', $idToken);
if (count($idTokenParts) !== 3) {
    redirectWithError('Token inválido');
}

$payload = json_decode(base64_decode(strtr($idTokenParts[1], '-_', '+/')), true);

$userEmail = $payload['email'] ?? $payload['preferred_username'] ?? null;
$userName = $payload['name'] ?? 'Usuário';
$microsoftId = $payload['oid'] ?? $payload['sub'] ?? null;

if (!$userEmail || !$microsoftId) {
    redirectWithError('Email ou ID não encontrado no token');
}

// Tentar criar/atualizar usuário manualmente (sem stored procedure)
try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar se usuário existe
    $stmt = $db->prepare("SELECT * FROM users WHERE microsoft_id = $1");
    $stmt->execute([$microsoftId]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Atualizar
        $stmt = $db->prepare("
            UPDATE users 
            SET email = $1, name = $2, last_login = NOW(), updated_at = NOW()
            WHERE microsoft_id = $3
            RETURNING *
        ");
        $stmt->execute([$userEmail, $userName, $microsoftId]);
        $user = $stmt->fetch();
    } else {
        // Inserir
        $stmt = $db->prepare("
            INSERT INTO users (microsoft_id, email, name, last_login, created_at, updated_at)
            VALUES ($1, $2, $3, NOW(), NOW(), NOW())
            RETURNING *
        ");
        $stmt->execute([$microsoftId, $userEmail, $userName]);
        $user = $stmt->fetch();
    }
    
    if (!$user) {
        throw new Exception("Falha ao criar/atualizar usuário");
    }
    
    // Criar sessão
    $sessionToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + (60 * 60 * 24 * 7)); // 7 dias
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $db->prepare("
        INSERT INTO sessions (user_id, session_token, access_token, refresh_token, expires_at, ip_address, user_agent, created_at, last_activity)
        VALUES ($1, $2, $3, $4, $5, $6, $7, NOW(), NOW())
    ");
    $stmt->execute([
        $user['id'],
        $sessionToken,
        $accessToken,
        $refreshToken,
        $expiresAt,
        $ipAddress,
        $userAgent
    ]);
    
    // Definir cookie
    setcookie('sso_token', $sessionToken, [
        'expires' => time() + (60 * 60 * 24 * 7),
        'path' => '/',
        'domain' => '.importemelhor.com.br',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    error_log("Login SSO bem-sucedido: {$userEmail}");
    
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit();
    
} catch (Exception $e) {
    error_log("Erro ao criar usuário: " . $e->getMessage());
    redirectWithError('Erro ao processar login. Contate o administrador.');
}
