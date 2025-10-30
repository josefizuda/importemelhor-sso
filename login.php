<?php
require_once 'config.php';

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$params = [
    'client_id' => CLIENT_ID,
    'response_type' => 'code',
    'redirect_uri' => REDIRECT_URI,
    'response_mode' => 'query',
    'scope' => SCOPES,
    'state' => $state
];

header('Location: ' . AUTHORIZE_ENDPOINT . '?' . http_build_query($params));
exit;
