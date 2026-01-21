<?php
require_once __DIR__ . '/includes/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$vehicles = loadJSON(VEHICLES_FILE);
$reservations = loadJSON(RESERVATIONS_FILE);

foreach ($vehicles as $key => $vehicle) {
    $vehicles[$key]['is_available'] = true;
    $vehicles[$key]['category'] = $vehicle['category'] ?? 'economy';

    foreach ($reservations as $res) {
        if ($res['vehicle_id'] === $vehicle['id'] && $res['status'] === 'confirmed') {
            $today = date('Y-m-d');
            $start_date = $res['start_date'] ?? '';
            $end_date = $res['end_date'] ?? '';
            
            if ($start_date && $end_date && $today >= $start_date && $today <= $end_date) {
                $vehicles[$key]['is_available'] = false;
                break;
            }
        }
    }
}

$categories = ['economy', 'comfort', 'luxury', 'motorcycle'];
$vehicles_by_category = [];
foreach ($categories as $cat) {
    $vehicles_by_category[$cat] = [];
}

foreach ($vehicles as $vehicle) {
    $cat = $vehicle['category'] ?? 'economy';
    if (isset($vehicles_by_category[$cat])) {
        $vehicles_by_category[$cat][] = $vehicle;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <title><?php echo htmlspecialchars(t('available_vehicles')); ?> - <?php echo htmlspecialchars(t('site_title')); ?></title>
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
        
        <div class="tabs-container">
            <div class="tabs-nav">
                <?php foreach ($categories as $index => $category): ?>
                    <button class="tab-btn <?php echo $index === 0 ? 'active' : ''; ?>" data-category="<?php echo $category; ?>">
                        <?php echo htmlspecialchars(t('category_' . $category)); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <?php foreach ($categories as $index => $category): ?>
            <div class="tab-content <?php echo $index === 0 ? 'active' : ''; ?>" id="tab-<?php echo $category; ?>" data-category="<?php echo $category; ?>">
                <?php if (empty($vehicles_by_category[$category])): ?>
                    <div class="empty-state">
                        <p><?php echo htmlspecialchars(t('no_reservations')); ?></p>
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
                                 data-name="<?php echo htmlspecialchars($vehicle['name']); ?>">
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
        const tabButtons = document.querySelectorAll('.tab-btn');
        tabButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const category = this.getAttribute('data-category');
                document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                const selectedTab = document.getElementById('tab-' + category);
                if (selectedTab) selectedTab.classList.add('active');
                this.classList.add('active');
            });
        });

        document.querySelectorAll('.sort-select').forEach(sel => {
            sel.addEventListener('change', function() {
                sortVehicles(this.getAttribute('data-category'), this.value);
            });
        });

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
                switch(mode) {
                    case 'price_asc': return pa - pb;
                    case 'price_desc': return pb - pa;
                    case 'year_newest': return yb - ya;
                    case 'year_oldest': return ya - yb;
                    case 'seats_desc': return sb - sa;
                    default: return 0;
                }
            });
            items.forEach(i => grid.appendChild(i));
        }
    });
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
