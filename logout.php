<?php
require_once 'config.php';

$auth = new Auth();

if (isset($_COOKIE['sso_token'])) {
    $auth->destroySession($_COOKIE['sso_token']);
    $auth->clearSessionCookie();
}

session_destroy();
header('Location: index.php');
exit;
