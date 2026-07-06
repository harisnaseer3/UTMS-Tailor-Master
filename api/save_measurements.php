<?php
// api/save_measurements.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

$pdo = getDBConnection();
$shopId = getCurrentShopId();
$role = $_SESSION['role'];

$customerId = $_POST['customer_id'] ?? null;
$redirectBack = isset($_POST['redirect_back']);

if (empty($customerId)) {
    if ($redirectBack) {
        header("Location: ../dashboard.php?error=missing_id");
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Customer ID is required']);
    }
    exit();
}

try {
    // 1. RBAC and Multi-tenancy validation
    if ($role === 'master') {
        // Master can edit any customer in their shop
        $stmtCheck = $pdo->prepare("SELECT id FROM customers WHERE id = ? AND shop_id = ?");
        $stmtCheck->execute([$customerId, $shopId]);
        if ($stmtCheck->rowCount() === 0) {
            throw new Exception("Customer not found or unauthorized");
        }
    } elseif ($role === 'customer') {
        // Customer can only edit their own profile
        if ($_SESSION['customer_id'] != $customerId) {
            throw new Exception("Unauthorized to edit this profile");
        }
        if ($shopId === null) {
            $stmtCheck = $pdo->prepare("SELECT id FROM customers WHERE id = ? AND shop_id IS NULL");
            $stmtCheck->execute([$customerId]);
        } else {
            $stmtCheck = $pdo->prepare("SELECT id FROM customers WHERE id = ? AND shop_id = ?");
            $stmtCheck->execute([$customerId, $shopId]);
        }
        if ($stmtCheck->rowCount() === 0) {
            throw new Exception("Profile matching workspace not found");
        }
    } else {
        throw new Exception("Unauthorized role");
    }

    // 2. Parse and Validate Measurements
    $upperInput = $_POST['upper'] ?? [];
    $lowerInput = $_POST['lower'] ?? [];
    
    // Clean upper body inputs (cast numbers to float)
    $upper = [
        "length" => floatval($upperInput['length'] ?? 0.0),
        "shoulder" => floatval($upperInput['shoulder'] ?? 0.0),
        "chest" => floatval($upperInput['chest'] ?? 0.0),
        "armhole" => floatval($upperInput['armhole'] ?? 0.0),
        "sleeve" => floatval($upperInput['sleeve'] ?? 0.0),
        "neck" => floatval($upperInput['neck'] ?? 0.0),
        "hem_width" => floatval($upperInput['hem_width'] ?? 0.0),
        "darts" => htmlspecialchars($upperInput['darts'] ?? 'No'),
        "cut" => htmlspecialchars($upperInput['cut'] ?? 'Straight'),
        "flare" => floatval($upperInput['flare'] ?? 0.0),
        "upper_chest" => floatval($upperInput['upper_chest'] ?? 0.0),
        "lower_chest" => floatval($upperInput['lower_chest'] ?? 0.0),
        "fitting" => floatval($upperInput['fitting'] ?? 0.0),
        "chowk" => floatval($upperInput['chowk'] ?? 0.0),
        "sleeve_style" => htmlspecialchars($upperInput['sleeve_style'] ?? '')
    ];

    // Clean lower body inputs
    $lower = [
        "length" => floatval($lowerInput['length'] ?? 0.0),
        "waist" => floatval($lowerInput['waist'] ?? 0.0),
        "hips" => floatval($lowerInput['hips'] ?? 0.0),
        "rise" => floatval($lowerInput['rise'] ?? 0.0),
        "bottom_opening" => floatval($lowerInput['bottom_opening'] ?? 0.0),
        "bottom_opening_type" => htmlspecialchars($lowerInput['bottom_opening_type'] ?? ''),
        "inseam" => floatval($lowerInput['inseam'] ?? 0.0),
        "length_type" => htmlspecialchars($lowerInput['length_type'] ?? '')
    ];

    $measurementNotes = trim($_POST['measurement_notes'] ?? '');

    $measurementsJson = json_encode([
        "upper" => $upper,
        "lower" => $lower,
        "notes" => $measurementNotes
    ]);

    // 3. Update Database
    if ($shopId === null) {
        $stmtUpdate = $pdo->prepare("UPDATE customers SET measurements = ? WHERE id = ? AND shop_id IS NULL");
        $stmtUpdate->execute([$measurementsJson, $customerId]);
    } else {
        $stmtUpdate = $pdo->prepare("UPDATE customers SET measurements = ? WHERE id = ? AND shop_id = ?");
        $stmtUpdate->execute([$measurementsJson, $customerId, $shopId]);
    }

    // 4. Return response
    if ($redirectBack) {
        header("Location: ../dashboard.php?msg=vault_updated");
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }
} catch (Exception $e) {
    if ($redirectBack) {
        header("Location: ../dashboard.php?error=" . urlencode($e->getMessage()));
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
