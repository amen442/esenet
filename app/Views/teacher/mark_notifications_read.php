<?php
require_once __DIR__ . '/../../layouts/components.php';
require_once __DIR__ . '/../../config/db_connect.php';

header('Content-Type: application/json');

$teacher_id = get_teacher_id();
if (!$teacher_id) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit();
}

$action = $_POST['action'] ?? '';

if ($action === 'mark_all') {
    $result = mark_all_notifications_as_read($teacher_id, 'ENSEIGNANT');
    echo json_encode(['success' => $result]);
} elseif ($action === 'mark_one') {
    $notification_id = $_POST['id'] ?? 0;
    $result = mark_notification_as_read($notification_id);
    echo json_encode(['success' => $result]);
} else {
    echo json_encode(['success' => false, 'error' => 'Action invalide']);
}
?>
