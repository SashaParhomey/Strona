<?php
// Database helper functions using global $pdo (created in config.php)
// Note: These functions are optional - the project primarily uses JSON files
// MySQL is used as a fallback if available

if (!isset($pdo)) {
    $pdo = null;
}

function ensurePdo() {
    global $pdo;
    if (!$pdo) {
        throw new Exception('Database connection not available');
    }
    return $pdo;
}

function findUserByEmail($email) {
    global $pdo;
    if (!$pdo) {
        return null;
    }
    try {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

function createUser($name, $email, $passwordHash, $phone = null) {
    global $pdo;
    
    // Always save to JSON first to ensure fallback works
    $users = loadJSON(USERS_FILE);
    $new_user = [
        'id' => generateID(),
        'email' => $email,
        'password' => $passwordHash,
        'name' => $name,
        'phone' => $phone,
        'created_at' => date('c')
    ];
    $users[] = $new_user;
    saveJSON(USERS_FILE, $users);

    if (!$pdo) {
        return $new_user['id'];
    }
    try {
        $stmt = $pdo->prepare('INSERT INTO users (name,email,password,phone,created_at) VALUES (?,?,?,?,NOW())');
        $stmt->execute([$name,$email,$passwordHash,$phone]);
        return $pdo->lastInsertId() ?: $new_user['id'];
    } catch (PDOException $e) {
        return $new_user['id'];
    }
}

function createReservation($userId, $vehicleId, $start, $end, $totalPrice = 0, $status = 'active') {
    global $pdo;
    if (!$pdo) {
        return null;
    }
    
    // Ensure userId and vehicleId are integers
    $userId = (int)$userId;
    $vehicleId = (int)$vehicleId;
    
    try {
        // Try vehicle_id first (new schema)
        try {
            $stmt = $pdo->prepare('INSERT INTO reservations (user_id,vehicle_id,start_dt,end_dt,total_price,status,created_at) VALUES (?,?,?,?,?,?,NOW())');
            $stmt->execute([$userId,$vehicleId,$start,$end,$totalPrice,$status]);
            return $pdo->lastInsertId();
        } catch (PDOException $e) {
            // If vehicle_id column doesn't exist, try equipment_id (old schema)
            $stmt = $pdo->prepare('INSERT INTO reservations (user_id,equipment_id,start_dt,end_dt,total_price,status,created_at) VALUES (?,?,?,?,?,?,NOW())');
            $stmt->execute([$userId,$vehicleId,$start,$end,$totalPrice,$status]);
            return $pdo->lastInsertId();
        }
    } catch (PDOException $e) {
        error_log('createReservation error: ' . $e->getMessage());
        return null;
    }
}

function getReservationsByUser($userId) {
    global $pdo;
    if (!$pdo) {
        return [];
    }
    try {
        // Try with equipment table first (old schema)
        $stmt = $pdo->prepare('SELECT r.*, e.name as vehicle_name FROM reservations r JOIN equipment e ON e.id = r.equipment_id WHERE r.user_id = ? ORDER BY r.start_dt DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        // Try with vehicles table (new schema)
        try {
            $stmt = $pdo->prepare('SELECT r.*, v.name as vehicle_name FROM reservations r JOIN vehicles v ON v.id = r.vehicle_id WHERE r.user_id = ? ORDER BY r.start_dt DESC');
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e2) {
            return [];
        }
    }
}

?>