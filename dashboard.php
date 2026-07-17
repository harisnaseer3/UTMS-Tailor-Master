<?php
// dashboard.php
require_once 'config/db.php';
require_once 'includes/auth.php';

requireLogin();

$pdo = getDBConnection();
$shopId = getCurrentShopId();
$role = $_SESSION['role'];

$errorMsg = '';
$successMsg = '';

// Handle POST submissions (Customer additions and Order creations)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($role, ['master'])) {
    
    // 1. ADD NEW CUSTOMER
    if (isset($_POST['action']) && $_POST['action'] === 'add_customer') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $gender = trim($_POST['gender'] ?? 'male');
        
        if (empty($name) || empty($phone)) {
            $_SESSION['error_msg'] = 'Customer name and phone number are required.';
        } else {
            // Check if phone number is already registered in this shop
            $check = $pdo->prepare("SELECT id FROM customers WHERE phone = ? AND shop_id = ?");
            $check->execute([$phone, $shopId]);
            if ($check->rowCount() > 0) {
                $_SESSION['error_msg'] = 'A customer with this phone number is already registered.';
            } else {
                try {
                    // Seed empty measurement tree structure
                    $emptyTree = [
                        "upper" => [
                            "length" => 0.0, "shoulder" => 0.0, "chest" => 0.0, "armhole" => 0.0, 
                            "sleeve" => 0.0, "neck" => 0.0, "hem_width" => 0.0, "darts" => "No", 
                            "cut" => "Straight", "flare" => 0.0, "upper_chest" => 0.0, "lower_chest" => 0.0
                        ],
                        "lower" => [
                            "length" => 0.0, "waist" => 0.0, "hips" => 0.0, "rise" => 0.0, "bottom_opening" => 0.0, "inseam" => 0.0
                        ]
                    ];
                    
                    $stmt = $pdo->prepare("INSERT INTO customers (shop_id, name, phone, gender, measurements) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$shopId, $name, $phone, $gender, json_encode($emptyTree)]);
                    $_SESSION['success_msg'] = 'Customer profile added successfully.';
                } catch (PDOException $e) {
                    $_SESSION['error_msg'] = 'Error saving customer: ' . $e->getMessage();
                }
            }
        }
        header("Location: dashboard.php");
        exit();
    }
    
    // 2. CREATE NEW ORDER
    if (isset($_POST['action']) && $_POST['action'] === 'create_order') {
        $customerId = $_POST['customer_id'] ?? null;
        $price = floatval($_POST['price'] ?? 0);
        $advance = floatval($_POST['advance'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        $tagId = trim($_POST['tag_id'] ?? '');
        $session_id = trim($_POST['bridge_session_id'] ?? ''); // Bridge photo connection
        $assignedTo = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
        
        if (empty($customerId) || empty($tagId)) {
            $_SESSION['error_msg'] = 'Customer and Tag ID are required.';
        } else {
            // Verify if tag is unique
            $check = $pdo->prepare("SELECT id FROM orders WHERE tag_id = ?");
            $check->execute([$tagId]);
            if ($check->rowCount() > 0) {
                $_SESSION['error_msg'] = 'This Tag ID is already assigned to another order.';
            } else {
                try {
                    // Update customer's measurements based on submitted form
                    $upper = isset($_POST['upper']) && is_array($_POST['upper']) ? $_POST['upper'] : [];
                    $lower = isset($_POST['lower']) && is_array($_POST['lower']) ? $_POST['lower'] : [];
                    $measurementNotes = trim($_POST['measurement_notes'] ?? '');
                    
                    $newMeasurements = [
                        'upper' => $upper,
                        'lower' => $lower,
                        'notes' => $measurementNotes
                    ];
                    $measurementsJson = json_encode($newMeasurements);

                    // Update the customer record with the new measurements
                    $stmtUpdateCust = $pdo->prepare("UPDATE customers SET measurements = ? WHERE id = ? AND shop_id = ?");
                    $stmtUpdateCust->execute([$measurementsJson, $customerId, $shopId]);

                    
                    // Check if fabric photo was uploaded via mobile bridge session
                    $fabricImage = null;
                    if (!empty($session_id)) {
                        $sessStmt = $pdo->prepare("SELECT image_path, status FROM upload_sessions WHERE session_id = ? AND status = 'uploaded'");
                        $sessStmt->execute([$session_id]);
                        $sessionRecord = $sessStmt->fetch();
                        if ($sessionRecord) {
                            $fabricImage = basename($sessionRecord['image_path']);
                        }
                    }

                    $stmt = $pdo->prepare("INSERT INTO orders (shop_id, customer_id, tag_id, measurements_snapshot, fabric_image, status, notes, price, advance_paid, assigned_to) VALUES (?, ?, ?, ?, ?, 'received', ?, ?, ?, ?)");
                    $stmt->execute([$shopId, $customerId, $tagId, $measurementsJson, $fabricImage, $notes, $price, $advance, $assignedTo]);
                    
                    $_SESSION['success_msg'] = 'Order created successfully.';
                } catch (PDOException $e) {
                    $_SESSION['error_msg'] = 'Error creating order: ' . $e->getMessage();
                }
            }
        }
        header("Location: dashboard.php");
        exit();
    }

    // 3. ADD WORKSHOP STAFF (Karigar or Tailor Master)
    if (isset($_POST['action']) && $_POST['action'] === 'add_staff') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $roleSelected = $_POST['role'] ?? 'karigar';
        $phone = trim($_POST['phone'] ?? '');
        
        // Karigars do not need a password from the UI, auto-generate one
        if (empty($password)) {
            $password = bin2hex(random_bytes(16));
        }
        
        if (empty($username)) {
            $_SESSION['error_msg'] = 'Username is required.';
        } elseif (!in_array($roleSelected, ['karigar', 'master'])) {
            $_SESSION['error_msg'] = 'Invalid role selected.';
        } else {
            // Check if username is already registered in this shop
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ? AND shop_id = ?");
            $check->execute([$username, $shopId]);
            if ($check->rowCount() > 0) {
                $_SESSION['error_msg'] = 'Username is already taken for this shop.';
            } else {
                try {
                    $hashedPass = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (shop_id, username, password, role, phone) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$shopId, $username, $hashedPass, $roleSelected, $phone]);
                    $_SESSION['success_msg'] = 'Workshop staff added successfully.';
                } catch (PDOException $e) {
                    $_SESSION['error_msg'] = 'Error saving staff: ' . $e->getMessage();
                }
            }
        }
        header("Location: dashboard.php");
        exit();
    }

    // 4. DELETE CUSTOMER (Single & Bulk)
    if (isset($_POST['action']) && in_array($_POST['action'], ['delete_customer', 'bulk_delete_customers'])) {
        $idsToDelete = [];
        if ($_POST['action'] === 'delete_customer') {
            $deleteId = intval($_POST['delete_customer_id'] ?? 0);
            if ($deleteId > 0) $idsToDelete[] = $deleteId;
        } else {
            $ids = $_POST['customer_ids'] ?? [];
            foreach ($ids as $id) {
                if (intval($id) > 0) $idsToDelete[] = intval($id);
            }
        }
        
        if (!empty($idsToDelete)) {
            try {
                $placeholders = implode(',', array_fill(0, count($idsToDelete), '?'));
                $params = array_merge($idsToDelete, [$shopId]);
                $stmt = $pdo->prepare("DELETE FROM customers WHERE id IN ($placeholders) AND shop_id = ?");
                $stmt->execute($params);
                $deletedCount = $stmt->rowCount();
                $_SESSION['success_msg'] = "Successfully deleted $deletedCount customer(s). Associated orders were also removed.";
            } catch (PDOException $e) {
                $_SESSION['error_msg'] = 'Error deleting customers: ' . $e->getMessage();
            }
        } else {
            $_SESSION['error_msg'] = 'No customers selected for deletion.';
        }
        header("Location: dashboard.php");
        exit();
    }

    // 5. DELETE ORDER (Single & Bulk)
    if (isset($_POST['action']) && in_array($_POST['action'], ['delete_order', 'bulk_delete_orders'])) {
        $idsToDelete = [];
        if ($_POST['action'] === 'delete_order') {
            $deleteId = intval($_POST['delete_order_id'] ?? 0);
            if ($deleteId > 0) $idsToDelete[] = $deleteId;
        } else {
            $ids = $_POST['order_ids'] ?? [];
            foreach ($ids as $id) {
                if (intval($id) > 0) $idsToDelete[] = intval($id);
            }
        }
        
        if (!empty($idsToDelete)) {
            try {
                $placeholders = implode(',', array_fill(0, count($idsToDelete), '?'));
                $params = array_merge($idsToDelete, [$shopId]);
                $stmt = $pdo->prepare("DELETE FROM orders WHERE id IN ($placeholders) AND shop_id = ?");
                $stmt->execute($params);
                $deletedCount = $stmt->rowCount();
                $_SESSION['success_msg'] = "Successfully deleted $deletedCount order(s).";
            } catch (PDOException $e) {
                $_SESSION['error_msg'] = 'Error deleting orders: ' . $e->getMessage();
            }
        } else {
            $_SESSION['error_msg'] = 'No orders selected for deletion.';
        }
        header("Location: dashboard.php");
        exit();
    }
}

