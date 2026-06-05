<?php
// api/upload_bridge.php
require_once '../config/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$pdo = getDBConnection();
$action = $_GET['action'] ?? '';

// Ensure directories exist
$uploadDir = dirname(__DIR__) . '/public/uploads/fabric';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// 1. CREATE SESSION (Desktop initiates this)
if ($action === 'create') {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit();
    }
    
    $shopId = getCurrentShopId();
    $tagId = $_GET['tag_id'] ?? 'temp';
    $sessionId = bin2hex(random_bytes(16));
    
    try {
        // Delete any expired sessions older than 10 minutes
        $pdo->exec("DELETE FROM upload_sessions WHERE created_at < NOW() - INTERVAL 10 MINUTE");
        
        // Insert new upload session
        $stmt = $pdo->prepare("INSERT INTO upload_sessions (session_id, shop_id, tag_id, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$sessionId, $shopId, $tagId]);
        
        echo json_encode(['success' => true, 'session_id' => $sessionId]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// 2. POLL SESSION (Desktop polls this)
if ($action === 'poll') {
    $sessionId = $_GET['session_id'] ?? '';
    
    if (empty($sessionId)) {
        echo json_encode(['success' => false, 'error' => 'Session ID is required']);
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM upload_sessions WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch();
        
        if (!$session) {
            echo json_encode(['status' => 'expired']);
            exit();
        }
        
        // Check 10 mins timeout manually (fallback for database datetime variations)
        $createdAt = strtotime($session['created_at']);
        if (time() - $createdAt > 600) {
            // Mark expired
            $pdo->prepare("UPDATE upload_sessions SET status = 'expired' WHERE session_id = ?")->execute([$sessionId]);
            echo json_encode(['status' => 'expired']);
            exit();
        }
        
        if ($session['status'] === 'uploaded') {
            echo json_encode([
                'status' => 'uploaded', 
                'image_path' => $session['image_path'],
                'filename' => basename($session['image_path'])
            ]);
        } else {
            echo json_encode(['status' => 'pending']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// 3. UPLOAD IMAGE (Mobile posts here)
if ($action === 'upload') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'POST request required']);
        exit();
    }
    
    // Parse json body
    $input = json_decode(file_get_contents('php://input'), true);
    $sessionId = $input['session_id'] ?? '';
    $imageData = $input['image_data'] ?? ''; // base64 string
    
    if (empty($sessionId) || empty($imageData)) {
        echo json_encode(['success' => false, 'error' => 'Session ID and image data are required']);
        exit();
    }
    
    try {
        // Validate session
        $stmt = $pdo->prepare("SELECT * FROM upload_sessions WHERE session_id = ? AND status = 'pending'");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch();
        
        if (!$session) {
            echo json_encode(['success' => false, 'error' => 'Session is invalid, expired, or already used']);
            exit();
        }
        
        $createdAt = strtotime($session['created_at']);
        if (time() - $createdAt > 600) {
            $pdo->prepare("UPDATE upload_sessions SET status = 'expired' WHERE session_id = ?")->execute([$sessionId]);
            echo json_encode(['success' => false, 'error' => 'Session expired (10 minutes limit exceeded)']);
            exit();
        }
        
        // Process base64 image data
        // Format: data:image/jpeg;base64,.....
        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
            $data = substr($imageData, strpos($imageData, ',') + 1);
            $type = strtolower($type[1]); // jpg, jpeg, png
            
            if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
                echo json_encode(['success' => false, 'error' => 'Invalid image format. Only JPG/PNG allowed.']);
                exit();
            }
            
            $data = base64_decode($data);
            if ($data === false) {
                echo json_encode(['success' => false, 'error' => 'Base64 decode failed']);
                exit();
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid image encoding']);
            exit();
        }
        
        $filename = 'fabric_' . $sessionId . '.' . $type;
        $filePath = $uploadDir . '/' . $filename;
        
        if (file_put_contents($filePath, $data)) {
            // Update session status in DB
            $dbPath = 'public/uploads/fabric/' . $filename;
            $update = $pdo->prepare("UPDATE upload_sessions SET status = 'uploaded', image_path = ? WHERE session_id = ?");
            $update->execute([$dbPath, $sessionId]);
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to write image file']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>
