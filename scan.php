<?php
// scan.php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/dictionary.php';

$pdo = getDBConnection();
$role = $_SESSION['role'] ?? '';
$shopId = getCurrentShopId();
$tagId = $_GET['id'] ?? '';
$error = '';
$order = null;

if (empty($tagId)) {
    $error = 'Tag ID is missing.';
} else {
    try {
        // Query order by Tag ID (Note: this is a global check or tenant check? 
        // For scan-to-action to work globally, we look up by the unique tag_id first,
        // and extract the shop details. Then we can display it. If the user is logged in,
        // they can toggle status if they belong to that shop)
        $stmt = $pdo->prepare("
            SELECT o.*, c.name as customer_name, c.phone as customer_phone, s.name as shop_name 
            FROM orders o
            JOIN customers c ON o.customer_id = c.id
            JOIN shops s ON o.shop_id = s.id
            WHERE o.tag_id = ?
        ");
        $stmt->execute([$tagId]);
        $order = $stmt->fetch();
        
        if (!$order) {
            $error = 'Order not found for the scanned Tag ID.';
        }
    } catch (PDOException $e) {
        $error = 'Database connection error: ' . $e->getMessage();
    }
}

require_once 'includes/header.php';
?>

<div style="max-width: 500px; margin: 20px auto;">
    <?php if (!empty($error)): ?>
        <div class="glass-card" style="text-align: center; padding: 30px;">
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgb(239, 68, 68); color: #fca5a5; padding: 15px; border-radius: 8px; font-size: 14px; margin-bottom: 20px;">
                ⚠️ <?php echo htmlspecialchars($error); ?>
            </div>
            <a href="index.php" class="btn-glass">Go to Portal</a>
        </div>
    <?php else: ?>
        <!-- Quick View Card -->
        <div class="glass-card">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 20px;">
                <div>
                    <span class="card-tag" style="font-size: 14px; padding: 4px 10px;"><?php echo htmlspecialchars($order['tag_id']); ?></span>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;"><?php echo htmlspecialchars($order['shop_name']); ?></div>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <a href="print_receipt.php?id=<?php echo urlencode($order['tag_id']); ?>" target="_blank" class="btn-glass" style="font-size: 12px; padding: 4px 10px; border-color: var(--neon-cyan); color: var(--neon-cyan); text-decoration: none;" title="Print Receipt">🖨️ Print</a>
                    
                    <!-- Active Status Badge -->
                    <?php if ($order['status'] === 'received'): ?>
                    <span style="color: var(--neon-gold); font-size: 13px; border: 1px solid var(--neon-gold); padding: 3px 10px; border-radius: 12px; background: rgba(255,184,0,0.1); font-weight: bold;"><?php echo __('status_received'); ?></span>
                <?php elseif ($order['status'] === 'cutting'): ?>
                    <span style="color: var(--neon-orchid); font-size: 13px; border: 1px solid var(--neon-orchid); padding: 3px 10px; border-radius: 12px; background: rgba(184,41,242,0.1); font-weight: bold;"><?php echo __('status_cutting'); ?></span>
                <?php elseif ($order['status'] === 'stitching'): ?>
                    <span style="color: var(--neon-cyan); font-size: 13px; border: 1px solid var(--neon-cyan); padding: 3px 10px; border-radius: 12px; background: rgba(0,240,255,0.1); font-weight: bold;"><?php echo __('status_stitching'); ?></span>
                <?php elseif ($order['status'] === 'ready'): ?>
                    <span style="color: var(--neon-emerald); font-size: 13px; border: 1px solid var(--neon-emerald); padding: 3px 10px; border-radius: 12px; background: rgba(13,242,138,0.1); font-weight: bold;"><?php echo __('status_ready'); ?></span>
                <?php elseif ($order['status'] === 'dispatched'): ?>
                    <span style="color: #6b7280; font-size: 13px; border: 1px solid #6b7280; padding: 3px 10px; border-radius: 12px; background: rgba(107,114,128,0.1); font-weight: bold;"><?php echo __('status_dispatched'); ?></span>
                <?php endif; ?>
                </div>
            </div>

            <!-- Customer Details (Masked for Karigars/Guests) -->
            <div style="margin-bottom: 20px;">
                <h4 style="color: var(--text-secondary); font-size: 13px; text-transform: uppercase; margin-bottom: 6px;">Customer Details</h4>
                <div style="font-size: 16px; font-weight: 600;"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                <?php if (isLoggedIn() && canViewCustomerContact()): ?>
                    <div style="font-size: 14px; color: var(--text-secondary); margin-top: 4px;">📞 <?php echo htmlspecialchars($order['customer_phone']); ?></div>
                <?php else: ?>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px; font-style: italic;">📞 Phone masked for production security</div>
                <?php endif; ?>
            </div>

            <!-- Fabric Image -->
            <div style="margin-bottom: 25px;">
                <h4 style="color: var(--text-secondary); font-size: 13px; text-transform: uppercase; margin-bottom: 6px;">Fabric Reference</h4>
                <?php if (!empty($order['fabric_image'])): ?>
                    <img src="public/uploads/fabric/<?php echo htmlspecialchars($order['fabric_image']); ?>" class="fabric-preview-large" style="max-height: 200px;">
                <?php else: ?>
                    <div style="background: rgba(0,0,0,0.3); border: 1px dashed var(--border-color); border-radius: 8px; padding: 25px; text-align: center; color: var(--text-muted); font-size: 13px;">
                        No fabric photo uploaded for this order.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Critical Sizing Measurements (Top 3) -->
            <div style="margin-bottom: 25px;">
                <h4 style="color: var(--neon-orchid); font-size: 13px; text-transform: uppercase; border-bottom: 1px solid rgba(184, 41, 242, 0.15); padding-bottom: 5px; margin-bottom: 12px;">
                    📏 Critical Measurements (Primary)
                </h4>
                
                <?php 
                    $measurements = json_decode($order['measurements_snapshot'], true);
                    $upper = $measurements['upper'] ?? [];
                    $lower = $measurements['lower'] ?? [];
                ?>
                
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; text-align: center;">
                    <div class="glass-card" style="padding: 10px; background: rgba(255,255,255,0.02); border-color: rgba(255,255,255,0.05);">
                        <span style="font-size: 11px; color: var(--text-secondary); display: block; margin-bottom: 4px;">Upper Length</span>
                        <strong style="font-size: 18px; color: var(--neon-cyan); font-family: var(--font-english);"><?php echo floatval($upper['length'] ?? 0); ?>"</strong>
                    </div>
                    <div class="glass-card" style="padding: 10px; background: rgba(255,255,255,0.02); border-color: rgba(255,255,255,0.05);">
                        <span style="font-size: 11px; color: var(--text-secondary); display: block; margin-bottom: 4px;">Shoulder</span>
                        <strong style="font-size: 18px; color: var(--neon-cyan); font-family: var(--font-english);"><?php echo floatval($upper['shoulder'] ?? 0); ?>"</strong>
                    </div>
                    <div class="glass-card" style="padding: 10px; background: rgba(255,255,255,0.02); border-color: rgba(255,255,255,0.05);">
                        <span style="font-size: 11px; color: var(--text-secondary); display: block; margin-bottom: 4px;">Chest/Bust</span>
                        <strong style="font-size: 18px; color: var(--neon-cyan); font-family: var(--font-english);"><?php echo floatval($upper['chest'] ?? 0); ?>"</strong>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; text-align: center; margin-top: 12px;">
                    <div class="glass-card" style="padding: 10px; background: rgba(255,255,255,0.02); border-color: rgba(255,255,255,0.05);">
                        <span style="font-size: 11px; color: var(--text-secondary); display: block; margin-bottom: 4px;">Lower Length</span>
                        <strong style="font-size: 18px; color: var(--neon-orchid); font-family: var(--font-english);"><?php echo floatval($lower['length'] ?? 0); ?>"</strong>
                    </div>
                    <div class="glass-card" style="padding: 10px; background: rgba(255,255,255,0.02); border-color: rgba(255,255,255,0.05);">
                        <span style="font-size: 11px; color: var(--text-secondary); display: block; margin-bottom: 4px;">Waist</span>
                        <strong style="font-size: 18px; color: var(--neon-orchid); font-family: var(--font-english);"><?php echo floatval($lower['waist'] ?? 0); ?>"</strong>
                    </div>
                    <div class="glass-card" style="padding: 10px; background: rgba(255,255,255,0.02); border-color: rgba(255,255,255,0.05);">
                        <span style="font-size: 11px; color: var(--text-secondary); display: block; margin-bottom: 4px;">Bottom Opening</span>
                        <strong style="font-size: 18px; color: var(--neon-orchid); font-family: var(--font-english);"><?php echo floatval($lower['bottom_opening'] ?? 0); ?>"</strong>
                    </div>
                </div>
            </div>

            <!-- Notes / Instructions -->
            <?php if (!empty($order['notes'])): ?>
                <div style="margin-bottom: 25px; background: rgba(255, 184, 0, 0.03); border: 1px solid rgba(255, 184, 0, 0.15); border-left: 3px solid var(--neon-gold); padding: 12px; border-radius: 6px;">
                    <h5 style="color: var(--neon-gold); font-size: 12px; text-transform: uppercase; margin-bottom: 4px;"><?php echo __('notes'); ?></h5>
                    <p style="font-size: 14px; font-style: italic; color: #fff;">"<?php echo htmlspecialchars($order['notes']); ?>"</p>
                </div>
            <?php endif; ?>

            <!-- Action: Update Status (RBAC validation: must be logged in as workshop staff and order must belong to their shop) -->
            <?php if (isLoggedIn() && in_array($role, ['master', 'karigar']) && $order['shop_id'] == $shopId): ?>
                <div style="margin-top: 30px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                    <h4 style="color: var(--neon-cyan); font-size: 13px; text-transform: uppercase; margin-bottom: 12px;">⚡ Update Order Status</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <button onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'received')" class="btn-glass <?php echo $order['status'] === 'received' ? 'btn-neon-emerald' : ''; ?>" style="font-size: 12px; justify-content: center;">
                            <?php echo __('status_received'); ?>
                        </button>
                        <button onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'cutting')" class="btn-glass <?php echo $order['status'] === 'cutting' ? 'btn-neon-emerald' : ''; ?>" style="font-size: 12px; justify-content: center;">
                            <?php echo __('status_cutting'); ?>
                        </button>
                        <button onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'stitching')" class="btn-glass <?php echo $order['status'] === 'stitching' ? 'btn-neon-emerald' : ''; ?>" style="font-size: 12px; justify-content: center;">
                            <?php echo __('status_stitching'); ?>
                        </button>
                        <button onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'ready')" class="btn-glass <?php echo $order['status'] === 'ready' ? 'btn-neon-emerald' : ''; ?>" style="font-size: 12px; justify-content: center;">
                            <?php echo __('status_ready'); ?>
                        </button>
                        <?php if ($order['status'] === 'ready' || $order['status'] === 'dispatched'): ?>
                        <button onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'dispatched')" class="btn-glass <?php echo $order['status'] === 'dispatched' ? 'btn-neon-emerald' : ''; ?>" style="font-size: 12px; justify-content: center; grid-column: span 2; border-color: var(--neon-gold); color: var(--neon-gold);">
                            <?php echo __('status_dispatched'); ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div style="margin-top: 20px; border-top: 1px dashed rgba(255,255,255,0.05); padding-top: 15px; text-align: center; font-size: 12px; color: var(--text-muted);">
                    🔒 Log in as workshop administrator or karigar to update production status.
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
require_once 'includes/footer.php';
?>
