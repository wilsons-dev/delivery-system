<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'driver') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$db = new PDO('mysql:host=localhost;dbname=delivery_service', 'root', 'root');
$data = json_decode(file_get_contents('php://input'), true);

switch($data['action']) {
    case 'accept':
        $stmt = $db->prepare("
            UPDATE deliveries 
            SET status = 'assigned', driver_id = ? 
            WHERE tracking_id = ? AND status = 'pending'
        ");
        $success = $stmt->execute([$_SESSION['user_id'], $data['tracking_id']]);
        
        if ($success) {
            // Add to status history
            $stmt = $db->prepare("
                INSERT INTO delivery_status_history (delivery_id, status, notes) 
                SELECT id, 'assigned', 'Driver assigned to delivery' 
                FROM deliveries 
                WHERE tracking_id = ?
            ");
            $stmt->execute([$data['tracking_id']]);
        }
        break;

    case 'update_status':
        $validStatuses = ['picked_up', 'in_transit', 'delivered'];
        if (!in_array($data['status'], $validStatuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }

        $stmt = $db->prepare("
            UPDATE deliveries 
            SET status = ? 
            WHERE tracking_id = ? AND driver_id = ?
        ");
        $success = $stmt->execute([$data['status'], $data['tracking_id'], $_SESSION['user_id']]);
        
        if ($success) {
            $stmt = $db->prepare("
                INSERT INTO delivery_status_history (delivery_id, status, notes) 
                SELECT id, ?, 'Status updated by driver' 
                FROM deliveries 
                WHERE tracking_id = ?
            ");
            $stmt->execute([$data['status'], $data['tracking_id']]);
        }
        break;

    case 'update_location':
        $stmt = $db->prepare("
            INSERT INTO delivery_tracking (delivery_id, latitude, longitude) 
            SELECT id, ?, ? 
            FROM deliveries 
            WHERE driver_id = ? AND status IN ('assigned', 'picked_up', 'in_transit')
        ");
        $success = $stmt->execute([$data['lat'], $data['lng'], $_SESSION['user_id']]);
        break;
}

echo json_encode(['success' => $success]);
