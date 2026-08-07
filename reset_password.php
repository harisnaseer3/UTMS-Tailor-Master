<?php
// reset_password.php
require_once 'config/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$pdo = getDBConnection();

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$success = false;

// Verify token
$resetReq = null;
if (!empty($token)) {
    $stmt = $pdo->prepare("SELECT pr.*, u.username FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.token = ? AND pr.expires_at > NOW()");
    $stmt->execute([$token]);
    $resetReq = $stmt->fetch();
}

if (!$resetReq && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $error = 'Invalid or expired password reset link. Please request a new one.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!$resetReq) {
        $error = 'Invalid or expired password reset session.';
    } else if (strlen($newPassword) < 4) {
        $error = 'Password must be at least 4 characters long.';
    } else if ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match. Please try again.';
    } else {
        // Update user password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $updateStmt->execute([$hashedPassword, $resetReq['user_id']]);

        // Invalidate used reset token
        $delStmt = $pdo->prepare("DELETE FROM password_resets WHERE id = ?");
        $delStmt->execute([$resetReq['id']]);

        $success = true;
    }
}

require_once 'includes/header.php';
?>

<div style="max-width: 450px; margin: 40px auto;">
    <div class="glass-card">
        <h2 style="text-align: center; font-size: 22px; margin-bottom: 15px; background: linear-gradient(to right, #ffffff, var(--neon-cyan)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            🔒 Create New Password
        </h2>

        <?php if ($success): ?>
            <div style="background: rgba(0, 255, 136, 0.15); border: 1px solid var(--neon-green, #00ff88); color: var(--neon-green, #00ff88); padding: 15px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; text-align: center;">
                🎉 Password updated successfully!
            </div>
            <div style="text-align: center; margin-top: 15px;">
                <a href="login.php" class="btn-glass btn-neon-cyan" style="display: inline-block; padding: 10px 24px; text-decoration: none;">
                    Go to Login
                </a>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div style="background: rgba(255, 77, 77, 0.15); border: 1px solid var(--neon-pink); color: var(--neon-pink); padding: 12px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; text-align: center;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($resetReq): ?>
                <p style="text-align: center; font-size: 13px; color: var(--text-secondary); margin-bottom: 20px;">
                    Resetting password for username: <strong style="color: var(--neon-cyan);"><?php echo htmlspecialchars($resetReq['username']); ?></strong>
                </p>

                <form action="reset_password.php" method="POST">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="form-group">
                        <label class="form-label" for="new_password">New Password *</label>
                        <div style="position: relative;">
                            <input type="password" name="new_password" id="new_password" class="form-control" required placeholder="Enter new password..." style="padding-inline-end: 45px;">
                            <button type="button" onclick="togglePasswordVisibility('new_password', this)" style="position: absolute; inset-inline-end: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-secondary); cursor: pointer; padding: 5px;" title="Toggle Password Visibility">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 20px; height: 20px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirm New Password *</label>
                        <div style="position: relative;">
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required placeholder="Re-enter new password..." style="padding-inline-end: 45px;">
                            <button type="button" onclick="togglePasswordVisibility('confirm_password', this)" style="position: absolute; inset-inline-end: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-secondary); cursor: pointer; padding: 5px;" title="Toggle Password Visibility">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 20px; height: 20px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-glass btn-neon-cyan" style="width: 100%; justify-content: center; padding: 12px; margin-top: 10px;">
                        Update Password
                    </button>
                </form>
            <?php else: ?>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="forgot_password.php" class="btn-glass btn-neon-cyan" style="display: inline-block; padding: 10px 20px; text-decoration: none;">
                        Request New Password Reset Link
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
