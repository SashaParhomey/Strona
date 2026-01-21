<?php
$user = getCurrentUser();
?>
<header class="navbar">
    <div class="container">
        <div class="navbar-content">
            <div class="navbar-brand">
                <a href="index.php" style="display: flex; align-items: center; text-decoration: none;">
                    <img src="images/logo.png" alt="<?php echo htmlspecialchars(t('site_title')); ?>" style="height: 40px; width: auto;">
                </a>
            </div>
            
            <nav class="navbar-menu">
                <a href="index.php" class="nav-link"><?php echo htmlspecialchars(t('home')); ?></a>
                <a href="vehicles.php" class="nav-link"><?php echo htmlspecialchars(t('vehicles')); ?></a>
                <a href="reservations.php" class="nav-link"><?php echo htmlspecialchars(t('reservations')); ?></a>
                <a href="profile.php" class="nav-link"><?php echo htmlspecialchars(t('profile')); ?></a>
            </nav>
            
            <div class="navbar-user">
                <?php if ($user): ?>
                    <span class="user-info"><?php echo htmlspecialchars(sprintf(t('greeting'), $user['name'])); ?></span>
                    <a href="api/logout.php" class="btn btn-secondary btn-sm"><?php echo htmlspecialchars(t('logout')); ?></a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary btn-sm"><?php echo htmlspecialchars(t('login_title')); ?></a>
                    <a href="register.php" class="btn btn-primary btn-sm"><?php echo htmlspecialchars(t('register')); ?></a>
                <?php endif; ?>

                <form method="get" class="lang-switch" style="display:inline-block;margin-left:10px;">
                    <select name="lang" onchange="this.form.submit()">
                        <?php foreach (get_available_langs() as $lang): ?>
                            <option value="<?php echo $lang; ?>" <?php echo (current_lang() === $lang) ? 'selected' : ''; ?>><?php echo strtoupper($lang); ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>
    </div>
</header>
