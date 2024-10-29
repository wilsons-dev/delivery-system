<?php
header('Content-Type: application/json');

class DeliveryTracker {
    private $db;

    public function __construct() {
        $this->db = new PDO('mysql:host=localhost;dbname=delivery_service', 'root', 'root');
    }

    public function getDeliveryLocation($tracking_id) {
        // Get delivery details
        $stmt = $this->db->prepare("
            SELECT d.*, dt.latitude, dt.longitude, dt.timestamp 
            FROM deliveries d
            LEFT JOIN delivery_tracking dt ON d.id = dt.delivery_id
            WHERE d.tracking_id = ?
            ORDER BY dt.timestamp DESC
            LIMIT 1
        ");
        
        $stmt->execute([$tracking_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return [
                'success' => true,
                'tracking_id' => $tracking_id,
                'status' => $result['status'],
                'lat' => $result['latitude'] ?? 0,
                'lng' => $result['longitude'] ?? 0,
                'pickup_address' => $result['pickup_address'],
                'dropoff_address' => $result['dropoff_address'],
                'last_update' => $result['timestamp']
            ];
        }

        return [
            'success' => false,
            'message' => 'Tracking ID not found'
        ];
    }

    public function getDeliveryStatus($tracking_id) {
        $stmt = $this->db->prepare("
            SELECT dsh.* 
            FROM delivery_status_history dsh
            JOIN deliveries d ON d.id = dsh.delivery_id
            WHERE d.tracking_id = ?
            ORDER BY dsh.created_at DESC
        ");
        
        $stmt->execute([$tracking_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Handle tracking request
if (isset($_GET['tracking_id'])) {
    $tracker = new DeliveryTracker();
    $location = $tracker->getDeliveryLocation($_GET['tracking_id']);
    
    if ($location['success']) {
        $status_history = $tracker->getDeliveryStatus($_GET['tracking_id']);
        $location['status_history'] = $status_history;
    }
    
    echo json_encode($location);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Tracking ID required'
    ]);
}
