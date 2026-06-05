/* public/js/app.js */

// Global state for QR bridge polling
let bridgePollInterval = null;

// ==================== MODAL HELPERS ====================
function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.style.display = 'none';
    }
    // Clear bridge session interval if closing order modal
    if (id === 'modal-order') {
        stopBridgePolling();
    }
}

// Open Sizing Vault Modal & populate values
function openVaultModal(customer) {
    document.getElementById('vault_customer_id').value = customer.id;
    document.getElementById('vault-title').innerText = `📐 Edit Measurements: ${customer.name}`;
    
    // Parse measurements JSON
    let measurements = {};
    try {
        measurements = typeof customer.measurements === 'string' 
            ? JSON.parse(customer.measurements) 
            : customer.measurements;
    } catch(e) {
        console.error("Failed to parse measurements JSON", e);
    }
    
    const upper = measurements.upper || {};
    const lower = measurements.lower || {};
    
    // Populate upper body inputs
    document.getElementById('m_up_length').value = upper.length || 0;
    document.getElementById('m_up_shoulder').value = upper.shoulder || 0;
    document.getElementById('m_up_chest').value = upper.chest || 0;
    document.getElementById('m_up_armhole').value = upper.armhole || 0;
    document.getElementById('m_up_sleeve').value = upper.sleeve || 0;
    document.getElementById('m_up_neck').value = upper.neck || 0;
    document.getElementById('m_up_hem_width').value = upper.hem_width || 0;
    document.getElementById('m_up_darts').value = upper.darts || 'No';
    document.getElementById('m_up_cut').value = upper.cut || 'Straight';
    document.getElementById('m_up_flare').value = upper.flare || 0;
    document.getElementById('m_up_upper_chest').value = upper.upper_chest || 0;
    document.getElementById('m_up_lower_chest').value = upper.lower_chest || 0;
    
    // Populate lower body inputs
    document.getElementById('m_lo_length').value = lower.length || 0;
    document.getElementById('m_lo_waist').value = lower.waist || 0;
    document.getElementById('m_lo_hips').value = lower.hips || 0;
    document.getElementById('m_lo_rise').value = lower.rise || 0;
    document.getElementById('m_lo_bottom_opening').value = lower.bottom_opening || 0;
    document.getElementById('m_lo_inseam').value = lower.inseam || 0;
    
    openModal('modal-vault');
}

// ==================== TOAST NOTIFICATION ====================
function showToast(message, duration = 4000) {
    const toast = document.getElementById('toast-notification');
    const msgSpan = document.getElementById('toast-message');
    if (toast && msgSpan) {
        msgSpan.innerText = message;
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, duration);
    }
}

// ==================== KANBAN DRAG & DROP ====================
let draggedOrderId = null;

function handleDragStart(event, orderId) {
    draggedOrderId = orderId;
    event.dataTransfer.setData('text/plain', orderId);
    
    // Add visual indicator to column dropzones
    const cols = document.querySelectorAll('.kanban-column');
    cols.forEach(col => col.classList.add('active-drag'));
}

function allowDrop(event) {
    event.preventDefault();
}

function handleDrop(event, columnStatus) {
    event.preventDefault();
    
    // Remove visual indicators
    const cols = document.querySelectorAll('.kanban-column');
    cols.forEach(col => col.classList.remove('active-drag'));
    
    const orderId = event.dataTransfer.getData('text/plain') || draggedOrderId;
    if (orderId) {
        updateOrderStatus(orderId, columnStatus);
    }
    draggedOrderId = null;
}

// AJAX Status Update
function updateOrderStatus(orderId, status) {
    fetch('api/update_order_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            order_id: orderId,
            status: status
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(`Order status updated to: ${status.toUpperCase()}`);
            // Reload page to re-calculate stats, balances, and move card cleanly
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(`⚠️ Error: ${data.error || 'Failed to update'}`);
        }
    })
    .catch(err => {
        console.error("Status update error", err);
        showToast("⚠️ Network error updating status");
    });
}

// ==================== LIGHTBOX SYSTEM ====================
function viewFabricLightbox(imageSrc) {
    const lightbox = document.getElementById('fabric-lightbox');
    const img = document.getElementById('lightbox-img');
    if (lightbox && img) {
        img.src = imageSrc;
        lightbox.style.display = 'flex';
    }
}

