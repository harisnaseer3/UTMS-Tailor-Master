<?php
// register.php
require_once 'config/db.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$errorMsg = '';
$successMsg = '';

// Handle Shop Registration Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shopName = trim($_POST['shop_name'] ?? '');
    $shopPhone = trim($_POST['shop_phone'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($shopName) || empty($username) || empty($password)) {
        $errorMsg = 'Shop Name, Master Username, and Password are required.';
    } else {
        $pdo = getDBConnection();
        try {
            $pdo->beginTransaction();
            
            // 1. Insert new shop
            $stmtShop = $pdo->prepare("INSERT INTO shops (name, phone) VALUES (?, ?)");
            $stmtShop->execute([$shopName, $shopPhone]);
            $shopId = $pdo->lastInsertId();
            
            // 2. Hash password and insert master user
            $hashedPass = password_hash($password, PASSWORD_DEFAULT);
            $stmtUser = $pdo->prepare("INSERT INTO users (shop_id, username, password, role, phone) VALUES (?, ?, ?, 'master', ?)");
            $stmtUser->execute([$shopId, $username, $hashedPass, $phone]);
            
            $pdo->commit();
            
            // 3. Auto login new master
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'master';
            $_SESSION['shop_id'] = $shopId;
            
            header("Location: dashboard.php?msg=welcome");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) { // Unique constraint violation
                $errorMsg = 'Username is already taken for this shop.';
            } else {
                $errorMsg = 'Database error during registration: ' . $e->getMessage();
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div style="max-width: 500px; margin: 40px auto;">
    <div class="glass-card">
        <h2 style="text-align: center; font-size: 24px; margin-bottom: 20px; background: linear-gradient(to right, #ffffff, var(--neon-orchid)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            <?php echo __('register'); ?>
        </h2>
        
        <?php if (!empty($errorMsg)): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgb(239, 68, 68); color: #fca5a5; padding: 10px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; text-align: center;">
                ⚠️ <?php echo htmlspecialchars($errorMsg); ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <h3 style="color: var(--neon-cyan); font-size: 16px; margin-bottom: 15px; border-bottom: 1px solid rgba(0, 240, 255, 0.15); padding-bottom: 5px;">
                1. Workshop Information
            </h3>
            
            <div class="form-group">
                <label class="form-label" for="shop_name"><?php echo __('shop_name'); ?> *</label>
                <input type="text" name="shop_name" id="shop_name" class="form-control" required placeholder="e.g. Royal Stitch Studio">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="shop_phone">Workshop Phone</label>
                <input type="text" name="shop_phone" id="shop_phone" class="form-control" placeholder="e.g. 021-1234567">
            </div>

            <h3 style="color: var(--neon-orchid); font-size: 16px; margin-top: 25px; margin-bottom: 15px; border-bottom: 1px solid rgba(184, 41, 242, 0.15); padding-bottom: 5px;">
                2. Master Credentials (Owner Profile)
            </h3>
            
            <div class="form-group">
                <label class="form-label" for="username">Master Username *</label>
                <input type="text" name="username" id="username" class="form-control" required placeholder="Desired username...">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Master Password *</label>
                <input type="password" name="password" id="password" class="form-control" required placeholder="Create password...">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="phone">Personal Mobile Number</label>
                <input type="text" name="phone" id="phone" class="form-control" placeholder="e.g. 03001234567">
            </div>
            
            <button type="submit" class="btn-glass btn-neon-orchid" style="width: 100%; justify-content: center; padding: 12px; margin-top: 15px;">
                <?php echo __('register'); ?> &rarr;
            </button>
        </form>
        
        <div style="text-align: center; margin-top: 20px; font-size: 14px; color: var(--text-secondary);">
            Already have a shop registered? <a href="login.php" style="font-weight: 500;"><?php echo __('login'); ?></a>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
