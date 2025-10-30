<?php
session_start();

define('AZURE_CLIENT_ID', getenv('AZURE_CLIENT_ID') ?: 'ac66d4b8-04a0-4534-9e02-3a7a49778af8');
define('AZURE_CLIENT_SECRET', getenv('AZURE_CLIENT_SECRET'));
define('AZURE_TENANT_ID', getenv('AZURE_TENANT_ID'));
define('AZURE_REDIRECT_URI', getenv('AZURE_REDIRECT_URI') ?: 'https://auth.importemelhor.com.br/callback.php');
define('BASE_URL', 'https://auth.importemelhor.com.br');

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
    redirectWithError('Configurações do Azure não definidas. Configure AZURE_CLIENT_SECRET e AZURE_TENANT_ID no Easypanel.');
}

if (isset($_GET['error'])) {
    $errorDescription = $_GET['error_description'] ?? 'Erro desconhecido';
    redirectWithError('Erro na autenticação: ' . $errorDescription);
}

if (!isset($_GET['code'])) {
    redirectWithError('Código de autorização não recebido');
}

$authorizationCode = $_GET['code'];

$tokenUrl = "https://login.microsoftonline.com/" . AZURE_TENANT_ID . "/oauth2/v2.0/token";

$tokenData = [
    'client_id' => AZURE_CLIENT_ID,
    'client_secret' => AZURE_CLIENT_SECRET,
    'code' => $authorizationCode,
    'redirect_uri' => AZURE_REDIRECT_URI,
    'grant_type' => 'authorization_code',
    'scope' => 'openid profile email User.Read'
];

$response = makeRequest($tokenUrl, 'POST', http_build_query($tokenData), [
    'Content-Type: application/x-www-form-urlencoded'
]);

if ($response['code'] !== 200) {
    error_log('Azure AD Token Error: ' . $response['body']);
    redirectWithError('Erro ao obter token de acesso. Código: ' . $response['code']);
}

$tokenResponse = json_decode($response['body'], true);

if (!isset($tokenResponse['access_token'])) {
    redirectWithError('Token de acesso não recebido');
}

$accessToken = $tokenResponse['access_token'];
$idToken = $tokenResponse['id_token'] ?? null;

if ($idToken) {
    $idTokenParts = explode('.', $idToken);
    if (count($idTokenParts) === 3) {
        $payload = json_decode(base64_decode(strtr($idTokenParts[1], '-_', '+/')), true);
        
        $userEmail = $payload['email'] ?? $payload['preferred_username'] ?? null;
        $userName = $payload['name'] ?? 'Usuário';
        $userId = $payload['oid'] ?? $payload['sub'] ?? null;
        
        if (!$userEmail) {
            redirectWithError('Não foi possível obter o email do usuário');
        }
        
        $graphResponse = makeRequest('https://graph.microsoft.com/v1.0/me', 'GET', null, [
            'Authorization: Bearer ' . $accessToken
        ]);
        
        if ($graphResponse['code'] === 200) {
            $graphData = json_decode($graphResponse['body'], true);
            $userEmail = $graphData['mail'] ?? $graphData['userPrincipalName'] ?? $userEmail;
            $userName = $graphData['displayName'] ?? $userName;
        }
        
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $userEmail;
        $_SESSION['user_name'] = $userName;
        $_SESSION['logged_in'] = true;
        $_SESSION['auth_method'] = 'microsoft_sso';
        
        error_log("Login SSO bem-sucedido: {$userEmail}");
        
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit();
        
    } else {
        redirectWithError('Token ID inválido');
    }
} else {
    redirectWithError('Token ID não recebido');
}
