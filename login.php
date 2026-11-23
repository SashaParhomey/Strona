<?php
require_once __DIR__ . '/includes/config.php';

$error = '';
$email = '';

if (isLoggedIn() && getCurrentUser()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if (!verify_csrf()) {
        $error = t('csrf_error');
    } elseif ($email === '' || $password === '') {
        $error = t('fill_all_fields');
    } else {
        $user = findUserByEmail($email);
        // Однакове повідомлення для невідомого email і невірного пароля,
        // щоб не розкривати, які адреси зареєстровані
        if ($user && verifyPassword($password, $user['password'])) {
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                $newHash = hashPassword($password);
                updateJSON(USERS_FILE, function (&$users) use ($user, $newHash) {
                    foreach ($users as &$u) {
                        if ($u['id'] === $user['id']) {
                            $u['password'] = $newHash;
                        }
                    }
                });
            }
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['last_activity'] = time();
            unset($_SESSION['csrf_token']);
            redirect('index.php');
        } else {
            $error = t('invalid_credentials');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(t('login_title')); ?> - <?php echo htmlspecialchars(t('site_title')); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1><?php echo htmlspecialchars(t('login_title')); ?></h1>
            <p class="auth-subtitle"><?php echo htmlspecialchars(t('login_subtitle')); ?></p>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label for="email"><?php echo htmlspecialchars(t('email_label')); ?></label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" autocomplete="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password"><?php echo htmlspecialchars(t('password_label')); ?></label>
                    <input type="password" id="password" name="password" autocomplete="current-password" required>
                </div>
                
                <button type="submit" class="btn btn-primary"><?php echo htmlspecialchars(t('login_button')); ?></button>
            </form>
            
            <p class="auth-link">
                <?php echo htmlspecialchars(t('no_account')); ?> <a href="register.php"><?php echo htmlspecialchars(t('register')); ?></a>
            </p>
        </div>
    </div>
</body>
</html>
