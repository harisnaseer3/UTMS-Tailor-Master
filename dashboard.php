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
                    
                    $stmt = $pdo->prepare("INSERT INTO customers (shop_id, name, phone, measurements) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$shopId, $name, $phone, json_encode($emptyTree)]);
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
                    // Get customer's current measurements to snap into order
                    $stmtCust = $pdo->prepare("SELECT measurements FROM customers WHERE id = ? AND shop_id = ?");
                    $stmtCust->execute([$customerId, $shopId]);
                    $cust = $stmtCust->fetch();
                    $measurements = $cust ? $cust['measurements'] : json_encode([]);
                    
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
                    $stmt->execute([$shopId, $customerId, $tagId, $measurements, $fabricImage, $notes, $price, $advance, $assignedTo]);
                    
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
                COUNT(id) as total_count,
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
}

// Auto-generate Tag ID for new orders (e.g. YEAR-XXXX)
$newTagId = date('Y') . '-' . str_pad(rand(100, 9999), 4, '0', STR_PAD_LEFT);

require_once 'includes/header.php';
?>



<?php if ($role === 'master'): ?>
    <!-- ==================== TAILOR MASTER DASHBOARD ==================== -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h2 style="font-size: 28px; font-weight: 700;"><?php echo __('dashboard'); ?></h2>
            <p style="color: var(--text-secondary);"><?php echo __('tagline'); ?> &bull; Shop ID: <?php echo $shopId; ?></p>
        </div>
        <div style="display: flex; gap: 12px;">
            <button onclick="openModal('modal-staff')" class="btn-glass btn-neon-gold">+ Add Karigar / کاریگر</button>
            <button onclick="openModal('modal-customer')" class="btn-glass btn-neon-orchid">+ <?php echo __('add_customer'); ?></button>
            <button onclick="openModal('modal-order')" class="btn-glass btn-neon-cyan">+ <?php echo __('new_order'); ?></button>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="dashboard-grid">
        <div class="glass-card stat-card">
            <div>
                <span class="form-label" style="margin: 0;"><?php echo __('total_revenue'); ?></span>
                <div class="stat-val" style="color: var(--neon-emerald);">Rs. <?php echo number_format($stats['revenue'], 2); ?></div>
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
                <div class="stat-val" style="color: var(--neon-gold);">Rs. <?php echo number_format($stats['balance'], 2); ?></div>
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

    <!-- Customers & Sizing Vault Scopes -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 30px;">
        <!-- Customers Directory -->
        <div class="glass-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0; font-size: 18px; color: var(--neon-orchid);">👥 <?php echo __('customers'); ?></h3>
                <input type="text" id="customers-search" placeholder="Search customers..." oninput="filterTable('customers-table', this.value)" style="width: 180px; font-size: 12px; padding: 6px 10px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: var(--text-primary); outline: none;">
            </div>
            <div style="overflow-x: auto;">
                <table id="customers-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-secondary); font-size: 13px;">
                            <th style="padding: 10px 5px; cursor: pointer; user-select: none;" onclick="sortTable('customers-table', 0, 'text', this)">
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
                            <tr><td colspan="4" style="padding: 20px; text-align: center; color: var(--text-muted);">No customers registered yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($customersList as $cust): ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.03); font-size: 14px;">
                                    <td style="padding: 12px 5px; font-weight: 500;"><?php echo htmlspecialchars($cust['name']); ?></td>
                                    <td style="padding: 12px 5px; color: var(--text-secondary);"><?php echo htmlspecialchars($cust['phone']); ?></td>
                                    <td style="padding: 12px 5px; color: var(--text-secondary);"><?php echo date('Y-m-d', strtotime($cust['created_at'])); ?></td>
                                    <td style="padding: 12px 5px; text-align: right;">
                                        <button onclick='openVaultModal(<?php echo json_encode($cust); ?>)' class="btn-glass" style="padding: 4px 8px; font-size: 12px; border-color: var(--neon-cyan); color: var(--neon-cyan);">
                                            📏 <?php echo __('measurements'); ?>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Financial Ledger -->
        <div class="glass-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0; font-size: 18px; color: var(--neon-gold);">💰 <?php echo __('ledger'); ?></h3>
                <input type="text" id="ledger-search" placeholder="Search ledger..." oninput="filterTable('ledger-table', this.value)" style="width: 180px; font-size: 12px; padding: 6px 10px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: var(--text-primary); outline: none;">
            </div>
            <div style="overflow-x: auto;">
                <table id="ledger-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-secondary); font-size: 13px;">
                            <th style="padding: 10px 5px; cursor: pointer; user-select: none;" onclick="sortTable('ledger-table', 0, 'text', this)">
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
                            <tr><td colspan="6" style="padding: 20px; text-align: center; color: var(--text-muted);">No financial entries.</td></tr>
                        <?php else: ?>
                            <?php foreach ($ordersList as $ord): ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.03); font-size: 14px;">
                                    <td style="padding: 12px 5px; font-weight: bold; color: var(--neon-cyan);"><?php echo htmlspecialchars($ord['tag_id']); ?></td>
                                    <td style="padding: 12px 5px; color: var(--text-secondary);"><?php echo htmlspecialchars($ord['customer_name']); ?></td>
                                    <td style="padding: 12px 5px;">Rs. <?php echo number_format($ord['price'], 2); ?></td>
                                    <td style="padding: 12px 5px; font-weight: 500; color: <?php echo ($ord['price'] - $ord['advance_paid'] > 0) ? 'var(--neon-gold)' : 'var(--neon-emerald)'; ?>">
                                        Rs. <?php echo number_format($ord['price'] - $ord['advance_paid'], 2); ?>
                                    </td>
                                    <td style="padding: 12px 5px; color: var(--text-secondary);"><?php echo date('Y-m-d', strtotime($ord['created_at'])); ?></td>
                                    <td style="padding: 12px 5px; text-align: right;">
                                        <a href="print_receipt.php?id=<?php echo urlencode($ord['tag_id']); ?>" target="_blank" class="btn-glass" style="padding: 4px 8px; font-size: 11px; text-decoration: none;" title="Print Receipt">🖨️</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

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
        
        var matchedRows = allRows.filter(function(row) {
            if (searchVal === '') return true;
            return row.textContent.toLowerCase().indexOf(searchVal) !== -1;
        });
        
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
    });
    </script>

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
    <div style="margin-bottom: 30px;">
        <h2 style="font-size: 28px; font-weight: 700; color: var(--neon-gold);">👤 <?php echo __('my_vault'); ?></h2>
        <p style="color: var(--text-secondary);">Manage your personalized sizing profile and track tailoring order statuses.</p>
    </div>

    <?php if ($customerProfile): ?>
        <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 24px; margin-bottom: 30px;">
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
            👥 <?php echo __('add_new_customer'); ?>
        </h3>
        <form action="dashboard.php" method="POST">
            <input type="hidden" name="action" value="add_customer">
            
            <div class="form-group">
                <label class="form-label" for="cust_name"><?php echo __('customer_name'); ?> *</label>
                <input type="text" name="name" id="cust_name" class="form-control" required placeholder="Ali Khan">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="cust_phone"><?php echo __('phone'); ?> *</label>
                <input type="text" name="phone" id="cust_phone" class="form-control" required placeholder="03001234567">
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 25px;">
                <button type="submit" class="btn-glass btn-neon-orchid" style="flex: 1; justify-content: center;"><?php echo __('save'); ?></button>
                <button type="button" onclick="closeModal('modal-customer')" class="btn-glass" style="flex: 1; justify-content: center;"><?php echo __('cancel'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- 2. Create Order Modal with QR Fabric upload bridge -->
<div id="modal-order" class="modal-overlay">
    <div class="modal-content glass-card" style="max-width: 550px;">
        <h3 style="color: var(--neon-cyan); font-size: 20px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
            📦 <?php echo __('new_order'); ?>
        </h3>
        <form action="dashboard.php" method="POST">
            <input type="hidden" name="action" value="create_order">
            <input type="hidden" name="bridge_session_id" id="bridge_session_id" value="">
            
            <div class="form-group">
                <label class="form-label" for="order_tag"><?php echo __('tag_id'); ?> *</label>
                <input type="text" name="tag_id" id="order_tag" class="form-control" required readonly value="<?php echo $newTagId; ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="cust_select"><?php echo __('select_customer'); ?> *</label>
                <select name="customer_id" id="cust_select" class="form-control" required>
                    <option value=""><?php echo __('select_customer'); ?>...</option>
                    <?php foreach ($customersList as $cust): ?>
                        <option value="<?php echo $cust['id']; ?>"><?php echo htmlspecialchars($cust['name']); ?> (<?php echo htmlspecialchars($cust['phone']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="assigned_karigar">Assign Karigar / کاریگر متعین کریں</label>
                <select name="assigned_to" id="assigned_karigar" class="form-control">
                    <option value="">Select Karigar (Optional)...</option>
                    <?php foreach ($karigarsList as $k): ?>
                        <option value="<?php echo $k['id']; ?>"><?php echo htmlspecialchars($k['username']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- ZERO-COST FABRIC IMAGE UPLOAD BRIDGE -->
            <div class="glass-card" style="background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,0.05); padding: 15px; margin-bottom: 20px;">
                <h4 style="font-size: 14px; color: var(--text-secondary); margin-bottom: 8px;">📷 Fabric Image Upload Bridge</h4>
                <div style="display: flex; gap: 15px; align-items: center;">
                    <button type="button" onclick="startUploadBridgeSession()" id="btn-init-bridge" class="btn-glass" style="font-size: 13px; border-color: var(--neon-cyan); color: var(--neon-cyan);">
                        Generate Upload QR Link
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
                    <label class="form-label" for="order_price"><?php echo __('price'); ?></label>
                    <input type="number" step="0.01" name="price" id="order_price" class="form-control" placeholder="0.00" value="0.00">
                </div>
                <div class="form-group">
                    <label class="form-label" for="order_advance"><?php echo __('advance'); ?></label>
                    <input type="number" step="0.01" name="advance" id="order_advance" class="form-control" placeholder="0.00" value="0.00">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="order_notes"><?php echo __('notes'); ?></label>
                <textarea name="notes" id="order_notes" rows="2" class="form-control" placeholder="e.g. Double stitching, short collar..."></textarea>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 25px;">
                <button type="submit" class="btn-glass btn-neon-cyan" style="flex: 1; justify-content: center;"><?php echo __('new_order'); ?></button>
                <button type="button" onclick="closeModal('modal-order')" class="btn-glass" style="flex: 1; justify-content: center;"><?php echo __('cancel'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- 3. Edit Measurements Modal for Master -->
<div id="modal-vault" class="modal-overlay">
    <div class="modal-content glass-card" style="max-width: 600px;">
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

<?php
require_once 'includes/footer.php';
?>
