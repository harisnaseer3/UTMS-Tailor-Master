<?php
require_once 'config/db.php';
$pdo = getDBConnection();

try {
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('master', 'karigar', 'customer', 'super_admin') NOT NULL");
    
    // Create a default super_admin account if none exists
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'super_admin'");
    if ($stmt->rowCount() == 0) {
        $hash = password_hash('superadmin123', PASSWORD_DEFAULT);
        // super_admin does not need a shop_id (it can manage all) or can have shop_id = NULL
        $pdo->exec("INSERT INTO users (shop_id, username, password, role, phone) VALUES (NULL, 'superadmin', '$hash', 'super_admin', '0000000000')");
        echo "Added super_admin role and created user 'superadmin' with password 'superadmin123'\n";
    } else {
        echo "super_admin role added, user already exists.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
