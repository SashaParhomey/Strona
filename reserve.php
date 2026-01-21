<?php
require_once __DIR__ . '/includes/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$vehicles = loadJSON(VEHICLES_FILE);
$reservations = loadJSON(RESERVATIONS_FILE);

// Отримання ID для деталей автомобіля
$vehicle_id = $_GET['id'] ?? null;
$vehicle = null;

if ($vehicle_id) {
    foreach ($vehicles as $v) {
        if ($v['id'] === $vehicle_id) {
            $vehicle = $v;
            break;
        }
    }
}

if (!$vehicle) {
    redirect('vehicles.php');
}

// Отримання зарезервованих дат для цього автомобіля
$reserved_dates = [];
foreach ($reservations as $res) {
    if ($res['vehicle_id'] === $vehicle_id && $res['status'] === 'confirmed') {
        $start = new DateTime($res['start_date']);
        $end = new DateTime($res['end_date']);
        $period = new DatePeriod($start, new DateInterval('P1D'), $end);
        
        foreach ($period as $date) {
            $reserved_dates[] = $date->format('Y-m-d');
        }
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    
    if (empty($start_date) || empty($end_date)) {
        $error = t('fill_all_dates');
    } else {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        
        if ($start >= $end) {
            $error = t('end_date_after_start');
        } else if ($start < new DateTime('today')) {
            $error = t('start_date_not_past');
        } else {
            // Перевірка чи автомобіль доступний
            $conflict = false;
            $period = new DatePeriod($start, new DateInterval('P1D'), $end);
            
            foreach ($period as $date) {
                if (in_array($date->format('Y-m-d'), $reserved_dates)) {
                    $conflict = true;
                    break;
                }
            }
            
            if ($conflict) {
                $error = t('vehicle_unavailable_dates');
            } else {
                try {
                    // Розрахунок ціни
                    $days = $end->diff($start)->days;
                    $total_price = $days * $vehicle['price_per_day'];
                    
                    // Try to save to MySQL if available
                    global $pdo;
                    $mysqlSaved = false;
                    if ($pdo) {
                        try {
                            // Get user ID from MySQL (convert from JSON ID if needed)
                            $userId = $_SESSION['user_id'];
                            $userDbId = null;
                            
                            // If user_id is string (from JSON), find MySQL ID
                            if (!is_numeric($userId)) {
                                $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                                $user = getCurrentUser();
                                if ($user && isset($user['email'])) {
                                    $stmt->execute([$user['email']]);
                                    $userDbId = $stmt->fetchColumn();
                                }
                            } else {
                                $userDbId = (int)$userId;
                            }
                            
                            // Find vehicle ID in MySQL
                            $vehicleDbId = null;
                            // Try vehicles table first (new schema)
                            try {
                                $stmt = $pdo->prepare('SELECT id FROM vehicles WHERE license_plate = ? OR name LIKE ? LIMIT 1');
                                $stmt->execute([$vehicle['license_plate'] ?? '', '%' . $vehicle['name'] . '%']);
                                $vehicleDbId = $stmt->fetchColumn();
                            } catch (PDOException $e) {
                                // vehicles table doesn't exist
                            }
                            
                            // Fallback to equipment table (old schema)
                            if (!$vehicleDbId) {
                                try {
                                    $stmt = $pdo->prepare('SELECT id FROM equipment WHERE plate = ? OR name LIKE ? LIMIT 1');
                                    $stmt->execute([$vehicle['license_plate'] ?? '', '%' . $vehicle['name'] . '%']);
                                    $vehicleDbId = $stmt->fetchColumn();
                                } catch (PDOException $e) {
                                    // equipment table doesn't exist
                                }
                            }
                            
                            // Save to MySQL if both IDs found
                            if ($userDbId && $vehicleDbId && function_exists('createReservation')) {
                                $startDt = $start_date . ' 00:00:00';
                                $endDt = $end_date . ' 23:59:59';
                                $result = createReservation($userDbId, $vehicleDbId, $startDt, $endDt, $total_price, 'active');
                                if ($result) {
                                    $mysqlSaved = true;
                                }
                            }
                        } catch (PDOException $e) {
                            // MySQL save failed, continue with JSON
                            error_log('MySQL reservation save error: ' . $e->getMessage());
                        }
                    }
                    
                    // Always save to JSON for fallback/compatibility
                    $new_reservation = [
                        'id' => generateID(),
                        'user_id' => $_SESSION['user_id'],
                        'vehicle_id' => $vehicle_id,
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'status' => 'confirmed',
                        'total_price' => $total_price,
                        'created_at' => date('c')
                    ];
                    
                    $reservations[] = $new_reservation;
                    saveJSON(RESERVATIONS_FILE, $reservations);
                    
                    $success = t('reservation_success');
                } catch (Exception $e) {
                    error_log('Reservation error: ' . $e->getMessage());
                    $error = t('reservation_error');
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Резервація: <?php echo htmlspecialchars($vehicle['name'] ?? ''); ?> - Прокат Автомобілів</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <div class="page-header">
            <a href="vehicles.php" class="back-link">&larr; Назад до списку</a>
            <h1>Резервація: <?php echo htmlspecialchars($vehicle['name']); ?></h1>
        </div>
        
        <div class="reserve-container">
            <div class="vehicle-details">
                <div class="vehicle-image">
                    <img src="images/<?php echo htmlspecialchars($vehicle['image']); ?>" alt="<?php echo htmlspecialchars($vehicle['name']); ?>">
                </div>
                
                <div class="vehicle-info">
                    <h2><?php echo htmlspecialchars($vehicle['name']); ?></h2>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="label"><?php echo htmlspecialchars(t('brand_label')); ?>:</span>
                            <span class="value"><?php echo htmlspecialchars($vehicle['brand']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label"><?php echo htmlspecialchars(t('model_label')); ?>:</span>
                            <span class="value"><?php echo htmlspecialchars($vehicle['model']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label"><?php echo htmlspecialchars(t('year_label')); ?>:</span>
                            <span class="value"><?php echo htmlspecialchars($vehicle['year']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label"><?php echo htmlspecialchars(t('fuel_type_label')); ?>:</span>
                            <span class="value"><?php echo htmlspecialchars($vehicle['fuel_type']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label"><?php echo htmlspecialchars(t('seats_label')); ?>:</span>
                            <span class="value"><?php echo htmlspecialchars($vehicle['seats']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label"><?php echo htmlspecialchars(t('transmission_label')); ?>:</span>
                            <span class="value"><?php echo htmlspecialchars($vehicle['transmission']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label"><?php echo htmlspecialchars(t('license_plate_label')); ?>:</span>
                            <span class="value"><?php echo htmlspecialchars($vehicle['license_plate']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label"><?php echo htmlspecialchars(t('price_per_day_label')); ?>:</span>
                            <span class="value price"><?php echo htmlspecialchars($vehicle['price_per_day']); ?> PLN</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="reservation-form-box">
                <h2><?php echo htmlspecialchars(t('reservation_form_title')); ?></h2>
                
                <?php if ($error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
                    <p style="text-align: center; margin-top: 15px;">
                        <a href="reservations.php" class="btn btn-primary"><?php echo htmlspecialchars(t('go_to_reservations')); ?></a>
                    </p>
                <?php else: ?>
                    <form method="POST">
                        <div class="form-group">
                            <label for="start_date"><?php echo htmlspecialchars(t('start_date_label')); ?></label>
                            <input 
                                type="date" 
                                id="start_date" 
                                name="start_date" 
                                min="<?php echo date('Y-m-d'); ?>"
                                required
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="end_date"><?php echo htmlspecialchars(t('end_date_label')); ?></label>
                            <input 
                                type="date" 
                                id="end_date" 
                                name="end_date" 
                                min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                required
                            >
                        </div>
                        
                        <div class="price-preview">
                            <p id="days-info"></p>
                            <p id="price-info"></p>
                        </div>
                        
                        <button type="submit" class="btn btn-primary"><?php echo htmlspecialchars(t('reserve_button_submit')); ?></button>
                    </form>
                    
                    <script>
                    const startDateInput = document.getElementById('start_date');
                    const endDateInput = document.getElementById('end_date');
                    const pricePerDay = <?php echo $vehicle['price_per_day']; ?>;
                    const reservedDates = <?php echo json_encode($reserved_dates); ?>;
                    
                    function updatePrice() {
                        const startDate = new Date(startDateInput.value);
                        const endDate = new Date(endDateInput.value);
                        
                        if (startDate && endDate && startDate < endDate) {
                            const days = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
                            const totalPrice = days * pricePerDay;
                            
                            document.getElementById('days-info').textContent = `Днів: ${days}`;
                            document.getElementById('price-info').textContent = `Загальна ціна: ${totalPrice} PLN`;
                        } else {
                            document.getElementById('days-info').textContent = '';
                            document.getElementById('price-info').textContent = '';
                        }
                    }
                    
                    startDateInput.addEventListener('change', updatePrice);
                    endDateInput.addEventListener('change', updatePrice);
                    </script>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
