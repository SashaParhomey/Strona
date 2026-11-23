<?php
require_once __DIR__ . '/includes/config.php';

requireLogin();
$user = getCurrentUser();

$vehicles_by_id = [];
foreach (loadJSON(VEHICLES_FILE) as $vehicle) {
    $vehicles_by_id[$vehicle['id']] = $vehicle;
}

// Резервації поточного користувача, найновіші першими
$user_reservations = [];
foreach (loadJSON(RESERVATIONS_FILE) as $res) {
    if ((string)$res['user_id'] === (string)$user['id']) {
        $res['vehicle'] = $vehicles_by_id[$res['vehicle_id']] ?? null;
        $user_reservations[] = $res;
    }
}
usort($user_reservations, fn($a, $b) => strcmp($b['start_date'], $a['start_date']));

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(t('my_reservations')); ?> - <?php echo htmlspecialchars(t('site_title')); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <div class="page-header">
            <h1><?php echo htmlspecialchars(t('my_reservations')); ?></h1>
        </div>
        
        <?php if ($flash): ?>
            <div class="<?php echo $flash['type'] === 'success' ? 'success-message' : 'error-message'; ?>"><?php echo htmlspecialchars($flash['text']); ?></div>
        <?php endif; ?>

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
                                <?php echo htmlspecialchars(t('status_' . $reservation['status'])); ?>
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
                            <?php if ($reservation['status'] === 'confirmed' && $reservation['end_date'] > $today): ?>
                                <form method="POST" action="api/cancel_reservation.php"
                                      onsubmit="return confirm(<?php echo htmlspecialchars(json_encode(t('cancel_confirm'), JSON_UNESCAPED_UNICODE)); ?>);">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($reservation['id']); ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"><?php echo htmlspecialchars(t('cancel_button')); ?></button>
                                </form>
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
