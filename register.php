<?php
require_once __DIR__ . '/includes/config.php';

$error = '';
$success = '';
$name = '';
$email = '';
$phone = '';

if (isLoggedIn() && getCurrentUser()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = normalizeEmail($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $password_confirm = (string)($_POST['password_confirm'] ?? '');

    if (!verify_csrf()) {
        $error = t('csrf_error');
    } elseif ($name === '' || $email === '' || $phone === '' || $password === '') {
        $error = t('fill_all_fields');
    } elseif (mb_strlen($name) > 100) {
        $error = t('name_too_long');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = t('invalid_email');
    } elseif (!preg_match('/^\+?[0-9 ()-]{6,20}$/', $phone)) {
        $error = t('invalid_phone');
    } elseif (strlen($password) < 8) {
        $error = t('password_min_length');
    } elseif ($password !== $password_confirm) {
        $error = t('passwords_mismatch');
    } else {
        $passwordHash = hashPassword($password);
        // Перевірка унікальності і запис виконуються під одним блокуванням
        $created = updateJSON(USERS_FILE, function (&$users) use ($name, $email, $phone, $passwordHash) {
            foreach ($users as $u) {
                if (normalizeEmail($u['email']) === $email) {
                    return false;
                }
            }
            $users[] = [
                'id' => generateID(),
                'email' => $email,
                'password' => $passwordHash,
                'name' => $name,
                'phone' => $phone,
                'created_at' => date('c'),
            ];
            return true;
        });

        if ($created) {
            $success = t('registration_success');
            $name = $email = $phone = '';
        } else {
            $error = t('email_exists');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(t('registration')); ?> - <?php echo htmlspecialchars(t('site_title')); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1><?php echo htmlspecialchars(t('registration')); ?></h1>
            <p class="auth-subtitle"><?php echo htmlspecialchars(t('register_subtitle')); ?></p>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
                <p style="text-align: center; margin-top: 15px;">
                    <a href="login.php" class="btn btn-primary" style="display: inline-block;"><?php echo htmlspecialchars(t('go_to_login')); ?></a>
                </p>
            <?php else: ?>
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <div class="form-group">
                        <label for="name"><?php echo htmlspecialchars(t('full_name_label')); ?></label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" maxlength="100" autocomplete="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email"><?php echo htmlspecialchars(t('email_label')); ?></label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone"><?php echo htmlspecialchars(t('phone_label')); ?></label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" autocomplete="tel" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password"><?php echo htmlspecialchars(t('password_label')); ?></label>
                        <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirm"><?php echo htmlspecialchars(t('password_confirm_label')); ?></label>
                        <input type="password" id="password_confirm" name="password_confirm" minlength="8" autocomplete="new-password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary"><?php echo htmlspecialchars(t('register_button')); ?></button>
                </form>
                
                <p class="auth-link">
                    <?php echo htmlspecialchars(t('have_account')); ?> <a href="login.php"><?php echo htmlspecialchars(t('login_title')); ?></a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
