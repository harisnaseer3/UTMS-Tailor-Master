<?php
// includes/auth.php

require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Enforce specific roles (accepts string or array of roles)
function requireRole($allowedRoles) {
    requireLogin();
    
    if (is_string($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }
    
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        // Forbidden access
        header("Location: index.php?error=unauthorized");
        exit();
    }
}

// Get the scoped shop ID for queries
function getCurrentShopId() {
    if (isset($_SESSION['shop_id'])) {
        return $_SESSION['shop_id'];
    }
    return null;
}

// Check if current user has permission to see financial data
function canViewFinancials() {
    if (!isLoggedIn()) return false;
    return $_SESSION['role'] === 'master';
}

// Check if current user can view customer contact phone numbers
function canViewCustomerContact() {
    if (!isLoggedIn()) return false;
    // Master can view contacts, Customers can view their own details. Karigars cannot view contact info.
    return in_array($_SESSION['role'], ['master', 'customer']);
}

// Login user action
function loginUser($username, $password, $shopId = null) {
    $pdo = getDBConnection();
    
    if ($shopId) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND shop_id = ?");
        $stmt->execute([$username, $shopId]);
    } else {
        // Customer login might not specify a shop initially or log in globally
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
    }
    
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['shop_id'] = $user['shop_id'];
        
        // If customer, check if they have a customer record
        if ($user['role'] === 'customer') {
            $stmtCust = $pdo->prepare("SELECT id, shop_id FROM customers WHERE user_id = ?");
            $stmtCust->execute([$user['id']]);
            $cust = $stmtCust->fetch();
            if ($cust) {
                $_SESSION['customer_id'] = $cust['id'];
                // Set shop_id based on the customer profile
                $_SESSION['shop_id'] = $cust['shop_id'];
            }
        }
        
        return true;
    }
    return false;
}

// Logout user action
function logoutUser() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
