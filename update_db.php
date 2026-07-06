<?php
require_once 'config/db.php';
$pdo = getDBConnection();
try {
    $pdo->exec("ALTER TABLE customers ADD COLUMN gender ENUM('male', 'female') NOT NULL DEFAULT 'male' AFTER phone");
    echo "Column added successfully";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
