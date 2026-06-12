<?php
// api/update_order_karigar.php
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

// Only Masters can assign/reassign orders to Karigars
if ($role !== 'master') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized role']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$orderId = $input['order_id'] ?? null;
$karigarId = isset($input['karigar_id']) && $input['karigar_id'] !== '' ? intval($input['karigar_id']) : null;

if (empty($orderId)) {
    echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
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
    
    // If karigarId is provided, verify they are a karigar in this shop
    if ($karigarId !== null) {
        $stmtKarigarCheck = $pdo->prepare("SELECT id FROM users WHERE id = ? AND shop_id = ? AND role = 'karigar'");
        $stmtKarigarCheck->execute([$karigarId, $shopId]);
        if ($stmtKarigarCheck->rowCount() === 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid Karigar selected']);
            exit();
        }
    }
    
    // Update order assignment
    $stmtUpdate = $pdo->prepare("UPDATE orders SET assigned_to = ? WHERE id = ? AND shop_id = ?");
    $stmtUpdate->execute([$karigarId, $orderId, $shopId]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
