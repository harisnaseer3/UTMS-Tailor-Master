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
    document.getElementById('vault-title').innerText = `📐 Edit Measurements / پیمائش تبدیل کریں: ${customer.name}`;
    
    let measurements = {};
    try {
        if (customer.measurements) {
            measurements = typeof customer.measurements === 'string' 
                ? JSON.parse(customer.measurements) 
                : customer.measurements;
        }
    } catch(e) {
        console.error("Failed to parse measurements JSON", e);
    }
    
    // Ensure measurements is an object, not null
    measurements = measurements || {};
    
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
    document.getElementById('m_up_fitting').value = upper.fitting || 0;
    document.getElementById('m_up_chowk').value = upper.chowk || 0;
    
    // Populate notes
    const notesEl = document.getElementById('m_measurement_notes');
    if (notesEl) {
        notesEl.value = measurements.notes || '';
    }
    
    // Populate lower body inputs
    document.getElementById('m_lo_length').value = lower.length || 0;
    document.getElementById('m_lo_waist').value = lower.waist || 0;
    document.getElementById('m_lo_hips').value = lower.hips || 0;
    document.getElementById('m_lo_rise').value = lower.rise || 0;
    document.getElementById('m_lo_bottom_opening').value = lower.bottom_opening || 0;
    document.getElementById('m_lo_inseam').value = lower.inseam || 0;
    
    // Toggle women's specific fields based on gender
    const womensFields = document.getElementById('m_womens-specific-fields');
    if (womensFields) {
        womensFields.style.display = (customer.gender === 'female') ? '' : 'none';
    }
    
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

// AJAX Karigar Assignment
function assignOrderToKarigar(orderId, karigarId) {
    fetch('api/update_order_karigar.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            order_id: orderId,
            karigar_id: karigarId
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast("Order assigned to Karigar successfully!");
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(`⚠️ Error: ${data.error || 'Failed to assign'}`);
        }
    })
    .catch(err => {
        console.error("Assignment error", err);
        showToast("⚠️ Network error assigning Karigar");
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

// ==================== KANBAN COLUMN LIVE SEARCH ====================
function filterColumnCards(statusKey, query) {
    const normalized = query.trim().toLowerCase();
    const column = document.getElementById('col-' + statusKey);
    if (!column) return;

    const cardsContainer = column.querySelector('.column-cards-container');
    if (!cardsContainer) return;

    const cards = cardsContainer.querySelectorAll('.kanban-card');
    let visibleCount = 0;

    cards.forEach(function(card) {
        const tag = card.querySelector('.card-tag');
        const customer = card.querySelector('.card-cust');
        const notesEl = card.querySelector('div[style*="font-style"]');

        const tagText = tag ? tag.textContent.toLowerCase() : '';
        const custText = customer ? customer.textContent.toLowerCase() : '';
        const notesText = notesEl ? notesEl.textContent.toLowerCase() : '';

        const combined = tagText + ' ' + custText + ' ' + notesText;

        if (normalized === '' || combined.indexOf(normalized) !== -1) {
            card.style.display = '';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    // Toggle empty placeholder
    const placeholder = cardsContainer.querySelector('.kanban-empty-placeholder');
    if (placeholder) {
        placeholder.style.display = (visibleCount === 0) ? '' : 'none';
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

    // Customer select change event for order modal
    const custSelect = document.getElementById('cust_select');
    if (custSelect) {
        // Initialize Tom Select for searchable dropdown
        if (typeof TomSelect !== 'undefined') {
            new TomSelect(custSelect, {
                create: false,
                sortField: {
                    field: "text",
                    direction: "asc"
                }
            });
        }
        
        custSelect.addEventListener('change', (e) => {
            const customerId = e.target.value;
            const measContainer = document.getElementById('order-measurements-container');
            
            if (!customerId) {
                measContainer.style.display = 'none';
                return;
            }
            
            // Show the container
            measContainer.style.display = 'block';
            
            fetch(`api/get_customer.php?id=${customerId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const upper = data.measurements.upper || {};
                    const lower = data.measurements.lower || {};
                    
                    document.getElementById('o_up_length').value = upper.length || 0;
                    document.getElementById('o_up_shoulder').value = upper.shoulder || 0;
                    document.getElementById('o_up_chest').value = upper.chest || 0;
                    document.getElementById('o_up_armhole').value = upper.armhole || 0;
                    document.getElementById('o_up_sleeve').value = upper.sleeve || 0;
                    document.getElementById('o_up_neck').value = upper.neck || 0;
                    document.getElementById('o_up_hem_width').value = upper.hem_width || 0;
                    document.getElementById('o_up_darts').value = upper.darts || 'No';
                    document.getElementById('o_up_cut').value = upper.cut || 'Straight';
                    document.getElementById('o_up_flare').value = upper.flare || 0;
                    document.getElementById('o_up_upper_chest').value = upper.upper_chest || 0;
                    document.getElementById('o_up_lower_chest').value = upper.lower_chest || 0;
                    
                    document.getElementById('o_lo_length').value = lower.length || 0;
                    document.getElementById('o_lo_waist').value = lower.waist || 0;
                    document.getElementById('o_lo_hips').value = lower.hips || 0;
                    document.getElementById('o_lo_rise').value = lower.rise || 0;
                    document.getElementById('o_lo_bottom_opening').value = lower.bottom_opening || 0;
                    document.getElementById('o_lo_inseam').value = lower.inseam || 0;
                    
                    const womensFields = document.getElementById('o_womens-specific-fields');
                    if (womensFields) {
                        womensFields.style.display = (data.gender === 'female') ? '' : 'none';
                    }
                } else {
                    showToast('⚠️ Could not load customer measurements.');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('⚠️ Network error fetching measurements.');
            });
        });
    }
});

// ==================== THEME SWITCHER ====================
function toggleTheme() {
    const isLight = document.body.classList.toggle('light-theme');
    localStorage.setItem('theme', isLight ? 'light' : 'dark');
    showToast(isLight ? "Light theme activated" : "Dark theme activated");
}

// ==================== PASSWORD TOGGLER ====================
function togglePasswordVisibility(id, btn) {
    const input = document.getElementById(id);
    if (!input) return;
    
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    
    if (isPassword) {
        // Show Slashed Eye
        btn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 20px; height: 20px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.822 7.822L21 21m-2.228-2.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
            </svg>
        `;
    } else {
        // Show Standard Eye
        btn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 20px; height: 20px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        `;
    }
}
