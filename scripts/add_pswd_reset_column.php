<?php
require_once __DIR__ . '/../config/db.php';

try {
    echo "Running migration: Adding pswd_reset column to users_credential...\n";
    $sql = "ALTER TABLE users_credential ADD COLUMN IF NOT EXISTS pswd_reset VARCHAR(255) DEFAULT NULL;";
    $conn->exec($sql);
    echo "SUCCESS: Column pswd_reset added/verified in users_credential table.\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
