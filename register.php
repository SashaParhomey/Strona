<?php
require_once __DIR__ . '/includes/config.php';

$error = '';
$success = '';
$name = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $password_confirm = trim($_POST['password_confirm'] ?? '');
    
    // Валідація
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $error = t('fill_all_fields');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = t('invalid_email');
    } elseif (strlen($password) < 6) {
        $error = t('password_min_length');
    } elseif ($password !== $password_confirm) {
        $error = t('passwords_mismatch');
    } else {
        try {
            $exists = false;
            if (function_exists('findUserByEmail')) {
                $exists = (bool)findUserByEmail($email);
            } else {
                $all = loadJSON(USERS_FILE);
                foreach ($all as $u) if ($u['email'] === $email) { $exists = true; break; }
            }

            if ($exists) {
                $error = t('email_exists');
            } else {
                $passwordHash = hashPassword($password);
                if (function_exists('createUser')) {
                    $newId = createUser($name, $email, $passwordHash, $phone);
                } else {
                    $new_user = [
                        'id' => generateID(),
                        'email' => $email,
                        'password' => $passwordHash,
                        'name' => $name,
                        'phone' => $phone,
                        'created_at' => date('c')
                    ];
                    $all = loadJSON(USERS_FILE);
                    $all[] = $new_user;
                    saveJSON(USERS_FILE, $all);
                }

                $success = t('registration_success');
                $name = '';
                $email = '';
                $phone = '';
            }
        } catch (Exception $e) {
            $error = t('registration_error');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Реєстрація - Прокат Автомобілів</title>
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
                    <div class="form-group">
                        <label for="name"><?php echo htmlspecialchars(t('full_name_label')); ?></label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            value="<?php echo htmlspecialchars($name); ?>" 
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="email"><?php echo htmlspecialchars(t('email_label')); ?></label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="<?php echo htmlspecialchars($email); ?>" 
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="phone"><?php echo htmlspecialchars(t('phone_label')); ?></label>
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            value="<?php echo htmlspecialchars($phone); ?>" 
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="password"><?php echo htmlspecialchars(t('password_label')); ?></label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirm"><?php echo htmlspecialchars(t('password_confirm_label')); ?></label>
                        <input 
                            type="password" 
                            id="password_confirm" 
                            name="password_confirm" 
                            required
                        >
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
