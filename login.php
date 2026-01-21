<?php
require_once __DIR__ . '/includes/config.php';

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($email) || empty($password)) {
        $error = t('fill_all_fields');
    } else {
        try {
            $user = null;
            if (function_exists('findUserByEmail')) {
                $user = findUserByEmail($email);
            }
            
            if (!$user) {
                $all = loadJSON(USERS_FILE);
                foreach ($all as $u) {
                    if ($u['email'] === $email) {
                        $user = $u;
                        break;
                    }
                }
            }

            if ($user) {
                if (verifyPassword($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['name'];
                    session_write_close();
                    redirect('index.php');
                } else {
                    $error = t('invalid_password');
                }
            } else {
                $error = t('user_not_found');
            }
        } catch (Exception $e) {
            $error = t('auth_error') . ': ' . $e->getMessage();
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
                <div class="form-group">
                    <label for="email"><?php echo htmlspecialchars(t('email_label')); ?></label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password"><?php echo htmlspecialchars(t('password_label')); ?></label>
                    <input type="password" id="password" name="password" required>
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
