<?php
// Роутер для вбудованого сервера PHP (php -S 0.0.0.0:5000 router.php).
// Вбудований сервер не читає .htaccess, тому тут закриваємо доступ
// до службових файлів: даних користувачів, includes, документації.

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');

$blocked = [
    '#^/data(/|$)#i',
    '#^/includes(/|$)#i',
    '#/\.#',                       // приховані файли (.git, .env, .replit…)
    '#\.(md|json|lock|tmp|nix|sql)$#i',
    '#^/router\.php$#i',
];
foreach ($blocked as $pattern) {
    if (preg_match($pattern, $path)) {
        http_response_code(404);
        echo 'Not Found';
        return true;
    }
}

// Базові заголовки безпеки
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');

// Статичні файли (css, зображення) і .php-скрипти віддає сам сервер
return false;
