<?php
session_start();

$clientId = getenv('AZURE_CLIENT_ID') ?: 'ac66d4b8-04a0-4534-9e02-3a7a49778af8';
$tenantId = getenv('AZURE_TENANT_ID');
$redirectUri = getenv('AZURE_REDIRECT_URI') ?: 'https://auth.importemelhor.com.br/callback.php';

if (!$tenantId) {
    die('Erro: AZURE_TENANT_ID não configurado nas variáveis de ambiente.');
}

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$scopes = 'openid profile email User.Read';

$authorizationUrl = sprintf(
    'https://login.microsoftonline.com/%s/oauth2/v2.0/authorize?'.
    'client_id=%s&'.
    'response_type=code&'.
    'redirect_uri=%s&'.
    'response_mode=query&'.
    'scope=%s&'.
    'state=%s',
    $tenantId,
    urlencode($clientId),
    urlencode($redirectUri),
    urlencode($scopes),
    $state
);

header('Location: ' . $authorizationUrl);
exit();
