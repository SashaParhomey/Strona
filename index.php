<?php
require_once __DIR__ . '/includes/config.php';

requireLogin();
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(t('home')); ?> - <?php echo htmlspecialchars(t('site_title')); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <div class="hero">
            <h1><?php echo htmlspecialchars(sprintf(t('welcome_greeting'), $user['name'])); ?></h1>
            <p class="hero-subtitle"><?php echo htmlspecialchars(t('car_rental_service')); ?></p>
        </div>
        
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-icon">🚗</div>
                <h2><?php echo htmlspecialchars(t('available_vehicles')); ?></h2>
                <p><?php echo htmlspecialchars(t('available_vehicles_desc')); ?></p>
                <a href="vehicles.php" class="btn btn-primary"><?php echo htmlspecialchars(t('view_button')); ?></a>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon">📋</div>
                <h2><?php echo htmlspecialchars(t('my_reservations')); ?></h2>
                <p><?php echo htmlspecialchars(t('my_reservations_desc')); ?></p>
                <a href="reservations.php" class="btn btn-primary"><?php echo htmlspecialchars(t('check_button')); ?></a>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon">👤</div>
                <h2><?php echo htmlspecialchars(t('profile')); ?></h2>
                <p><?php echo htmlspecialchars(t('profile_desc')); ?></p>
                <a href="profile.php" class="btn btn-primary"><?php echo htmlspecialchars(t('my_profile_button')); ?></a>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