function closeFabricLightbox() {
    const lightbox = document.getElementById('fabric-lightbox');
    if (lightbox) {
        lightbox.style.display = 'none';
    }
}

// ==================== QR FABRIC UPLOAD BRIDGE ====================
function startUploadBridgeSession() {
    const tagId = document.getElementById('order_tag').value;
    const btnInit = document.getElementById('btn-init-bridge');
    const statusText = document.getElementById('bridge-status-text');
    
    btnInit.disabled = true;
    statusText.innerText = "Initiating temporary upload session...";
    
    fetch(`api/upload_bridge.php?action=create&tag_id=${tagId}`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const sessionId = data.session_id;
            document.getElementById('bridge_session_id').value = sessionId;
            statusText.innerHTML = `<span style="color: var(--neon-cyan);">Session Active (Expires in 10m)</span>`;
            
            // Build mobile URL dynamically to work on localhost or production
            const baseDir = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
            const mobileUrl = `${window.location.origin}${baseDir}upload_portal.php?session_id=${sessionId}`;
            
            // Generate QR Code using QRious
            const qrContainer = document.getElementById('bridge-qr-container');
            qrContainer.style.display = 'block';
            
            // Clear any old canvas elements inside
            const oldCanvas = document.getElementById('bridge-qr-canvas');
            
            new QRious({
                element: oldCanvas,
                value: mobileUrl,
                size: 200,
                background: '#ffffff',
                foreground: '#07050f',
                level: 'H'
            });
            
            // Start polling backend for status change
            startBridgePolling(sessionId);
        } else {
            btnInit.disabled = false;
            statusText.innerText = "Error: " + data.error;
        }
    })
    .catch(err => {
        btnInit.disabled = false;
        statusText.innerText = "Failed to establish bridge connection.";
        console.error(err);
    });
}

function startBridgePolling(sessionId) {
    stopBridgePolling();
    
    bridgePollInterval = setInterval(() => {
        fetch(`api/upload_bridge.php?action=poll&session_id=${sessionId}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'uploaded') {
                stopBridgePolling();
                showToast("✓ Fabric image uploaded & linked to order!");
                
                // Show preview inside form
                document.getElementById('bridge-qr-container').style.display = 'block';
                const previewBox = document.getElementById('uploaded-fabric-preview-box');
                const previewImg = document.getElementById('uploaded-fabric-preview-img');
                
                if (previewBox && previewImg) {
                    previewImg.src = data.image_path;
                    previewBox.style.display = 'block';
                }
                
                document.getElementById('bridge-status-text').innerHTML = `<span style="color: var(--neon-emerald);">✓ Image Received Successfully!</span>`;
            } else if (data.status === 'expired') {
                stopBridgePolling();
                document.getElementById('bridge-status-text').innerHTML = `<span style="color: rgba(239, 68, 68, 0.8);">⚠️ Session expired. Please retry.</span>`;
                document.getElementById('btn-init-bridge').disabled = false;
                document.getElementById('bridge-qr-container').style.display = 'none';
            }
        })
        .catch(err => {
            console.error("Bridge polling error", err);
        });
    }, 3000); // Poll every 3 seconds
}

function stopBridgePolling() {
    if (bridgePollInterval) {
        clearInterval(bridgePollInterval);
        bridgePollInterval = null;
    }
}

// Double check decimal / numeric float inputs for measurements
document.addEventListener('DOMContentLoaded', () => {
    const numberInputs = document.querySelectorAll('input[type="number"]');
    numberInputs.forEach(input => {
        input.addEventListener('blur', (e) => {
            let val = parseFloat(e.target.value);
            if (isNaN(val) || val < 0) {
                e.target.value = 0.0;
            } else {
                e.target.value = val.toFixed(1);
            }
        });
    });
});

// ==================== THEME SWITCHER ====================
function toggleTheme() {
    const isLight = document.body.classList.toggle('light-theme');
    localStorage.setItem('theme', isLight ? 'light' : 'dark');
    showToast(isLight ? "Light theme activated" : "Dark theme activated");
}
