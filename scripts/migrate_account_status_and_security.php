<?php
/**
 * Migration Script:
 * 1. Convert 'status' (SMALLINT) in users_credential to 'account_status' (VARCHAR(30)).
 * 2. Rename 'last_login' to 'last_login_at' if exists.
 * 3. Add security & session columns:
 *    - last_active_at (TIMESTAMP)
 *    - session_token (VARCHAR(255))
 *    - failed_attempts (SMALLINT DEFAULT 0)
 *    - locked_until (TIMESTAMP DEFAULT NULL)
 */

require_once __DIR__ . '/../config/db.php';

if (!$conn) {
    echo "[ERROR] Database connection failed.\n";
    exit(1);
}

echo "=======================================================\n";
echo " Starting users_credential Security & Status Migration\n";
echo "=======================================================\n\n";

try {
    // 1. Check existing columns in users_credential
    $colStmt = $conn->query("
        SELECT column_name, data_type 
        FROM information_schema.columns 
        WHERE table_name = 'users_credential'
    ");
    $columns = $colStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    echo "[INFO] Existing columns in users_credential: " . implode(', ', array_keys($columns)) . "\n";

    // 2. Add account_status if not exists
    if (!isset($columns['account_status'])) {
        echo "[1/5] Adding column 'account_status' (VARCHAR(30) DEFAULT 'active')...\n";
        $conn->exec("ALTER TABLE users_credential ADD COLUMN account_status VARCHAR(30) DEFAULT 'active'");
        
        // Migrate values from legacy 'status' column if exists
        if (isset($columns['status'])) {
            echo " -> Migrating legacy 'status' values to 'account_status'...\n";
            $conn->exec("
                UPDATE users_credential 
                SET account_status = CASE 
                    WHEN status = 1 THEN 'active' 
                    ELSE 'suspended' 
                END
            ");
            echo " -> Dropping legacy column 'status'...\n";
            $conn->exec("ALTER TABLE users_credential DROP COLUMN status");
        }
    } else {
        echo "[1/5] Column 'account_status' already exists.\n";
    }

    // 3. Rename last_login to last_login_at if needed
    if (isset($columns['last_login']) && !isset($columns['last_login_at'])) {
        echo "[2/5] Renaming column 'last_login' to 'last_login_at'...\n";
        $conn->exec("ALTER TABLE users_credential RENAME COLUMN last_login TO last_login_at");
    } elseif (!isset($columns['last_login_at'])) {
        echo "[2/5] Adding column 'last_login_at' (TIMESTAMP)...\n";
        $conn->exec("ALTER TABLE users_credential ADD COLUMN last_login_at TIMESTAMP DEFAULT NULL");
    } else {
        echo "[2/5] Column 'last_login_at' already exists.\n";
    }

    // 4. Add last_active_at
    if (!isset($columns['last_active_at'])) {
        echo "[3/5] Adding column 'last_active_at' (TIMESTAMP)...\n";
        $conn->exec("ALTER TABLE users_credential ADD COLUMN last_active_at TIMESTAMP DEFAULT NULL");
    } else {
        echo "[3/5] Column 'last_active_at' already exists.\n";
    }

    // 5. Add session_token
    if (!isset($columns['session_token'])) {
        echo "[4/5] Adding column 'session_token' (VARCHAR(255))...\n";
        $conn->exec("ALTER TABLE users_credential ADD COLUMN session_token VARCHAR(255) DEFAULT NULL");
        $conn->exec("CREATE INDEX IF NOT EXISTS idx_users_session_token ON users_credential(session_token)");
    } else {
        echo "[4/5] Column 'session_token' already exists.\n";
    }

    // 6. Add failed_attempts and locked_until
    if (!isset($columns['failed_attempts'])) {
        echo "[5/5] Adding column 'failed_attempts' (SMALLINT DEFAULT 0)...\n";
        $conn->exec("ALTER TABLE users_credential ADD COLUMN failed_attempts SMALLINT DEFAULT 0");
    }
    if (!isset($columns['locked_until'])) {
        echo " -> Adding column 'locked_until' (TIMESTAMP)...\n";
        $conn->exec("ALTER TABLE users_credential ADD COLUMN locked_until TIMESTAMP DEFAULT NULL");
    }

    // 7. Ensure valid CHECK constraint on account_status
    echo "\n[INFO] Setting CHECK constraint for valid account_status values...\n";
    $conn->exec("ALTER TABLE users_credential DROP CONSTRAINT IF EXISTS chk_account_status");
    $conn->exec("
        ALTER TABLE users_credential 
        ADD CONSTRAINT chk_account_status 
        CHECK (account_status IN ('active', 'suspended', 'banned', 'archived', 'pending_activation'))
    ");

    // 8. Ensure Superadmin is active
    $conn->exec("UPDATE users_credential SET account_status = 'active' WHERE role = 'superadmin'");

    echo "\n=======================================================\n";
    echo " Verification Audit: users_credential Schema\n";
    echo "=======================================================\n";
    $newCols = $conn->query("
        SELECT column_name, data_type, column_default, is_nullable
        FROM information_schema.columns 
        WHERE table_name = 'users_credential'
        ORDER BY ordinal_position
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($newCols as $col) {
        printf(" -> %-18s | %-16s | default: %-25s | nullable: %s\n", 
            $col['column_name'], 
            $col['data_type'], 
            $col['column_default'] ?? 'NULL', 
            $col['is_nullable']
        );
    }

    $admin = $conn->query("SELECT id, username, email, role, account_status, last_login_at, last_active_at, session_token, failed_attempts, locked_until FROM users_credential WHERE role = 'superadmin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    echo "\nCurrent Superadmin Record:\n";
    print_r($admin);

    echo "\n[SUCCESS] Migration completed successfully!\n";

} catch (Exception $e) {
    echo "\n[ERROR] Migration failed: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
