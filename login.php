<?php
// login.php
require_once 'config/db.php';
require_once 'includes/auth.php';

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logoutUser();
    header("Location: login.php?msg=logged_out");
    exit();
}

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$errorMsg = '';

// Handle POST Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $shopId = $_POST['shop_id'] ?? null;
    
    if (empty($username) || empty($password)) {
        $errorMsg = 'Please enter both username and password.';
    } else {
        if (loginUser($username, $password, $shopId)) {
            header("Location: dashboard.php");
            exit();
        } else {
            $errorMsg = 'Invalid username, password, or shop selection.';
        }
    }
}

// Get Shops for dropdown selector
$pdo = getDBConnection();
$shops = [];
try {
    $shops = $pdo->query("SELECT id, name FROM shops ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {
    // Database may not be fully initialized yet, but getDBConnection() auto-initializes it
}

require_once 'includes/header.php';
?>

<div style="max-width: 450px; margin: 40px auto;">
    <div class="glass-card">
        <h2 style="text-align: center; font-size: 24px; margin-bottom: 20px; background: linear-gradient(to right, #ffffff, var(--neon-cyan)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            <?php echo __('login'); ?>
        </h2>
        
        <?php if (!empty($errorMsg)): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgb(239, 68, 68); color: #fca5a5; padding: 10px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; text-align: center;">
                ⚠️ <?php echo htmlspecialchars($errorMsg); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'logged_out'): ?>
            <div style="background: rgba(13, 242, 138, 0.1); border: 1px solid var(--neon-emerald); color: #a7f3d0; padding: 10px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; text-align: center;">
                🔒 You have been signed out successfully.
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <!-- Shop Selection (Required for Multi-Tenant Scoping) -->
            <div class="form-group">
                <label class="form-label" for="shop_id"><?php echo __('shop'); ?> *</label>
                <select name="shop_id" id="shop_id" class="form-control" required>
                    <option value=""><?php echo __('select_customer'); ?>...</option>
                    <?php foreach ($shops as $shop): ?>
                        <option value="<?php echo $shop['id']; ?>">
                            <?php echo htmlspecialchars($shop['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="username"><?php echo __('username'); ?> *</label>
                <input type="text" name="username" id="username" class="form-control" required placeholder="Enter username...">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password"><?php echo __('password'); ?> *</label>
                <input type="password" name="password" id="password" class="form-control" required placeholder="Enter password...">
            </div>
            
            <button type="submit" class="btn-glass btn-neon-cyan" style="width: 100%; justify-content: center; padding: 12px; margin-top: 10px;">
                <?php echo __('login'); ?>
            </button>
        </form>
        
        <div style="text-align: center; margin-top: 20px; font-size: 14px; color: var(--text-secondary);">
            Don't have a workshop registered? <a href="register.php" style="font-weight: 500;"><?php echo __('register'); ?></a>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
