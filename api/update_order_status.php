<?php
// api/update_order_status.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit();
}

$pdo = getDBConnection();
$shopId = getCurrentShopId();
$role = $_SESSION['role'];

// Only Masters and Karigars can modify order production statuses
if (!in_array($role, ['master', 'karigar'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized role']);
    exit();
}

// Read JSON input or POST params
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$orderId = $input['order_id'] ?? null;
$newStatus = $input['status'] ?? '';

$allowedStatuses = ['received', 'cutting', 'stitching', 'ready'];

if (empty($orderId) || !in_array($newStatus, $allowedStatuses)) {
    echo json_encode(['success' => false, 'error' => 'Invalid order ID or status value']);
    exit();
}

try {
    // Multi-tenant check: ensure order belongs to this shop
    $stmtCheck = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND shop_id = ?");
    $stmtCheck->execute([$orderId, $shopId]);
    
    if ($stmtCheck->rowCount() === 0) {
        echo json_encode(['success' => false, 'error' => 'Order not found in this shop']);
        exit();
    }
    
    // Update order status
    $stmtUpdate = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ? AND shop_id = ?");
    $stmtUpdate->execute([$newStatus, $orderId, $shopId]);
    
    echo json_encode(['success' => true, 'status' => $newStatus]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
