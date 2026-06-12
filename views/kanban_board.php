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

<style>
.column-cards-container.scrollable-column {
    max-height: 750px !important;
    overflow-y: auto !important;
    padding-right: 6px;
}

/* Custom scrollbar for cards container */
.column-cards-container.scrollable-column::-webkit-scrollbar {
    width: 6px;
}
.column-cards-container.scrollable-column::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.02);
    border-radius: 3px;
}
.column-cards-container.scrollable-column::-webkit-scrollbar-thumb {
    background: rgba(147, 51, 234, 0.3);
    border-radius: 3px;
}
.column-cards-container.scrollable-column::-webkit-scrollbar-thumb:hover {
    background: var(--neon-cyan);
}
</style>


    <div class="kanban-container">
    <?php foreach ($statuses as $statusKey => $statusMeta): ?>
        <div class="kanban-column" id="col-<?php echo $statusKey; ?>" ondragover="allowDrop(event)" ondrop="handleDrop(event, '<?php echo $statusKey; ?>')">
            <div class="column-header">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span class="column-title"><?php echo $statusMeta['title']; ?></span>
                    <span style="display:inline-block; width: 8px; height: 8px; border-radius: 50%; background: currentColor; box-shadow: 0 0 8px currentColor;"></span>
                </div>
                <input type="text" class="kanban-col-search" placeholder="Search by name..." oninput="filterColumnCards('<?php echo $statusKey; ?>', this.value)" style="width: 100%; margin-top: 8px; font-size: 12px; padding: 6px 10px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: var(--text-primary); outline: none;">
            </div>
            
            <?php 
            $orders = $kanbanOrders[$statusKey] ?? [];
            $hasScroll = count($orders) > 3;
            ?>
            <div class="column-cards-container<?php echo $hasScroll ? ' scrollable-column' : ''; ?>" style="min-height: 400px;">
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

<script>
// Kanban column live search — inline to avoid caching issues
function filterColumnCards(statusKey, query) {
    var normalized = query.trim().toLowerCase();
    var column = document.getElementById('col-' + statusKey);
    if (!column) return;

    var cardsContainer = column.querySelector('.column-cards-container');
    if (!cardsContainer) return;

    var cards = cardsContainer.querySelectorAll('.kanban-card');
    var visibleCount = 0;

    for (var i = 0; i < cards.length; i++) {
        var card = cards[i];
        var tagEl = card.querySelector('.card-tag');
        var custEl = card.querySelector('.card-cust');
        var notesEl = card.querySelector('div[style*="font-style"]');

        var tagText = tagEl ? tagEl.textContent.trim().toLowerCase() : '';
        var custText = custEl ? custEl.textContent.trim().toLowerCase() : '';
        var notesText = notesEl ? notesEl.textContent.trim().toLowerCase() : '';

        var combined = tagText + ' ' + custText + ' ' + notesText;

        if (normalized === '' || combined.indexOf(normalized) !== -1) {
            card.style.display = '';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    }

    // Toggle empty placeholder
    var placeholder = cardsContainer.querySelector('.kanban-empty-placeholder');
    if (placeholder) {
        placeholder.style.display = (visibleCount === 0) ? '' : 'none';
    }
}
</script>
