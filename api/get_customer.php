<?php
// api/get_customer.php
require_once '../config/db.php';
$pdo = getDBConnection();

header('Content-Type: application/json');

$customerId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$shopId = $_SESSION['shop_id'] ?? null;

if (!$customerId || !$shopId) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT gender, measurements FROM customers WHERE id = ? AND shop_id = ?");
    $stmt->execute([$customerId, $shopId]);
    $customer = $stmt->fetch();
    
    if ($customer) {
        $measurements = $customer['measurements'] ? json_decode($customer['measurements'], true) : null;
        if (!$measurements) {
            $measurements = [
                'upper' => [],
                'lower' => []
            ];
        }
        
        echo json_encode([
            'success' => true,
            'gender' => $customer['gender'],
            'measurements' => $measurements
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Customer not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
