<?php
require_once __DIR__ . '/includes/config.php';

requireLogin();
$user = getCurrentUser();

$vehicle_id = (string)($_GET['id'] ?? '');
$vehicle = null;
foreach (loadJSON(VEHICLES_FILE) as $v) {
    if ($v['id'] === $vehicle_id) {
        $vehicle = $v;
        break;
    }
}
if (!$vehicle) {
    redirect('vehicles.php');
}

/**
 * Повертає зайняті дати авто (дата повернення вільна для нового клієнта).
 */
function reservedDatesFor($reservations, $vehicle_id) {
    $dates = [];
    foreach ($reservations as $res) {
        if ($res['vehicle_id'] !== $vehicle_id || $res['status'] !== 'confirmed') {
            continue;
        }
        $start = parseDate($res['start_date'] ?? '');
        $end = parseDate($res['end_date'] ?? '');
        if (!$start || !$end) {
            continue;
        }
        foreach (new DatePeriod($start, new DateInterval('P1D'), $end) as $date) {
            $dates[] = $date->format('Y-m-d');
        }
    }
    return $dates;
}

$error = '';
$success = '';
$start_date = '';
$end_date = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    $start = parseDate($start_date);
    $end = parseDate($end_date);
    $today = new DateTimeImmutable('today');

    if (!verify_csrf()) {
        $error = t('csrf_error');
    } elseif ($start_date === '' || $end_date === '') {
        $error = t('fill_all_dates');
    } elseif (!$start || !$end) {
        $error = t('invalid_date');
    } elseif ($start >= $end) {
        $error = t('end_date_after_start');
    } elseif ($start < $today) {
        $error = t('start_date_not_past');
    } elseif ($start->diff($end)->days > 90) {
        $error = t('reservation_too_long');
    } else {
        $days = $start->diff($end)->days;
        $total_price = $days * $vehicle['price_per_day'];

        // Перевірка конфлікту і запис виконуються під одним блокуванням,
        // щоб два користувачі не забронювали одне авто на ті самі дати
        $created = updateJSON(RESERVATIONS_FILE, function (&$reservations) use ($vehicle_id, $start, $end, $start_date, $end_date, $total_price, $user) {
            foreach ($reservations as $res) {
                if ($res['vehicle_id'] !== $vehicle_id || $res['status'] !== 'confirmed') {
                    continue;
                }
                $rs = parseDate($res['start_date'] ?? '');
                $re = parseDate($res['end_date'] ?? '');
                if ($rs && $re && datesOverlap($start, $end, $rs, $re)) {
                    return false;
                }
            }
            $reservations[] = [
                'id' => generateID(),
                'user_id' => $user['id'],
                'vehicle_id' => $vehicle_id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'status' => 'confirmed',
                'total_price' => $total_price,
                'created_at' => date('c'),
            ];
            return true;
        });

        if ($created) {
            $success = t('reservation_success');
        } else {
            $error = t('vehicle_unavailable_dates');
        }
    }
}

$all_reservations = loadJSON(RESERVATIONS_FILE);
$reserved_dates = reservedDatesFor($all_reservations, $vehicle_id);

// Майбутні зайняті періоди для підказки користувачу
$booked_periods = [];
$todayStr = date('Y-m-d');
foreach ($all_reservations as $res) {
    if ($res['vehicle_id'] === $vehicle_id && $res['status'] === 'confirmed' && ($res['end_date'] ?? '') > $todayStr) {
        $booked_periods[] = [$res['start_date'], $res['end_date']];
    }
}
usort($booked_periods, fn($a, $b) => strcmp($a[0], $b[0]));
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(t('reservation_title', $vehicle['name'])); ?> - <?php echo htmlspecialchars(t('site_title')); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <div class="page-header">
            <a href="vehicles.php" class="back-link">&larr; <?php echo htmlspecialchars(t('back_to_list')); ?></a>
            <h1><?php echo htmlspecialchars(t('reservation_title', $vehicle['name'])); ?></h1>
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
                    <?php if ($booked_periods): ?>
                        <div class="booked-periods">
                            <strong><?php echo htmlspecialchars(t('booked_periods')); ?></strong>
                            <ul>
                                <?php foreach ($booked_periods as [$from, $to]): ?>
                                    <li><?php echo date('d.m.Y', strtotime($from)); ?> – <?php echo date('d.m.Y', strtotime($to)); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <form method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="form-group">
                            <label for="start_date"><?php echo htmlspecialchars(t('start_date_label')); ?></label>
                            <input 
                                type="date" 
                                id="start_date" 
                                name="start_date" 
                                min="<?php echo date('Y-m-d'); ?>"
                                value="<?php echo htmlspecialchars($start_date); ?>"
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
                                value="<?php echo htmlspecialchars($end_date); ?>"
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
                    const pricePerDay = <?php echo json_encode((float)$vehicle['price_per_day']); ?>;
                    const i18n = <?php echo json_encode(['days' => t('days_info'), 'total' => t('price_info'), 'conflict' => t('vehicle_unavailable_dates')], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
                    const reservedDates = new Set(<?php echo json_encode($reserved_dates); ?>);
                    
                    function updatePrice() {
                        const daysInfo = document.getElementById('days-info');
                        const priceInfo = document.getElementById('price-info');
                        daysInfo.textContent = '';
                        priceInfo.textContent = '';
                        if (!startDateInput.value || !endDateInput.value) return;

                        const startDate = new Date(startDateInput.value + 'T00:00:00Z');
                        const endDate = new Date(endDateInput.value + 'T00:00:00Z');
                        if (startDate >= endDate) return;

                        // Підсвічуємо, якщо вибраний період перетинає зайняті дати
                        for (let d = new Date(startDate); d < endDate; d.setUTCDate(d.getUTCDate() + 1)) {
                            if (reservedDates.has(d.toISOString().slice(0, 10))) {
                                daysInfo.textContent = i18n.conflict;
                                return;
                            }
                        }

                        const days = Math.round((endDate - startDate) / 86400000);
                        daysInfo.textContent = `${i18n.days} ${days}`;
                        priceInfo.textContent = `${i18n.total} ${days * pricePerDay} PLN`;
                    }

                    startDateInput.addEventListener('change', () => {
                        if (startDateInput.value) {
                            const next = new Date(startDateInput.value + 'T00:00:00Z');
                            next.setUTCDate(next.getUTCDate() + 1);
                            endDateInput.min = next.toISOString().slice(0, 10);
                        }
                        updatePrice();
                    });
                    endDateInput.addEventListener('change', updatePrice);
                    updatePrice();
                    </script>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
