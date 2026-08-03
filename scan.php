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
            SELECT o.*, c.name as customer_name, c.phone as customer_phone, c.gender, s.name as shop_name 
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

<div style="max-width: 750px; margin: 20px auto;">
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
                
                    <button onclick="window.history.back()" style="background: none; border: none; color: var(--text-muted); font-size: 20px; cursor: pointer; padding: 0 0 0 10px; transition: all 0.2s ease;" onmouseover="this.style.color='#ef4444'; this.style.transform='translateY(-3px)'; this.style.fontWeight='900';" onmouseout="this.style.color='var(--text-muted)'; this.style.transform='translateY(0)'; this.style.fontWeight='normal';" title="Go Back">✕</button>
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
                    📏 All Measurements
                </h4>
                
                <?php 
                    require_once 'includes/CuttingFormulaEngine.php';
                    $measurements = json_decode($order['measurements_snapshot'], true);
                    if ($order['status'] === 'cutting') {
                        $measurements = CuttingFormulaEngine::applyFormulas($order['gender'] ?? '', $measurements);
                    }
                    $upper = $measurements['upper'] ?? [];
                    $lower = $measurements['lower'] ?? [];
                ?>
                
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; text-align: center;">
                    <?php 
                    $isGentsCutting = ($order['status'] === 'cutting' && in_array(strtolower(trim($order['gender'] ?? '')), ['male', 'gents', 'man']));

                    // Group paired measurement fields so length/size and style/type appear in one single card
                    $fields = [
                        [
                            'label' => 'Kameez Length', 
                            'urdu' => 'قمیض لمبائی / دامن', 
                            'val' => $upper['length'] ?? '', 
                            'sub_val' => $upper['daman_style'] ?? ''
                        ],
                        [
                            'label' => 'Kameez Width', 
                            'urdu' => 'قمیض چوڑائی', 
                            'val' => $upper['kameez_width'] ?? ''
                        ],
                        [
                            'label' => 'Sleeve (Bazu)', 
                            'urdu' => 'بازو / سٹائل', 
                            'val' => $upper['sleeve'] ?? '',
                            'sub_val' => $upper['sleeve_style'] ?? ''
                        ],
                        [
                            'label' => 'Cuff Size', 
                            'urdu' => 'کف سائز', 
                            'val' => $upper['cuff_size'] ?? ''
                        ],
                        [
                            'label' => 'Shoulder (Teera)', 
                            'urdu' => 'تیرا', 
                            'val' => $upper['shoulder'] ?? ''
                        ],
                    ];

                    if ($isGentsCutting) {
                        $fields[] = ['label' => 'Kameez Armhole', 'urdu' => 'قمیض مونڈھا', 'val' => $upper['kameez_armhole'] ?? ''];
                        $fields[] = ['label' => 'Bazu Armhole', 'urdu' => 'بازو مونڈھا', 'val' => $upper['bazu_armhole'] ?? ''];
                    } else {
                        $fields[] = ['label' => 'Chest', 'urdu' => 'چھاتی', 'val' => $upper['chest'] ?? ''];
                    }

                    $fields = array_merge($fields, [
                        ['label' => 'Fitting/Waist', 'urdu' => 'فٹنگ/کمر', 'val' => $upper['fitting'] ?? ''],
                        ['label' => 'Hips', 'urdu' => 'ہپس', 'val' => $lower['hips'] ?? ''],
                        ['label' => 'Flare/Daman', 'urdu' => 'دامن/گھیرا', 'val' => $upper['hem_width'] ?? ''],
                        ['label' => 'Chowk', 'urdu' => 'چاک', 'val' => $upper['chowk'] ?? ''],
                        ['label' => 'Armhole', 'urdu' => 'مونڈھا', 'val' => $upper['armhole'] ?? ''],
                        [
                            'label' => 'Neck (Gala)', 
                            'urdu' => 'گلا سائز اور سٹائل', 
                            'val' => $upper['neck'] ?? '',
                            'sub_val' => $upper['gala_style'] ?? ''
                        ],
                        ['label' => 'Front Patti', 'urdu' => 'سامنے پٹی', 'val' => $upper['patti_length'] ?? ''],
                        ['label' => 'Pockets', 'urdu' => 'جیبیں', 'val' => is_array($upper['pockets'] ?? null) ? implode(', ', $upper['pockets']) : ($upper['pockets'] ?? ''), 'is_string' => true],
                        ['label' => 'Darts', 'urdu' => 'ڈارٹس', 'val' => $upper['darts'] ?? '', 'is_string' => true],
                        ['label' => 'Cut', 'urdu' => 'کٹائی', 'val' => $upper['cut'] ?? '', 'is_string' => true],
                        ['label' => 'Up. Chest', 'urdu' => 'اوپری چھاتی', 'val' => $upper['upper_chest'] ?? ''],
                        ['label' => 'Low. Chest', 'urdu' => 'نچلی چھاتی', 'val' => $upper['lower_chest'] ?? ''],
                        
                        [
                            'label' => 'Shalwar / Lower', 
                            'urdu' => 'شلوار لمبائی / قسم', 
                            'val' => $lower['length'] ?? '',
                            'sub_val' => $lower['length_type'] ?? ''
                        ],
                        ['label' => 'Inseam', 'urdu' => 'اندرونی لمبائی', 'val' => $lower['inseam'] ?? ''],
                        [
                            'label' => 'Bottom (Paincha)', 
                            'urdu' => 'پائنچہ / قسم', 
                            'val' => $lower['bottom_opening'] ?? '',
                            'sub_val' => $lower['bottom_opening_type'] ?? ''
                        ],
                        ['label' => 'Rise', 'urdu' => 'آسن', 'val' => $lower['rise'] ?? '']
                    ]);

                    foreach ($fields as $field) {
                        $val = $field['val'];
                        $subVal = trim((string)($field['sub_val'] ?? ''));
                        $isString = $field['is_string'] ?? false;
                        
                        $hasNum = floatval($val) > 0;
                        $hasText = $isString && !empty(trim((string)$val)) && trim((string)$val) !== 'No';

                        if ($hasNum || $hasText || !empty($subVal)) {
                            echo '<div class="glass-card" style="padding: 12px 10px; background: rgba(255,255,255,0.02); border-color: rgba(255,255,255,0.05); display: flex; flex-direction: column; justify-content: center;">';
                            echo '<span style="font-size: 12px; font-weight: bold; color: var(--text-secondary); display: block; margin-bottom: 2px;">' . $field['label'] . '</span>';
                            echo '<span style="font-size: 11px; font-weight: bold; color: var(--text-muted); display: block; margin-bottom: 6px; font-family: var(--font-urdu);">' . $field['urdu'] . '</span>';
                            
                            if ($hasNum) {
                                echo '<strong style="font-size: 20px; font-weight: 900; color: var(--neon-cyan); font-family: var(--font-english);">' . htmlspecialchars((string)$val) . '"</strong>';
                            } else if ($hasText) {
                                echo '<strong style="font-size: 15px; font-weight: 800; color: var(--neon-cyan); font-family: var(--font-english);">' . htmlspecialchars((string)$val) . '</strong>';
                            }

                            if (!empty($subVal)) {
                                echo '<span style="display: inline-block; margin-top: 4px; font-size: 12px; font-weight: 700; color: var(--neon-pink); background: rgba(255, 0, 127, 0.1); padding: 2px 8px; border-radius: 10px; border: 1px solid rgba(255, 0, 127, 0.2);">' . htmlspecialchars($subVal) . '</span>';
                            }
                            
                            echo '</div>';
                        }
                    }
                    ?>
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
