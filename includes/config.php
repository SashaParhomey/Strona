<?php
// Конфігурація проекту

// Безпечні налаштування cookie сесії (до session_start)
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
ini_set('session.use_strict_mode', '1');
session_start();

// Дані зберігаються у JSON-файлах
define('DB_DIR', __DIR__ . '/../data/');
define('USERS_FILE', DB_DIR . 'users.json');
define('VEHICLES_FILE', DB_DIR . 'vehicles.json');
define('RESERVATIONS_FILE', DB_DIR . 'reservations.json');

// Час неактивності, після якого сесія завершується (секунди)
define('SESSION_TIMEOUT', 3600);

if (!empty($_SESSION['user_id'])) {
    $last = $_SESSION['last_activity'] ?? time();
    if (time() - $last > SESSION_TIMEOUT) {
        $_SESSION = [];
        session_regenerate_id(true);
    } else {
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Завантажує JSON-файл (зі спільним блокуванням на час читання).
 */
function loadJSON($filepath) {
    if (!file_exists($filepath)) {
        return [];
    }
    $fp = fopen($filepath, 'r');
    if (!$fp) {
        return [];
    }
    flock($fp, LOCK_SH);
    $data = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    $decoded = json_decode($data, true);
    return is_array($decoded) ? $decoded : [];
}

/**
 * Зберігає дані у JSON-файл атомарно.
 */
function saveJSON($filepath, $data) {
    $dir = dirname($filepath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $json = json_encode(array_values($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $tmp = $filepath . '.' . bin2hex(random_bytes(4)) . '.tmp';
    file_put_contents($tmp, $json, LOCK_EX);
    rename($tmp, $filepath);
}

/**
 * Читає, змінює та зберігає JSON-файл під ексклюзивним блокуванням,
 * щоб одночасні запити не перезаписували зміни один одного.
 * $callback отримує масив за посиланням і може повернути будь-яке значення.
 */
function updateJSON($filepath, callable $callback) {
    $dir = dirname($filepath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $lock = fopen($filepath . '.lock', 'c');
    flock($lock, LOCK_EX);
    try {
        $data = loadJSON($filepath);
        $result = $callback($data);
        saveJSON($filepath, $data);
        return $result;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

// Перевірка чи користувач залогінений
function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}

// Отримання поточного користувача (кешується на час запиту)
function getCurrentUser() {
    static $cache = [];
    if (!isLoggedIn()) {
        return null;
    }
    $userId = (string)$_SESSION['user_id'];
    if (array_key_exists($userId, $cache)) {
        return $cache[$userId];
    }
    $cache[$userId] = findUserById($userId);
    return $cache[$userId];
}

function findUserById($id) {
    foreach (loadJSON(USERS_FILE) as $user) {
        if ((string)$user['id'] === (string)$id) {
            return $user;
        }
    }
    return null;
}

function normalizeEmail($email) {
    return mb_strtolower(trim($email));
}

function findUserByEmail($email) {
    $email = normalizeEmail($email);
    foreach (loadJSON(USERS_FILE) as $user) {
        if (normalizeEmail($user['email']) === $email) {
            return $user;
        }
    }
    return null;
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Унікальний криптографічно випадковий ID
function generateID() {
    return bin2hex(random_bytes(12));
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

// Захищає сторінку: неавторизованих користувачів перенаправляє на вхід
function requireLogin($loginUrl = 'login.php') {
    if (!isLoggedIn() || !getCurrentUser()) {
        $_SESSION = [];
        redirect($loginUrl);
    }
}

// Безпечне виведення в HTML
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// ---- CSRF-захист ----
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf() {
    $token = $_POST['csrf_token'] ?? '';
    return is_string($token) && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Перевіряє дату у форматі Y-m-d. Повертає DateTimeImmutable або null.
 */
function parseDate($value) {
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', (string)$value);
    if (!$d || $d->format('Y-m-d') !== $value) {
        return null;
    }
    return $d;
}

/**
 * Чи перетинаються два періоди [start, end) (дата повернення не займає авто).
 */
function datesOverlap($startA, $endA, $startB, $endB) {
    return $startA < $endB && $startB < $endA;
}

// Мовні функції
require_once __DIR__ . '/lang.php';
