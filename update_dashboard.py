import sys

with open('dashboard.php', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Restore JSON htmlspecialchars
content = content.replace("onclick='openVaultModal(<?php echo json_encode($cust); ?>)'", "onclick='openVaultModal(<?php echo htmlspecialchars(json_encode($cust), ENT_QUOTES, \"UTF-8\"); ?>)'")
content = content.replace("onclick='openEditShopModal(<?php echo json_encode($shop); ?>)'", "onclick='openEditShopModal(<?php echo htmlspecialchars(json_encode($shop), ENT_QUOTES, \"UTF-8\"); ?>)'")
content = content.replace("onclick='openEditUserModal(<?php echo json_encode($u); ?>)'", "onclick='openEditUserModal(<?php echo htmlspecialchars(json_encode($u), ENT_QUOTES, \"UTF-8\"); ?>)'")
content = content.replace("onclick='openChangePasswordModal(<?php echo json_encode($u); ?>)'", "onclick='openChangePasswordModal(<?php echo htmlspecialchars(json_encode($u), ENT_QUOTES, \"UTF-8\"); ?>)'")

# 2. Re-insert Backend Handlers
backend_target = '''        header("Location: dashboard.php");
        exit();
    }
}

// Handle Super Admin POST actions'''

backend_replacement = '''        header("Location: dashboard.php");
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

// Handle Super Admin POST actions'''

content = content.replace(backend_target, backend_replacement)

# 3. UI: Completed Orders
section1 = content.split('<!-- Customers & Sizing Vault Scopes -->')
completed_orders = section1[0]

completed_orders = completed_orders.replace('''        <h3 style="margin-bottom: 10px; font-size: 18px; color: var(--text-muted); display: flex; align-items: center; gap: 8px;">
            ✅ Completed Orders (Dispatched)
        </h3>
        <div style="overflow-x: auto; margin-top: 15px;">''', '''        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <h3 style="margin: 0; font-size: 18px; color: var(--text-muted); display: flex; align-items: center; gap: 8px;">
                ✅ Completed Orders (Dispatched)
            </h3>
            <button type="button" onclick="confirmBulkDelete('form-bulk-completed', 'Are you sure you want to permanently delete the selected completed orders?')" class="btn-glass" style="padding: 6px 12px; font-size: 12px; border-color: red; color: red;">🗑️ Bulk Delete</button>
        </div>
        <form id="form-bulk-completed" action="dashboard.php" method="POST">
            <input type="hidden" name="action" value="bulk_delete_orders">
            <div style="overflow-x: auto; margin-top: 15px;">''')

completed_orders = completed_orders.replace('''                        <th style="padding: 10px 5px;"><?php echo __('tag_id'); ?></th>''', '''                        <th style="padding: 10px 5px; width: 40px;"><input type="checkbox" class="select-all-cb" onchange="toggleSelectAll(this, 'order_ids[]')"></th>
                        <th style="padding: 10px 5px;"><?php echo __('tag_id'); ?></th>''')

completed_orders = completed_orders.replace('''                            <td style="padding: 12px 5px; font-weight: bold; color: #6b7280;"><?php echo htmlspecialchars($ord['tag_id']); ?></td>''', '''                            <td style="padding: 12px 5px;"><input type="checkbox" name="order_ids[]" value="<?php echo $ord['id']; ?>"></td>
                            <td style="padding: 12px 5px; font-weight: bold; color: #6b7280;"><?php echo htmlspecialchars($ord['tag_id']); ?></td>''')

completed_orders = completed_orders.replace('''                            <td style="padding: 12px 5px; text-align: right;">
                                <a href="print_receipt.php?id=<?php echo urlencode($ord['tag_id']); ?>" target="_blank" class="btn-glass" style="padding: 4px 8px; font-size: 11px; text-decoration: none;" title="Print Receipt">🖨️</a>
                            </td>''', '''                            <td style="padding: 12px 5px; text-align: right; white-space: nowrap;">
                                <a href="print_receipt.php?id=<?php echo urlencode($ord['tag_id']); ?>" target="_blank" class="btn-glass" style="padding: 4px 8px; font-size: 11px; text-decoration: none;" title="Print Receipt">🖨️</a>
                                <button type="button" class="btn-glass" onclick="if(confirm('Delete this completed order?')) { const f = document.createElement('form'); f.method = 'POST'; f.action = 'dashboard.php'; const a = document.createElement('input'); a.type='hidden'; a.name='action'; a.value='delete_order'; const i = document.createElement('input'); i.type='hidden'; i.name='delete_order_id'; i.value='<?php echo $ord['id']; ?>'; f.appendChild(a); f.appendChild(i); document.body.appendChild(f); f.submit(); }" style="padding: 4px 8px; font-size: 11px; border-color: red; color: red; margin-left: 5px;" title="Delete Order">🗑️</button>
                            </td>''')

completed_orders = completed_orders.replace('''                        <tr><td colspan="4" style="padding: 20px; text-align: center; color: var(--text-muted);">No completed orders yet.</td></tr>''', '''                        <tr><td colspan="5" style="padding: 20px; text-align: center; color: var(--text-muted);">No completed orders yet.</td></tr>''')

completed_orders = completed_orders.replace('''                </tbody>
            </table>
        </div>
    </div>''', '''                </tbody>
            </table>
        </div>
        </form>
    </div>''')

content = completed_orders + '<!-- Customers & Sizing Vault Scopes -->' + section1[1]

# 4. UI: Customers Table
section2 = content.split('<!-- Financial Ledger -->')
customers = section2[0]

customers = customers.replace('''            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0; font-size: 18px; color: var(--neon-orchid);">👥 <?php echo __('customers'); ?></h3>
                <input type="text" id="customers-search" placeholder="Search customers..." oninput="filterTable('customers-table', this.value)" style="width: 180px; font-size: 12px; padding: 6px 10px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: var(--text-primary); outline: none;">
            </div>
            <div style="overflow-x: auto;">''', '''            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
                <h3 style="margin: 0; font-size: 18px; color: var(--neon-orchid);">👥 <?php echo __('customers'); ?></h3>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button type="button" onclick="confirmBulkDelete('form-bulk-customers', 'Are you sure you want to permanently delete the selected customers AND all their orders?')" class="btn-glass" style="padding: 6px 12px; font-size: 12px; border-color: red; color: red;">🗑️ Bulk Delete</button>
                    <input type="text" id="customers-search" placeholder="Search customers..." oninput="filterTable('customers-table', this.value)" style="width: 180px; font-size: 12px; padding: 6px 10px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: var(--text-primary); outline: none;">
                </div>
            </div>
            <form id="form-bulk-customers" action="dashboard.php" method="POST">
                <input type="hidden" name="action" value="bulk_delete_customers">
            <div style="overflow-x: auto;">''')

customers = customers.replace('''                            <th style="padding: 10px 5px; cursor: pointer; user-select: none;" onclick="sortTable('customers-table', 0, 'text', this)">
                                <?php echo __('customer_name'); ?> <span class="sort-arrow">⇅</span>
                            </th>''', '''                            <th style="padding: 10px 5px; width: 40px;"><input type="checkbox" class="select-all-cb" onchange="toggleSelectAll(this, 'customer_ids[]')"></th>
                            <th style="padding: 10px 5px; cursor: pointer; user-select: none;" onclick="sortTable('customers-table', 1, 'text', this)">
                                <?php echo __('customer_name'); ?> <span class="sort-arrow">⇅</span>
                            </th>''')

customers = customers.replace('''                                    <td style="padding: 12px 5px; font-weight: 500;"><?php echo htmlspecialchars($cust['name']); ?></td>''', '''                                    <td style="padding: 12px 5px;"><input type="checkbox" name="customer_ids[]" value="<?php echo $cust['id']; ?>"></td>
                                    <td style="padding: 12px 5px; font-weight: 500;"><?php echo htmlspecialchars($cust['name']); ?></td>''')

customers = customers.replace('''                                        <button onclick='openVaultModal(<?php echo htmlspecialchars(json_encode($cust), ENT_QUOTES, "UTF-8"); ?>)' class="btn-glass" style="padding: 4px 8px; font-size: 12px; border-color: var(--neon-cyan); color: var(--neon-cyan);">
                                            📏 <?php echo __('measurements'); ?>
                                        </button>
                                    </td>''', '''                                        <button type="button" onclick='openVaultModal(<?php echo htmlspecialchars(json_encode($cust), ENT_QUOTES, "UTF-8"); ?>)' class="btn-glass" style="padding: 4px 8px; font-size: 12px; border-color: var(--neon-cyan); color: var(--neon-cyan);">
                                            📏 <?php echo __('measurements'); ?>
                                        </button>
                                        <button type="button" class="btn-glass" onclick="if(confirm('Delete this customer and ALL associated orders?')) { const f = document.createElement('form'); f.method = 'POST'; f.action = 'dashboard.php'; const a = document.createElement('input'); a.type='hidden'; a.name='action'; a.value='delete_customer'; const i = document.createElement('input'); i.type='hidden'; i.name='delete_customer_id'; i.value='<?php echo $cust['id']; ?>'; f.appendChild(a); f.appendChild(i); document.body.appendChild(f); f.submit(); }" style="padding: 4px 8px; font-size: 12px; border-color: red; color: red; margin-left: 5px;" title="Delete Customer">🗑️</button>
                                    </td>''')

customers = customers.replace('''                            <tr><td colspan="4" style="padding: 20px; text-align: center; color: var(--text-muted);">No customers registered yet.</td></tr>''', '''                            <tr><td colspan="5" style="padding: 20px; text-align: center; color: var(--text-muted);">No customers registered yet.</td></tr>''')

customers = customers.replace('''                </table>
            </div>
        </div>''', '''                </table>
            </div>
            </form>
        </div>''')

content = customers + '<!-- Financial Ledger -->' + section2[1]

# 5. UI: Ledger Table
section3 = content.split('<!-- ==================== KARIGAR DASHBOARD ==================== -->')
ledger = section3[0]

ledger = ledger.replace('''            <div style="overflow-x: auto;">
                <table id="ledger-table" style="width: 100%; border-collapse: collapse; text-align: left;">''', '''            <div style="display: flex; justify-content: flex-end; margin-bottom: 10px;">
                <button type="button" onclick="confirmBulkDelete('form-bulk-ledger', 'Are you sure you want to permanently delete the selected orders?')" class="btn-glass" style="padding: 6px 12px; font-size: 12px; border-color: red; color: red;">🗑️ Bulk Delete</button>
            </div>
            <form id="form-bulk-ledger" action="dashboard.php" method="POST">
                <input type="hidden" name="action" value="bulk_delete_orders">
            <div style="overflow-x: auto;">
                <table id="ledger-table" style="width: 100%; border-collapse: collapse; text-align: left;">''')

ledger = ledger.replace('''                            <th style="padding: 10px 5px; cursor: pointer; user-select: none;" onclick="sortTable('ledger-table', 0, 'text', this)">
                                <?php echo __('tag_id'); ?> <span class="sort-arrow">⇅</span>
                            </th>''', '''                            <th style="padding: 10px 5px; width: 40px;"><input type="checkbox" class="select-all-cb" onchange="toggleSelectAll(this, 'order_ids[]')"></th>
                            <th style="padding: 10px 5px; cursor: pointer; user-select: none;" onclick="sortTable('ledger-table', 1, 'text', this)">
                                <?php echo __('tag_id'); ?> <span class="sort-arrow">⇅</span>
                            </th>''')

ledger = ledger.replace('''                                    <td style="padding: 12px 5px; font-weight: bold; color: var(--neon-cyan);"><?php echo htmlspecialchars($ord['tag_id']); ?></td>''', '''                                    <td style="padding: 12px 5px;"><input type="checkbox" name="order_ids[]" value="<?php echo $ord['id']; ?>"></td>
                                    <td style="padding: 12px 5px; font-weight: bold; color: var(--neon-cyan);"><?php echo htmlspecialchars($ord['tag_id']); ?></td>''')

ledger = ledger.replace('''                                    <td style="padding: 12px 5px; text-align: right;">
                                        <a href="print_receipt.php?id=<?php echo urlencode($ord['tag_id']); ?>" target="_blank" class="btn-glass" style="padding: 4px 8px; font-size: 11px; text-decoration: none;" title="Print Receipt">🖨️</a>
                                    </td>''', '''                                    <td style="padding: 12px 5px; text-align: right; white-space: nowrap;">
                                        <a href="print_receipt.php?id=<?php echo urlencode($ord['tag_id']); ?>" target="_blank" class="btn-glass" style="padding: 4px 8px; font-size: 11px; text-decoration: none;" title="Print Receipt">🖨️</a>
                                        <button type="button" class="btn-glass" onclick="if(confirm('Delete this order?')) { const f = document.createElement('form'); f.method = 'POST'; f.action = 'dashboard.php'; const a = document.createElement('input'); a.type='hidden'; a.name='action'; a.value='delete_order'; const i = document.createElement('input'); i.type='hidden'; i.name='delete_order_id'; i.value='<?php echo $ord['id']; ?>'; f.appendChild(a); f.appendChild(i); document.body.appendChild(f); f.submit(); }" style="padding: 4px 8px; font-size: 11px; border-color: red; color: red; margin-left: 5px;" title="Delete Order">🗑️</button>
                                    </td>''')

ledger = ledger.replace('''                            <tr><td colspan="6" style="padding: 20px; text-align: center; color: var(--text-muted);">No financial entries.</td></tr>''', '''                            <tr><td colspan="7" style="padding: 20px; text-align: center; color: var(--text-muted);">No financial entries.</td></tr>''')

ledger = ledger.replace('''                </table>
            </div>
        </div>''', '''                </table>
            </div>
            </form>
        </div>''')

content = ledger + '<!-- ==================== KARIGAR DASHBOARD ==================== -->' + section3[1]

with open('dashboard.php', 'w', encoding='utf-8') as f:
    f.write(content)
print("Updated dashboard.php successfully")
