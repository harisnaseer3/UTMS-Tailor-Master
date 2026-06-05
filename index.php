<?php
// index.php
require_once 'includes/header.php';

// If user is already logged in, send them directly to dashboard
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}
?>

<div style="text-align: center; margin: 60px 0;">
    <h1 style="font-size: 3.5rem; background: linear-gradient(135deg, #ffffff 30%, var(--neon-cyan) 80%, var(--neon-orchid)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-shadow: 0 0 30px rgba(0, 240, 255, 0.15); margin-bottom: 15px;">
        <?php echo __('title'); ?>
    </h1>
    <p style="font-size: 1.3rem; color: var(--text-secondary); max-width: 700px; margin: 0 auto 40px auto; font-weight: 300;">
        <?php echo __('tagline'); ?>. Empowering workshops with real-time Kanban scheduling, local sizing vaults, and seamless mobile fabric upload sync.
    </p>

    <!-- Quick Role Login Card -->
    <div class="glass-card" style="max-width: 600px; margin: 0 auto; text-align: left;">
        <h3 style="font-size: 1.5rem; color: var(--neon-cyan); margin-bottom: 20px; text-align: center; border-bottom: 1px solid var(--border-color); padding-bottom: 15px;">
            🧪 Developer Demo - Quick Access
        </h3>
        <p style="color: var(--text-secondary); font-size: 14px; margin-bottom: 20px; text-align: center;">
            Click any demo profile below to instantly log in and experience the respective role dashboard:
        </p>

        <div style="display: flex; flex-direction: column; gap: 14px;">
            <!-- Master Login -->
            <div class="glass-card" style="padding: 15px; border-color: rgba(13, 242, 138, 0.2); background: rgba(13, 242, 138, 0.02); display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h4 style="color: var(--neon-emerald); margin-bottom: 4px;">👑 <?php echo __('role_master'); ?></h4>
                    <p style="font-size: 12px; color: var(--text-secondary);">Full ledger control, Kanban board, Shop setup.</p>
                </div>
                <form action="login.php" method="POST">
                    <input type="hidden" name="username" value="master">
                    <input type="hidden" name="password" value="master123">
                    <input type="hidden" name="shop_id" value="1">
                    <button type="submit" class="btn-glass btn-neon-emerald" style="padding: 6px 14px; font-size: 13px;">Login (master123)</button>
                </form>
            </div>

            <!-- Karigar Login -->
            <div class="glass-card" style="padding: 15px; border-color: rgba(0, 240, 255, 0.2); background: rgba(0, 240, 255, 0.02); display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h4 style="color: var(--neon-cyan); margin-bottom: 4px;">🛠️ <?php echo __('role_karigar'); ?></h4>
                    <p style="font-size: 12px; color: var(--text-secondary);">Production columns only. Financial & Customer info hidden.</p>
                </div>
                <form action="login.php" method="POST">
                    <input type="hidden" name="username" value="karigar">
                    <input type="hidden" name="password" value="karigar123">
                    <input type="hidden" name="shop_id" value="1">
                    <button type="submit" class="btn-glass btn-neon-cyan" style="padding: 6px 14px; font-size: 13px;">Login (karigar123)</button>
                </form>
            </div>

            <!-- Customer Login -->
            <div class="glass-card" style="padding: 15px; border-color: rgba(255, 184, 0, 0.2); background: rgba(255, 184, 0, 0.02); display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h4 style="color: var(--neon-gold); margin-bottom: 4px;">👤 <?php echo __('role_customer'); ?></h4>
                    <p style="font-size: 12px; color: var(--text-secondary);">Personal measurement vault access and order tracking.</p>
                </div>
                <form action="login.php" method="POST">
                    <input type="hidden" name="username" value="customer">
                    <input type="hidden" name="password" value="customer123">
                    <input type="hidden" name="shop_id" value="1">
                    <button type="submit" class="btn-glass" style="padding: 6px 14px; font-size: 13px; border-color: var(--neon-gold); color: var(--neon-gold);">Login (customer123)</button>
                </form>
            </div>
        </div>

        <div style="margin-top: 25px; display: flex; justify-content: center; gap: 15px;">
            <a href="login.php" class="btn-glass">Regular Sign In</a>
            <a href="register.php" class="btn-glass btn-neon-cyan">Register New Shop</a>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
