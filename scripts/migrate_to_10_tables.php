<?php
/**
 * Standalone Migration Script: Legacy to 10 Standardized Tables
 * Sistem AKADEMIK FIK & PMB Online
 */

require_once __DIR__ . '/../config/db.php';

if (!$conn) {
    echo "[ERROR] Cannot connect to database. Migration aborted.\n";
    exit(1);
}

echo "========================================================\n";
echo " Starting Enterprise Database Migration (10 Tables)\n";
echo "========================================================\n\n";

try {
    // 1. Create 10 Standardized Tables
    echo "[1/8] Creating 10 Standardized Tables...\n";
    $schemaSql = file_get_contents(__DIR__ . '/../schema.sql');
    $conn->exec($schemaSql);
    echo " -> Schema DDL executed successfully.\n\n";

    // Helper map of major_code to major_id
    $majorsMap = [];
    $stmt = $conn->query("SELECT id, major_code FROM majors_data");
    while ($row = $stmt->fetch()) {
        $majorsMap[$row['major_code']] = (int)$row['id'];
    }

    function getMajorId($codeOrName, $fallback = 'A11') {
        global $majorsMap;
        $clean = strtoupper(trim((string)$codeOrName));
        if (strpos($clean, 'A11') !== false || strpos($clean, 'INFORMATIKA') !== false) return $majorsMap['A11'] ?? 1;
        if (strpos($clean, 'A12') !== false || strpos($clean, 'INFORMASI') !== false) return $majorsMap['A12'] ?? 2;
        if (strpos($clean, 'A14') !== false || strpos($clean, 'DKV') !== false || strpos($clean, 'VISUAL') !== false) return $majorsMap['A14'] ?? 3;
        if (strpos($clean, 'A15') !== false || strpos($clean, 'KOMUNIKASI') !== false) return $majorsMap['A15'] ?? 4;
        if (strpos($clean, 'A22') !== false || strpos($clean, 'D3') !== false) return $majorsMap['A22'] ?? 5;
        return $majorsMap[$fallback] ?? 1;
    }

    $defaultPasswordHash = '$2y$10$O0.gCGhpRQmZuGDYEB5euudSCUqW1XyrU6AHTIFO7IbrqYA7RdzWS'; // localadmin001

    // 2. Migrate legacy 'users' table
    echo "[2/8] Migrating legacy users...\n";
    $legacyUsers = $conn->query("SELECT * FROM users")->fetchAll();
    $migratedUsers = 0;
    foreach ($legacyUsers as $u) {
        $username = trim($u['username']);
        $role = strtolower(trim($u['role'] ?? 'user'));
        if ($role === 'mahasiswa') $role = 'student';
        elseif ($role === 'dosen') $role = 'lecturer';
        elseif ($role === 'superadmin') $role = 'superadmin';
        elseif ($role === 'admin') $role = 'admin';
        else $role = 'applicant';

        $email = $username . '@dinus.ac.id';
        if (strpos($username, '@') !== false) {
            $email = $username;
        }

        // Check if user exists
        $checkStmt = $conn->prepare("SELECT id FROM users_credential WHERE username = ? OR email = ?");
        $checkStmt->execute([$username, $email]);
        $existingId = $checkStmt->fetchColumn();

        if (!$existingId) {
            $accStatus = (($u['status'] ?? 1) == 1) ? 'active' : 'suspended';
            $insertUser = $conn->prepare("INSERT INTO users_credential (username, email, password, role, account_status, created_at) VALUES (?, ?, ?, ?, ?, COALESCE(?, CURRENT_TIMESTAMP)) RETURNING id");
            $insertUser->execute([$username, $email, $u['password'], $role, $accStatus, $u['created_at'] ?? null]);
            $userId = $insertUser->fetchColumn();
            $migratedUsers++;
        } else {
            $userId = $existingId;
        }

        // Insert or update personal profile
        $checkProf = $conn->prepare("SELECT id FROM personal_profiles WHERE user_id = ?");
        $checkProf->execute([$userId]);
        if (!$checkProf->fetchColumn()) {
            $insertProf = $conn->prepare("INSERT INTO personal_profiles (user_id, full_name, photo, created_at) VALUES (?, ?, ?, COALESCE(?, CURRENT_TIMESTAMP))");
            $insertProf->execute([$userId, $u['name'] ?? $username, $u['pict'] ?? null, $u['created_at'] ?? null]);
        }
    }
    echo " -> Users processed. Migrated new: $migratedUsers\n\n";

    // 3. Migrate legacy 'applicants' table
    echo "[3/8] Migrating legacy applicants to users_credential, personal_profiles, & pmb_data...\n";
    $legacyApplicants = $conn->query("SELECT * FROM applicants")->fetchAll();
    $migratedApplicants = 0;
    foreach ($legacyApplicants as $app) {
        $username = !empty($app['username']) ? trim($app['username']) : 'app_' . $app['id'];
        $email = trim($app['email']);
        $password = !empty($app['password']) ? $app['password'] : $defaultPasswordHash;

        // Check user_credential
        $checkStmt = $conn->prepare("SELECT id FROM users_credential WHERE username = ? OR email = ?");
        $checkStmt->execute([$username, $email]);
        $userId = $checkStmt->fetchColumn();

        $role = ($app['status'] === 'Approved') ? 'student' : 'applicant';

        if (!$userId) {
            $insertUser = $conn->prepare("INSERT INTO users_credential (username, email, password, role, account_status, created_at) VALUES (?, ?, ?, ?, 'active', COALESCE(?, CURRENT_TIMESTAMP)) RETURNING id");
            $insertUser->execute([$username, $email, $password, $role, $app['created_at'] ?? null]);
            $userId = $insertUser->fetchColumn();
        }

        // Upsert personal_profiles
        $checkProf = $conn->prepare("SELECT id FROM personal_profiles WHERE user_id = ?");
        $checkProf->execute([$userId]);
        $profId = $checkProf->fetchColumn();

        $domicile = !empty($app['domicile_address']) ? $app['domicile_address'] : ($app['address'] ?? null);
        $ktpAddress = !empty($app['ktp_address']) ? $app['ktp_address'] : $domicile;

        if (!$profId) {
            $insertProf = $conn->prepare("
                INSERT INTO personal_profiles 
                (user_id, nik, full_name, gender, pob, dob, religion, marital_status, job_status, phone, ktp_address, domicile_address, photo, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, COALESCE(?, CURRENT_TIMESTAMP))
            ");
            $insertProf->execute([
                $userId,
                $app['nik'] ?? null,
                $app['full_name'],
                $app['gender'] ?? null,
                $app['pob'] ?? null,
                !empty($app['dob']) ? $app['dob'] : null,
                $app['religion'] ?? null,
                $app['marital_status'] ?? 'Belum Menikah',
                $app['job_status'] ?? 'Belum Bekerja',
                $app['phone'] ?? null,
                $ktpAddress,
                $domicile,
                $app['pict'] ?? null,
                $app['created_at'] ?? null
            ]);
        }

        // Insert pmb_data
        $majorId = getMajorId($app['program_code'] ?? $app['prodi'] ?? 'A11');
        $highSchoolName = !empty($app['high_school_name']) ? $app['high_school_name'] : ($app['school_origin'] ?? null);
        $highSchoolMajor = !empty($app['high_school_major']) ? $app['high_school_major'] : (!empty($app['major']) ? $app['major'] : ($app['prodi'] ?? null));
        $highSchoolAddress = !empty($app['high_school_address']) ? $app['high_school_address'] : ($app['school_address'] ?? null);
        $highSchoolScore = !empty($app['high_school_score']) ? $app['high_school_score'] : (!empty($app['final_score']) ? (float)$app['final_score'] : null);
        $parentPhone = !empty($app['parent_phone']) ? $app['parent_phone'] : ($app['father_phone'] ?? null);

        $checkPmb = $conn->prepare("SELECT id FROM pmb_data WHERE user_id = ?");
        $checkPmb->execute([$userId]);
        if (!$checkPmb->fetchColumn()) {
            $insertPmb = $conn->prepare("
                INSERT INTO pmb_data 
                (id, user_id, major_id, nisn, mother_name, father_name, parent_phone, high_school_name, high_school_major, high_school_address, high_school_score, application_status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, COALESCE(?, CURRENT_TIMESTAMP))
            ");
            $insertPmb->execute([
                $app['id'],
                $userId,
                $majorId,
                $app['nisn'] ?? null,
                $app['mother_name'] ?? null,
                $app['father_name'] ?? null,
                $parentPhone,
                $highSchoolName,
                $highSchoolMajor,
                $highSchoolAddress,
                $highSchoolScore,
                $app['status'] ?? 'Draft',
                $app['created_at'] ?? null
            ]);
            $migratedApplicants++;
        }
    }
    // Update pmb_data sequence
    $conn->exec("SELECT setval('pmb_data_id_seq', (SELECT COALESCE(MAX(id), 1) FROM pmb_data))");
    echo " -> Applicants processed: $migratedApplicants entries in pmb_data.\n\n";

    // 4. Migrate legacy 'students' table to students_data
    echo "[4/8] Migrating legacy students to students_data...\n";
    $legacyStudents = $conn->query("SELECT * FROM students")->fetchAll();
    $migratedStudents = 0;
    foreach ($legacyStudents as $s) {
        $nim = trim($s['nim']);
        $email = trim($s['email'] ?? ($nim . '@mhs.dinus.ac.id'));

        // Check if student's user_credential exists
        $checkStmt = $conn->prepare("SELECT id FROM users_credential WHERE username = ? OR email = ?");
        $checkStmt->execute([$nim, $email]);
        $userId = $checkStmt->fetchColumn();

        if (!$userId) {
            $insertUser = $conn->prepare("INSERT INTO users_credential (username, email, password, role, account_status, created_at) VALUES (?, ?, ?, 'student', 'active', COALESCE(?, CURRENT_TIMESTAMP)) RETURNING id");
            $insertUser->execute([$nim, $email, $defaultPasswordHash, $s['created_at'] ?? null]);
            $userId = $insertUser->fetchColumn();
        } else {
            // Update role to student
            $conn->prepare("UPDATE users_credential SET role = 'student' WHERE id = ?")->execute([$userId]);
        }

        // Ensure personal profile
        $checkProf = $conn->prepare("SELECT id FROM personal_profiles WHERE user_id = ?");
        $checkProf->execute([$userId]);
        if (!$checkProf->fetchColumn()) {
            $domicile = !empty($s['domicile_address']) ? $s['domicile_address'] : ($s['address'] ?? null);
            $ktpAddress = !empty($s['ktp_address']) ? $s['ktp_address'] : $domicile;
            $insertProf = $conn->prepare("
                INSERT INTO personal_profiles 
                (user_id, nik, full_name, gender, pob, dob, religion, marital_status, job_status, phone, ktp_address, domicile_address, photo, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, COALESCE(?, CURRENT_TIMESTAMP))
            ");
            $insertProf->execute([
                $userId,
                $s['nik'] ?? null,
                $s['name'],
                $s['gender'] ?? null,
                $s['pob'] ?? null,
                !empty($s['dob']) ? $s['dob'] : null,
                $s['religion'] ?? null,
                $s['marital_status'] ?? 'Belum Menikah',
                $s['job_status'] ?? 'Belum Bekerja',
                $s['phone'] ?? null,
                $ktpAddress,
                $domicile,
                $s['pict'] ?? null,
                $s['created_at'] ?? null
            ]);
        }

        // Check pmb_data
        $checkPmb = $conn->prepare("SELECT id FROM pmb_data WHERE user_id = ?");
        $checkPmb->execute([$userId]);
        $pmbId = $checkPmb->fetchColumn();

        $majorId = getMajorId($s['prodi'] ?? substr($nim, 0, 3));

        if (!$pmbId) {
            // Create corresponding approved pmb record for legacy student
            $insertPmb = $conn->prepare("
                INSERT INTO pmb_data 
                (user_id, major_id, nisn, mother_name, father_name, parent_phone, application_status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'Approved', COALESCE(?, CURRENT_TIMESTAMP)) RETURNING id
            ");
            $parentPhone = !empty($s['parent_phone']) ? $s['parent_phone'] : ($s['father_phone'] ?? null);
            $insertPmb->execute([
                $userId,
                $majorId,
                $s['nisn'] ?? null,
                $s['mother_name'] ?? null,
                $s['father_name'] ?? null,
                $parentPhone,
                $s['created_at'] ?? null
            ]);
            $pmbId = $insertPmb->fetchColumn();
        }

        // Determine batch year from NIM (e.g. A12.2022.06867 -> 2022)
        $batchYear = 2026;
        $nimParts = explode('.', $nim);
        if (isset($nimParts[1]) && is_numeric($nimParts[1])) {
            $batchYear = (int)$nimParts[1];
        }

        // Insert students_data
        $checkStd = $conn->prepare("SELECT id FROM students_data WHERE nim = ?");
        $checkStd->execute([$nim]);
        if (!$checkStd->fetchColumn()) {
            $insertStd = $conn->prepare("
                INSERT INTO students_data 
                (id, user_id, pmb_id, major_id, nim, batch_year, current_semester, academic_status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 1, ?, COALESCE(?, CURRENT_TIMESTAMP))
            ");
            $status = !empty($s['status']) ? $s['status'] : 'Aktif';
            $insertStd->execute([
                $s['id'],
                $userId,
                $pmbId,
                $majorId,
                $nim,
                $batchYear,
                $status,
                $s['created_at'] ?? null
            ]);
            $migratedStudents++;
        }
    }
    // Update students_data sequence
    $conn->exec("SELECT setval('students_data_id_seq', (SELECT COALESCE(MAX(id), 1) FROM students_data))");
    echo " -> Students processed: $migratedStudents entries in students_data.\n\n";

    // 5. Migrate legacy 'lecturers' table
    echo "[5/8] Migrating legacy lecturers to lecturers_data...\n";
    $legacyLecturers = $conn->query("SELECT * FROM lecturers")->fetchAll();
    $migratedLecturers = 0;
    foreach ($legacyLecturers as $l) {
        $npp = trim($l['npp']);
        $email = strtolower(str_replace('.', '', $npp)) . '@dsn.dinus.ac.id';

        $checkStmt = $conn->prepare("SELECT id FROM users_credential WHERE username = ?");
        $checkStmt->execute([$npp]);
        $userId = $checkStmt->fetchColumn();

        if (!$userId) {
            $insertUser = $conn->prepare("INSERT INTO users_credential (username, email, password, role, account_status, created_at) VALUES (?, ?, ?, 'lecturer', 'active', COALESCE(?, CURRENT_TIMESTAMP)) RETURNING id");
            $insertUser->execute([$npp, $email, $defaultPasswordHash, $l['created_at'] ?? null]);
            $userId = $insertUser->fetchColumn();
        }

        // Profile
        $checkProf = $conn->prepare("SELECT id FROM personal_profiles WHERE user_id = ?");
        $checkProf->execute([$userId]);
        if (!$checkProf->fetchColumn()) {
            $conn->prepare("INSERT INTO personal_profiles (user_id, full_name, created_at) VALUES (?, ?, COALESCE(?, CURRENT_TIMESTAMP))")
                 ->execute([$userId, $l['name'], $l['created_at'] ?? null]);
        }

        $majorId = getMajorId($l['homebase'] ?? 'A11');
        $checkLect = $conn->prepare("SELECT id FROM lecturers_data WHERE npp = ?");
        $checkLect->execute([$npp]);
        if (!$checkLect->fetchColumn()) {
            $insertLect = $conn->prepare("INSERT INTO lecturers_data (id, user_id, major_id, npp, status, created_at) VALUES (?, ?, ?, ?, 'Aktif', COALESCE(?, CURRENT_TIMESTAMP))");
            $insertLect->execute([$l['id'], $userId, $majorId, $npp, $l['created_at'] ?? null]);
            $migratedLecturers++;
        }
    }
    $conn->exec("SELECT setval('lecturers_data_id_seq', (SELECT COALESCE(MAX(id), 1) FROM lecturers_data))");
    echo " -> Lecturers processed: $migratedLecturers entries in lecturers_data.\n\n";

    // 6. Migrate legacy 'courses' table
    echo "[6/8] Migrating legacy courses to courses_data...\n";
    $legacyCourses = $conn->query("SELECT * FROM courses")->fetchAll();
    $migratedCourses = 0;
    foreach ($legacyCourses as $c) {
        $code = trim($c['code']);
        $majorId = getMajorId(substr($code, 0, 3));
        $checkCourse = $conn->prepare("SELECT id FROM courses_data WHERE course_code = ?");
        $checkCourse->execute([$code]);
        if (!$checkCourse->fetchColumn()) {
            $insertCourse = $conn->prepare("
                INSERT INTO courses_data 
                (id, major_id, course_code, course_name, credits, semester, course_type, is_active, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, TRUE, COALESCE(?, CURRENT_TIMESTAMP))
            ");
            $insertCourse->execute([
                $c['id'],
                $majorId,
                $code,
                $c['name'],
                (int)($c['sks'] ?? 3),
                (int)($c['smt'] ?? 1),
                $c['type'] ?? 'Wajib',
                $c['created_at'] ?? null
            ]);
            $migratedCourses++;
        }
    }
    $conn->exec("SELECT setval('courses_data_id_seq', (SELECT COALESCE(MAX(id), 1) FROM courses_data))");
    echo " -> Courses processed: $migratedCourses entries in courses_data.\n\n";

    // 7. Verification Audit
    echo "[7/8] Verifying migration integrity across all 10 tables...\n";
    $stats = [
        'users_credential'   => $conn->query("SELECT COUNT(*) FROM users_credential")->fetchColumn(),
        'personal_profiles' => $conn->query("SELECT COUNT(*) FROM personal_profiles")->fetchColumn(),
        'majors_data'       => $conn->query("SELECT COUNT(*) FROM majors_data")->fetchColumn(),
        'courses_data'      => $conn->query("SELECT COUNT(*) FROM courses_data")->fetchColumn(),
        'pmb_data'          => $conn->query("SELECT COUNT(*) FROM pmb_data")->fetchColumn(),
        'students_data'     => $conn->query("SELECT COUNT(*) FROM students_data")->fetchColumn(),
        'lecturers_data'    => $conn->query("SELECT COUNT(*) FROM lecturers_data")->fetchColumn(),
        'students_bills_data'=> $conn->query("SELECT COUNT(*) FROM students_bills_data")->fetchColumn(),
        'students_krs_data' => $conn->query("SELECT COUNT(*) FROM students_krs_data")->fetchColumn(),
        'students_score_data'=> $conn->query("SELECT COUNT(*) FROM students_score_data")->fetchColumn(),
    ];

    foreach ($stats as $table => $count) {
        echo " -> $table: $count rows\n";
    }

    echo "\n========================================================\n";
    echo " [SUCCESS] Enterprise 10-Table Migration Completed 100%!\n";
    echo "========================================================\n";

} catch (Exception $e) {
    echo "\n[ERROR] Migration failed: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
