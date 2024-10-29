<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$db = new PDO('mysql:host=localhost;dbname=delivery_service', 'root', 'root');

$stmt = $db->prepare("
    INSERT INTO delivery_ratings (delivery_id, driver_id, customer_id, rating, comment)
    VALUES (?, ?, ?, ?, ?)
");

$success = $stmt->execute([
    $data['delivery_id'],
    $data['driver_id'],
    $_SESSION['user_id'],
    $data['rating'],
    $data['comment']
]);

echo json_encode(['success' => $success]);
