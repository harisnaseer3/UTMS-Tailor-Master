<?php
// register.php
require_once 'config/db.php';
require_once 'includes/auth.php';

// Redirect if already logged in, unless super admin
if (isLoggedIn() && $_SESSION['role'] !== 'super_admin') {
    header("Location: dashboard.php");
    exit();
}
$isSuperAdmin = isLoggedIn() && $_SESSION['role'] === 'super_admin';

$errorMsg = '';
$successMsg = '';

// Handle Registration Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($regMode === 'customer') {
        $fullName = trim($_POST['customer_fullname'] ?? '');
        $username = trim($_POST['customer_username'] ?? '');
        $password = $_POST['customer_password'] ?? '';
        $phone = trim($_POST['customer_phone'] ?? '');
        
        if (empty($fullName) || empty($username) || empty($password) || empty($phone)) {
            $errorMsg = 'Full Name, Username, Password, and Mobile Number are required.';
        } else {
            $pdo = getDBConnection();
            
            // Check if username is already taken for customer role
            $checkUser = $pdo->prepare("SELECT id FROM users WHERE username = ? AND role = 'customer'");
            $checkUser->execute([$username]);
            if ($checkUser->rowCount() > 0) {
                $errorMsg = 'Username is already taken.';
            } else {
                try {
                    $pdo->beginTransaction();
                    
                    // 1. Insert user
                    $hashedPass = password_hash($password, PASSWORD_DEFAULT);
                    $stmtUser = $pdo->prepare("INSERT INTO users (shop_id, username, password, role, phone) VALUES (NULL, ?, ?, 'customer', ?)");
                    $stmtUser->execute([$username, $hashedPass, $phone]);
                    $userId = $pdo->lastInsertId();
                    
                    // 2. Create customer record with empty measurements
                    $emptyTree = [
                        "upper" => [
                            "length" => 0.0, "shoulder" => 0.0, "chest" => 0.0, "armhole" => 0.0, 
                            "sleeve" => 0.0, "neck" => 0.0, "hem_width" => 0.0, "darts" => "No", 
                            "cut" => "Straight", "flare" => 0.0, "upper_chest" => 0.0, "lower_chest" => 0.0
                        ],
                        "lower" => [
                            "length" => 0.0, "waist" => 0.0, "hips" => 0.0, "rise" => 0.0, "bottom_opening" => 0.0, "inseam" => 0.0
                        ]
                    ];
                    $stmtCust = $pdo->prepare("INSERT INTO customers (shop_id, user_id, name, phone, measurements) VALUES (NULL, ?, ?, ?, ?)");
                    $stmtCust->execute([$userId, $fullName, $phone, json_encode($emptyTree)]);
                    $customerId = $pdo->lastInsertId();
                    
                    $pdo->commit();
                    
                    // Auto login
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = 'customer';
                    $_SESSION['shop_id'] = null;
                    $_SESSION['customer_id'] = $customerId;
                    $_SESSION['phone'] = $phone;
                    
                    header("Location: dashboard.php?msg=welcome");
                    exit();
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $errorMsg = 'Database error during customer registration: ' . $e->getMessage();
                }
            }
        }
    } else {
        if (!$isSuperAdmin) {
            die("Unauthorized: Only super admins can register a new shop.");
        }
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
}

require_once 'includes/header.php';
?>

