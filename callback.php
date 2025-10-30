<?php
require_once 'config.php';

$auth = new Auth();

if (isset($_GET['error'])) {
    die('Erro: ' . htmlspecialchars($_GET['error_description'] ?? 'Erro desconhecido'));
}

if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    die('Estado inválido. Possível ataque CSRF.');
}

if (!isset($_GET['code'])) {
    die('Código não recebido.');
}

// Trocar código por token
$ch = curl_init(TOKEN_ENDPOINT);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'client_id' => CLIENT_ID,
        'client_secret' => CLIENT_SECRET,
        'code' => $_GET['code'],
        'redirect_uri' => REDIRECT_URI,
        'grant_type' => 'authorization_code',
        'scope' => SCOPES
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    error_log("Token error: " . $response);
    die('Erro ao obter token. Tente novamente.');
}

$token_data = json_decode($response, true);
$access_token = $token_data['access_token'];

// Buscar dados do usuário
$ch = curl_init(USER_ENDPOINT);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $access_token]
]);
$user_response = curl_exec($ch);
curl_close($ch);

$user_data = json_decode($user_response, true);

// Salvar usuário
$user = $auth->upsertUser(
    $user_data['id'],
    $user_data['mail'] ?? $user_data['userPrincipalName'],
    $user_data['displayName'],
    null,
    $user_data['jobTitle'] ?? null,
    $user_data['department'] ?? null
);

if (!$user) {
    die('Erro ao salvar usuário. Contate o administrador.');
}

// Criar sessão
$session_token = $auth->createSession($user['id'], $access_token, $token_data['refresh_token'] ?? null);
$auth->setSessionCookie($session_token);
$auth->logAudit($user['id'], 'login');

unset($_SESSION['oauth_state']);
$return_url = $_SESSION['return_url'] ?? 'dashboard.php';
unset($_SESSION['return_url']);

header('Location: ' . $return_url);
exit;
