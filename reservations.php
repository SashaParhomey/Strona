<?php
require_once __DIR__ . '/includes/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user = getCurrentUser();
if (!$user) {
    redirect('login.php');
}

$reservations = loadJSON(RESERVATIONS_FILE);
$vehicles = loadJSON(VEHICLES_FILE);

// Фільтрування резервацій поточного користувача
$user_reservations = [];
foreach ($reservations as $res) {
    if (($res['user_id'] === $user['id']) || ($res['user_id'] == $user['id'])) {
        // Додаємо інформацію про транспортний засіб
        foreach ($vehicles as $vehicle) {
            if ($vehicle['id'] === $res['vehicle_id']) {
                $res['vehicle'] = $vehicle;
                break;
            }
        }
        $user_reservations[] = $res;
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мої Резервації - Прокат Автомобілів</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <div class="page-header">
            <h1><?php echo htmlspecialchars(t('my_reservations')); ?></h1>
        </div>
        
        <?php if (empty($user_reservations)): ?>
            <div class="empty-state">
                <p><?php echo htmlspecialchars(t('no_reservations')); ?></p>
                <a href="vehicles.php" class="btn btn-primary"><?php echo htmlspecialchars(t('view_vehicles')); ?></a>
            </div>
        <?php else: ?>
            <div class="reservations-grid">
                <?php foreach ($user_reservations as $reservation): ?>
                    <div class="reservation-card">
                        <div class="card-header">
                            <h3><?php echo htmlspecialchars($reservation['vehicle']['name'] ?? ''); ?></h3>
                            <span class="status status-<?php echo htmlspecialchars($reservation['status']); ?>">
                                <?php echo htmlspecialchars(ucfirst($reservation['status'])); ?>
                            </span>
                        </div>
                        
                        <div class="card-content">
                            <div class="info-group">
                                <p><strong><?php echo htmlspecialchars(t('brand_label')); ?>:</strong> <?php echo htmlspecialchars($reservation['vehicle']['brand'] ?? ''); ?></p>
                                <p><strong><?php echo htmlspecialchars(t('model_label')); ?>:</strong> <?php echo htmlspecialchars($reservation['vehicle']['model'] ?? ''); ?></p>
                                <p><strong><?php echo htmlspecialchars(t('license_plate_label')); ?>:</strong> <?php echo htmlspecialchars($reservation['vehicle']['license_plate'] ?? ''); ?></p>
                            </div>
                            
                            <div class="info-group">
                                <p><strong><?php echo htmlspecialchars(t('reservation_from')); ?></strong> <?php echo date('d.m.Y', strtotime($reservation['start_date'])); ?></p>
                                <p><strong><?php echo htmlspecialchars(t('reservation_to')); ?></strong> <?php echo date('d.m.Y', strtotime($reservation['end_date'])); ?></p>
                                <p><strong><?php echo htmlspecialchars(t('total_price_label')); ?>:</strong> <span class="price"><?php echo htmlspecialchars($reservation['total_price']); ?> PLN</span></p>
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <?php if ($reservation['status'] === 'confirmed'): ?>
                                <a href="api/cancel_reservation.php?id=<?php echo urlencode($reservation['id']); ?>" 
                                   class="btn btn-danger btn-sm" 
                                   onclick="return confirm('Ви впевнені, що хочете скасувати резервацію?');">
                                    <?php echo htmlspecialchars(t('cancel_button')); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