// Handle Super Admin POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'super_admin') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete_shop') {
        $deleteShopId = intval($_POST['delete_shop_id'] ?? 0);
        if ($deleteShopId > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM shops WHERE id = ?");
                $stmt->execute([$deleteShopId]);
                $_SESSION['success_msg'] = 'Workshop successfully deleted.';
            } catch (PDOException $e) {
                $_SESSION['error_msg'] = 'Error deleting workshop: ' . $e->getMessage();
            }
        }
        header("Location: dashboard.php");
        exit();
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit_shop') {
        $editShopId = intval($_POST['edit_shop_id'] ?? 0);
        $shopName = trim($_POST['shop_name'] ?? '');
        $shopPhone = trim($_POST['shop_phone'] ?? '');
        
        if ($editShopId > 0 && !empty($shopName)) {
            try {
                $stmt = $pdo->prepare("UPDATE shops SET name = ?, phone = ? WHERE id = ?");
                $stmt->execute([$shopName, $shopPhone, $editShopId]);
                $_SESSION['success_msg'] = 'Workshop successfully updated.';
            } catch (PDOException $e) {
                $_SESSION['error_msg'] = 'Error updating workshop: ' . $e->getMessage();
            }
        } else {
            $_SESSION['error_msg'] = 'Shop name is required.';
        }
        header("Location: dashboard.php");
        exit();
    } elseif (isset($_POST['action']) && $_POST['action'] === 'change_user_password') {
        $targetUserId = intval($_POST['user_id'] ?? 0);
        $newPassword = $_POST['new_password'] ?? '';
        
        if ($targetUserId > 0 && !empty($newPassword)) {
            try {
                $hashedPass = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPass, $targetUserId]);
                $_SESSION['success_msg'] = 'User password updated successfully.';
            } catch (PDOException $e) {
                $_SESSION['error_msg'] = 'Error updating password: ' . $e->getMessage();
            }
        } else {
            $_SESSION['error_msg'] = 'User ID and New Password are required.';
        }
        header("Location: dashboard.php");
        exit();
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_user') {
        $deleteUserId = intval($_POST['delete_user_id'] ?? 0);
        if ($deleteUserId === $_SESSION['user_id']) {
            $_SESSION['error_msg'] = 'You cannot delete yourself.';
        } elseif ($deleteUserId > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$deleteUserId]);
                $_SESSION['success_msg'] = 'User deleted successfully.';
            } catch (PDOException $e) {
                $_SESSION['error_msg'] = 'Error deleting user: ' . $e->getMessage();
            }
        }
        header("Location: dashboard.php");
        exit();
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit_user') {
        $editUserId = intval($_POST['edit_user_id'] ?? 0);
        $editUsername = trim($_POST['username'] ?? '');
        $editPhone = trim($_POST['phone'] ?? '');
        $editRole = trim($_POST['role'] ?? '');
        
        if ($editUserId > 0 && !empty($editUsername) && !empty($editRole)) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, phone = ?, role = ? WHERE id = ?");
                $stmt->execute([$editUsername, $editPhone, $editRole, $editUserId]);
                $_SESSION['success_msg'] = 'User updated successfully.';
            } catch (PDOException $e) {
                $_SESSION['error_msg'] = 'Error updating user: ' . $e->getMessage();
            }
        } else {
            $_SESSION['error_msg'] = 'Username and Role are required.';
        }
        header("Location: dashboard.php");
        exit();
    }
}

// Fetch dashboard statistics & records depending on Role
$customersList = [];
$ordersList = [];
$karigarsList = [];
$kanbanOrders = [
    'received' => [],
    'cutting' => [],
    'stitching' => [],
    'ready' => []
];

$stats = [
    'revenue' => 0.00,
    'orders' => 0,
    'balance' => 0.00
];

