<?php
require_once __DIR__ . '/includes/config.php';

requireLogin();
$user = getCurrentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (!verify_csrf()) {
        $error = t('csrf_error');
    } elseif ($name === '' || $phone === '') {
        $error = t('fill_all_fields');
    } elseif (mb_strlen($name) > 100) {
        $error = t('name_too_long');
    } elseif (!preg_match('/^\+?[0-9 ()-]{6,20}$/', $phone)) {
        $error = t('invalid_phone');
    } else {
        $updated = updateJSON(USERS_FILE, function (&$users) use ($user, $name, $phone) {
            foreach ($users as &$u) {
                if ((string)$u['id'] === (string)$user['id']) {
                    $u['name'] = $name;
                    $u['phone'] = $phone;
                    return $u;
                }
            }
            return null;
        });
        if ($updated) {
            $user = $updated;
            $success = t('profile_updated');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(t('profile')); ?> - <?php echo htmlspecialchars(t('site_title')); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <div class="page-header">
            <h1><?php echo htmlspecialchars(t('profile')); ?></h1>
        </div>
        
        <div class="profile-container">
            <div class="profile-box">
                <h2><?php echo htmlspecialchars(t('profile_information')); ?></h2>
                
                <?php if ($error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <div class="form-group">
                        <label for="email"><?php echo htmlspecialchars(t('email_label')); ?></label>
                        <input 
                            type="email" 
                            id="email" 
                            value="<?php echo htmlspecialchars($user['email']); ?>" 
                            disabled
                        >
                        <small><?php echo htmlspecialchars(t('email_cannot_change')); ?></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="name"><?php echo htmlspecialchars(t('full_name_label')); ?></label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            value="<?php echo htmlspecialchars($user['name']); ?>" 
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="phone"><?php echo htmlspecialchars(t('phone_label')); ?></label>
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                            required
                        >
                    </div>
                    
                    <button type="submit" class="btn btn-primary"><?php echo htmlspecialchars(t('save_changes')); ?></button>
                </form>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
