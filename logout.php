<?php
require_once 'config.php';

$auth = new Auth();

if (isset($_COOKIE['sso_token'])) {
    $session = $auth->validateSession($_COOKIE['sso_token']);
    if ($session) {
        $auth->logAudit($session['user_id'], 'logout');
        $auth->destroySession($_COOKIE['sso_token']);
    }
    $auth->clearSessionCookie();
}

session_destroy();
header('Location: index.php');
exit;
