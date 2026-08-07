<?php
// config/db.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tailor_master');

function getDBConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        // First, connect to MySQL without selecting database to ensure it exists
        $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        $tempPdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        // Create database if not exists
        $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Now connect to the database
        $dsnWithDb = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsnWithDb, DB_USER, DB_PASS, $options);
        
        // Verify if tables exist, if not, create them
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'shops'");
        if ($tableCheck->rowCount() === 0) {
            // Read and execute schema.sql
            $schemaFile = dirname(__DIR__) . '/db/schema.sql';
            if (file_exists($schemaFile)) {
                $sql = file_get_contents($schemaFile);
                $pdo->exec($sql);
                // Seed initial data
                seedInitialData($pdo);
            }
        }
        
        // Auto-migration to allow nullable shop_id in customers table
        try {
            $pdo->exec("ALTER TABLE `customers` MODIFY COLUMN `shop_id` INT DEFAULT NULL");
        } catch (PDOException $e) {
            // Table might not exist or alter already done
        }

        // Auto-migration to add logo column to shops table
        try {
            $pdo->exec("ALTER TABLE `shops` ADD COLUMN `logo` VARCHAR(255) DEFAULT NULL");
        } catch (PDOException $e) {}

        // Auto-migration for password_resets table
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `password_resets` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT NOT NULL,
                `token` VARCHAR(100) NOT NULL,
                `expires_at` DATETIME NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT `fk_pw_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        } catch (PDOException $e) {}

        // Auto-migration to allow assigning orders to karigar (users table)
        try {
            $checkColumn = $pdo->query("SHOW COLUMNS FROM `orders` LIKE 'assigned_to'");
            if ($tableCheck->rowCount() > 0 && $checkColumn->rowCount() === 0) {
                $pdo->exec("ALTER TABLE `orders` ADD COLUMN `assigned_to` INT DEFAULT NULL");
                $pdo->exec("ALTER TABLE `orders` ADD CONSTRAINT `fk_orders_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL");
            }
        } catch (PDOException $e) {
            // Table might not exist or alter already done
        }
        
        // Auto-migration for dispatched status
        try {
            $pdo->exec("ALTER TABLE `orders` MODIFY COLUMN `status` ENUM('received', 'cutting', 'stitching', 'ready', 'dispatched') DEFAULT 'received'");
        } catch (PDOException $e) {}
        
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

function seedInitialData($pdo) {
    // Check if shops table is empty
    $count = $pdo->query("SELECT COUNT(*) FROM shops")->fetchColumn();
    if ($count > 0) return;

    // 1. Insert shops
    $stmt = $pdo->prepare("INSERT INTO shops (name, phone) VALUES (?, ?)");
    $stmt->execute(["Royal Stitch Tailors", "021-111-789-123"]);
    $shopId1 = $pdo->lastInsertId();

    $stmt->execute(["Elite Fits Boutique", "042-333-456-789"]);
    $shopId2 = $pdo->lastInsertId();

    // 2. Insert Users (role: master, karigar, customer)
    $stmtUser = $pdo->prepare("INSERT INTO users (shop_id, username, password, role, phone) VALUES (?, ?, ?, ?, ?)");
    
    // Shop 1 Users
    $masterPass = password_hash('master123', PASSWORD_DEFAULT);
    $karigarPass = password_hash('karigar123', PASSWORD_DEFAULT);
    $customerPass = password_hash('customer123', PASSWORD_DEFAULT);
    
    $stmtUser->execute([$shopId1, 'master', $masterPass, 'master', '03001112222']);
    $masterUserId = $pdo->lastInsertId();

    $stmtUser->execute([$shopId1, 'karigar', $karigarPass, 'karigar', '03003334444']);
    
    $stmtUser->execute([$shopId1, 'customer', $customerPass, 'customer', '03005556666']);
    $customerUserId = $pdo->lastInsertId();

    // Shop 2 Users
    $eliteMasterPass = password_hash('elite123', PASSWORD_DEFAULT);
    $stmtUser->execute([$shopId2, 'elite_master', $eliteMasterPass, 'master', '03009998888']);

    // 3. Insert Customer Profile linked to user and shop
    // Standard measurement templates (scientific: native)
    $defaultMeasurements = [
        "upper" => [
            "length" => 40.5,
            "shoulder" => 18.0,
            "chest" => 42.0,
            "armhole" => 9.5,
            "sleeve" => 24.5,
            "neck" => 15.5,
            "hem_width" => 26.0,
            "darts" => "No",
            "cut" => "Straight",
            "flare" => 0.0,
            "upper_chest" => 0.0,
            "lower_chest" => 0.0
        ],
        "lower" => [
            "length" => 38.0,
            "waist" => 36.0,
            "hips" => 44.0,
            "rise" => 14.0,
            "bottom_opening" => 8.5,
            "inseam" => 28.0
        ]
    ];
    
    $stmtCustomer = $pdo->prepare("INSERT INTO customers (shop_id, user_id, name, phone, gender, measurements) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtCustomer->execute([
        $shopId1,
        $customerUserId,
        "Ali Khan",
        "03005556666",
        "male",
        json_encode($defaultMeasurements)
    ]);
    $customerId = $pdo->lastInsertId();

    // 4. Create an initial Order for Ali Khan
    $stmtOrder = $pdo->prepare("INSERT INTO orders (shop_id, customer_id, tag_id, measurements_snapshot, fabric_image, status, notes, price, advance_paid) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtOrder->execute([
        $shopId1,
        $customerId,
        "2026-0001",
        json_encode($defaultMeasurements),
        null,
        "received",
        "Urgent delivery requested for Eid. Double stitch on shoulder.",
        2500.00,
        1000.00
    ]);
}
