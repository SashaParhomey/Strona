<?php
// One-time import script: JSON -> MySQL
// Run from browser or CLI: php tools/import_json_to_mysql.php
require_once __DIR__ . '/../includes/config.php';

if (!isset($pdo) || !$pdo) {
    echo "No DB connection. Please check includes/config.php settings.\n";
    exit;
}

function load($path) {
    if (!file_exists($path)) return [];
    return json_decode(file_get_contents($path), true) ?? [];
}

$users = load(__DIR__ . '/../data/users.json');
$vehicles = load(__DIR__ . '/../data/vehicles.json');
$reservations = load(__DIR__ . '/../data/reservations.json');

$pdo->beginTransaction();
try {
    $userMap = [];
    $stmtFindUser = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmtInsertUser = $pdo->prepare('INSERT INTO users (name,email,password,phone,created_at) VALUES (?,?,?,?,?)');

    foreach ($users as $u) {
        $oldId = $u['id'] ?? null;
        $email = $u['email'] ?? null;
        if (!$email) continue;
        $stmtFindUser->execute([$email]);
        $exists = $stmtFindUser->fetchColumn();
        if ($exists) {
            $userMap[$oldId] = $exists;
            continue;
        }
        $created_at = $u['created_at'] ?? date('Y-m-d H:i:s');
        $stmtInsertUser->execute([$u['name'] ?? '', $email, $u['password'] ?? '', $u['phone'] ?? null, $created_at]);
        $userMap[$oldId] = $pdo->lastInsertId();
    }

    $vehicleMap = [];
    $stmtInsertEquip = $pdo->prepare('INSERT INTO equipment (name,description,plate,status,created_at) VALUES (?,?,?,?,?)');
    foreach ($vehicles as $v) {
        $oldId = $v['id'] ?? null;
        $name = $v['name'] ?? ($v['brand'] . ' ' . $v['model']);
        $desc = isset($v['brand']) ? ($v['brand'] . ' ' . ($v['model'] ?? '')) : '';
        $plate = $v['license_plate'] ?? ($v['plate'] ?? null);
        $status = $v['status'] ?? 'available';
        $created_at = date('Y-m-d H:i:s');
        $stmtInsertEquip->execute([$name, $desc, $plate, $status, $created_at]);
        $vehicleMap[$oldId] = $pdo->lastInsertId();
    }

    $stmtInsertRes = $pdo->prepare('INSERT INTO reservations (user_id,equipment_id,start_dt,end_dt,status,total_price,created_at) VALUES (?,?,?,?,?,?,?)');
    foreach ($reservations as $r) {
        $oldUser = $r['user_id'] ?? null;
        $oldVeh = $r['vehicle_id'] ?? null;
        $userId = $userMap[$oldUser] ?? null;
        $vehId = $vehicleMap[$oldVeh] ?? null;
        if (!$userId || !$vehId) continue;
        $start = ($r['start_date'] ?? $r['start_dt'] ?? date('Y-m-d')) . ' 00:00:00';
        $end = ($r['end_date'] ?? $r['end_dt'] ?? date('Y-m-d')) . ' 23:59:59';
        $status = ($r['status'] === 'confirmed') ? 'active' : ($r['status'] ?? 'active');
        $total = $r['total_price'] ?? 0;
        $created_at = $r['created_at'] ?? date('Y-m-d H:i:s');
        $stmtInsertRes->execute([$userId,$vehId,$start,$end,$status,$total,$created_at]);
    }

    $pdo->commit();
    echo "Import completed. Users: " . count($userMap) . ", Vehicles: " . count($vehicleMap) . "\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Import error: " . $e->getMessage();
}

?>
