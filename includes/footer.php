<?php
// includes/footer.php
?>
</main>

<footer style="margin-top: 50px; border-top: 1px solid var(--border-color); padding: 20px 0; background: rgba(5, 3, 10, 0.9);">
    <div class="app-container" style="display: flex; justify-content: space-between; align-items: center; color: var(--text-muted); font-size: 13px;">
        <div>
            &copy; <?php echo date('Y'); ?> <?php echo __('title'); ?>. All Rights Reserved.
        </div>
        <div>
            Designed with <span style="color: var(--neon-orchid);">&hearts;</span> (AetherThread UI)
        </div>
    </div>
</footer>

<!-- Toast notification container -->
<div id="toast-notification" class="aether-toast">
    <span id="toast-message"></span>
</div>

<script src="public/js/app.js"></script>

<?php
$toastMessage = '';
$toastType = 'success'; // 'success' or 'error'

// Check GET redirect messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'welcome') {
        $toastMessage = "Welcome to your UTMS workspace dashboard!";
    } elseif ($_GET['msg'] === 'logged_out') {
        $toastMessage = "You have been signed out successfully.";
    } elseif ($_GET['msg'] === 'vault_updated') {
        $toastMessage = "Sizing vault measurements updated successfully.";
    } else {
        $toastMessage = htmlspecialchars($_GET['msg']);
    }
} elseif (isset($_GET['error'])) {
    $toastType = 'error';
    if ($_GET['error'] === 'unauthorized') {
        $toastMessage = "Unauthorized access attempt.";
    } elseif ($_GET['error'] === 'missing_id') {
        $toastMessage = "Customer ID is missing.";
    } else {
        $toastMessage = htmlspecialchars($_GET['error']);
    }
}

// Check page scope or session-stored redirect messages
if (isset($_SESSION['success_msg'])) {
    $toastMessage = $_SESSION['success_msg'];
    $toastType = 'success';
    unset($_SESSION['success_msg']);
} elseif (isset($_SESSION['error_msg'])) {
    $toastMessage = $_SESSION['error_msg'];
    $toastType = 'error';
    unset($_SESSION['error_msg']);
} elseif (!empty($successMsg)) {
    $toastMessage = $successMsg;
    $toastType = 'success';
} elseif (!empty($errorMsg)) {
    $toastMessage = $errorMsg;
    $toastType = 'error';
}

if (!empty($toastMessage)):
?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const toast = document.getElementById('toast-notification');
        if (toast) {
            if ('<?php echo $toastType; ?>' === 'error') {
                toast.style.borderLeft = '4px solid #ef4444'; // Red border
                toast.style.boxShadow = '0 0 20px rgba(239, 68, 68, 0.25)';
            } else {
                toast.style.borderLeft = '4px solid var(--neon-cyan)'; // Cyan/Emerald success border
                toast.style.boxShadow = '0 0 20px var(--neon-cyan-glow)';
            }
            showToast(<?php echo json_encode($toastMessage); ?>);
        }
    });
</script>
<?php endif; ?>

</body>
</html>
