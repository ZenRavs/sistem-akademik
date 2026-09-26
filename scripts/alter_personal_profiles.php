<?php
require_once __DIR__ . '/../config/db.php';

try {
    echo "Altering personal_profiles columns to DEFAULT NULL...\n";
    $conn->exec("
        ALTER TABLE personal_profiles ALTER COLUMN marital_status DROP DEFAULT;
        ALTER TABLE personal_profiles ALTER COLUMN marital_status SET DEFAULT NULL;
        
        ALTER TABLE personal_profiles ALTER COLUMN job_status DROP DEFAULT;
        ALTER TABLE personal_profiles ALTER COLUMN job_status SET DEFAULT NULL;
    ");
    echo "SUCCESS: Columns marital_status and job_status altered to DEFAULT NULL successfully!\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
