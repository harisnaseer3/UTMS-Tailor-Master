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
    $shopId = !empty($_POST['shop_id']) ? intval($_POST['shop_id']) : null;
    
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
        
        <!-- Modern Role Tabs -->
        <div style="display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); padding-bottom: 10px;">
            <button type="button" id="tab-staff" onclick="setLoginMode('staff')" style="flex: 1; padding: 10px; background: none; border: none; border-bottom: 2px solid var(--neon-cyan); color: var(--neon-cyan); font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                Workshop Staff
            </button>
            <button type="button" id="tab-customer" onclick="setLoginMode('customer')" style="flex: 1; padding: 10px; background: none; border: none; border-bottom: 2px solid transparent; color: var(--text-secondary); font-weight: 500; cursor: pointer; transition: all 0.3s ease;">
                Customer
            </button>
        </div>
        <input type="hidden" name="login_mode" id="login_mode" value="staff">

        <form action="login.php" method="POST">
            <!-- Shop Selection (Required for Multi-Tenant Scoping) -->
    <div class="form-group" id="shop-selection-group">
    <label class="form-label" for="shop_search"><?php echo __('shop'); ?> *</label>
        <input type="text" id="shop_search" name="shop_search" class="form-control" placeholder="<?php echo __('select_customer'); ?>..." autocomplete="off" list="shop_options" required>
    <input type="hidden" name="shop_id" id="shop_id">
    <datalist id="shop_options">
            <?php foreach ($shops as $shop): ?>
                <option value="<?php echo htmlspecialchars($shop['name']); ?>" data-id="<?php echo $shop['id']; ?>"></option>
            <?php endforeach; ?>
    </datalist>
</div>
            
            <div class="form-group">
                <label class="form-label" for="username"><?php echo __('username'); ?> *</label>
                <input type="text" name="username" id="username" class="form-control" required placeholder="Enter username...">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password"><?php echo __('password'); ?> *</label>
                <div style="position: relative;">
                    <input type="password" name="password" id="password" class="form-control" required placeholder="Enter password..." style="padding-inline-end: 45px;">
                    <button type="button" onclick="togglePasswordVisibility('password', this)" style="position: absolute; inset-inline-end: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-secondary); cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 5px;" title="Toggle Password Visibility">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 20px; height: 20px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>
                </div>
            </div>
            
            <button type="submit" class="btn-glass btn-neon-cyan" id="login_btn" style="width: 100%; justify-content: center; padding: 12px; margin-top: 10px;">
                <?php echo __('login'); ?>
            </button>
        </form>
        
        <div style="text-align: center; margin-top: 20px; font-size: 14px; color: var(--text-secondary);">
            Don't have an account? <a href="register.php" style="font-weight: 500;">Register as Customer</a>
        </div>
    </div>
</div>

<script>
function setLoginMode(mode) {
    const shopGroup = document.getElementById('shop-selection-group');
    const shopSelect = document.getElementById('shop_id');
    const shopSearch = document.getElementById('shop_search');
    const loginModeInput = document.getElementById('login_mode');
    const tabStaff = document.getElementById('tab-staff');
    const tabCustomer = document.getElementById('tab-customer');
    const loginBtn = document.getElementById('login_btn');
    
    loginModeInput.value = mode;
    
    if (mode === 'customer') {
        shopGroup.style.display = 'none';
        shopSelect.removeAttribute('required');
        shopSearch.removeAttribute('required');
        shopSelect.value = '';
        shopSearch.value = '';
        
        tabCustomer.style.color = 'var(--neon-gold)';
        tabCustomer.style.borderBottomColor = 'var(--neon-gold)';
        tabCustomer.style.fontWeight = '600';
        
        tabStaff.style.color = 'var(--text-secondary)';
        tabStaff.style.borderBottomColor = 'transparent';
        tabStaff.style.fontWeight = '500';
        
        // Add subtle gold tint to login button for customer
        loginBtn.className = 'btn-glass';
        loginBtn.style.borderColor = 'var(--neon-gold)';
        loginBtn.style.color = 'var(--neon-gold)';
    } else {
        shopGroup.style.display = 'block';
        shopSelect.setAttribute('required', 'required');
        shopSearch.setAttribute('required', 'required');
        
        tabStaff.style.color = 'var(--neon-cyan)';
        tabStaff.style.borderBottomColor = 'var(--neon-cyan)';
        tabStaff.style.fontWeight = '600';
        
        tabCustomer.style.color = 'var(--text-secondary)';
        tabCustomer.style.borderBottomColor = 'transparent';
        tabCustomer.style.fontWeight = '500';
        
        // Reset cyan button class
        loginBtn.className = 'btn-glass btn-neon-cyan';
        loginBtn.style.borderColor = '';
        loginBtn.style.color = '';
    }
}

// Live shop search handling
(function() {
    const shopSearch = document.getElementById('shop_search');
    const shopIdInput = document.getElementById('shop_id');
    const shopOptions = document.getElementById('shop_options').options;

    shopSearch.addEventListener('input', function () {
        const val = this.value.trim();
        let matched = false;
        for (let i = 0; i < shopOptions.length; i++) {
            if (shopOptions[i].value === val) {
                shopIdInput.value = shopOptions[i].dataset.id;
                matched = true;
                break;
            }
        }
        if (!matched) {
            shopIdInput.value = '';
        }
    });
})();
</script>

<?php
require_once 'includes/footer.php';
?>