if ($role === 'master' || $role === 'karigar') {
    // Both Master and Karigar need order data. Scoped strictly by shop_id.
    
    // Get Orders Scoped by shop_id (with assigned Karigar info)
    $stmtOrders = $pdo->prepare("
        SELECT o.*, c.name as customer_name, c.phone as customer_phone, u.username as karigar_name 
        FROM orders o 
        JOIN customers c ON o.customer_id = c.id 
        LEFT JOIN users u ON o.assigned_to = u.id 
        WHERE o.shop_id = ? 
        ORDER BY o.created_at DESC
    ");
    $stmtOrders->execute([$shopId]);
    $ordersList = $stmtOrders->fetchAll();
    
    // Sort into Kanban buckets
    foreach ($ordersList as $o) {
        $kanbanOrders[$o['status']][] = $o;
    }
    
    // Scoped Statistics (Master Only)
    if ($role === 'master') {
        $statsStmt = $pdo->prepare("
            SELECT 
                SUM(price) as total_rev, 
                SUM(CASE WHEN status != 'dispatched' THEN 1 ELSE 0 END) as total_count,
                SUM(price - advance_paid) as outstanding
            FROM orders 
            WHERE shop_id = ?
        ");
        $statsStmt->execute([$shopId]);
        $res = $statsStmt->fetch();
        if ($res) {
            $stats['revenue'] = floatval($res['total_rev'] ?? 0);
            $stats['orders'] = intval($res['total_count'] ?? 0);
            $stats['balance'] = floatval($res['outstanding'] ?? 0);
        }
        
        // Fetch Customers Scoped by shop_id
        $stmtCusts = $pdo->prepare("SELECT * FROM customers WHERE shop_id = ? ORDER BY name ASC");
        $stmtCusts->execute([$shopId]);
        $customersList = $stmtCusts->fetchAll();

        // Fetch Karigars Scoped by shop_id
        $stmtKarigars = $pdo->prepare("SELECT * FROM users WHERE shop_id = ? AND role = 'karigar' ORDER BY username ASC");
        $stmtKarigars->execute([$shopId]);
        $karigarsList = $stmtKarigars->fetchAll();
    }
} elseif ($role === 'customer') {
    // Customer Scoped Dashboard
    $customerId = $_SESSION['customer_id'] ?? null;
    $customerProfile = null;
    
    if ($customerId) {
        // Fetch Customer details & measurements (handle nullable shopId)
        if ($shopId === null) {
            $stmtCust = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND shop_id IS NULL");
            $stmtCust->execute([$customerId]);
        } else {
            $stmtCust = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND shop_id = ?");
            $stmtCust->execute([$customerId, $shopId]);
        }
        $customerProfile = $stmtCust->fetch();
        
        // Fetch Customer orders across all workshops matching their phone or customer ID
        $custIds = [];
        if (!empty($customerId)) {
            $custIds[] = $customerId;
        }
        
        $userPhone = $_SESSION['phone'] ?? null;
        if (!empty($userPhone)) {
            $stmtAllCust = $pdo->prepare("SELECT id FROM customers WHERE phone = ?");
            $stmtAllCust->execute([$userPhone]);
            $custIds = array_merge($custIds, $stmtAllCust->fetchAll(PDO::FETCH_COLUMN));
        }
        $custIds = array_unique(array_filter($custIds));
        
        if (!empty($custIds)) {
            $inClause = implode(',', array_fill(0, count($custIds), '?'));
            $stmtCustOrders = $pdo->prepare("
                SELECT o.*, s.name as shop_name 
                FROM orders o 
                JOIN shops s ON o.shop_id = s.id 
                WHERE o.customer_id IN ($inClause) 
                ORDER BY o.created_at DESC
            ");
            $stmtCustOrders->execute($custIds);
            $ordersList = $stmtCustOrders->fetchAll();
        } else {
            $ordersList = [];
        }
    }
} elseif ($role === 'super_admin') {
    // Super Admin Scoped Dashboard
    $superStats = [
        'shops' => $pdo->query("SELECT COUNT(id) FROM shops")->fetchColumn(),
        'users' => $pdo->query("SELECT COUNT(id) FROM users")->fetchColumn(),
        'customers' => $pdo->query("SELECT COUNT(id) FROM customers")->fetchColumn(),
        'orders' => $pdo->query("SELECT COUNT(id) FROM orders")->fetchColumn()
    ];
    $stmtShops = $pdo->query("SELECT * FROM shops ORDER BY created_at DESC");
    $shopsList = $stmtShops->fetchAll(PDO::FETCH_ASSOC);
        
    $stmtAllUsers = $pdo->query("SELECT u.id, u.username, u.role, s.name as shop_name FROM users u LEFT JOIN shops s ON u.shop_id = s.id ORDER BY u.role ASC, u.username ASC");
    $allUsersList = $stmtAllUsers->fetchAll(PDO::FETCH_ASSOC);
}

// Auto-generate Tag ID for new orders (e.g. YEAR-XXXX)
$newTagId = date('Y') . '-' . str_pad(rand(100, 9999), 4, '0', STR_PAD_LEFT);

require_once 'includes/header.php';
?>



<?php if ($role === 'master'): ?>
    <!-- ==================== TAILOR MASTER DASHBOARD ==================== -->
    <div class="responsive-header-flex" style="margin-bottom: 30px;">
        <div>
            <h2 style="font-size: 28px; font-weight: 700;"><?php echo __('dashboard'); ?></h2>
            <p style="color: var(--text-secondary);"><?php echo __('tagline'); ?> &bull; Shop ID: <?php echo $shopId; ?></p>
        </div>
        <div class="header-action-buttons">
            <button onclick="openModal('modal-staff')" class="btn-glass btn-neon-gold">+ Add Karigar / کاریگر</button>
            <button onclick="openModal('modal-customer')" class="btn-glass btn-neon-orchid">+ Add Customer / نیا گاہک</button>
            <button onclick="openModal('modal-order')" class="btn-glass btn-neon-cyan">+ Create New Order / نیا آرڈر</button>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="dashboard-grid">
        <div class="glass-card stat-card">
            <div>
                <span class="form-label" style="margin: 0;"><?php echo __('total_revenue'); ?></span>
                <div class="stat-val" style="color: var(--neon-emerald);">Rs. <?php echo number_format($stats['revenue'], 0); ?></div>
            </div>
            <div class="stat-icon revenue">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 24px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.251.11a3.375 3.375 0 003.498 0L13 12m-3-2.818l.251-.11a3.375 3.375 0 013.498 0L17 12m-7.141 3.536L12 16.5m0-9v9" /></svg>
            </div>
        </div>
        <div class="glass-card stat-card">
            <div>
                <span class="form-label" style="margin: 0;"><?php echo __('total_orders'); ?></span>
                <div class="stat-val" style="color: var(--neon-cyan);"><?php echo $stats['orders']; ?></div>
            </div>
            <div class="stat-icon orders">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 24px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
            </div>
        </div>
        <div class="glass-card stat-card">
            <div>
                <span class="form-label" style="margin: 0;"><?php echo __('outstanding'); ?></span>
                <div class="stat-val" style="color: var(--neon-gold);">Rs. <?php echo number_format($stats['balance'], 0); ?></div>
            </div>
            <div class="stat-icon balance">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 24px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            </div>
        </div>
    </div>

    <!-- Kanban Production Section -->
    <div class="glass-card" style="margin-bottom: 30px;">
        <h3 style="margin-bottom: 10px; font-size: 20px; display: flex; align-items: center; gap: 8px; color: var(--neon-cyan);">
            📅 <?php echo __('kanban_board'); ?>
        </h3>
        <p style="color: var(--text-secondary); font-size: 14px; margin-bottom: 20px;">
            Drag and drop orders between stages or click status controls to update. Scopes are secured to your tenant.
        </p>
        
        <?php include 'views/kanban_board.php'; ?>
    </div>

    <!-- Completed Orders Section -->
    <div class="glass-card" style="margin-bottom: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <h3 style="margin: 0; font-size: 18px; color: var(--text-muted); display: flex; align-items: center; gap: 8px;">
                ✅ Completed Orders (Dispatched)
            </h3>
            <button type="button" onclick="confirmBulkDelete('form-bulk-completed', 'Are you sure you want to permanently delete the selected completed orders?')" class="btn-glass" style="padding: 6px 12px; font-size: 12px; border-color: red; color: red;">🗑️ Bulk Delete</button>
        </div>
        <form id="form-bulk-completed" action="dashboard.php" method="POST">
            <input type="hidden" name="action" value="bulk_delete_orders">
            <div style="overflow-x: auto; margin-top: 15px;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-secondary); font-size: 13px;">
                        <th style="padding: 10px 5px; width: 40px;"><input type="checkbox" class="select-all-cb" onchange="toggleSelectAll(this, 'order_ids[]')"></th>
                        <th style="padding: 10px 5px;"><?php echo __('tag_id'); ?></th>
                        <th style="padding: 10px 5px;"><?php echo __('customer_name'); ?></th>
                        <th style="padding: 10px 5px;">Date Dispatched</th>
                        <th style="padding: 10px 5px; text-align: right;"><?php echo __('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $dispatchedCount = 0;
                    foreach ($ordersList as $ord): 
                        if ($ord['status'] === 'dispatched'):
                            $dispatchedCount++;
                    ?>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.03); font-size: 14px;">
                            <td style="padding: 12px 5px;"><input type="checkbox" name="order_ids[]" value="<?php echo $ord['id']; ?>"></td>
                            <td style="padding: 12px 5px; font-weight: bold; color: #6b7280;"><?php echo htmlspecialchars($ord['tag_id']); ?></td>
                            <td style="padding: 12px 5px; color: var(--text-secondary);"><?php echo htmlspecialchars($ord['customer_name']); ?></td>
                            <td style="padding: 12px 5px; color: var(--text-secondary);"><?php echo date('Y-m-d', strtotime($ord['created_at'])); ?></td>
                            <td style="padding: 12px 5px; text-align: right; white-space: nowrap;">
                                <a href="print_receipt.php?id=<?php echo urlencode($ord['tag_id']); ?>" target="_blank" class="btn-glass" style="padding: 4px 8px; font-size: 11px; text-decoration: none;" title="Print Receipt">🖨️</a>
                                <button type="button" class="btn-glass" onclick="if(confirm('Delete this completed order?')) { const f = document.createElement('form'); f.method = 'POST'; f.action = 'dashboard.php'; const a = document.createElement('input'); a.type='hidden'; a.name='action'; a.value='delete_order'; const i = document.createElement('input'); i.type='hidden'; i.name='delete_order_id'; i.value='<?php echo $ord['id']; ?>'; f.appendChild(a); f.appendChild(i); document.body.appendChild(f); f.submit(); }" style="padding: 4px 8px; font-size: 11px; border-color: red; color: red; margin-left: 5px;" title="Delete Order">🗑️</button>
                            </td>
                        </tr>
                    <?php 
                        endif;
                    endforeach; 
                    if ($dispatchedCount === 0):
                    ?>
                        <tr><td colspan="5" style="padding: 20px; text-align: center; color: var(--text-muted);">No completed orders yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        </form>
    </div>

    <!-- Customers & Sizing Vault Scopes -->
    <div class="responsive-grid-2col" style="margin-bottom: 30px;">
        <!-- Customers Directory -->
        <div class="glass-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
                <h3 style="margin: 0; font-size: 18px; color: var(--neon-orchid);">👥 <?php echo __('customers'); ?></h3>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button type="button" onclick="confirmBulkDelete('form-bulk-customers', 'Are you sure you want to permanently delete the selected customers AND all their orders?')" class="btn-glass" style="padding: 6px 12px; font-size: 12px; border-color: red; color: red;">🗑️ Bulk Delete</button>
                    <input type="text" id="customers-search" placeholder="Search customers..." oninput="filterTable('customers-table', this.value)" style="width: 180px; font-size: 12px; padding: 6px 10px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: var(--text-primary); outline: none;">
                </div>
            </div>
            <form id="form-bulk-customers" action="dashboard.php" method="POST">
                <input type="hidden" name="action" value="bulk_delete_customers">
            <div style="overflow-x: auto;">
                <table id="customers-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-secondary); font-size: 13px;">
                            <th style="padding: 10px 5px; width: 40px;"><input type="checkbox" class="select-all-cb" onchange="toggleSelectAll(this, 'customer_ids[]')"></th>
                            <th style="padding: 10px 5px; cursor: pointer; user-select: none;" onclick="sortTable('customers-table', 1, 'text', this)">
                                <?php echo __('customer_name'); ?> <span class="sort-arrow">⇅</span>
                            </th>
                            <th style="padding: 10px 5px; cursor: pointer; user-select: none;" onclick="sortTable('customers-table', 1, 'text', this)">
                                <?php echo __('phone'); ?> <span class="sort-arrow">⇅</span>
                            </th>
                            <th style="padding: 10px 5px; cursor: pointer; user-select: none;" onclick="sortTable('customers-table', 2, 'date', this)">
                                <?php echo __('date'); ?> <span class="sort-arrow">⇅</span>
                            </th>
                            <th style="padding: 10px 5px; text-align: right;"><?php echo __('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($customersList)): ?>
                            <tr><td colspan="5" style="padding: 20px; text-align: center; color: var(--text-muted);">No customers registered yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($customersList as $cust): ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.03); font-size: 14px;">
                                    <td style="padding: 12px 5px;"><input type="checkbox" name="customer_ids[]" value="<?php echo $cust['id']; ?>"></td>
                                    <td style="padding: 12px 5px; font-weight: 500;"><?php echo htmlspecialchars($cust['name']); ?></td>
                                    <td style="padding: 12px 5px; color: var(--text-secondary);"><?php echo htmlspecialchars($cust['phone']); ?></td>
                                    <td style="padding: 12px 5px; color: var(--text-secondary);"><?php echo date('Y-m-d', strtotime($cust['created_at'])); ?></td>
                                    <td style="padding: 12px 5px; text-align: right;">
                                        <button type="button" onclick='openVaultModal(<?php echo htmlspecialchars(json_encode($cust), ENT_QUOTES, "UTF-8"); ?>)' class="btn-glass" style="padding: 4px 8px; font-size: 12px; border-color: var(--neon-cyan); color: var(--neon-cyan);">
                                            📏 <?php echo __('measurements'); ?>
                                        </button>
                                        <button type="button" class="btn-glass" onclick="if(confirm('Delete this customer and ALL associated orders?')) { const f = document.createElement('form'); f.method = 'POST'; f.action = 'dashboard.php'; const a = document.createElement('input'); a.type='hidden'; a.name='action'; a.value='delete_customer'; const i = document.createElement('input'); i.type='hidden'; i.name='delete_customer_id'; i.value='<?php echo $cust['id']; ?>'; f.appendChild(a); f.appendChild(i); document.body.appendChild(f); f.submit(); }" style="padding: 4px 8px; font-size: 12px; border-color: red; color: red; margin-left: 5px;" title="Delete Customer">🗑️</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            </form>
        </div>

        <!-- Financial Ledger -->
        <div class="glass-card">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 15px; flex-wrap: wrap; gap: 15px;">
                <h3 style="margin: 0; font-size: 18px; color: var(--neon-gold); min-width: 150px;">💰 <?php echo __('ledger'); ?></h3>
                
                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <label for="ledger-start" style="color: var(--text-secondary); font-size: 12px;">From:</label>
                        <input type="date" id="ledger-start" class="form-control" onchange="filterTable('ledger-table')" style="padding: 4px 8px; font-size: 12px; height: 30px;">
                    </div>
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <label for="ledger-end" style="color: var(--text-secondary); font-size: 12px;">To:</label>
                        <input type="date" id="ledger-end" class="form-control" onchange="filterTable('ledger-table')" style="padding: 4px 8px; font-size: 12px; height: 30px;">
                    </div>
                    <input type="text" id="ledger-search" placeholder="Search ledger..." oninput="filterTable('ledger-table', this.value)" style="width: 150px; font-size: 12px; padding: 6px 10px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: var(--text-primary); outline: none;">
                </div>
            </div>
            
            <!-- Dynamic Ledger Totals -->
            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div class="glass-card stat-card" style="padding: 10px 15px; flex: 1; border-color: rgba(13,242,138,0.3);">
                    <span class="form-label" style="margin: 0; font-size: 12px;">Received Amount (Filtered)</span>
                    <div class="stat-val" id="filter-received" style="color: var(--neon-emerald); font-size: 18px;">Rs. 0</div>
                </div>
                <div class="glass-card stat-card" style="padding: 10px 15px; flex: 1; border-color: rgba(255,184,0,0.3);">
                    <span class="form-label" style="margin: 0; font-size: 12px;">Remaining Balance (Filtered)</span>
                    <div class="stat-val" id="filter-balance" style="color: var(--neon-gold); font-size: 18px;">Rs. 0</div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-bottom: 10px;">
                <button type="button" onclick="confirmBulkDelete('form-bulk-ledger', 'Are you sure you want to permanently delete the selected orders?')" class="btn-glass" style="padding: 6px 12px; font-size: 12px; border-color: red; color: red;">🗑️ Bulk Delete</button>
            </div>
            <form id="form-bulk-ledger" action="dashboard.php" method="POST">
                <input type="hidden" name="action" value="bulk_delete_orders">
            <div style="overflow-x: auto;">
                <table id="ledger-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-secondary); font-size: 13px;">
                            <th style="padding: 10px 5px; width: 40px;"><input type="checkbox" class="select-all-cb" onchange="toggleSelectAll(this, 'order_ids[]')"></th>
                            <th style="padding: 10px 5px; cursor: pointer; user-select: none;" onclick="sortTable('ledger-table', 1, 'text', this)">
                                <?php echo __('tag_id'); ?> <span class="sort-arrow">⇅</span>
                            </th>
                            <th style="padding: 10px 5px; cursor: pointer; user-select: none;" onclick="sortTable('ledger-table', 1, 'text', this)">
                                <?php echo __('customer_name'); ?> <span class="sort-arrow">⇅</span>
                            </th>
                            <th style="padding: 10px 5px; cursor: pointer; user-select: none;" onclick="sortTable('ledger-table', 2, 'number', this)">
                                <?php echo __('price'); ?> <span class="sort-arrow">⇅</span>
                            </th>
                            <th style="padding: 10px 5px; cursor: pointer; user-select: none;" onclick="sortTable('ledger-table', 3, 'number', this)">
                                <?php echo __('balance'); ?> <span class="sort-arrow">⇅</span>
                            </th>
                            <th style="padding: 10px 5px; cursor: pointer; user-select: none;" onclick="sortTable('ledger-table', 4, 'date', this)">
                                <?php echo __('date'); ?> <span class="sort-arrow">⇅</span>
                            </th>
                            <th style="padding: 10px 5px; text-align: right;"><?php echo __('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ordersList)): ?>
                            <tr><td colspan="7" style="padding: 20px; text-align: center; color: var(--text-muted);">No financial entries.</td></tr>
                        <?php else: ?>
                            <?php foreach ($ordersList as $ord): ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.03); font-size: 14px;">
                                    <td style="padding: 12px 5px;"><input type="checkbox" name="order_ids[]" value="<?php echo $ord['id']; ?>"></td>
                                    <td style="padding: 12px 5px; font-weight: bold; color: var(--neon-cyan);"><?php echo htmlspecialchars($ord['tag_id']); ?></td>
                                    <td style="padding: 12px 5px; color: var(--text-secondary);"><?php echo htmlspecialchars($ord['customer_name']); ?></td>
                                    <td style="padding: 12px 5px;">Rs. <?php echo number_format($ord['price'], 0); ?></td>
                                    <td style="padding: 12px 5px; font-weight: 500; color: <?php echo ($ord['price'] - $ord['advance_paid'] > 0) ? 'var(--neon-gold)' : 'var(--neon-emerald)'; ?>">
                                        Rs. <?php echo number_format($ord['price'] - $ord['advance_paid'], 0); ?>
                                    </td>
                                    <td style="padding: 12px 5px; color: var(--text-secondary);"><?php echo date('Y-m-d', strtotime($ord['created_at'])); ?></td>
                                    <td style="padding: 12px 5px; text-align: right; white-space: nowrap;">
                                        <a href="print_receipt.php?id=<?php echo urlencode($ord['tag_id']); ?>" target="_blank" class="btn-glass" style="padding: 4px 8px; font-size: 11px; text-decoration: none;" title="Print Receipt">🖨️</a>
                                        <button type="button" class="btn-glass" onclick="if(confirm('Delete this order?')) { const f = document.createElement('form'); f.method = 'POST'; f.action = 'dashboard.php'; const a = document.createElement('input'); a.type='hidden'; a.name='action'; a.value='delete_order'; const i = document.createElement('input'); i.type='hidden'; i.name='delete_order_id'; i.value='<?php echo $ord['id']; ?>'; f.appendChild(a); f.appendChild(i); document.body.appendChild(f); f.submit(); }" style="padding: 4px 8px; font-size: 11px; border-color: red; color: red; margin-left: 5px;" title="Delete Order">🗑️</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            </form>
        </div>
    </div>

    <?php elseif ($role === 'karigar'): ?>
    <!-- ==================== KARIGAR DASHBOARD ==================== -->
    <div style="margin-bottom: 30px;">
        <h2 style="font-size: 28px; font-weight: 700; color: var(--neon-cyan);">🛠️ <?php echo __('role_karigar'); ?> Dashboard</h2>
        <p style="color: var(--text-secondary);">Production-only dashboard. Scoped work for shop ID: <?php echo $shopId; ?></p>
    </div>

    <!-- Kanban Production Section -->
    <div class="glass-card" style="margin-bottom: 30px;">
        <h3 style="margin-bottom: 15px; font-size: 20px; display: flex; align-items: center; gap: 8px; color: var(--neon-cyan);">
            📅 <?php echo __('kanban_board'); ?>
        </h3>
        
        <?php include 'views/kanban_board.php'; ?>
    </div>

<?php elseif ($role === 'customer'): ?>
    <!-- ==================== CUSTOMER MY VAULT DASHBOARD ==================== -->
    <!-- Customer My Vault Dashboard Section -->
    <div style="margin-bottom: 30px;">
        <h2 style="font-size: 28px; font-weight: 700; color: var(--neon-gold);">👤 <?php echo __('my_vault'); ?></h2>
        <p style="color: var(--text-secondary);">Manage your personalized sizing profile and track tailoring order statuses.</p>
    </div>

    <style>
        .customer-dashboard-layout {
            display: grid; 
            grid-template-columns: 1.2fr 0.8fr; 
            gap: 24px; 
            margin-bottom: 30px;
        }
        @media (max-width: 768px) {
            .customer-dashboard-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <?php if ($customerProfile): ?>
        <div class="customer-dashboard-layout">
            <!-- Sizing Vault -->
            <div class="glass-card">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; margin-bottom: 15px;">
                    <h3 style="color: var(--neon-orchid); font-size: 18px;">📐 Personal Sizing Vault / ذاتی پیمائش کا والٹ</h3>
                    <span style="font-size: 12px; color: var(--text-muted);">Self-Editable Profile / خود قابلِ ترمیم پروفائل</span>
                </div>
                
                <form action="api/save_measurements.php" method="POST" id="vault-edit-form">
                    <input type="hidden" name="customer_id" value="<?php echo $customerProfile['id']; ?>">
                    <input type="hidden" name="redirect_back" value="1">
                    
                    <?php 
                        $measurements = json_decode($customerProfile['measurements'], true);
                        include 'views/measurement_inputs.php'; 
                    ?>
                    
                    <button type="submit" class="btn-glass btn-neon-orchid" style="width: 100%; justify-content: center; padding: 12px; margin-top: 20px;">
                        💾 Save Sizing Measurements / پیمائش محفوظ کریں
                    </button>
                </form>
            </div>

            <!-- Order Tracking -->
            <div class="glass-card">
                <h3 style="color: var(--neon-cyan); font-size: 18px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; margin-bottom: 15px;">
                    📦 Orders & Production Status
                </h3>
                
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <?php if (empty($ordersList)): ?>
                        <div style="text-align: center; color: var(--text-muted); padding: 30px;">
                            No active orders placed yet.
                        </div>
                    <?php else: ?>
                        <?php foreach ($ordersList as $ord): ?>
                            <div class="glass-card" style="padding: 15px; border-color: rgba(255,255,255,0.05); background: rgba(0,0,0,0.2);">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <span style="font-family: var(--font-english); font-weight: bold; color: var(--neon-cyan);"><?php echo htmlspecialchars($ord['tag_id']); ?></span>
                                    
                                    <!-- Status Badges -->
                                    <?php if ($ord['status'] === 'received'): ?>
                                        <span style="color: var(--neon-gold); font-size: 12px; border: 1px solid var(--neon-gold); padding: 2px 8px; border-radius: 12px; background: rgba(255,184,0,0.1);"><?php echo __('status_received'); ?></span>
                                    <?php elseif ($ord['status'] === 'cutting'): ?>
                                        <span style="color: var(--neon-orchid); font-size: 12px; border: 1px solid var(--neon-orchid); padding: 2px 8px; border-radius: 12px; background: rgba(184,41,242,0.1);"><?php echo __('status_cutting'); ?></span>
                                    <?php elseif ($ord['status'] === 'stitching'): ?>
                                        <span style="color: var(--neon-cyan); font-size: 12px; border: 1px solid var(--neon-cyan); padding: 2px 8px; border-radius: 12px; background: rgba(0,240,255,0.1);"><?php echo __('status_stitching'); ?></span>
                                    <?php elseif ($ord['status'] === 'ready'): ?>
                                        <span style="color: var(--neon-emerald); font-size: 12px; border: 1px solid var(--neon-emerald); padding: 2px 8px; border-radius: 12px; background: rgba(13,242,138,0.1);"><?php echo __('status_ready'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 4px;">
                                    Shop: <strong><?php echo htmlspecialchars($ord['shop_name']); ?></strong>
                                </div>
                                <div style="font-size: 13px; color: var(--text-secondary);">
                                    Price: Rs. <?php echo number_format($ord['price'], 2); ?> &bull; Balance: 
                                    <strong style="color: <?php echo ($ord['price'] - $ord['advance_paid'] > 0) ? 'var(--neon-gold)' : 'var(--neon-emerald)'; ?>">
                                        Rs. <?php echo number_format($ord['price'] - $ord['advance_paid'], 2); ?>
                                    </strong>
                                </div>
                                <?php if (!empty($ord['notes'])): ?>
                                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 8px; font-style: italic; border-top: 1px dashed rgba(255,255,255,0.05); padding-top: 8px;">
                                        "<?php echo htmlspecialchars($ord['notes']); ?>"
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="glass-card" style="text-align: center; padding: 40px; color: var(--text-secondary);">
            ⚠️ No measurement vault record linked. Please contact your workshop admin.
        </div>
    <?php endif; ?>

<?php elseif ($role === 'super_admin'): ?>
    <!-- ==================== SUPER ADMIN DASHBOARD ==================== -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h2 style="font-size: 28px; font-weight: 700; color: var(--neon-orchid);">👑 Super Admin Dashboard</h2>
            <p style="color: var(--text-secondary);">System-wide overview and management.</p>
        </div>
        <div>
            <a href="register.php" class="btn-glass btn-neon-orchid">+ Register New Workshop</a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="dashboard-grid">
        <div class="glass-card stat-card">
            <div>
                <span class="form-label" style="margin: 0;">Total Workshops</span>
                <div class="stat-val" style="color: var(--neon-cyan);"><?php echo $superStats['shops']; ?></div>
            </div>
        </div>
        <div class="glass-card stat-card">
            <div>
                <span class="form-label" style="margin: 0;">Total Users</span>
                <div class="stat-val" style="color: var(--neon-orchid);"><?php echo $superStats['users']; ?></div>
            </div>
        </div>
        <div class="glass-card stat-card">
            <div>
                <span class="form-label" style="margin: 0;">Total Customers</span>
                <div class="stat-val" style="color: var(--neon-gold);"><?php echo $superStats['customers']; ?></div>
            </div>
        </div>
        <div class="glass-card stat-card">
            <div>
                <span class="form-label" style="margin: 0;">Total Orders</span>
                <div class="stat-val" style="color: var(--neon-emerald);"><?php echo $superStats['orders']; ?></div>
            </div>
        </div>
    </div>

    <!-- Shops Table -->
    <div class="glass-card" style="margin-top: 30px;">
        <h3 style="margin-bottom: 15px; font-size: 20px; color: var(--neon-cyan);">Registered Workshops</h3>
        <div style="overflow-x: auto;">
            <table id="shops-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-secondary);">
                        <th style="padding: 10px 5px; cursor: pointer; user-select: none;" onclick="sortTable('shops-table', 0, 'number', this)">ID <span class="sort-arrow" style="font-size: 10px; opacity: 0.5; margin-left: 5px;">⇅</span></th>
                        <th style="padding: 10px 5px; cursor: pointer; user-select: none;" onclick="sortTable('shops-table', 1, 'text', this)">Shop Name <span class="sort-arrow" style="font-size: 10px; opacity: 0.5; margin-left: 5px;">⇅</span></th>
                        <th style="padding: 10px 5px; cursor: pointer; user-select: none;" onclick="sortTable('shops-table', 2, 'text', this)">Phone <span class="sort-arrow" style="font-size: 10px; opacity: 0.5; margin-left: 5px;">⇅</span></th>
                        <th style="padding: 10px 5px; cursor: pointer; user-select: none;" onclick="sortTable('shops-table', 3, 'date', this)">Registered On <span class="sort-arrow" style="font-size: 10px; opacity: 0.5; margin-left: 5px;">⇅</span></th>
                        <th style="padding: 10px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody class="paginated-table" data-rows="10">
                    <?php foreach ($shopsList as $shop): ?>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <td style="padding: 10px;"><?php echo $shop['id']; ?></td>
                        <td style="padding: 10px; font-weight: bold;"><?php echo htmlspecialchars($shop['name']); ?></td>
                        <td style="padding: 10px;"><?php echo htmlspecialchars($shop['phone']); ?></td>
                        <td style="padding: 10px;"><?php echo date('M d, Y', strtotime($shop['created_at'])); ?></td>
                        <td style="padding: 10px; text-align: right;">
                            <button type="button" class="btn-glass" onclick='openEditShopModal(<?php echo htmlspecialchars(json_encode($shop), ENT_QUOTES, "UTF-8"); ?>)' style="padding: 4px 8px; font-size: 12px; border-color: var(--neon-gold); color: var(--neon-gold); margin-right: 5px;">✏️ Edit</button>
                            <form action="dashboard.php" method="POST" onsubmit="return confirm('Are you sure you want to completely delete this workshop and ALL its associated users, customers, and orders? This cannot be undone.');" style="display:inline;">
                                <input type="hidden" name="action" value="delete_shop">
                                <input type="hidden" name="delete_shop_id" value="<?php echo $shop['id']; ?>">
                                <button type="submit" class="btn-glass" style="padding: 4px 8px; font-size: 12px; border-color: red; color: red;">🗑️ Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="pagination-controls" style="margin-top: 10px; text-align: center;"></div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="glass-card" style="margin-top: 30px;">
        <h3 style="margin-bottom: 15px; font-size: 20px; color: var(--neon-gold);">System Users</h3>
        <div style="overflow-x: auto;">
            <table id="users-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-secondary);">
                        <th style="padding: 10px 5px; cursor: pointer; user-select: none;" onclick="sortTable('users-table', 0, 'number', this)">ID <span class="sort-arrow" style="font-size: 10px; opacity: 0.5; margin-left: 5px;">⇅</span></th>
                        <th style="padding: 10px 5px; cursor: pointer; user-select: none;" onclick="sortTable('users-table', 1, 'text', this)">Username <span class="sort-arrow" style="font-size: 10px; opacity: 0.5; margin-left: 5px;">⇅</span></th>
                        <th style="padding: 10px 5px; cursor: pointer; user-select: none;" onclick="sortTable('users-table', 2, 'text', this)">Role <span class="sort-arrow" style="font-size: 10px; opacity: 0.5; margin-left: 5px;">⇅</span></th>
                        <th style="padding: 10px 5px; cursor: pointer; user-select: none;" onclick="sortTable('users-table', 3, 'text', this)">Workshop <span class="sort-arrow" style="font-size: 10px; opacity: 0.5; margin-left: 5px;">⇅</span></th>
                        <th style="padding: 10px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allUsersList as $u): ?>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <td style="padding: 10px;"><?php echo $u['id']; ?></td>
                        <td style="padding: 10px; font-weight: bold;"><?php echo htmlspecialchars($u['username']); ?></td>
                        <td style="padding: 10px; text-transform: capitalize;"><?php echo htmlspecialchars($u['role']); ?></td>
                        <td style="padding: 10px;"><?php echo htmlspecialchars($u['shop_name'] ?? 'System Wide'); ?></td>
                        <td style="padding: 10px; text-align: right; white-space: nowrap;">
                            <button type="button" class="btn-glass" onclick='openEditUserModal(<?php echo htmlspecialchars(json_encode($u), ENT_QUOTES, "UTF-8"); ?>)' style="padding: 4px 8px; font-size: 12px; border-color: var(--neon-gold); color: var(--neon-gold); margin-right: 5px;">✏️ Edit</button>
                            <form action="dashboard.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this user? This cannot be undone.');" style="display:inline; margin-right: 5px;">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="delete_user_id" value="<?php echo $u['id']; ?>">
                                <button type="submit" class="btn-glass" style="padding: 4px 8px; font-size: 12px; border-color: red; color: red;">🗑️ Delete</button>
                            </form>
                            <button type="button" class="btn-glass" onclick='openChangePasswordModal(<?php echo htmlspecialchars(json_encode($u), ENT_QUOTES, "UTF-8"); ?>)' style="padding: 4px 8px; font-size: 12px; border-color: var(--neon-cyan); color: var(--neon-cyan);">🔑 Password</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>


<!-- ==================== MODALS (MASTER ROLE) ==================== -->

<!-- 0. Add Karigar/Staff Modal -->
<div id="modal-staff" class="modal-overlay">
    <div class="modal-content glass-card" style="max-width: 450px;">
        <h3 style="color: var(--neon-gold); font-size: 20px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
            🛠️ Add Karigar / کاریگر شامل کریں
        </h3>
        <form action="dashboard.php" method="POST">
            <input type="hidden" name="action" value="add_staff">
            <input type="hidden" name="role" value="karigar">
            
            <div class="form-group">
                <label class="form-label" for="staff_username">Username / کاریگر کا نام *</label>
                <input type="text" name="username" id="staff_username" class="form-control" required placeholder="karigar_ahmed">
            </div>
            
            <!-- Password field removed as Karigars do not login directly -->
            
            <div class="form-group">
                <label class="form-label" for="staff_phone">Phone Number / فون نمبر</label>
                <input type="text" name="phone" id="staff_phone" class="form-control" placeholder="03001234567">
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 25px;">
                <button type="submit" class="btn-glass btn-neon-gold" style="flex: 1; justify-content: center;">Add Karigar / کاریگر شامل کریں</button>
                <button type="button" onclick="closeModal('modal-staff')" class="btn-glass" style="flex: 1; justify-content: center;">Cancel / منسوخ کریں</button>
            </div>
        </form>
    </div>
</div>

<!-- 1. Add Customer Modal -->
<div id="modal-customer" class="modal-overlay">
    <div class="modal-content glass-card" style="max-width: 450px;">
        <h3 style="color: var(--neon-orchid); font-size: 20px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
            👥 Add Customer / نیا گاہک شامل کریں
        </h3>
        <form action="dashboard.php" method="POST">
            <input type="hidden" name="action" value="add_customer">
            
            <div class="form-group">
                <label class="form-label" for="cust_name">Customer Name / گاہک کا نام *</label>
                <input type="text" name="name" id="cust_name" class="form-control" required placeholder="Ali Khan">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="cust_phone">Phone Number / فون نمبر *</label>
                <input type="text" name="phone" id="cust_phone" class="form-control" required placeholder="03001234567">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="cust_gender">Gender / جنس *</label>
                <select name="gender" id="cust_gender" class="form-control" required>
                    <option value="male">Male (Gents) / مرد</option>
                    <option value="female">Female (Lady) / خاتون</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 25px;">
                <button type="submit" class="btn-glass btn-neon-orchid" style="flex: 1; justify-content: center;">Save / محفوظ کریں</button>
                <button type="button" onclick="closeModal('modal-customer')" class="btn-glass" style="flex: 1; justify-content: center;">Cancel / منسوخ کریں</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. Create Order Modal with QR Fabric upload bridge -->
<div id="modal-order" class="modal-overlay">
    <div class="modal-content glass-card" style="max-width: 900px; width: 95%;">
        <h3 style="color: var(--neon-cyan); font-size: 20px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
            📦 Create Order / نیا آرڈر درج کریں
        </h3>
        <form action="dashboard.php" method="POST">
            <input type="hidden" name="action" value="create_order">
            <input type="hidden" name="bridge_session_id" id="bridge_session_id" value="">
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                <div class="form-group" style="margin-bottom: 5px;">
                    <label class="form-label" for="order_tag">Tag ID / ٹیگ آئی ڈی *</label>
                    <input type="text" name="tag_id" id="order_tag" class="form-control" required readonly value="<?php echo $newTagId; ?>">
                </div>
                
                <div class="form-group" style="margin-bottom: 5px;">
                    <label class="form-label" for="cust_select">Select Customer / گاہک منتخب کریں *</label>
                    <select name="customer_id" id="cust_select" class="form-control" required>
                        <option value="">Select Customer / گاہک منتخب کریں...</option>
                        <?php foreach ($customersList as $cust): ?>
                            <option value="<?php echo $cust['id']; ?>"><?php echo htmlspecialchars($cust['name']); ?> (<?php echo htmlspecialchars($cust['phone']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 5px;">
                    <label class="form-label" for="assigned_karigar">Assign Karigar / کاریگر متعین کریں</label>
                    <select name="assigned_to" id="assigned_karigar" class="form-control">
                        <option value="">Select Karigar / کاریگر منتخب کریں (Optional)...</option>
                        <?php foreach ($karigarsList as $k): ?>
                            <option value="<?php echo $k['id']; ?>"><?php echo htmlspecialchars($k['username']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- MEASUREMENTS SECTION INSIDE ORDER FORM -->
            <div id="order-measurements-container" style="display: none; margin-top: 15px; margin-bottom: 20px; border-top: 1px dashed rgba(255,255,255,0.1); padding-top: 15px;">
                <?php 
                    $idPrefix = 'o_';
                    $measurements = ["upper" => [], "lower" => []];
                    $isFemale = false; // Default until JS sets it
                    include 'views/measurement_inputs.php'; 
                ?>
            </div>
            
            
            <!-- ZERO-COST FABRIC IMAGE UPLOAD BRIDGE -->
            <div class="glass-card" style="background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,0.05); padding: 15px; margin-bottom: 20px;">
                <h4 style="font-size: 14px; color: var(--text-secondary); margin-bottom: 8px;">📷 Fabric Image Upload Bridge / کپڑے کی تصویر کا لنک</h4>
                <div style="display: flex; gap: 15px; align-items: center;">
                    <button type="button" onclick="startUploadBridgeSession()" id="btn-init-bridge" class="btn-glass" style="font-size: 13px; border-color: var(--neon-cyan); color: var(--neon-cyan);">
                        Generate Upload QR Link / کیو آر کوڈ بنائیں
                    </button>
                    <div id="bridge-status-text" style="font-size: 12px; color: var(--text-muted);">No upload bridge session active.</div>
                </div>
                
                <!-- Expanded QR container hidden initially -->
                <div id="bridge-qr-container" style="display: none; margin-top: 15px; text-align: center;">
                    <p style="font-size: 11px; color: var(--text-secondary); margin-bottom: 8px;">
                        Scan from mobile to take a photo & compress (< 200KB):
                    </p>
                    <div style="background: white; padding: 8px; border-radius: 8px; display: inline-block;">
                        <canvas id="bridge-qr-canvas"></canvas>
                    </div>
                    <div id="uploaded-fabric-preview-box" style="display: none; margin-top: 15px;">
                        <span style="font-size: 12px; color: var(--neon-emerald); display: block; margin-bottom: 5px;">✓ Fabric Image Synced!</span>
                        <img id="uploaded-fabric-preview-img" src="" class="fabric-preview-large">
                    </div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label" for="order_price">Price (Rs) / کل رقم</label>
                    <input type="number" step="0.01" name="price" id="order_price" class="form-control" placeholder="0.00" value="0.00">
                </div>
                <div class="form-group">
                    <label class="form-label" for="order_advance">Advance Paid (Rs) / پیشگی رقم</label>
                    <input type="number" step="0.01" name="advance" id="order_advance" class="form-control" placeholder="0.00" value="0.00">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="order_notes">Notes / خصوصی ہدایات</label>
                <textarea name="notes" id="order_notes" rows="2" class="form-control" placeholder="e.g. Double stitching, short collar..."></textarea>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 25px;">
                <button type="submit" class="btn-glass btn-neon-cyan" style="flex: 1; justify-content: center;">Create Order / نیا آرڈر درج کریں</button>
                <button type="button" onclick="closeModal('modal-order')" class="btn-glass" style="flex: 1; justify-content: center;">Cancel / منسوخ کریں</button>
            </div>
        </form>
    </div>
</div>

<!-- 3. Edit Measurements Modal for Master -->
<div id="modal-vault" class="modal-overlay">
    <div class="modal-content glass-card" style="max-width: 900px; width: 95%;">
        <h3 id="vault-title" style="color: var(--neon-orchid); font-size: 20px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
            📐 Edit Sizing Vault measurements / پیمائش تبدیل کریں
        </h3>
        
        <form action="api/save_measurements.php" method="POST" id="vault-form">
            <input type="hidden" name="customer_id" id="vault_customer_id" value="">
            <input type="hidden" name="redirect_back" value="1">
            
            <div id="measurement-fields-container">
                <!-- Dynamically loaded via views/measurement_inputs.php or standard PHP loading -->
                <!-- We will include the template statically and populate values using JS -->
                <?php 
                    // Render the inputs. We will set inputs value manually via JavaScript.
                    $idPrefix = 'm_';
                    $measurements = [
                        "upper" => [],
                        "lower" => []
                    ];
                    include 'views/measurement_inputs.php'; 
                ?>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 25px;">
                <button type="submit" class="btn-glass btn-neon-orchid" style="flex: 1; justify-content: center;">Save Changes / محفوظ کریں</button>
                <button type="button" onclick="closeModal('modal-vault')" class="btn-glass" style="flex: 1; justify-content: center;">Cancel / منسوخ کریں</button>
            </div>
        </form>
    </div>
</div>

<script>
    function updateOrderStatus(tagId, status) {
        if (!confirm('Are you sure you want to update this order to ' + status.toUpperCase() + '?')) return;
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'dashboard.php';
        form.innerHTML = `
            <input type="hidden" name="action" value="update_order_status">
            <input type="hidden" name="tag_id" value="${tagId}">
            <input type="hidden" name="status" value="${status}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
</script>

<!-- Super Admin Modals -->
<?php if ($role === 'super_admin'): ?>
<!-- Edit Shop Modal -->
<div id="modal-edit-shop" class="modal-overlay">
    <div class="modal-content glass-card" style="max-width: 450px;">
        <h3 style="color: var(--neon-gold); font-size: 20px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
            ✏️ Edit Workshop
        </h3>
        <form action="dashboard.php" method="POST">
            <input type="hidden" name="action" value="edit_shop">
            <input type="hidden" name="edit_shop_id" id="edit_shop_id" value="">
            
            <div class="form-group">
                <label class="form-label" for="edit_shop_name">Shop Name *</label>
                <input type="text" name="shop_name" id="edit_shop_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="edit_shop_phone">Shop Phone</label>
                <input type="text" name="shop_phone" id="edit_shop_phone" class="form-control">
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 25px;">
                <button type="submit" class="btn-glass btn-neon-gold" style="flex: 1; justify-content: center;">Save Changes</button>
                <button type="button" onclick="closeModal('modal-edit-shop')" class="btn-glass" style="flex: 1; justify-content: center;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-edit-user" class="modal-overlay">
    <div class="modal-content glass-card" style="max-width: 450px;">
        <h3 style="color: var(--neon-gold); font-size: 20px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
            ✏️ Edit System User
        </h3>
        <form action="dashboard.php" method="POST">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="edit_user_id" id="edit_user_id" value="">
            
            <div class="form-group">
                <label class="form-label" for="edit_user_username">Username *</label>
                <input type="text" name="username" id="edit_user_username" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="edit_user_phone">Phone</label>
                <input type="text" name="phone" id="edit_user_phone" class="form-control">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="edit_user_role">Role *</label>
                <select name="role" id="edit_user_role" class="form-control" required>
                    <option value="super_admin">Super Admin</option>
                    <option value="master">Master (Shop Owner)</option>
                    <option value="karigar">Karigar</option>
                    <option value="customer">Customer</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 25px;">
                <button type="submit" class="btn-glass btn-neon-gold" style="flex: 1; justify-content: center;">Save Changes</button>
                <button type="button" onclick="closeModal('modal-edit-user')" class="btn-glass" style="flex: 1; justify-content: center;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-change-password" class="modal-overlay">
    <div class="modal-content glass-card" style="max-width: 400px;">
        <h3 style="color: var(--neon-cyan); font-size: 20px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
            🔑 Change User Password
        </h3>
        <form action="dashboard.php" method="POST">
            <input type="hidden" name="action" value="change_user_password">
            <input type="hidden" name="user_id" id="cp_user_id" value="">
            
            <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 15px;">
                Updating password for: <strong id="cp_username" style="color: white;"></strong>
            </p>
            
            <div class="form-group">
                <label class="form-label" for="cp_new_password">New Password *</label>
                <input type="password" name="new_password" id="cp_new_password" class="form-control" required placeholder="Enter new password...">
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 25px;">
                <button type="submit" class="btn-glass btn-neon-cyan" style="flex: 1; justify-content: center;">Update Password</button>
                <button type="button" onclick="closeModal('modal-change-password')" class="btn-glass" style="flex: 1; justify-content: center;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditShopModal(shop) {
    document.getElementById('edit_shop_id').value = shop.id;
    document.getElementById('edit_shop_name').value = shop.name || '';
    document.getElementById('edit_shop_phone').value = shop.phone || '';
    openModal('modal-edit-shop');
}

function openEditUserModal(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_user_username').value = user.username || '';
    document.getElementById('edit_user_phone').value = user.phone || '';
    document.getElementById('edit_user_role').value = user.role || 'customer';
    openModal('modal-edit-user');
}

function openChangePasswordModal(user) {
    document.getElementById('cp_user_id').value = user.id;
    document.getElementById('cp_username').innerText = user.username;
    document.getElementById('cp_new_password').value = '';
    openModal('modal-change-password');
}
</script>
<?php endif; ?>

<script>
    // Generic client-side pagination
    function paginateTable(tableId, recordsPerPage = 10) {
        var table = document.getElementById(tableId);
        if (!table) return;
        
        var pagContainer = document.getElementById(tableId + '-pagination');
        if (!pagContainer) {
            pagContainer = document.createElement('div');
            pagContainer.id = tableId + '-pagination';
            pagContainer.style.display = 'flex';
            pagContainer.style.justifyContent = 'center';
            pagContainer.style.gap = '8px';
            pagContainer.style.marginTop = '15px';
            pagContainer.style.alignItems = 'center';
            table.parentNode.appendChild(pagContainer);
        }
        
        var currentPage = parseInt(table.getAttribute('data-current-page') || '1');
        var tbody = table.querySelector('tbody');
        var allRows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
        
        // If table has empty/placeholder row, don't paginate
        if (allRows.length === 1 && allRows[0].cells.length <= 1 && allRows[0].textContent.indexOf('No ') !== -1) {
            pagContainer.innerHTML = '';
            return;
        }
        
        // Filter rows that match search query
        var searchVal = '';
        var searchInput = document.getElementById(tableId === 'customers-table' ? 'customers-search' : 'ledger-search');
        if (searchInput) {
            searchVal = searchInput.value.trim().toLowerCase();
        }
        
        var startFilter = document.getElementById('ledger-start') ? document.getElementById('ledger-start').value : '';
        var endFilter = document.getElementById('ledger-end') ? document.getElementById('ledger-end').value : '';
        var startDate = startFilter ? new Date(startFilter) : null;
        var endDate = endFilter ? new Date(endFilter) : null;
        if (startDate) startDate.setHours(0,0,0,0);
        if (endDate) endDate.setHours(23,59,59,999);

        var totalReceived = 0;
        var totalBalance = 0;

        var matchedRows = allRows.filter(function(row) {
            if (searchVal !== '' && row.textContent.toLowerCase().indexOf(searchVal) === -1) {
                return false;
            }
            
            if (tableId === 'ledger-table' && row.cells.length > 4) {
                if (startDate || endDate) {
                    var dateStr = row.cells[4].innerText.trim();
                    var rowDate = new Date(dateStr);
                    rowDate.setHours(12,0,0,0);
                    if (startDate && rowDate < startDate) return false;
                    if (endDate && rowDate > endDate) return false;
                }
                
                var priceStr = row.cells[2].innerText.replace(/[^0-9]/g, '');
                var balStr = row.cells[3].innerText.replace(/[^0-9]/g, '');
                var price = parseInt(priceStr) || 0;
                var bal = parseInt(balStr) || 0;
                totalReceived += (price - bal);
                totalBalance += bal;
            }
            
            return true;
        });
        
        if (tableId === 'ledger-table') {
            var recEl = document.getElementById('filter-received');
            var balEl = document.getElementById('filter-balance');
            if (recEl) recEl.innerText = 'Rs. ' + totalReceived.toLocaleString();
            if (balEl) balEl.innerText = 'Rs. ' + totalBalance.toLocaleString();
        }
        
        var totalRecords = matchedRows.length;
        var totalPages = Math.ceil(totalRecords / recordsPerPage);
        
        if (totalRecords <= recordsPerPage) {
            pagContainer.innerHTML = '';
            // Reset display style for search matchings
            allRows.forEach(function(row) {
                if (searchVal === '') {
                    row.style.display = '';
                } else {
                    row.style.display = (row.textContent.toLowerCase().indexOf(searchVal) !== -1) ? '' : 'none';
                }
            });
            return;
        }
        
        if (currentPage > totalPages) {
            currentPage = totalPages;
        }
        if (currentPage < 1) {
            currentPage = 1;
        }
        table.setAttribute('data-current-page', currentPage);
        
        // Hide all rows
        allRows.forEach(function(row) {
            row.style.display = 'none';
        });
        
        // Show matched rows for the current page
        var start = (currentPage - 1) * recordsPerPage;
        var end = start + recordsPerPage;
        matchedRows.slice(start, end).forEach(function(row) {
            row.style.display = '';
        });
        
        // Render pagination buttons
        var html = '';
        html += '<button type="button" class="btn-glass" style="padding: 4px 10px; font-size: 12px; margin: 0;' + (currentPage === 1 ? ' opacity: 0.5; cursor: not-allowed;' : '') + '" ' + (currentPage === 1 ? 'disabled' : 'onclick="changeTablePage(\'' + tableId + '\', ' + (currentPage - 1) + ')"') + '>&larr; Prev</button>';
        html += '<span style="font-size: 13px; color: var(--text-secondary); min-width: 80px; text-align: center;">Page ' + currentPage + ' of ' + totalPages + '</span>';
        html += '<button type="button" class="btn-glass" style="padding: 4px 10px; font-size: 12px; margin: 0;' + (currentPage === totalPages ? ' opacity: 0.5; cursor: not-allowed;' : '') + '" ' + (currentPage === totalPages ? 'disabled' : 'onclick="changeTablePage(\'' + tableId + '\', ' + (currentPage + 1) + ')"') + '>Next &rarr;</button>';
        
        pagContainer.innerHTML = html;
    }

    function changeTablePage(tableId, newPage) {
        var table = document.getElementById(tableId);
        if (!table) return;
        table.setAttribute('data-current-page', newPage);
        paginateTable(tableId);
    }

    // Live search filter for any table
    function filterTable(tableId, query) {
        var table = document.getElementById(tableId);
        if (!table) return;
        
        // Reset to page 1 on search and re-paginate
        table.setAttribute('data-current-page', '1');
        paginateTable(tableId);
    }

    // Sort table by column
    function sortTable(tableId, colIndex, type, thEl) {
        var table = document.getElementById(tableId);
        if (!table) return;
        var tbody = table.querySelector('tbody');
        var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));

        // Determine sort direction
        var currentDir = thEl.getAttribute('data-sort-dir') || 'none';
        var newDir = (currentDir === 'asc') ? 'desc' : 'asc';

        // Reset all arrows in this table's header
        var allTh = table.querySelectorAll('thead th .sort-arrow');
        for (var a = 0; a < allTh.length; a++) {
            allTh[a].textContent = '⇅';
        }
        // Set active arrow
        var arrow = thEl.querySelector('.sort-arrow');
        if (arrow) {
            arrow.textContent = (newDir === 'asc') ? '↑' : '↓';
        }
        thEl.setAttribute('data-sort-dir', newDir);

        // Reset other th sort directions
        var allHeaders = table.querySelectorAll('thead th');
        for (var h = 0; h < allHeaders.length; h++) {
            if (allHeaders[h] !== thEl) {
                allHeaders[h].setAttribute('data-sort-dir', 'none');
            }
        }

        rows.sort(function(a, b) {
            var cellA = a.cells[colIndex];
            var cellB = b.cells[colIndex];
            if (!cellA || !cellB) return 0;

            var valA = cellA.textContent.trim();
            var valB = cellB.textContent.trim();

            if (type === 'number') {
                // Extract numeric value (strip "Rs.", commas, spaces)
                valA = parseFloat(valA.replace(/[^0-9.\-]/g, '')) || 0;
                valB = parseFloat(valB.replace(/[^0-9.\-]/g, '')) || 0;
            } else if (type === 'date') {
                valA = new Date(valA).getTime() || 0;
                valB = new Date(valB).getTime() || 0;
            } else {
                valA = valA.toLowerCase();
                valB = valB.toLowerCase();
            }

            if (valA < valB) return newDir === 'asc' ? -1 : 1;
            if (valA > valB) return newDir === 'asc' ? 1 : -1;
            return 0;
        });

        // Re-append sorted rows
        for (var r = 0; r < rows.length; r++) {
            tbody.appendChild(rows[r]);
        }
        
        // Re-paginate sorted rows
        paginateTable(tableId);
    }

    document.addEventListener('DOMContentLoaded', function() {
        paginateTable('customers-table');
        paginateTable('ledger-table');
        paginateTable('users-table', 10);
    });
    </script>


<script>
    // Generic client-side pagination
    function paginateTable(tableId, recordsPerPage = 10) {
        var table = document.getElementById(tableId);
        if (!table) return;
        
        var pagContainer = document.getElementById(tableId + '-pagination');
        if (!pagContainer) {
            pagContainer = document.createElement('div');
            pagContainer.id = tableId + '-pagination';
            pagContainer.style.display = 'flex';
            pagContainer.style.justifyContent = 'center';
            pagContainer.style.gap = '8px';
            pagContainer.style.marginTop = '15px';
            pagContainer.style.alignItems = 'center';
            table.parentNode.appendChild(pagContainer);
        }
        
        var currentPage = parseInt(table.getAttribute('data-current-page') || '1');
        var tbody = table.querySelector('tbody');
        var allRows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
        
        // If table has empty/placeholder row, don't paginate
        if (allRows.length === 1 && allRows[0].cells.length <= 1 && allRows[0].textContent.indexOf('No ') !== -1) {
            pagContainer.innerHTML = '';
            return;
        }
        
        // Filter rows that match search query
        var searchVal = '';
        var searchInput = document.getElementById(tableId === 'customers-table' ? 'customers-search' : 'ledger-search');
        if (searchInput) {
            searchVal = searchInput.value.trim().toLowerCase();
        }
        
        var startFilter = document.getElementById('ledger-start') ? document.getElementById('ledger-start').value : '';
        var endFilter = document.getElementById('ledger-end') ? document.getElementById('ledger-end').value : '';
        var startDate = startFilter ? new Date(startFilter) : null;
        var endDate = endFilter ? new Date(endFilter) : null;
        if (startDate) startDate.setHours(0,0,0,0);
        if (endDate) endDate.setHours(23,59,59,999);

        var totalReceived = 0;
        var totalBalance = 0;

        var matchedRows = allRows.filter(function(row) {
            if (searchVal !== '' && row.textContent.toLowerCase().indexOf(searchVal) === -1) {
                return false;
            }
            
            if (tableId === 'ledger-table' && row.cells.length > 4) {
                if (startDate || endDate) {
                    var dateStr = row.cells[4].innerText.trim();
                    var rowDate = new Date(dateStr);
                    rowDate.setHours(12,0,0,0);
                    if (startDate && rowDate < startDate) return false;
                    if (endDate && rowDate > endDate) return false;
                }
                
                var priceStr = row.cells[2].innerText.replace(/[^0-9]/g, '');
                var balStr = row.cells[3].innerText.replace(/[^0-9]/g, '');
                var price = parseInt(priceStr) || 0;
                var bal = parseInt(balStr) || 0;
                totalReceived += (price - bal);
                totalBalance += bal;
            }
            
            return true;
        });
        
        if (tableId === 'ledger-table') {
            var recEl = document.getElementById('filter-received');
            var balEl = document.getElementById('filter-balance');
            if (recEl) recEl.innerText = 'Rs. ' + totalReceived.toLocaleString();
            if (balEl) balEl.innerText = 'Rs. ' + totalBalance.toLocaleString();
        }
        
        var totalRecords = matchedRows.length;
        var totalPages = Math.ceil(totalRecords / recordsPerPage);
        
        if (totalRecords <= recordsPerPage) {
            pagContainer.innerHTML = '';
            // Reset display style for search matchings
            allRows.forEach(function(row) {
                if (searchVal === '') {
                    row.style.display = '';
                } else {
                    row.style.display = (row.textContent.toLowerCase().indexOf(searchVal) !== -1) ? '' : 'none';
                }
            });
            return;
        }
        
        if (currentPage > totalPages) {
            currentPage = totalPages;
        }
        if (currentPage < 1) {
            currentPage = 1;
        }
        table.setAttribute('data-current-page', currentPage);
        
        // Hide all rows
        allRows.forEach(function(row) {
            row.style.display = 'none';
        });
        
        // Show matched rows for the current page
        var start = (currentPage - 1) * recordsPerPage;
        var end = start + recordsPerPage;
        matchedRows.slice(start, end).forEach(function(row) {
            row.style.display = '';
        });
        
        // Render pagination buttons
        var html = '';
        html += '<button type="button" class="btn-glass" style="padding: 4px 10px; font-size: 12px; margin: 0;' + (currentPage === 1 ? ' opacity: 0.5; cursor: not-allowed;' : '') + '" ' + (currentPage === 1 ? 'disabled' : 'onclick="changeTablePage(\'' + tableId + '\', ' + (currentPage - 1) + ')"') + '>&larr; Prev</button>';
        html += '<span style="font-size: 13px; color: var(--text-secondary); min-width: 80px; text-align: center;">Page ' + currentPage + ' of ' + totalPages + '</span>';
        html += '<button type="button" class="btn-glass" style="padding: 4px 10px; font-size: 12px; margin: 0;' + (currentPage === totalPages ? ' opacity: 0.5; cursor: not-allowed;' : '') + '" ' + (currentPage === totalPages ? 'disabled' : 'onclick="changeTablePage(\'' + tableId + '\', ' + (currentPage + 1) + ')"') + '>Next &rarr;</button>';
        
        pagContainer.innerHTML = html;
    }

    function changeTablePage(tableId, newPage) {
        var table = document.getElementById(tableId);
        if (!table) return;
        table.setAttribute('data-current-page', newPage);
        paginateTable(tableId);
    }

    // Live search filter for any table
    function filterTable(tableId, query) {
        var table = document.getElementById(tableId);
        if (!table) return;
        
        // Reset to page 1 on search and re-paginate
        table.setAttribute('data-current-page', '1');
        paginateTable(tableId);
    }

    // Sort table by column
    function sortTable(tableId, colIndex, type, thEl) {
        var table = document.getElementById(tableId);
        if (!table) return;
        var tbody = table.querySelector('tbody');
        var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));

        // Determine sort direction
        var currentDir = thEl.getAttribute('data-sort-dir') || 'none';
        var newDir = (currentDir === 'asc') ? 'desc' : 'asc';

        // Reset all arrows in this table's header
        var allTh = table.querySelectorAll('thead th .sort-arrow');
        for (var a = 0; a < allTh.length; a++) {
            allTh[a].textContent = '⇅';
        }
        // Set active arrow
        var arrow = thEl.querySelector('.sort-arrow');
        if (arrow) {
            arrow.textContent = (newDir === 'asc') ? '↑' : '↓';
        }
        thEl.setAttribute('data-sort-dir', newDir);

        // Reset other th sort directions
        var allHeaders = table.querySelectorAll('thead th');
        for (var h = 0; h < allHeaders.length; h++) {
            if (allHeaders[h] !== thEl) {
                allHeaders[h].setAttribute('data-sort-dir', 'none');
            }
        }

        rows.sort(function(a, b) {
            var cellA = a.cells[colIndex];
            var cellB = b.cells[colIndex];
            if (!cellA || !cellB) return 0;

            var valA = cellA.textContent.trim();
            var valB = cellB.textContent.trim();

            if (type === 'number') {
                // Extract numeric value (strip "Rs.", commas, spaces)
                valA = parseFloat(valA.replace(/[^0-9.\-]/g, '')) || 0;
                valB = parseFloat(valB.replace(/[^0-9.\-]/g, '')) || 0;
            } else if (type === 'date') {
                valA = new Date(valA).getTime() || 0;
                valB = new Date(valB).getTime() || 0;
            } else {
                valA = valA.toLowerCase();
                valB = valB.toLowerCase();
            }

            if (valA < valB) return newDir === 'asc' ? -1 : 1;
            if (valA > valB) return newDir === 'asc' ? 1 : -1;
            return 0;
        });

        // Re-append sorted rows
        for (var r = 0; r < rows.length; r++) {
            tbody.appendChild(rows[r]);
        }
        
        // Re-paginate sorted rows
        paginateTable(tableId);
    }

    document.addEventListener('DOMContentLoaded', function() {
        paginateTable('customers-table');
        paginateTable('ledger-table');
        paginateTable('users-table', 10);
    });
    </script>


<?php
require_once 'includes/footer.php';
?>
