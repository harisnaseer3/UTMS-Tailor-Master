<?php
// upload_portal.php
require_once 'config/db.php';
require_once 'includes/dictionary.php';

$pdo = getDBConnection();
$sessionId = $_GET['session_id'] ?? '';
$error = '';
$sessionRecord = null;

if (empty($sessionId)) {
    $error = 'Invalid Session ID.';
} else {
    try {
        // Find session
        $stmt = $pdo->prepare("SELECT * FROM upload_sessions WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $sessionRecord = $stmt->fetch();
        
        if (!$sessionRecord) {
            $error = 'Upload session not found or expired.';
        } else {
            // Check timeout (10 mins)
            $createdAt = strtotime($sessionRecord['created_at']);
            if (time() - $createdAt > 600) {
                // Update status to expired
                $pdo->prepare("UPDATE upload_sessions SET status = 'expired' WHERE session_id = ?")->execute([$sessionId]);
                $error = 'This upload session has expired.';
            } elseif ($sessionRecord['status'] === 'uploaded') {
                $error = 'Fabric image already uploaded for this session.';
            }
        }
    } catch (PDOException $e) {
        $error = 'Database connection error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>" dir="<?php echo getHTMLDirection(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UTMS Mobile Fabric Upload Bridge</title>
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
        }
        .mobile-card {
            width: 100%;
            max-width: 450px;
            padding: 30px 20px;
            text-align: center;
        }
        .upload-zone {
            border: 2px dashed var(--neon-cyan);
            background: rgba(0, 240, 255, 0.03);
            border-radius: 12px;
            padding: 40px 20px;
            cursor: pointer;
            margin: 25px 0;
            transition: var(--transition-smooth);
        }
        .upload-zone:hover {
            background: rgba(0, 240, 255, 0.07);
            box-shadow: 0 0 15px var(--neon-cyan-glow);
        }
    </style>
</head>
<body>

<div class="glass-card mobile-card">
    <h2 style="font-size: 20px; margin-bottom: 8px; color: var(--text-primary);">📷 Fabric Upload Portal</h2>
    <span style="font-size: 12px; color: var(--text-muted);">Session ID: <?php echo htmlspecialchars(substr($sessionId, 0, 10)); ?>...</span>
    
    <?php if (!empty($error)): ?>
        <div style="margin: 20px 0; padding: 15px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgb(239, 68, 68); color: #fca5a5; border-radius: 8px; font-size: 14px;">
            ⚠️ <?php echo htmlspecialchars($error); ?>
        </div>
        <a href="index.php" class="btn-glass" style="width: 100%; justify-content: center;">Go to Portal</a>
    <?php else: ?>
        <div style="font-size: 14px; color: var(--text-secondary); margin-top: 15px; text-align: left;">
            Tag ID: <strong style="color: var(--neon-cyan);"><?php echo htmlspecialchars($sessionRecord['tag_id']); ?></strong>
        </div>

        <div id="upload-interactive-area">
            <!-- Invisible file picker -->
            <input type="file" accept="image/*" capture="environment" id="fabric-picker" style="display: none;">
            
            <div class="upload-zone" onclick="document.getElementById('fabric-picker').click()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 50px; height: 50px; color: var(--neon-cyan); margin-bottom: 15px;">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                </svg>
                <div style="font-weight: 600; color: #ffffff;">Snap or Choose Fabric</div>
                <div style="font-size: 11px; color: var(--text-muted); margin-top: 8px;">Auto-compressed below 200KB before upload</div>
            </div>

            <!-- Upload progress / resizing preview -->
            <div id="upload-status" style="display: none; margin-bottom: 20px;">
                <div id="loader-bar" style="height: 4px; width: 100%; background: rgba(255,255,255,0.05); border-radius: 2px; overflow: hidden; margin-bottom: 10px;">
                    <div id="loader-progress" style="height: 100%; width: 0%; background: var(--neon-cyan); box-shadow: 0 0 8px var(--neon-cyan-glow); transition: width 0.3s;"></div>
                </div>
                <span id="loader-text" style="font-size: 13px; color: var(--text-secondary);">Compressing fabric photo...</span>
            </div>

            <div id="upload-preview-box" style="display: none; margin-bottom: 20px;">
                <img id="upload-preview-img" src="" style="width: 100%; max-height: 200px; object-fit: contain; border-radius: 8px; border: 1px solid var(--border-color); background: rgba(0,0,0,0.5);">
            </div>
            
            <button id="btn-upload" class="btn-glass btn-neon-cyan" style="width: 100%; justify-content: center; display: none;">
                Confirm Upload
            </button>
        </div>

        <div id="upload-success-area" style="display: none; text-align: center; padding: 20px 0;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 60px; height: 60px; color: var(--neon-emerald); margin: 0 auto 15px auto;">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 style="color: var(--neon-emerald); margin-bottom: 10px;">Upload Successful!</h3>
            <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 20px;">
                You can now close this tab on your phone. The fabric photo is synchronized with the desktop system.
            </p>
        </div>
    <?php endif; ?>
</div>

<script>
    const filePicker = document.getElementById('fabric-picker');
    const uploadZone = document.querySelector('.upload-zone');
    const statusBox = document.getElementById('upload-status');
    const progressFill = document.getElementById('loader-progress');
    const statusText = document.getElementById('loader-text');
    const previewBox = document.getElementById('upload-preview-box');
    const previewImg = document.getElementById('upload-preview-img');
    const btnUpload = document.getElementById('btn-upload');
    const interactiveArea = document.getElementById('upload-interactive-area');
    const successArea = document.getElementById('upload-success-area');
    
    let compressedBase64 = null;
    
    if (filePicker) {
        filePicker.addEventListener('change', handleFilePick);
    }
    
    function handleFilePick(event) {
        const file = event.target.files[0];
        if (!file) return;
        
        // Show status loader
        statusBox.style.display = 'block';
        progressFill.style.width = '20%';
        statusText.innerText = 'Reading image...';
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                // Compress image using canvas
                progressFill.style.width = '50%';
                statusText.innerText = 'Compressing to sub-200KB...';
                
                const canvas = document.createElement('canvas');
                let width = img.width;
                let height = img.height;
                
                // Max size limit 800px
                const maxSize = 800;
                if (width > height) {
                    if (width > maxSize) {
                        height = Math.round((height * maxSize) / width);
                        width = maxSize;
                    }
                } else {
                    if (height > maxSize) {
                        width = Math.round((width * maxSize) / height);
                        height = maxSize;
                    }
                }
                
                canvas.width = width;
                canvas.height = height;
                
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                
                // Compress to quality 0.7 JPEG (usually results in 40KB - 120KB)
                compressedBase64 = canvas.toDataURL('image/jpeg', 0.7);
                
                progressFill.style.width = '100%';
                statusText.innerText = 'Ready to upload';
                
                // Show preview
                previewImg.src = compressedBase64;
                previewBox.style.display = 'block';
                btnUpload.style.display = 'inline-flex';
                
                // Calculate size in KB
                const approxSizeKB = Math.round((compressedBase64.length * 3/4) / 1024);
                statusText.innerHTML = `Compressed size: <strong style="color: var(--neon-cyan);">${approxSizeKB} KB</strong>`;
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
    
    if (btnUpload) {
        btnUpload.addEventListener('click', () => {
            if (!compressedBase64) return;
            
            btnUpload.disabled = true;
            statusText.innerText = 'Uploading to workshop system...';
            progressFill.style.width = '70%';
            
            fetch('api/upload_bridge.php?action=upload', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    session_id: '<?php echo $sessionId; ?>',
                    image_data: compressedBase64
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    progressFill.style.width = '100%';
                    interactiveArea.style.display = 'none';
                    successArea.style.display = 'block';
                } else {
                    btnUpload.disabled = false;
                    statusText.innerText = 'Upload failed: ' + data.error;
                    progressFill.style.width = '0%';
                }
            })
            .catch(err => {
                btnUpload.disabled = false;
                statusText.innerText = 'Upload failed. Connection error.';
                progressFill.style.width = '0%';
                console.error(err);
            });
        });
    }
</script>

</body>
</html>
