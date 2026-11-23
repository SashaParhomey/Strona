<?php
require_once __DIR__ . '/../includes/config.php';

// Вихід лише через POST із CSRF-токеном (захист від виходу за чужим посиланням)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    redirect('../index.php');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();
redirect('../login.php');
