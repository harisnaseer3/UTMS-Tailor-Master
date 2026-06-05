<?php
// views/kanban_board.php

$statuses = [
    'received' => [
        'title' => __('status_received'),
        'color' => 'received',
        'next' => 'cutting',
        'prev' => null
    ],
    'cutting' => [
        'title' => __('status_cutting'),
        'color' => 'cutting',
        'next' => 'stitching',
        'prev' => 'received'
    ],
    'stitching' => [
        'title' => __('status_stitching'),
        'color' => 'stitching',
        'next' => 'ready',
        'prev' => 'cutting'
    ],
    'ready' => [
        'title' => __('status_ready'),
        'color' => 'ready',
        'next' => null,
        'prev' => 'stitching'
    ]
];
?>

<div class="kanban-container">
    <?php foreach ($statuses as $statusKey => $statusMeta): ?>
        <div class="kanban-column" id="col-<?php echo $statusKey; ?>" ondragover="allowDrop(event)" ondrop="handleDrop(event, '<?php echo $statusKey; ?>')">
            <div class="column-header">
                <span class="column-title <?php echo $statusMeta['color']; ?>">
                    <span style="display:inline-block; width: 8px; height: 8px; border-radius: 50%; background: currentColor; box-shadow: 0 0 8px currentColor;"></span>
                    <?php echo $statusMeta['title']; ?>
                </span>
                <span class="column-count"><?php echo count($kanbanOrders[$statusKey]); ?></span>
            </div>
            
            <div class="column-cards-container" style="min-height: 400px;">
                <?php if (empty($kanbanOrders[$statusKey])): ?>
                    <div class="kanban-empty-placeholder" style="text-align: center; color: var(--text-muted); font-size: 12px; padding: 40px 0; border: 1px dashed rgba(255,255,255,0.02); border-radius: 8px;">
                        Drop here
                    </div>
                <?php else: ?>
                    <?php foreach ($kanbanOrders[$statusKey] as $order): ?>
                        <div class="kanban-card" draggable="true" ondragstart="handleDragStart(event, <?php echo $order['id']; ?>)" id="order-card-<?php echo $order['id']; ?>">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                                <span class="card-tag"><?php echo htmlspecialchars($order['tag_id']); ?></span>
                                
                                <?php if (!empty($order['fabric_image'])): ?>
                                    <!-- Clicking thumbnail opens full preview -->
                                    <img src="public/uploads/fabric/<?php echo htmlspecialchars($order['fabric_image']); ?>" 
                                         class="fabric-preview-thumb" 
                                         onclick="viewFabricLightbox('public/uploads/fabric/<?php echo htmlspecialchars($order['fabric_image']); ?>')" 
                                         title="Click to view fabric"
                                         style="cursor: pointer;">
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-cust">
                                <?php echo htmlspecialchars($order['customer_name']); ?>
                            </div>
                            
                            <?php if (!empty($order['notes'])): ?>
                                <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 10px; font-style: italic; background: rgba(0,0,0,0.2); padding: 6px; border-radius: 4px; border-left: 2px solid var(--border-color);">
                                    "<?php echo htmlspecialchars($order['notes']); ?>"
                                </div>
                            <?php endif; ?>
                            
                            <!-- Financial overview hidden for Karigar -->
                            <?php if ($role === 'master'): ?>
                                <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 10px;">
                                    Price: Rs. <?php echo number_format($order['price'], 2); ?> &bull; Bal: 
                                    <span style="color: <?php echo ($order['price'] - $order['advance_paid'] > 0) ? 'var(--neon-gold)' : 'var(--neon-emerald)'; ?>">
                                        Rs. <?php echo number_format($order['price'] - $order['advance_paid'], 2); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Quick action buttons to transition columns on mobile -->
                            <div class="card-actions" style="justify-content: flex-end; border-top: 1px solid rgba(255,255,255,0.03); padding-top: 8px;">
                                <?php if ($statusMeta['prev']): ?>
                                    <button onclick="updateOrderStatus(<?php echo $order['id']; ?>, '<?php echo $statusMeta['prev']; ?>')" class="btn-glass" style="padding: 2px 6px; font-size: 10px;" title="Move Back">
                                        &larr;
                                    </button>
                                <?php endif; ?>
                                
                                <a href="scan.php?id=<?php echo htmlspecialchars($order['tag_id']); ?>" class="btn-glass" style="padding: 2px 6px; font-size: 10px; border-color: var(--neon-cyan); color: var(--neon-cyan);">
                                    👁️ <?php echo __('quick_view'); ?>
                                </a>

                                <?php if ($statusMeta['next']): ?>
                                    <button onclick="updateOrderStatus(<?php echo $order['id']; ?>, '<?php echo $statusMeta['next']; ?>')" class="btn-glass btn-neon-cyan" style="padding: 2px 8px; font-size: 10px;" title="Move Forward">
                                        &rarr;
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Lightbox Modal for Fabric Images -->
<div id="fabric-lightbox" class="modal-overlay" onclick="closeFabricLightbox()">
    <div style="position: relative; max-width: 90%; max-height: 90vh;">
        <img id="lightbox-img" src="" style="width: auto; height: auto; max-width: 100%; max-height: 85vh; border-radius: 12px; border: 1px solid var(--neon-cyan); box-shadow: 0 0 30px var(--neon-cyan-glow);">
        <p style="text-align: center; color: var(--text-secondary); margin-top: 10px; font-size: 14px;">Click anywhere to close</p>
    </div>
</div>
