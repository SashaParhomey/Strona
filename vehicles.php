<?php
require_once __DIR__ . '/includes/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$vehicles = loadJSON(VEHICLES_FILE);
$reservations = loadJSON(RESERVATIONS_FILE);

// Визначення статусу кожного автомобіля
foreach ($vehicles as $key => $vehicle) {
    $vehicles[$key]['is_available'] = true;
    $vehicles[$key]['category'] = $vehicle['category'] ?? 'economy';

    foreach ($reservations as $res) {
        // Перевіряємо тільки підтверджені резервації
        if ($res['vehicle_id'] === $vehicle['id'] && $res['status'] === 'confirmed') {
            $today = date('Y-m-d');
            $start_date = $res['start_date'] ?? '';
            $end_date = $res['end_date'] ?? '';
            
            // Автомобіль недоступний, якщо сьогоднішня дата в межах резервації
            if ($start_date && $end_date && $today >= $start_date && $today <= $end_date) {
                $vehicles[$key]['is_available'] = false;
                break;
            }
        }
    }
}

// Group vehicles by category
$categories = ['economy', 'comfort', 'luxury', 'motorcycle'];
$vehicles_by_category = [];
foreach ($categories as $cat) {
    $vehicles_by_category[$cat] = [];
}

foreach ($vehicles as $vehicle) {
    $cat = $vehicle['category'] ?? 'economy';
    if (!isset($vehicles_by_category[$cat])) {
        $vehicles_by_category[$cat] = [];
    }
    $vehicles_by_category[$cat][] = $vehicle;
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <title>Доступні Автомобілі - Прокат Автомобілів</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <style>
    .vehicles-controls { margin: 12px 0; }
    .vehicles-controls label { font-weight: 500; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <div class="page-header">
            <h1><?php echo htmlspecialchars(t('available_vehicles')); ?></h1>
        </div>
        
        <!-- Category Tabs -->
        <div class="tabs-container">
            <div class="tabs-nav">
                <button class="tab-btn active" data-category="economy"><?php echo htmlspecialchars(t('category_economy')); ?></button>
                <button class="tab-btn" data-category="comfort"><?php echo htmlspecialchars(t('category_comfort')); ?></button>
                <button class="tab-btn" data-category="luxury"><?php echo htmlspecialchars(t('category_luxury')); ?></button>
                <button class="tab-btn" data-category="motorcycle"><?php echo htmlspecialchars(t('category_motorcycle')); ?></button>
            </div>
        </div>

        <!-- Tab Content -->
        <?php foreach ($categories as $index => $category): ?>
            <div class="tab-content <?php echo $index === 0 ? 'active' : ''; ?>" id="tab-<?php echo $category; ?>" data-category="<?php echo $category; ?>">
                <?php if (empty($vehicles_by_category[$category])): ?>
                    <div class="empty-state">
                        <p><?php echo sprintf(htmlspecialchars(t('no_reservations')), htmlspecialchars(t('category_' . $category))); ?></p>
                    </div>
                <?php else: ?>
                    <div class="vehicles-controls">
                        <label><?php echo htmlspecialchars(t('sort_label')); ?>:
                            <select class="sort-select" data-category="<?php echo $category; ?>">
                                <option value="price_asc"><?php echo htmlspecialchars(t('sort_price_asc')); ?></option>
                                <option value="price_desc"><?php echo htmlspecialchars(t('sort_price_desc')); ?></option>
                                <option value="year_newest"><?php echo htmlspecialchars(t('sort_year_newest')); ?></option>
                                <option value="year_oldest"><?php echo htmlspecialchars(t('sort_year_oldest')); ?></option>
                                <option value="seats_desc"><?php echo htmlspecialchars(t('sort_seats_desc')); ?></option>
                            </select>
                        </label>
                    </div>
                    <div class="vehicles-grid">
                        <?php foreach ($vehicles_by_category[$category] as $vehicle): ?>
                            <div class="vehicle-card <?php echo !$vehicle['is_available'] ? 'unavailable' : ''; ?>"
                                 id="vehicle-<?php echo htmlspecialchars($vehicle['id']); ?>"
                                 data-vehicle-id="<?php echo htmlspecialchars($vehicle['id']); ?>"
                                 data-price="<?php echo htmlspecialchars($vehicle['price_per_day']); ?>"
                                 data-year="<?php echo htmlspecialchars($vehicle['year']); ?>"
                                 data-seats="<?php echo htmlspecialchars($vehicle['seats']); ?>"
                                 data-name="<?php echo htmlspecialchars($vehicle['name']); ?>"
                                 data-image="<?php echo htmlspecialchars($vehicle['image']); ?>">
                                <div class="vehicle-image">
                                    <img src="images/<?php echo htmlspecialchars($vehicle['image']); ?>" alt="<?php echo htmlspecialchars($vehicle['name']); ?>">
                                    <div class="vehicle-status">
                                        <?php echo $vehicle['is_available'] ? htmlspecialchars(t('available')) : htmlspecialchars(t('unavailable')); ?>
                                    </div>
                                </div>
                                
                                <div class="vehicle-content">
                                    <h3><?php echo htmlspecialchars($vehicle['name']); ?></h3>
                                    
                                    <div class="vehicle-specs">
                                        <p><span class="label"><?php echo htmlspecialchars(t('model_label')); ?>:</span> <?php echo htmlspecialchars($vehicle['model']); ?></p>
                                        <p><span class="label"><?php echo htmlspecialchars(t('year_label')); ?>:</span> <?php echo htmlspecialchars($vehicle['year']); ?></p>
                                        <p><span class="label"><?php echo htmlspecialchars(t('fuel_type_label')); ?>:</span> <?php echo htmlspecialchars($vehicle['fuel_type']); ?></p>
                                        <p><span class="label"><?php echo htmlspecialchars(t('seats_label')); ?>:</span> <?php echo htmlspecialchars($vehicle['seats']); ?></p>
                                        <p><span class="label"><?php echo htmlspecialchars(t('transmission_label')); ?>:</span> <?php echo htmlspecialchars($vehicle['transmission']); ?></p>
                                    </div>
                                    
                                    <div class="vehicle-footer">
                                        <span class="price"><?php echo htmlspecialchars($vehicle['price_per_day']); ?> PLN/<?php echo htmlspecialchars(t('per_day')); ?></span>
                                        <?php if ($vehicle['is_available']): ?>
                                            <a href="reserve.php?id=<?php echo urlencode($vehicle['id']); ?>" class="btn btn-primary btn-sm">
                                                <?php echo htmlspecialchars(t('reserve_button')); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </main>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        try {
        const tabButtons = document.querySelectorAll('.tab-btn');

        // Ensure only one active tab at start
        const activeTabs = Array.from(document.querySelectorAll('.tab-content.active'));
        if (activeTabs.length > 1) {
            activeTabs.forEach((el, idx) => {
                if (idx !== 0) el.classList.remove('active');
            });
        }
        
        tabButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const category = this.getAttribute('data-category');
                
                // Hide all tabs
                document.querySelectorAll('.tab-content').forEach(tab => {
                    tab.classList.remove('active');
                });
                
                // Remove active class from all buttons
                document.querySelectorAll('.tab-btn').forEach(b => {
                    b.classList.remove('active');
                });
                
                // Show selected tab
                const selectedTab = document.getElementById('tab-' + category);
                if (selectedTab) {
                    // remove any other accidental active classes
                    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
                    selectedTab.classList.add('active');
                }
                
                // Add active class to clicked button
                this.classList.add('active');
            });
        });

        // Sorting
        document.querySelectorAll('.sort-select').forEach(sel => {
            sel.addEventListener('change', function() {
                sortVehicles(this.getAttribute('data-category'), this.value);
            });
        });

        } catch (err) {
            // Silently handle errors
        }

        function sortVehicles(category, mode) {
            const grid = document.querySelector('#tab-' + category + ' .vehicles-grid');
            if (!grid) return;
            const items = Array.from(grid.querySelectorAll('.vehicle-card'));
            items.sort((a,b) => {
                const pa = parseFloat(a.getAttribute('data-price')) || 0;
                const pb = parseFloat(b.getAttribute('data-price')) || 0;
                const ya = parseInt(a.getAttribute('data-year')) || 0;
                const yb = parseInt(b.getAttribute('data-year')) || 0;
                const sa = parseInt(a.getAttribute('data-seats')) || 0;
                const sb = parseInt(b.getAttribute('data-seats')) || 0;
                const na = (a.getAttribute('data-name') || '').toLowerCase();
                const nb = (b.getAttribute('data-name') || '').toLowerCase();
                switch(mode) {
                    case 'price_asc': return pa - pb;
                    case 'price_desc': return pb - pa;
                    case 'year_newest': return yb - ya;
                    case 'year_oldest': return ya - yb;
                    case 'seats_desc': return sb - sa;
                    default: return na.localeCompare(nb);
                }
            });
            items.forEach(i => grid.appendChild(i));
        }
    });
    </script>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
