<?php
// forgot_password.php
require_once 'config/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$message = '';
$error = '';
$resetUrl = '';

$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($username)) {
        $error = 'Please enter your username.';
    } else {
        // Find user by username (and optionally phone if provided)
        if (!empty($phone)) {
            $stmt = $pdo->prepare("SELECT id, username, phone FROM users WHERE username = ? AND phone = ?");
            $stmt->execute([$username, $phone]);
        } else {
            $stmt = $pdo->prepare("SELECT id, username, phone FROM users WHERE username = ?");
            $stmt->execute([$username]);
        }
        
        $user = $stmt->fetch();

        if ($user) {
            // Generate secure token valid for 1 hour
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Invalidate any existing tokens for this user
            $delStmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $delStmt->execute([$user['id']]);

            // Insert new token
            $insStmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            $insStmt->execute([$user['id'], $token, $expiresAt]);

            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $domain = $_SERVER['HTTP_HOST'];
            $dir = dirname($_SERVER['PHP_SELF']);
            $resetUrl = rtrim($protocol . $domain . $dir, '/\\') . '/reset_password.php?token=' . $token;

            $message = 'Password reset token generated successfully!';
        } else {
            $error = 'No account found matching the provided username/phone details.';
        }
    }
}

require_once 'includes/header.php';
?>

<div style="max-width: 480px; margin: 40px auto;">
    <div class="glass-card">
        <h2 style="text-align: center; font-size: 22px; margin-bottom: 15px; background: linear-gradient(to right, #ffffff, var(--neon-cyan)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            🔑 Reset Your Password
        </h2>
        <p style="text-align: center; font-size: 13px; color: var(--text-secondary); margin-bottom: 25px;">
            Enter your account username (and registered phone number if applicable) to verify identity.
        </p>

        <?php if ($error): ?>
            <div style="background: rgba(255, 77, 77, 0.15); border: 1px solid var(--neon-pink); color: var(--neon-pink); padding: 12px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; text-align: center;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($message && $resetUrl): ?>
            <div style="background: rgba(0, 240, 255, 0.1); border: 1px solid var(--neon-cyan); color: white; padding: 15px; border-radius: 8px; font-size: 13px; margin-bottom: 20px;">
                <p style="color: var(--neon-cyan); font-weight: 600; margin-bottom: 10px; text-align: center;">
                    ✓ <?php echo htmlspecialchars($message); ?>
                </p>
                <p style="font-size: 12px; color: var(--text-secondary); margin-bottom: 10px;">
                    Click the link below to set your new password:
                </p>
                <div style="text-align: center; margin: 15px 0;">
                    <a href="<?php echo htmlspecialchars($resetUrl); ?>" class="btn-glass btn-neon-cyan" style="display: inline-block; padding: 8px 18px; text-decoration: none; font-size: 13px;">
                        Proceed to Reset Password
                    </a>
                </div>
            </div>
        <?php else: ?>
            <form action="forgot_password.php" method="POST">
                <div class="form-group">
                    <label class="form-label" for="username">Username *</label>
                    <input type="text" name="username" id="username" class="form-control" required placeholder="Enter your username...">
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Registered Phone Number (Optional)</label>
                    <input type="text" name="phone" id="phone" class="form-control" placeholder="Enter phone number if available...">
                </div>

                <button type="submit" class="btn-glass btn-neon-cyan" style="width: 100%; justify-content: center; padding: 12px; margin-top: 10px;">
                    Verify Account & Reset
                </button>
            </form>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 20px; font-size: 13px; color: var(--text-secondary);">
            Remembered your password? <a href="login.php" style="color: var(--neon-cyan); text-decoration: none; font-weight: 500;">Back to Login</a>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
