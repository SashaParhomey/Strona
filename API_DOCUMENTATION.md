# 📚 API Документація

## Загальна інформація

Цей проект використовує JSON файли для зберігання даних. Всі операції виконуються через PHP скрипти.

## 🔐 Автентифікація

Всі операції (окрім логування та реєстрації) потребують активної сессії PHP.

### Перевірка аутентифікації
```php
if (!isLoggedIn()) {
    redirect('login.php');
}
```

---

## 👤 Операції з користувачами

### POST: Логування
**URL:** `login.php`
**Параметри:**
- `email` (string): Електронна пошта користувача
- `password` (string): Пароль (у відкритому вигляді, хешується на сервері)

**Результат:** Сессія встановлена, перенаправлення на `index.php`

### POST: Реєстрація
**URL:** `register.php`
**Параметри:**
- `name` (string): Повне ім'я
- `email` (string): Електронна пошта (унікальна)
- `phone` (string): Телефон
- `password` (string): Пароль (мін. 6 символів)
- `password_confirm` (string): Підтвердження пароля

**Результат:** Новий користувач створено, перенаправлення на логування

### GET: Вихід
**URL:** `api/logout.php`
**Результат:** Сессія очищена, перенаправлення на логування

---

## 🚗 Операції з автомобілями

### GET: Список всіх автомобілів
Отримати список всіх автомобілів:
```php
$vehicles = loadJSON(VEHICLES_FILE);
```

**Структура:**
```json
{
  "id": "vehicle_001",
  "name": "BMW X5",
  "brand": "BMW",
  "model": "X5",
  "year": 2024,
  "license_plate": "WAR12345",
  "status": "available",
  "price_per_day": 250,
  "fuel_type": "Diesel",
  "seats": 5,
  "transmission": "Automatic"
}
```

### GET: Деталі автомобіля
```php
$vehicle_id = $_GET['id'];
// Знайти автомобіль за ID
```

---

## 📅 Операції з резерваціями

### POST: Створення резервації
**URL:** `reserve.php?id=vehicle_001`
**Параметри:**
- `start_date` (date): YYYY-MM-DD
- `end_date` (date): YYYY-MM-DD

**Валідація:**
- Дата початку не може бути в минулому
- Дата закінчення повинна бути після дати початку
- Автомобіль не повинен бути зарезервований на вибрані дати

**Результат:** Резервація створена, перенаправлення на `reservations.php`

### GET: Список резервацій користувача
**URL:** `reservations.php`
**Отримання:**
```php
$reservations = loadJSON(RESERVATIONS_FILE);
foreach ($reservations as $res) {
    if ($res['user_id'] === $user_id) {
        // Резервація належить користувачу
    }
}
```

### GET: Скасування резервації
**URL:** `api/cancel_reservation.php?id=reservation_id`
**Валідація:**
- Резервація повинна належати користувачу
- Можна скасувати лише підтверджені резервації

**Результат:** Статус змінено на "cancelled", перенаправлення на `reservations.php`

---

## 🔧 Корисні функції

### loadJSON($filepath)
Завантажить JSON файл та повернення масив

```php
$users = loadJSON(USERS_FILE);
```

### saveJSON($filepath, $data)
Збереження масиву у JSON файл

```php
saveJSON(USERS_FILE, $users_array);
```

### isLoggedIn()
Перевірка чи користувач залогінений

```php
if (!isLoggedIn()) {
    redirect('login.php');
}
```

### getCurrentUser()
Отримання даних поточного користувача

```php
$user = getCurrentUser();
echo $user['name'];
```

### hashPassword($password)
Хешування пароля (bcrypt)

```php
$hashed = hashPassword($password);
```

### verifyPassword($password, $hash)
Перевірка пароля проти хеша

```php
if (verifyPassword($input_password, $stored_hash)) {
    // Пароль правильний
}
```

### generateID()
Генерація унікального ID

```php
$id = generateID();
```

### redirect($url)
Перенаправлення на інший URL

```php
redirect('index.php');
```

---

## 📊 Структури даних

### Користувач
```json
{
  "id": "5672fe4d8aee37.09371653",
  "email": "user@example.com",
  "password": "$2y$10$...", // bcrypt hash
  "name": "Іван Петренко",
  "phone": "123456789",
  "created_at": "2025-01-01T10:00:00Z"
}
```

### Автомобіль
```json
{
  "id": "vehicle_001",
  "name": "BMW X5",
  "brand": "BMW",
  "model": "X5",
  "year": 2024,
  "license_plate": "WAR12345",
  "status": "available",
  "price_per_day": 250,
  "fuel_type": "Diesel",
  "seats": 5,
  "transmission": "Automatic",
  "image": "bmw.jpg"
}
```

### Резервація
```json
{
  "id": "5672fe4d8aee37.09371653",
  "user_id": "user_001",
  "vehicle_id": "vehicle_001",
  "start_date": "2025-01-10",
  "end_date": "2025-01-15",
  "status": "confirmed",
  "total_price": 1250,
  "created_at": "2025-01-01T10:30:00Z"
}
```

---

## 🔐 Безпека

### Хешування паролів
Всі паролі хешуються за допомогою bcrypt перед збереженням:
```php
$hashed = password_hash($password, PASSWORD_BCRYPT);
```

### HTML Екранування
Всі користувацькі дані екрануються перед виводом:
```php
echo htmlspecialchars($user_input);
```

### Валідація даних
- Email валідація: `filter_var($email, FILTER_VALIDATE_EMAIL)`
- Довжина пароля: мін. 6 символів
- Перевірка унікальності email

---

## 🚀 Розширення API

Для додання нових функцій:

1. Створіть новий файл у папці `api/`
2. Включіть конфіг: `require_once __DIR__ . '/../includes/config.php';`
3. Перевірте аутентифікацію: `if (!isLoggedIn()) { redirect('../login.php'); }`
4. Реалізуйте логіку
5. Збережіть дані: `saveJSON($file, $data);`
6. Перенаправте користувача: `redirect('../page.php');`

---

**Версія:** 1.0  
**Останнє оновлення:** Січень 2025
