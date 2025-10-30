<?php
require_once 'config.php';

$auth = new Auth();

define('AZURE_CLIENT_ID', getenv('AZURE_CLIENT_ID'));
define('AZURE_CLIENT_SECRET', getenv('AZURE_CLIENT_SECRET'));
define('AZURE_TENANT_ID', getenv('AZURE_TENANT_ID'));
define('AZURE_REDIRECT_URI', getenv('AZURE_REDIRECT_URI'));
define('BASE_URL', getenv('APP_URL'));

function redirectWithError($message) {
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
    redirectWithError('Erro ao obter token. Código: ' . $response['code']);
}

$tokenResponse = json_decode($response['body'], true);

if (!isset($tokenResponse['access_token'])) {
    redirectWithError('Token não recebido');
}

$accessToken = $tokenResponse['access_token'];
$refreshToken = $tokenResponse['refresh_token'] ?? null;
$idToken = $tokenResponse['id_token'] ?? null;

if ($idToken) {
    $idTokenParts = explode('.', $idToken);
    if (count($idTokenParts) === 3) {
        $payload = json_decode(base64_decode(strtr($idTokenParts[1], '-_', '+/')), true);
        
        $userEmail = $payload['email'] ?? $payload['preferred_username'] ?? null;
        $userName = $payload['name'] ?? 'Usuário';
        $microsoftId = $payload['oid'] ?? $payload['sub'] ?? null;
        
        if (!$userEmail || !$microsoftId) {
            redirectWithError('Email ou ID não encontrado');
        }
        
        // Criar ou atualizar usuário no PostgreSQL
        $user = $auth->upsertUser($microsoftId, $userEmail, $userName);
        
        if (!$user) {
            redirectWithError('Erro ao criar usuário');
        }
        
        // Criar sessão no PostgreSQL
        $sessionToken = $auth->createSession($user['id'], $accessToken, $refreshToken);
        
        if (!$sessionToken) {
            redirectWithError('Erro ao criar sessão');
        }
        
        // Definir cookie SSO
        $auth->setSessionCookie($sessionToken);
        
        error_log("Login SSO: {$userEmail}");
        
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit();
    }
}

redirectWithError('Token inválido');
