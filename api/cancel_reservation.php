<?php
require_once __DIR__ . '/../includes/config.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$reservation_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$reservation_id) {
    redirect('../reservations.php');
}

// Load JSON first to get reservation details
$reservations = loadJSON(RESERVATIONS_FILE);
$reservation_data = null;

foreach ($reservations as $res) {
    // Match by ID and user_id (support both string and numeric IDs)
    if (($res['id'] === $reservation_id || $res['id'] == $reservation_id) && 
        ($res['user_id'] === $user_id || $res['user_id'] == $user_id)) {
        $reservation_data = $res;
        break;
    }
}

if (!$reservation_data) {
    redirect('../reservations.php');
}

// Update in MySQL if available
global $pdo;
if ($pdo) {
    try {
        // Get MySQL user ID (convert from JSON ID if needed)
        $userDbId = null;
        if (!is_numeric($user_id)) {
            $user = getCurrentUser();
            if ($user && isset($user['email'])) {
                $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                $stmt->execute([$user['email']]);
                $userDbId = $stmt->fetchColumn();
            }
        } else {
            $userDbId = (int)$user_id;
        }
        
        if ($userDbId) {
            // Try to find reservation in MySQL by matching dates and user_id
            $start_date = $reservation_data['start_date'] ?? '';
            $end_date = $reservation_data['end_date'] ?? '';
            
            if ($start_date && $end_date) {
                // Convert dates to MySQL datetime format
                $start_dt = $start_date . ' 00:00:00';
                $end_dt = $end_date . ' 23:59:59';
                
                // Try to find by start_dt and user_id (most reliable)
                $stmt = $pdo->prepare('SELECT id FROM reservations WHERE user_id = ? AND start_dt = ? AND end_dt = ? LIMIT 1');
                $stmt->execute([$userDbId, $start_dt, $end_dt]);
                $db_reservation_id = $stmt->fetchColumn();
                
                if ($db_reservation_id) {
                    // Update status in MySQL
                    $stmt = $pdo->prepare('UPDATE reservations SET status = ? WHERE id = ? AND user_id = ?');
                    $stmt->execute(['cancelled', $db_reservation_id, $userDbId]);
                } else {
                    // Fallback: try to find by date range
                    $stmt = $pdo->prepare('SELECT id FROM reservations WHERE user_id = ? AND start_dt LIKE ? LIMIT 1');
                    $start_like = $start_date . '%';
                    $stmt->execute([$userDbId, $start_like]);
                    $db_reservation_id = $stmt->fetchColumn();
                    
                    if ($db_reservation_id) {
                        $stmt = $pdo->prepare('UPDATE reservations SET status = ? WHERE id = ? AND user_id = ?');
                        $stmt->execute(['cancelled', $db_reservation_id, $userDbId]);
                    }
                }
            }
        }
    } catch (PDOException $e) {
        error_log('MySQL cancel reservation error: ' . $e->getMessage());
    }
}

// Update JSON (primary storage)
$updated = false;
foreach ($reservations as &$res) {
    // Match by ID and user_id (support both string and numeric IDs)
    if (($res['id'] === $reservation_id || $res['id'] == $reservation_id) && 
        ($res['user_id'] === $user_id || $res['user_id'] == $user_id)) {
        $res['status'] = 'cancelled';
        $updated = true;
        break;
    }
}

if ($updated) {
    saveJSON(RESERVATIONS_FILE, $reservations);
}

redirect('../reservations.php');
?>
