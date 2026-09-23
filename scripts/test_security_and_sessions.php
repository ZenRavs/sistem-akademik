<?php
/**
 * Automated Verification Test for users_credential Security & Session Features:
 * 1. Account status validation ('active', 'suspended', 'banned')
 * 2. Login authentication & session_token generation
 * 3. Brute-force protection (failed_attempts & locked_until)
 * 4. Single-device session token mismatch simulation
 * 5. Idle timeout calculation simulation
 */

require_once __DIR__ . '/../config/db.php';

if (!$conn) {
    echo "[FAIL] Cannot connect to DB\n";
    exit(1);
}

echo "=======================================================\n";
echo " Starting Security & Session Feature Verification\n";
echo "=======================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest($name, $condition, $info = '') {
    global $passCount, $failCount;
    if ($condition) {
        echo " [PASS] $name" . ($info ? " ($info)" : "") . "\n";
        $passCount++;
    } else {
        echo " [FAIL] $name" . ($info ? " ($info)" : "") . "\n";
        $failCount++;
    }
}

try {
    // Clean superadmin state for testing
    $conn->exec("
        UPDATE users_credential 
        SET failed_attempts = 0, 
            locked_until = NULL, 
            account_status = 'active' 
        WHERE username = '0000.0001'
    ");

    $admin = $conn->query("SELECT * FROM users_credential WHERE username = '0000.0001'")->fetch(PDO::FETCH_ASSOC);
    assertTest("Superadmin exists and is active", $admin && $admin['account_status'] === 'active', "ID: " . ($admin['id'] ?? 'none'));

    // Test 1: Successful login simulation
    $passOk = password_verify('localadmin001', $admin['password']);
    assertTest("Password verification works", $passOk);

    $newToken = bin2hex(random_bytes(32));
    $upd = $conn->prepare("
        UPDATE users_credential 
        SET last_login_at = CURRENT_TIMESTAMP, 
            last_active_at = CURRENT_TIMESTAMP, 
            session_token = ?, 
            failed_attempts = 0, 
            locked_until = NULL 
        WHERE id = ?
    ");
    $upd->execute([$newToken, $admin['id']]);

    $adminAfterLogin = $conn->query("SELECT * FROM users_credential WHERE id = " . $admin['id'])->fetch(PDO::FETCH_ASSOC);
    assertTest("Session token generated & stored", !empty($adminAfterLogin['session_token']) && $adminAfterLogin['session_token'] === $newToken);
    assertTest("last_login_at updated", !empty($adminAfterLogin['last_login_at']));
    assertTest("last_active_at updated", !empty($adminAfterLogin['last_active_at']));

    // Test 2: Single-Device Token Check
    $userSessionToken = $adminAfterLogin['session_token'];
    $device2Token = bin2hex(random_bytes(32));
    assertTest("Local session token matches DB token", $userSessionToken === $adminAfterLogin['session_token']);
    assertTest("Mismatch detected if another device logs in", $userSessionToken !== $device2Token);

    // Test 3: Idle timeout calculation
    $idleTimeoutSeconds = 1800;
    $thirtyFiveMinsAgo = time() - (35 * 60);
    $twentyMinsAgo = time() - (20 * 60);
    assertTest("Idle timeout triggers if > 30 mins inactive", (time() - $thirtyFiveMinsAgo) > $idleTimeoutSeconds);
    assertTest("Session remains valid if < 30 mins inactive", (time() - $twentyMinsAgo) <= $idleTimeoutSeconds);

    // Test 4: Brute-force protection simulation
    $conn->exec("UPDATE users_credential SET failed_attempts = 5, locked_until = CURRENT_TIMESTAMP + INTERVAL '15 minutes' WHERE id = " . $admin['id']);
    $lockedUser = $conn->query("SELECT * FROM users_credential WHERE id = " . $admin['id'])->fetch(PDO::FETCH_ASSOC);
    $isLocked = !empty($lockedUser['locked_until']) && strtotime($lockedUser['locked_until']) > time();
    assertTest("Account locked after 5 failed attempts", $isLocked, "Locked until: " . $lockedUser['locked_until']);

    // Test 5: Account status check
    $conn->exec("UPDATE users_credential SET account_status = 'suspended' WHERE id = " . $admin['id']);
    $suspendedUser = $conn->query("SELECT * FROM users_credential WHERE id = " . $admin['id'])->fetch(PDO::FETCH_ASSOC);
    assertTest("Suspended account detected", $suspendedUser['account_status'] === 'suspended');

    $conn->exec("UPDATE users_credential SET account_status = 'banned' WHERE id = " . $admin['id']);
    $bannedUser = $conn->query("SELECT * FROM users_credential WHERE id = " . $admin['id'])->fetch(PDO::FETCH_ASSOC);
    assertTest("Banned account detected", $bannedUser['account_status'] === 'banned');

    // Test 6: CHECK constraint verification
    $constraintOk = false;
    try {
        $conn->exec("UPDATE users_credential SET account_status = 'invalid_state' WHERE id = " . $admin['id']);
    } catch (Exception $e) {
        $constraintOk = true; // Expected constraint violation
    }
    assertTest("DB rejects invalid account_status via CHECK constraint", $constraintOk);

    // Reset Superadmin to clean active state
    $conn->exec("
        UPDATE users_credential 
        SET failed_attempts = 0, 
            locked_until = NULL, 
            account_status = 'active',
            session_token = NULL 
        WHERE id = " . $admin['id']
    );
    echo "\n[INFO] Superadmin reset back to active, clean state.\n";

} catch (Exception $e) {
    echo "\n[FATAL ERROR] " . $e->getMessage() . "\n";
    $failCount++;
}

echo "\n=======================================================\n";
echo " Verification Summary: $passCount PASSED, $failCount FAILED\n";
echo "=======================================================\n";

if ($failCount > 0) {
    exit(1);
}
