<?php
header('Content-Type: application/json');

$db = new PDO('mysql:host=localhost;dbname=delivery_service', 'root', 'root');

$tracking_id = 'DEL' . time() . rand(1000, 9999);

$sql = "INSERT INTO deliveries (tracking_id, customer_id, pickup_address, dropoff_address, item_type, status, special_instructions) 
        VALUES (:tracking_id, :customer_id, :pickup, :dropoff, :type, 'pending', :instructions)";

$stmt = $db->prepare($sql);
$result = $stmt->execute([
    ':tracking_id' => $tracking_id,
    ':customer_id' => 1, // Default customer ID until auth is implemented
    ':pickup' => $_POST['pickup_address'],
    ':dropoff' => $_POST['dropoff_address'],
    ':type' => $_POST['item_type'],
    ':instructions' => $_POST['special_instructions']
]);

echo json_encode([
    'success' => $result,
    'tracking_id' => $tracking_id
]);