<div style="max-width: 500px; margin: 40px auto;">
    <div class="glass-card">
        <h2 style="text-align: center; font-size: 24px; margin-bottom: 20px; background: linear-gradient(to right, #ffffff, var(--neon-orchid)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            <?php echo __('register'); ?>
        </h2>
        
        <!-- Modern Role Tabs -->
        <div style="display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); padding-bottom: 10px;">
            <?php if ($isSuperAdmin): ?>
            <button type="button" id="tab-shop" onclick="setRegMode('shop')" style="flex: 1; padding: 10px; background: none; border: none; border-bottom: 2px solid var(--neon-orchid); color: var(--neon-orchid); font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                Register Shop
            </button>
            <?php endif; ?>
            <button type="button" id="tab-customer" onclick="setRegMode('customer')" style="flex: 1; padding: 10px; background: none; border: none; <?php echo $isSuperAdmin ? 'border-bottom: 2px solid transparent; color: var(--text-secondary);' : 'border-bottom: 2px solid var(--neon-gold); color: var(--neon-gold); font-weight: 600;'; ?> cursor: pointer; transition: all 0.3s ease;">
                Register as Customer
            </button>
        </div>

        <form action="register.php" method="POST" id="reg-form">
            <input type="hidden" name="reg_mode" id="reg_mode" value="<?php echo $isSuperAdmin ? 'shop' : 'customer'; ?>">
            
            <!-- Section 1: Shop Register Container -->
            <?php if ($isSuperAdmin): ?>
            <div id="shop-reg-fields">
                <h3 style="color: var(--neon-cyan); font-size: 16px; margin-bottom: 15px; border-bottom: 1px solid rgba(0, 240, 255, 0.15); padding-bottom: 5px;">
                    1. Workshop Information
                </h3>
                
                <div class="form-group">
                    <label class="form-label" for="shop_name"><?php echo __('shop_name'); ?> *</label>
                    <input type="text" name="shop_name" id="shop_name" class="form-control" <?php echo $isSuperAdmin ? 'required' : ''; ?> placeholder="e.g. Royal Stitch Studio">
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
                    <input type="text" name="username" id="username" class="form-control" <?php echo $isSuperAdmin ? 'required' : ''; ?> placeholder="Desired username...">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Master Password *</label>
                    <div style="position: relative;">
                        <input type="password" name="password" id="password" class="form-control" <?php echo $isSuperAdmin ? 'required' : ''; ?> placeholder="Create password..." style="padding-inline-end: 45px;">
                        <button type="button" onclick="togglePasswordVisibility('password', this)" style="position: absolute; inset-inline-end: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-secondary); cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 5px;" title="Toggle Password Visibility">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 20px; height: 20px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="phone">Personal Mobile Number</label>
                    <input type="text" name="phone" id="phone" class="form-control" placeholder="e.g. 03001234567">
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Section 2: Customer Register Container -->
            <div id="customer-reg-fields" style="<?php echo $isSuperAdmin ? 'display: none;' : ''; ?>">
                <h3 style="color: var(--neon-gold); font-size: 16px; margin-bottom: 15px; border-bottom: 1px solid rgba(255, 184, 0, 0.15); padding-bottom: 5px;">
                    👤 Personal Information
                </h3>
                
                <div class="form-group">
                    <label class="form-label" for="customer_fullname">Full Name *</label>
                    <input type="text" name="customer_fullname" id="customer_fullname" class="form-control" <?php echo $isSuperAdmin ? '' : 'required'; ?> placeholder="e.g. Ali Khan">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="customer_phone">Mobile Number *</label>
                    <input type="text" name="customer_phone" id="customer_phone" class="form-control" placeholder="e.g. 03001234567">
                </div>
                
                <h3 style="color: var(--neon-orchid); font-size: 16px; margin-top: 25px; margin-bottom: 15px; border-bottom: 1px solid rgba(184, 41, 242, 0.15); padding-bottom: 5px;">
                    🔑 Account Credentials
                </h3>
                
                <div class="form-group">
                    <label class="form-label" for="customer_username">Username *</label>
                    <input type="text" name="customer_username" id="customer_username" class="form-control" placeholder="Choose username...">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="customer_password">Password *</label>
                    <div style="position: relative;">
                        <input type="password" name="customer_password" id="customer_password" class="form-control" placeholder="Choose password..." style="padding-inline-end: 45px;">
                        <button type="button" onclick="togglePasswordVisibility('customer_password', this)" style="position: absolute; inset-inline-end: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-secondary); cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 5px;" title="Toggle Password Visibility">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 20px; height: 20px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn-glass btn-neon-orchid" id="reg_btn" style="width: 100%; justify-content: center; padding: 12px; margin-top: 15px;">
                <?php echo __('register'); ?> &rarr;
            </button>
        </form>
        
        <div style="text-align: center; margin-top: 20px; font-size: 14px; color: var(--text-secondary);">
            Already have a shop registered? <a href="login.php" style="font-weight: 500;"><?php echo __('login'); ?></a>
        </div>
    </div>
</div>

<script>
function setRegMode(mode) {
    const shopFields = document.getElementById('shop-reg-fields');
    const customerFields = document.getElementById('customer-reg-fields');
    const regModeInput = document.getElementById('reg_mode');
    const tabShop = document.getElementById('tab-shop');
    const tabCustomer = document.getElementById('tab-customer');
    const regBtn = document.getElementById('reg_btn');
    
    // Form Inputs
    const shopName = document.getElementById('shop_name');
    const username = document.getElementById('username');
    const password = document.getElementById('password');
    
    const customerFullname = document.getElementById('customer_fullname');
    const customerPhone = document.getElementById('customer_phone');
    const customerUsername = document.getElementById('customer_username');
    const customerPassword = document.getElementById('customer_password');
    
    regModeInput.value = mode;
    
    if (mode === 'customer') {
        shopFields.style.display = 'none';
        customerFields.style.display = 'block';
        
        // Toggle required attributes
        shopName.removeAttribute('required');
        username.removeAttribute('required');
        password.removeAttribute('required');
        
        customerFullname.setAttribute('required', 'required');
        customerPhone.setAttribute('required', 'required');
        customerUsername.setAttribute('required', 'required');
        customerPassword.setAttribute('required', 'required');
        
        // Update Tabs style
        tabCustomer.style.color = 'var(--neon-gold)';
        tabCustomer.style.borderBottomColor = 'var(--neon-gold)';
        tabCustomer.style.fontWeight = '600';
        
        tabShop.style.color = 'var(--text-secondary)';
        tabShop.style.borderBottomColor = 'transparent';
        tabShop.style.fontWeight = '500';
        
        regBtn.className = 'btn-glass';
        regBtn.style.borderColor = 'var(--neon-gold)';
        regBtn.style.color = 'var(--neon-gold)';
    } else {
        shopFields.style.display = 'block';
        customerFields.style.display = 'none';
        
        // Toggle required attributes
        shopName.setAttribute('required', 'required');
        username.setAttribute('required', 'required');
        password.setAttribute('required', 'required');
        
        customerFullname.removeAttribute('required');
        customerPhone.removeAttribute('required');
        customerUsername.removeAttribute('required');
        customerPassword.removeAttribute('required');
        
        // Update Tabs style
        tabShop.style.color = 'var(--neon-orchid)';
        tabShop.style.borderBottomColor = 'var(--neon-orchid)';
        tabShop.style.fontWeight = '600';
        
        tabCustomer.style.color = 'var(--text-secondary)';
        tabCustomer.style.borderBottomColor = 'transparent';
        tabCustomer.style.fontWeight = '500';
        
        regBtn.className = 'btn-glass btn-neon-orchid';
        regBtn.style.borderColor = '';
        regBtn.style.color = '';
    }
}
</script>

<?php
require_once 'includes/footer.php';
?>
