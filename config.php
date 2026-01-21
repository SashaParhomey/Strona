<?php
// Конфігурація проекту
session_start();

// Налаштування бази даних (JSON-файли використовуються для зберігання)
define('DB_DIR', __DIR__ . '/../data/');
define('USERS_FILE', DB_DIR . 'users.json');
define('VEHICLES_FILE', DB_DIR . 'vehicles.json');
define('RESERVATIONS_FILE', DB_DIR . 'reservations.json');

// Налаштування сессії
define('SESSION_TIMEOUT', 3600); // 1 час

// MySQL settings — update these values for your environment
define('DB_HOST', 'localhost');
define('DB_NAME', 'strona');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create PDO connection (optional — keep JSON fallback if DB not available)
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    $pdo = null;
}

// Функція для завантаження JSON даних
function loadJSON($filepath) {
    if (!file_exists($filepath)) {
        return [];
    }
    $data = file_get_contents($filepath);
    return json_decode($data, true) ?? [];
}

// Функція для збереження JSON даних
function saveJSON($filepath, $data) {
    $dir = dirname($filepath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Функція для перевірки чи користувач залогінений
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Функція для отримання поточного користувача
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $userId = $_SESSION['user_id'];
    
    // Try database first if available
    global $pdo;
    if ($pdo) {
        try {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if ($user) return $user;
        } catch (PDOException $e) {
            // Fall through to JSON
        }
    }
    
    // Fallback to JSON
    $users = loadJSON(USERS_FILE);
    foreach ($users as $user) {
        if ($user['id'] == $userId) {
            return $user;
        }
    }
    return null;
}

// Функція для хешування пароля
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Функція для перевірки пароля
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Функція для генерації унікального ID
function generateID() {
    return uniqid(mt_rand(), true);
}

// Функція для редиректу
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

// Load language utilities
require_once __DIR__ . '/lang.php';

// Load DB helpers (uses $pdo if available)
require_once __DIR__ . '/db.php';
?>
