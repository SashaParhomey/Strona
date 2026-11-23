<?php
$AVAILABLE_LANGS = ['en', 'uk', 'pl'];
$DEFAULT_LANG = 'pl';

function get_available_langs() {
    global $AVAILABLE_LANGS;
    return $AVAILABLE_LANGS;
}

function current_lang() {
    global $DEFAULT_LANG;
    $langs = get_available_langs();
    if (!empty($_SESSION['lang']) && in_array($_SESSION['lang'], $langs, true)) {
        return $_SESSION['lang'];
    }
    if (!empty($_COOKIE['lang']) && in_array($_COOKIE['lang'], $langs, true)) {
        return $_COOKIE['lang'];
    }
    return $DEFAULT_LANG;
}

function set_language($lang) {
    global $AVAILABLE_LANGS;
    if (!in_array($lang, $AVAILABLE_LANGS, true)) return false;
    $_SESSION['lang'] = $lang;
    setcookie('lang', $lang, [
        'expires' => time() + (3600 * 24 * 30),
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    return true;
}

// If lang is requested via GET, set it and redirect to clean URL
if (isset($_GET['lang'])) {
    $req = $_GET['lang'];
    if (is_string($req) && in_array($req, get_available_langs(), true)) {
        set_language($req);
    }
    $url = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    // Лише відносний шлях на цьому ж сайті
    if (!is_string($url) || $url === '' || $url[0] !== '/' || str_starts_with($url, '//')) {
        $url = '/';
    }
    // Зберігаємо інші параметри (наприклад, ?id= на сторінці резервації)
    $params = $_GET;
    unset($params['lang']);
    if ($params) {
        $url .= '?' . http_build_query($params);
    }
    header('Location: ' . $url);
    exit;
}

// Translation function
function t($key, ...$args) {
    static $translations = null;
    $lang = current_lang();
    if ($translations === null || ($translations['__lang'] ?? '') !== $lang) {
        $file = __DIR__ . '/langs/' . $lang . '.php';
        if (file_exists($file)) {
            $translations = require $file;
        } else {
            $translations = [];
        }
        $translations['__lang'] = $lang;
    }
    $text = $translations[$key] ?? $key;
    if ($args) {
        return vsprintf($text, $args);
    }
    return $text;
}
