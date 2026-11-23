<?php
require_once __DIR__ . '/../includes/config.php';

requireLogin('../login.php');

// Скасування лише через POST із CSRF-токеном
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    $_SESSION['flash'] = ['type' => 'error', 'text' => t('csrf_error')];
    redirect('../reservations.php');
}

$reservation_id = (string)($_POST['id'] ?? '');
$user_id = (string)getCurrentUser()['id'];
$today = date('Y-m-d');

$cancelled = updateJSON(RESERVATIONS_FILE, function (&$reservations) use ($reservation_id, $user_id, $today) {
    foreach ($reservations as &$res) {
        if ((string)$res['id'] === $reservation_id
            && (string)$res['user_id'] === $user_id
            && $res['status'] === 'confirmed'
            && $res['end_date'] > $today) {
            $res['status'] = 'cancelled';
            $res['cancelled_at'] = date('c');
            return true;
        }
    }
    return false;
});

$_SESSION['flash'] = $cancelled
    ? ['type' => 'success', 'text' => t('reservation_cancelled')]
    : ['type' => 'error', 'text' => t('reservation_cancel_error')];

redirect('../reservations.php');
