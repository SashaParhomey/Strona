# 🚗 Як додати новий автомобіль

## Метод 1: Прямо через файл (Легше для тестування)

### Крок 1: Відкрийте файл
Перейдіть на:
```
C:\xampp\htdocs\Strona samochod\data\vehicles.json
```

Відкрийте його в текстовому редакторі (Notepad, VS Code, Sublime Text)

### Крок 2: Додайте новий автомобіль

Поточний формат:
```json
[
  {
    "id": "vehicle_001",
    "name": "BMW X5",
    ...
  },
  {
    "id": "vehicle_002",
    ...
  }
]
```

Додайте новий запис:
```json
{
  "id": "vehicle_005",
  "name": "Audi A6",
  "brand": "Audi",
  "model": "A6",
  "year": 2024,
  "license_plate": "WAR22222",
  "status": "available",
  "price_per_day": 280,
  "fuel_type": "Diesel",
  "seats": 5,
  "transmission": "Automatic",
  "image": "audi.jpg"
}
```

### Крок 3: Збережіть файл
- Натисніть Ctrl+S
- Закрийте редактор

### Крок 4: Перевіримо результат
- Перезавантажте сторінку браузера (Ctrl+Shift+R)
- Перейдіть на сторінку "Автомобілі"
- Новий автомобіль повинен з'явитись!

---

## 📝 Поля автомобіля (обовязкові)

| Поле | Тип | Приклад | Опис |
|------|-----|---------|------|
| `id` | String | "vehicle_005" | Унікальний ID (не повинен повторюватися) |
| `name` | String | "Audi A6" | Читабельна назва автомобіля |
| `brand` | String | "Audi" | Марка авто |
| `model` | String | "A6" | Модель |
| `year` | Number | 2024 | Рік випуску |
| `license_plate` | String | "WAR22222" | Номерний знак |
| `status` | String | "available" | Статус (available/unavailable) |
| `price_per_day` | Number | 280 | Ціна за день в PLN |
| `fuel_type` | String | "Diesel" | Тип палива |
| `seats` | Number | 5 | Кількість місць |
| `transmission` | String | "Automatic" | Коробка (Automatic/Manual) |
| `image` | String | "audi.jpg" | Назва картинки (не обов'язково) |

---

## ⚠️ Важливо!

### Унікальний ID
Кожен автомобіль повинен мати різний `id`:
- ✅ "vehicle_001", "vehicle_002", "vehicle_005"
- ❌ Не допускаються одинакові: "vehicle_001", "vehicle_001"

### Коректний JSON
Важлива правильна синтаксис:
- Всі рядки мають кавички: `"id": "vehicle_005"`
- Кома після кожної властивості КРІМ останньої
- Завершуючий дужка `}`

### Приклад помилки:
```json
{
  "id": "vehicle_005",
  "name": "Audi A6",
  "year": 2024,  // ← Тут ДОЗВОЛЕНА кома
  "price_per_day": 280  // ← А тут НЕ дозволена
}
```

---

## 🔧 Метод 2: Через PHP скрипт (Для розширення)

Якщо хочете автоматизувати додавання автомобілів, створіть файл `api/add_vehicle.php`:

```php
<?php
require_once __DIR__ . '/../includes/config.php';

// Тільки для адміністратора (можна додати перевірку)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_data = [
        'id' => generateID(),
        'name' => $_POST['name'],
        'brand' => $_POST['brand'],
        'model' => $_POST['model'],
        'year' => (int)$_POST['year'],
        'license_plate' => $_POST['license_plate'],
        'status' => 'available',
        'price_per_day' => (float)$_POST['price_per_day'],
        'fuel_type' => $_POST['fuel_type'],
        'seats' => (int)$_POST['seats'],
        'transmission' => $_POST['transmission'],
        'image' => $_POST['image'] ?? ''
    ];
    
    $vehicles = loadJSON(VEHICLES_FILE);
    $vehicles[] = $vehicle_data;
    saveJSON(VEHICLES_FILE, $vehicles);
    
    echo json_encode(['success' => true]);
}
?>
```

---

## 📚 Готові приклади для копіювання

### Tesla Model 3
```json
{
  "id": "vehicle_006",
  "name": "Tesla Model 3",
  "brand": "Tesla",
  "model": "Model 3",
  "year": 2024,
  "license_plate": "WAR33333",
  "status": "available",
  "price_per_day": 320,
  "fuel_type": "Electric",
  "seats": 5,
  "transmission": "Automatic",
  "image": "tesla.jpg"
}
```

### Ford Focus
```json
{
  "id": "vehicle_007",
  "name": "Ford Focus",
  "brand": "Ford",
  "model": "Focus",
  "year": 2023,
  "license_plate": "WAR44444",
  "status": "available",
  "price_per_day": 95,
  "fuel_type": "Petrol",
  "seats": 5,
  "transmission": "Manual",
  "image": "ford.jpg"
}
```

### Porsche 911
```json
{
  "id": "vehicle_008",
  "name": "Porsche 911",
  "brand": "Porsche",
  "model": "911",
  "year": 2024,
  "license_plate": "WAR55555",
  "status": "available",
  "price_per_day": 500,
  "fuel_type": "Petrol",
  "seats": 2,
  "transmission": "Automatic",
  "image": "porsche.jpg"
}
```

---

## ✅ Чек-лист

Перед збереженням перевірте:
- [ ] Унікальний ID (не совпадає з іншими)
- [ ] Всі поля заповнені
- [ ] Цифри без кавичок: `"year": 2024` не `"year": "2024"`
- [ ] Правильна синтаксис JSON
- [ ] Коми між полями (крім останнього)
- [ ] Правильна структура дужок `{}`

---

## 🐛 Якщо щось не працює

### Помилка: "Invalid JSON"
- Відкрийте файл в VS Code
- Дивіться червону хвилю (помилка синтаксису)
- Виправте кавички та коми

### Новий автомобіль не показується
- Перезавантажте сторінку (Ctrl+Shift+R)
- Перевірте чи ID унікальний
- Переконайтесь що файл збережений

### Помилка доступу до файлу
- Дайте дозвіл на запись у папку `data/`
- Клікніть правою кнопкою → Властивості → Безпека

---

**Готово! Новий автомобіль тепер в системі! 🎉**

Для більш детальної інформації дивіться `API_DOCUMENTATION.md`
