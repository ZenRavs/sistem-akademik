<?php
/**
 * Database Cleaning Script:
 * 1. Delete all records from all operational tables except superadmin in users_credential & personal_profiles.
 * 2. Drop unused legacy tables: applicants, courses, users, students, lecturers (and legacy student_krs, krs_offers).
 */

require_once __DIR__ . '/../config/db.php';

if (!$conn) {
    echo "[ERROR] Database connection failed.\n";
    exit(1);
}

echo "=======================================================\n";
echo " Starting Database Cleanup & Legacy Table Removal\n";
echo "=======================================================\n\n";

try {
    // 1. Delete records from operational tables
    echo "[1/3] Truncating operational tables...\n";
    
    // Dependent tables first
    $conn->exec("TRUNCATE TABLE students_score_data CASCADE");
    $conn->exec("TRUNCATE TABLE students_krs_data CASCADE");
    $conn->exec("TRUNCATE TABLE students_bills_data CASCADE");
    $conn->exec("TRUNCATE TABLE students_data CASCADE");
    $conn->exec("TRUNCATE TABLE pmb_data CASCADE");
    $conn->exec("TRUNCATE TABLE lecturers_data CASCADE");
    $conn->exec("TRUNCATE TABLE courses_data CASCADE");
    echo " -> Truncated: students_score_data, students_krs_data, students_bills_data, students_data, pmb_data, lecturers_data, courses_data.\n";

    // 2. Clean users_credential and personal_profiles except superadmin
    echo "[2/3] Cleaning users_credential & personal_profiles (preserving superadmin)...\n";
    
    // Find superadmin id
    $stmt = $conn->query("SELECT id FROM users_credential WHERE username = '0000.0001' OR role = 'superadmin' LIMIT 1");
    $superadminId = $stmt->fetchColumn();

    if ($superadminId) {
        // Delete all profiles except superadmin
        $delProf = $conn->prepare("DELETE FROM personal_profiles WHERE user_id != ?");
        $delProf->execute([$superadminId]);

        // Delete all credentials except superadmin
        $delUser = $conn->prepare("DELETE FROM users_credential WHERE id != ?");
        $delUser->execute([$superadminId]);
        
        echo " -> Preserved superadmin ID: $superadminId. All other user records deleted.\n";
    } else {
        echo " -> [WARN] Superadmin not found. Creating default superadmin account...\n";
        $hash = '$2y$10$O0.gCGhpRQmZuGDYEB5euudSCUqW1XyrU6AHTIFO7IbrqYA7RdzWS';
        $ins = $conn->prepare("INSERT INTO users_credential (username, email, password, role, account_status) VALUES ('0000.0001', 'admin@dinus.ac.id', ?, 'superadmin', 'active') RETURNING id");
        $ins->execute([$hash]);
        $superadminId = $ins->fetchColumn();
        $conn->prepare("INSERT INTO personal_profiles (user_id, full_name, phone, ktp_address, domicile_address) VALUES (?, 'Super Admin Local', '08123456789', 'Kampus UDINUS', 'Semarang')")->execute([$superadminId]);
        echo " -> Default superadmin created with ID: $superadminId.\n";
    }

    // Reset sequences for operational tables
    $conn->exec("SELECT setval('students_data_id_seq', 1, false)");
    $conn->exec("SELECT setval('pmb_data_id_seq', 1, false)");
    $conn->exec("SELECT setval('lecturers_data_id_seq', 1, false)");
    $conn->exec("SELECT setval('courses_data_id_seq', 1, false)");
    $conn->exec("SELECT setval('students_bills_data_id_seq', 1, false)");
    $conn->exec("SELECT setval('students_krs_data_id_seq', 1, false)");
    $conn->exec("SELECT setval('students_score_data_id_seq', 1, false)");

    // Ensure master majors are available
    $conn->exec("
        INSERT INTO majors_data (major_code, major_name, degree, faculty, is_active) VALUES
            ('A11', 'Teknik Informatika', 'S1', 'Fakultas Ilmu Komputer', TRUE),
            ('A12', 'Sistem Informasi', 'S1', 'Fakultas Ilmu Komputer', TRUE),
            ('A14', 'Desain Komunikasi Visual', 'S1', 'Fakultas Ilmu Komputer', TRUE),
            ('A15', 'Ilmu Komunikasi', 'S1', 'Fakultas Ilmu Komputer', TRUE),
            ('A22', 'Teknik Informatika', 'D3', 'Fakultas Ilmu Komputer', TRUE)
        ON CONFLICT (major_code) DO NOTHING;
    ");

    // 3. Drop unused legacy tables
    echo "[3/3] Dropping unused legacy tables...\n";
    $legacyTables = ['applicants', 'courses', 'users', 'students', 'lecturers', 'krs_offers', 'student_krs'];
    foreach ($legacyTables as $tbl) {
        $conn->exec("DROP TABLE IF EXISTS $tbl CASCADE");
        echo " -> Dropped legacy table: $tbl\n";
    }

    echo "\n=======================================================\n";
    echo " Database Cleanup Audit Report\n";
    echo "=======================================================\n";
    
    // Remaining tables audit
    $activeTables = [
        'users_credential',
        'personal_profiles',
        'majors_data',
        'courses_data',
        'pmb_data',
        'students_data',
        'lecturers_data',
        'students_bills_data',
        'students_krs_data',
        'students_score_data'
    ];
    foreach ($activeTables as $tbl) {
        $cnt = $conn->query("SELECT COUNT(*) FROM $tbl")->fetchColumn();
        echo " -> $tbl: $cnt rows\n";
    }

    // Verify legacy tables are gone
    $stmt = $conn->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
    $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "\nActive tables currently in database:\n";
    foreach ($existing as $t) {
        echo " - $t\n";
    }

    echo "\n[SUCCESS] Cleanup and legacy table removal completed successfully!\n";

} catch (Exception $e) {
    echo "\n[ERROR] Cleanup failed: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
