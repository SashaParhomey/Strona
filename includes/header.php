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
                <?php
                $current_page = basename($_SERVER['SCRIPT_NAME'] ?? '');
                $nav_items = ['index.php' => 'home', 'vehicles.php' => 'vehicles', 'reservations.php' => 'reservations', 'profile.php' => 'profile'];
                foreach ($nav_items as $href => $label):
                    $is_active = $current_page === $href || ($href === 'vehicles.php' && $current_page === 'reserve.php');
                ?>
                    <a href="<?php echo $href; ?>" class="nav-link<?php echo $is_active ? ' active' : ''; ?>"<?php echo $is_active ? ' aria-current="page"' : ''; ?>><?php echo htmlspecialchars(t($label)); ?></a>
                <?php endforeach; ?>
            </nav>
            
            <div class="navbar-user">
                <?php if ($user): ?>
                    <span class="user-info"><?php echo htmlspecialchars(sprintf(t('greeting'), $user['name'])); ?></span>
                    <form method="POST" action="api/logout.php" class="logout-form">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-secondary btn-sm"><?php echo htmlspecialchars(t('logout')); ?></button>
                    </form>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary btn-sm"><?php echo htmlspecialchars(t('login_title')); ?></a>
                    <a href="register.php" class="btn btn-primary btn-sm"><?php echo htmlspecialchars(t('register')); ?></a>
                <?php endif; ?>

                <form method="get" class="lang-switch" style="display:inline-block;margin-left:10px;">
                    <?php foreach ($_GET as $k => $v): if ($k === 'lang' || !is_string($v)) continue; ?>
                        <input type="hidden" name="<?php echo htmlspecialchars($k); ?>" value="<?php echo htmlspecialchars($v); ?>">
                    <?php endforeach; ?>
                    <select name="lang" aria-label="Language" onchange="this.form.submit()">
                        <?php foreach (get_available_langs() as $lang): ?>
                            <option value="<?php echo $lang; ?>" <?php echo (current_lang() === $lang) ? 'selected' : ''; ?>><?php echo strtoupper($lang); ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>
    </div>
</header>
