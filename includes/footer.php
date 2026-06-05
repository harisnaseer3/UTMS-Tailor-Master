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
</body>
</html>
