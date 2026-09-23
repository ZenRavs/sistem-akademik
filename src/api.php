<?php
try {
    include_once __DIR__ . '/../config/db.php';
    global $conn;
    if (!isset($_SESSION)) {
        session_start();
    }

    if (!$conn) {
        if (empty($_SESSION['error'])) {
            $_SESSION['error'] = 'Database connection failed. Please check your .env settings.';
        }
        if (isset($_GET['req']) && $_GET['req'] === 'userLogin') {
            header("location: ../index.php");
            exit;
        }
        echo json_encode(['status' => 'error', 'message' => $_SESSION['error']]);
        exit;
    }

    if (isset($_SESSION['user']) || isset($_GET['req']) || $_SERVER['REQUEST_METHOD'] == 'POST') {
        $requests = $_GET['req'] ?? '';

        // Randomizing output file to prevent duplicates
        function randomizer($length)
        {
            $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charsLength = strlen($chars);
            $randString = '';
            for ($i = 0; $i < $length; $i++) {
                $randString .= $chars[rand(0, $charsLength - 1)];
            }
            return $randString;
        }

        // Major ID helper
        if (!function_exists('getMajorIdFromCode')) {
            function getMajorIdFromCode($codeOrName)
            {
                global $conn;
                static $majorsCache = null;
                if ($majorsCache === null) {
                    $majorsCache = [];
                    $stmt = $conn->query("SELECT id, major_code FROM majors_data");
                    while ($row = $stmt->fetch()) {
                        $majorsCache[$row['major_code']] = (int)$row['id'];
                    }
                }
                $clean = strtoupper(trim((string)$codeOrName));
                if (isset($majorsCache[$clean])) return $majorsCache[$clean];
                if (strpos($clean, 'A11') !== false || strpos($clean, 'INFORMATIKA') !== false) return $majorsCache['A11'] ?? 1;
                if (strpos($clean, 'A12') !== false || strpos($clean, 'INFORMASI') !== false) return $majorsCache['A12'] ?? 2;
                if (strpos($clean, 'A14') !== false || strpos($clean, 'DKV') !== false || strpos($clean, 'VISUAL') !== false) return $majorsCache['A14'] ?? 3;
                if (strpos($clean, 'A15') !== false || strpos($clean, 'KOMUNIKASI') !== false) return $majorsCache['A15'] ?? 4;
                if (strpos($clean, 'A22') !== false || strpos($clean, 'D3') !== false) return $majorsCache['A22'] ?? 5;
                return $majorsCache['A11'] ?? 1;
            }
        }

        // Payment methods config loader
        if (!function_exists('getPaymentMethodsConfig')) {
            function getPaymentMethodsConfig()
            {
                $path = defined('CONFIG_PATH') ? CONFIG_PATH . '/payment_methods.json' : __DIR__ . '/../config/payment_methods.json';
                if (file_exists($path)) {
                    $json = file_get_contents($path);
                    return json_decode($json, true) ?: [];
                }
                return [];
            }
        }

        // Student Virtual Account generator helper
        if (!function_exists('generateStudentVaList')) {
            function generateStudentVaList($nim)
            {
                $config = getPaymentMethodsConfig();
                $vas = [];
                $cleanNim = trim((string)$nim);
                // Convert leading letter to digit: A->1, B->2, C->3, etc.
                $numericNim = preg_replace_callback('/^[A-Za-z]/', function($m) {
                    return ord(strtoupper($m[0])) - 64;
                }, $cleanNim);

                if (!empty($config['virtual_accounts'])) {
                    foreach ($config['virtual_accounts'] as $va) {
                        if (!empty($va['is_active'])) {
                            $vas[$va['name']] = [
                                'bank_name' => $va['name'],
                                'display_name' => $va['display_name'],
                                'bank_code' => $va['bank_code'],
                                'va_number' => $va['bank_code'] . '.' . $numericNim,
                                'badge_class' => $va['badge_class'] ?? 'bg-secondary'
                            ];
                        }
                    }
                }
                return $vas;
            }
        }

        // Tuition fees config loader (Pricelist FIK)
        if (!function_exists('getTuitionFeesConfig')) {
            function getTuitionFeesConfig()
            {
                $path = defined('CONFIG_PATH') ? CONFIG_PATH . '/tuition_fees.json' : __DIR__ . '/../config/tuition_fees.json';
                if (file_exists($path)) {
                    $json = file_get_contents($path);
                    return json_decode($json, true) ?: [];
                }
                return [];
            }
        }

        // Get UKT rate by major code (A11, A12, A14, A15, A22)
        if (!function_exists('getStudentUktRate')) {
            function getStudentUktRate($majorCode)
            {
                $config = getTuitionFeesConfig();
                $cleanCode = strtoupper(trim((string)$majorCode));
                if (!empty($config['rates_by_major'][$cleanCode])) {
                    return $config['rates_by_major'][$cleanCode];
                }
                return $config['rates_by_major']['default'] ?? [
                    'major_code' => 'DEFAULT',
                    'major_name' => 'Default FIK',
                    'degree' => 'S1',
                    'ukt_pokok' => 5000000
                ];
            }
        }

        // Duplicate check using PDO (supports both new and legacy table names)
        function duplicateCheck($table, $column, $value)
        {
            global $conn;
            $tableMap = [
                'students'   => 'students_data',
                'lecturers'  => 'lecturers_data',
                'courses'    => 'courses_data',
                'users'      => 'users_credential',
                'applicants' => 'pmb_data'
            ];
            $targetTable = $tableMap[$table] ?? $table;

            $columnMap = [
                'courses_data' => ['code' => 'course_code'],
            ];
            $targetCol = $columnMap[$targetTable][$column] ?? $column;

            $allowedTables = ['students_data', 'lecturers_data', 'courses_data', 'users_credential', 'personal_profiles', 'pmb_data', 'krs_offers'];
            if (!in_array($targetTable, $allowedTables)) {
                return false;
            }
            $stmt = $conn->prepare("SELECT COUNT(*) FROM $targetTable WHERE $targetCol = ?");
            $stmt->execute([$value]);
            return ($stmt->fetchColumn() > 0);
        }

        if (!function_exists('formatProdiDisplay')) {
            function formatProdiDisplay($prodi, $nim, $programCode = '')
            {
                $cleanProdi = trim((string)$prodi);
                if (!empty($cleanProdi) && !in_array(strtoupper($cleanProdi), ['IPA', 'IPS', 'TKJ', 'RPL', 'BAHASA'])) {
                    if (strtoupper($cleanProdi) === 'SI') return 'Sistem Informasi (S1)';
                    if (strtoupper($cleanProdi) === 'TI') return 'Teknik Informatika (S1)';
                    if (strtoupper($cleanProdi) === 'DKV') return 'Desain Komunikasi Visual (S1)';
                    return $cleanProdi;
                }
                $prefix = !empty($programCode) ? strtoupper(trim($programCode)) : strtoupper(substr(trim((string)$nim), 0, 3));
                if ($prefix === 'A11') return 'Teknik Informatika (S1)';
                if ($prefix === 'A12') return 'Sistem Informasi (S1)';
                if ($prefix === 'A14') return 'Desain Komunikasi Visual (S1)';
                if ($prefix === 'A15') return 'Ilmu Komunikasi (S1)';
                if ($prefix === 'A22') return 'Teknik Informatika (D3)';
                return !empty($cleanProdi) ? $cleanProdi : '-';
            }
        }

        switch ($requests) {
            //
            // Students Management (students_data + personal_profiles + pmb_data)
            //
            case 'insertStudent':
                $_SESSION['crud']['message'] = 'Manual student registration is disabled. Please use PMB Online verification.';
                $response['status'] = 'error';
                $response['message'] = 'Manual student registration is disabled. Please use PMB Online verification.';
                echo json_encode($response);
                break;

            case 'updateStudent':
                try {
                    $id = (int)($_POST['id'] ?? 0);
                    $name = trim($_POST['name'] ?? '');
                    $email = trim($_POST['email'] ?? '');
                    $gender = trim($_POST['gender'] ?? '');
                    $pob = trim($_POST['pob'] ?? '');
                    $dob = !empty($_POST['dob']) ? trim($_POST['dob']) : null;
                    $religion = trim($_POST['religion'] ?? '');
                    $prodi = trim($_POST['prodi'] ?? '');
                    $major = trim($_POST['major'] ?? $_POST['school_major'] ?? '');
                    $nisn = trim($_POST['nisn'] ?? '');
                    $motherName = trim($_POST['mother_name'] ?? '');
                    $fatherName = trim($_POST['father_name'] ?? '');
                    $parentPhone = trim($_POST['parent_phone'] ?? $_POST['father_phone'] ?? '');
                    $status = trim($_POST['status'] ?? 'Aktif');
                    $ktpAddress = trim($_POST['ktp_address'] ?? '');
                    $maritalStatus = trim($_POST['marital_status'] ?? '');
                    $jobStatus = trim($_POST['job_status'] ?? '');
                    $phone = trim($_POST['phone'] ?? '');
                    $address = trim($_POST['address'] ?? '');
                    $schoolOrigin = trim($_POST['school_origin'] ?? '');
                    $schoolAddress = trim($_POST['school_address'] ?? '');
                    $finalScore = !empty($_POST['final_score']) ? (float)$_POST['final_score'] : null;
                    $page = $_POST['page'] ?? 1;

                    // Fetch student record
                    $getCurMhsStmt = $conn->prepare("
                        SELECT s.id, s.nim, s.user_id, s.pmb_id, p.photo AS pict, u.email AS old_email
                        FROM students_data s
                        JOIN users_credential u ON s.user_id = u.id
                        JOIN personal_profiles p ON p.user_id = u.id
                        WHERE s.id = ?
                    ");
                    $getCurMhsStmt->execute([$id]);
                    $row = $getCurMhsStmt->fetch();
                    if (!$row) {
                        throw new Exception("Student record not found.");
                    }
                    $userId = $row['user_id'];
                    $pmbId = $row['pmb_id'];
                    $oldPict = $row['pict'] ?? '';
                    $currentNim = $row['nim'] ?? '';

                    $filename = $oldPict;
                    if (!empty($_FILES['pict']['name'])) {
                        $targetdir = defined('UPLOAD_PATH') ? UPLOAD_PATH . '/' : __DIR__ . '/../public/uploads/user_photos/';
                        if (!is_dir($targetdir)) {
                            mkdir($targetdir, 0777, true);
                        }
                        if ($oldPict) {
                            if (file_exists($targetdir . $oldPict) && is_file($targetdir . $oldPict)) {
                                @unlink($targetdir . $oldPict);
                            }
                            $legacyDir = __DIR__ . '/../public/uploads/userpict/';
                            if (file_exists($legacyDir . $oldPict) && is_file($legacyDir . $oldPict)) {
                                @unlink($legacyDir . $oldPict);
                            }
                        }
                        $file = $targetdir . basename($_FILES['pict']['name']);
                        $filetype = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        $filename = substr(str_replace(' ', '', $name), 0, 6) . randomizer(12) . "." . $filetype;
                        move_uploaded_file($_FILES['pict']['tmp_name'], $targetdir . $filename);
                    }

                    // 1. Update personal_profiles (Legal Identity Data)
                    $updateProfileStmt = $conn->prepare("
                        UPDATE personal_profiles SET
                            full_name = ?, nik = ?, gender = ?, pob = ?, dob = ?, religion = ?,
                            marital_status = ?, job_status = ?, phone = ?, ktp_address = ?,
                            domicile_address = ?, photo = ?
                        WHERE user_id = ?
                    ");
                    $nik = trim($_POST['nik'] ?? '');
                    $updateProfileStmt->execute([
                        $name, $nik, $gender, $pob, $dob, $religion,
                        $maritalStatus, $jobStatus, $phone, $ktpAddress,
                        $address, $filename, $userId
                    ]);

                    // 2. Update users_credential (Email)
                    if (!empty($email)) {
                        $updateUserStmt = $conn->prepare("UPDATE users_credential SET email = ? WHERE id = ?");
                        $updateUserStmt->execute([$email, $userId]);
                    }

                    // 3. Update students_data (Academic Status)
                    $updateStudentStmt = $conn->prepare("UPDATE students_data SET academic_status = ? WHERE id = ?");
                    $updateStudentStmt->execute([$status, $id]);

                    // 4. Update pmb_data (Parents & Origin School Data)
                    if ($pmbId) {
                        $updatePmbStmt = $conn->prepare("
                            UPDATE pmb_data SET
                                nisn = ?, mother_name = ?, father_name = ?, parent_phone = ?,
                                high_school_name = ?, high_school_major = ?, high_school_address = ?, high_school_score = ?
                            WHERE id = ?
                        ");
                        $updatePmbStmt->execute([
                            $nisn, $motherName, $fatherName, $parentPhone,
                            $schoolOrigin, $major, $schoolAddress, $finalScore, $pmbId
                        ]);
                    }

                    $_SESSION['crud']['message'] = '<b>' . htmlspecialchars($currentNim) . '</b> updated successfully.';
                    $response['status'] = 'success';
                    $response['page'] = $page;
                } catch (Exception $e) {
                    $_SESSION['crud']['message'] = 'Update Error! err: ' . $e->getMessage();
                    $response['message'] = $e->getMessage();
                    $response['status'] = 'error';
                }
                echo json_encode($response);
                break;

            case 'deleteStudent':
                try {
                    $id = (int)($_POST['id'] ?? 0);
                    $stmt = $conn->prepare("
                        SELECT s.id, s.nim, s.user_id, p.photo AS pict 
                        FROM students_data s
                        JOIN personal_profiles p ON p.user_id = s.user_id
                        WHERE s.id = ?
                    ");
                    $stmt->execute([$id]);
                    $row = $stmt->fetch();
                    if ($row) {
                        $userId = $row['user_id'];
                        if (!empty($row['pict'])) {
                            $targetdir = defined('UPLOAD_PATH') ? UPLOAD_PATH . '/' : __DIR__ . '/../public/uploads/user_photos/';
                            $filePath = $targetdir . $row['pict'];
                            if (file_exists($filePath) && is_file($filePath)) {
                                @unlink($filePath);
                            }
                            $legacyFilePath = __DIR__ . '/../public/uploads/userpict/' . $row['pict'];
                            if (file_exists($legacyFilePath) && is_file($legacyFilePath)) {
                                @unlink($legacyFilePath);
                            }
                        }

                        // Delete from students_data (cascades krs & score)
                        $deleteStmt = $conn->prepare("DELETE FROM students_data WHERE id = ?");
                        $deleteStmt->execute([$id]);

                        // Delete login credential
                        if ($userId) {
                            $deleteUserStmt = $conn->prepare("DELETE FROM users_credential WHERE id = ?");
                            $deleteUserStmt->execute([$userId]);
                        }

                        $response['status'] = 'success';
                        $response['message'] = 'Data mahasiswa dan akun user terikat berhasil dihapus.';
                    } else {
                        $response['status'] = 'error';
                        $response['message'] = 'Student record not found.';
                    }
                } catch (Exception $e) {
                    $response['status'] = 'error';
                    $response['message'] = "An error occurred: " . $e->getMessage();
                }
                echo json_encode($response);
                break;

            case 'getStudentDetail':
                try {
                    $id = (int)($_POST['id'] ?? 0);
                    $stmt = $conn->prepare("
                        SELECT s.id, s.nim, s.batch_year, s.current_semester, s.academic_status AS status, s.created_at,
                               p.full_name AS name, p.nik, p.gender, p.pob, p.dob, p.religion, p.marital_status, p.job_status,
                               p.phone, p.emerg_phone, p.ktp_address, p.domicile_address AS address, p.photo AS pict,
                               u.email, u.username,
                               m.major_code, m.major_name, m.degree,
                               pmb.id AS pmb_id, pmb.nisn, pmb.mother_name, pmb.father_name, pmb.parent_phone,
                               pmb.high_school_name AS school_origin, pmb.high_school_major AS major,
                               pmb.high_school_address AS school_address, pmb.high_school_score AS final_score
                        FROM students_data s
                        JOIN users_credential u ON s.user_id = u.id
                        JOIN personal_profiles p ON p.user_id = u.id
                        JOIN majors_data m ON s.major_id = m.id
                        LEFT JOIN pmb_data pmb ON s.pmb_id = pmb.id
                        WHERE s.id = ?
                    ");
                    $stmt->execute([$id]);
                    $student = $stmt->fetch();
                    if ($student) {
                        $pictName = $student['pict'] ?? '';
                        $defaultPict = 'https://cdn-icons-png.freepik.com/512/3875/3875148.png?ga=GA1.1.599436757.1735230785';
                        $imgSrc = $defaultPict;
                        if ($pictName) {
                            $targetdir = defined('UPLOAD_PATH') ? UPLOAD_PATH . '/' : __DIR__ . '/../public/uploads/user_photos/';
                            $uploadUrl = defined('UPLOAD_URL') ? UPLOAD_URL : './public/uploads/user_photos/';
                            if (file_exists($targetdir . $pictName)) {
                                $imgSrc = $uploadUrl . $pictName;
                            }
                        }
                        $student['img_src'] = $imgSrc;
                        $student['created_at_formatted'] = !empty($student['created_at']) ? date('d M Y, H:i', strtotime($student['created_at'])) . ' WIB' : '-';
                        $student['edited_at_formatted'] = !empty($student['edited_at']) ? date('d M Y, H:i', strtotime($student['edited_at'])) . ' WIB' : 'Belum pernah diubah';
                        $student['author_display'] = 'System / TU';
                        $student['prodi_display'] = $student['major_name'] . ' (' . $student['degree'] . ')';
                        $student['status'] = !empty($student['status']) ? $student['status'] : 'Aktif';
                        $student['major'] = $student['major'] ?? '';
                        $student['parent_phone'] = $student['parent_phone'] ?? '';

                        $response['status'] = 'success';
                        $response['data'] = $student;
                    } else {
                        $response['status'] = 'error';
                        $response['message'] = 'Student not found.';
                    }
                } catch (Exception $e) {
                    $response['status'] = 'error';
                    $response['message'] = $e->getMessage();
                }
                echo json_encode($response);
                break;

            case 'searchStudent':
                try {
                    $searchInput = trim($_POST['searchInput'] ?? '');
                    $searchCategory = $_POST['searchCategory'] ?? 'name';

                    $whereClause = "(p.full_name ILIKE ? OR s.nim ILIKE ? OR u.email ILIKE ?)";
                    $params = ['%' . $searchInput . '%', '%' . $searchInput . '%', '%' . $searchInput . '%'];

                    if ($searchCategory === 'nim') {
                        $whereClause = "s.nim ILIKE ?";
                        $params = ['%' . $searchInput . '%'];
                    } elseif ($searchCategory === 'email') {
                        $whereClause = "u.email ILIKE ?";
                        $params = ['%' . $searchInput . '%'];
                    } elseif ($searchCategory === 'name') {
                        $whereClause = "p.full_name ILIKE ?";
                        $params = ['%' . $searchInput . '%'];
                    }

                    $sql = "
                        SELECT s.id, s.nim, s.academic_status AS status,
                               p.full_name AS name, p.phone,
                               u.email,
                               m.major_code, m.major_name, m.degree
                        FROM students_data s
                        JOIN users_credential u ON s.user_id = u.id
                        JOIN personal_profiles p ON p.user_id = u.id
                        JOIN majors_data m ON s.major_id = m.id
                        WHERE $whereClause
                        ORDER BY s.id DESC
                    ";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute($params);
                    $rows = $stmt->fetchAll();

                    $html = "";
                    $i = 0;
                    if (count($rows) > 0) {
                        foreach ($rows as $row) {
                            $prodiDisplay = $row['major_name'] . ' (' . $row['degree'] . ')';
                            $status = !empty($row['status']) ? $row['status'] : 'Aktif';
                            $statusBadge = '<span class="badge bg-success">Aktif</span>';
                            if ($status === 'Cuti') {
                                $statusBadge = '<span class="badge bg-warning text-dark">Cuti</span>';
                            } elseif ($status === 'Lulus') {
                                $statusBadge = '<span class="badge bg-info text-dark">Lulus</span>';
                            } elseif ($status === 'Non-Aktif' || $status === 'Drop Out') {
                                $statusBadge = '<span class="badge bg-danger">Non-Aktif</span>';
                            }

                            $phoneText = !empty($row['phone']) ? htmlspecialchars($row['phone']) : '-';

                            $html .= '<tr>';
                            $html .= '<td class="text-center fw-bold text-secondary">' . ($i + 1) . '</td>';
                            $html .= '<td class="font-monospace">' . htmlspecialchars($row['nim']) . '</td>';
                            $html .= '<td class="fw-semibold">' . htmlspecialchars($row['name']) . '</td>';
                            $html .= '<td>' . htmlspecialchars($prodiDisplay) . '</td>';
                            $html .= '<td class="text-center">' . $statusBadge . '</td>';
                            $html .= '<td><div>' . htmlspecialchars($row['email'] ?? '-') . '</div><small class="text-secondary">' . $phoneText . '</small></td>';
                            $html .= '<td class="text-center align-middle text-nowrap">';
                            $html .= '<div class="d-inline-flex gap-1">';
                            $html .= '<button type="button" class="btn btn-sm btn-info text-white viewStudentModalBtn" data-id="' . $row['id'] . '" title="Lihat Detail Mahasiswa">Lihat</button>';
                            $html .= '<a class="btn btn-sm btn-success" id="krs_btn" href="?view=students_krs_items&student_id=' . $row['id'] . '" title="KRS Mahasiswa">KRS</a>';
                            $html .= '<a class="btn btn-sm btn-warning text-dark fw-semibold" href="?view=students_bills&search=' . urlencode($row['nim']) . '" title="Tagihan UKT Mahasiswa">UKT</a>';
                            $html .= '</div>';
                            $html .= '</td>';
                            $html .= '</tr>';
                            $i++;
                        }
                    } else {
                        $html .= "<tr><td colspan='7' class='text-center py-4 text-muted'>Tidak ada data mahasiswa ditemukan.</td></tr>";
                    }
                    $response['html'] = $html;
                    $response['status'] = 'success';
                } catch (Exception $e) {
                    $response['message'] = "An error occurred: " . $e->getMessage();
                    $response['status'] = 'error';
                }
                echo json_encode($response);
                break;

            case 'fetchStudents':
                $page = (int)($_POST['page'] ?? 1);
                $maxRow = (int)($_POST['maxRow'] ?? 10);
                $offset = ($page - 1) * $maxRow;

                $sql = "
                    SELECT s.id, s.nim, s.academic_status AS status,
                           p.full_name AS name, p.phone,
                           u.email,
                           m.major_code, m.major_name, m.degree
                    FROM students_data s
                    JOIN users_credential u ON s.user_id = u.id
                    JOIN personal_profiles p ON p.user_id = u.id
                    JOIN majors_data m ON s.major_id = m.id
                    ORDER BY s.id DESC
                    LIMIT ? OFFSET ?
                ";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$maxRow, $offset]);
                $rows = $stmt->fetchAll();

                $totalRows = (int)$conn->query("SELECT COUNT(*) FROM students_data")->fetchColumn();
                $pages = ceil($totalRows / $maxRow);

                $html = "";
                if (count($rows) > 0) {
                    $i = ($page - 1) * $maxRow;
                    foreach ($rows as $row) {
                        $prodiDisplay = $row['major_name'] . ' (' . $row['degree'] . ')';
                        $status = !empty($row['status']) ? $row['status'] : 'Aktif';
                        $statusBadge = '<span class="badge bg-success">Aktif</span>';
                        if ($status === 'Cuti') {
                            $statusBadge = '<span class="badge bg-warning text-dark">Cuti</span>';
                        } elseif ($status === 'Lulus') {
                            $statusBadge = '<span class="badge bg-info text-dark">Lulus</span>';
                        } elseif ($status === 'Non-Aktif' || $status === 'Drop Out') {
                            $statusBadge = '<span class="badge bg-danger">Non-Aktif</span>';
                        }

                        $phoneText = !empty($row['phone']) ? htmlspecialchars($row['phone']) : '-';

                        $html .= '<tr>';
                        $html .= '<td class="text-center fw-bold text-secondary">' . ($i + 1) . '</td>';
                        $html .= '<td class="font-monospace">' . htmlspecialchars($row['nim']) . '</td>';
                        $html .= '<td class="fw-semibold">' . htmlspecialchars($row['name']) . '</td>';
                        $html .= '<td>' . htmlspecialchars($prodiDisplay) . '</td>';
                        $html .= '<td class="text-center">' . $statusBadge . '</td>';
                        $html .= '<td><div>' . htmlspecialchars($row['email'] ?? '-') . '</div><small class="text-secondary">' . $phoneText . '</small></td>';
                        $html .= '<td class="text-center align-middle text-nowrap">';
                        $html .= '<div class="d-inline-flex gap-1">';
                        $html .= '<button type="button" class="btn btn-sm btn-info text-white viewStudentModalBtn" data-id="' . $row['id'] . '" title="Lihat Detail Mahasiswa">Lihat</button>';
                        $html .= '<a class="btn btn-sm btn-success" id="krs_btn" href="?view=students_krs_items&student_id=' . $row['id'] . '" title="KRS Mahasiswa">KRS</a>';
                        $html .= '<a class="btn btn-sm btn-warning text-dark fw-semibold" href="?view=students_bills&search=' . urlencode($row['nim']) . '" title="Tagihan UKT Mahasiswa">UKT</a>';
                        $html .= '</div>';
                        $html .= '</td>';
                        $html .= '</tr>';
                        $i++;
                    }
                    $response['html'] = $html;
                    $response['pages'] = $pages;
                } else {
                    $html .= "<tr><td colspan='7' class='text-center py-4 text-muted'>Tidak ada data mahasiswa ditemukan.</td></tr>";
                    $response['html'] = $html;
                    $response['pages'] = 1;
                }
                echo json_encode($response);
                break;

            //
            // Lecturers Management (lecturers_data + personal_profiles + majors_data)
            //
            case 'insertLecturer':
                try {
                    $npp1 = $_POST['npp1'] ?? '';
                    $npp2 = $_POST['npp2'] ?? '';
                    $npp3 = $_POST['npp3'] ?? '';
                    $npp = $npp1 . '.' . $npp2 . '.' . $npp3;
                    $name = trim($_POST['name'] ?? '');
                    $homebase = trim($_POST['homebase'] ?? 'A11');
                    $majorId = getMajorIdFromCode($homebase);
                    $email = strtolower(str_replace('.', '', $npp)) . '@dsn.dinus.ac.id';
                    $defaultHash = '$2y$10$O0.gCGhpRQmZuGDYEB5euudSCUqW1XyrU6AHTIFO7IbrqYA7RdzWS';

                    // 1. Create users_credential
                    $insertUser = $conn->prepare("INSERT INTO users_credential (username, email, password, role, status) VALUES (?, ?, ?, 'lecturer', 1) RETURNING id");
                    $insertUser->execute([$npp, $email, $defaultHash]);
                    $userId = $insertUser->fetchColumn();

                    // 2. Create personal_profiles
                    $conn->prepare("INSERT INTO personal_profiles (user_id, full_name) VALUES (?, ?)")->execute([$userId, $name]);

                    // 3. Create lecturers_data
                    $stmt = $conn->prepare("INSERT INTO lecturers_data (user_id, major_id, npp, status) VALUES (?, ?, ?, 'Aktif')");
                    if ($stmt->execute([$userId, $majorId, $npp])) {
                        $_SESSION['crud']['message'] = 'Lecturer: <b>' . htmlspecialchars($name) . '</b> is registered successfully.';
                        $response['status'] = 'success';
                    } else {
                        $_SESSION['crud']['message'] = 'An error occurred while adding lecturer.';
                        $response['status'] = 'error';
                    }
                } catch (Exception $e) {
                    $_SESSION['crud']['message'] = 'An error occurred! err: ' . $e->getMessage();
                    $response['message'] = 'error: ' . $e->getMessage();
                    $response['status'] = 'error';
                }
                echo json_encode($response);
                break;

            case 'updateLecturer':
                try {
                    $id = (int)($_POST['id'] ?? 0);
                    $npp = trim($_GET['npp'] ?? $_POST['npp'] ?? '');
                    $name = trim($_POST['name'] ?? '');
                    $homebase = trim($_POST['homebase'] ?? 'A11');
                    $majorId = getMajorIdFromCode($homebase);
                    $statusNum = (int)($_POST['status'] ?? 1);
                    $statusStr = ($statusNum == 0) ? 'Nonaktif' : 'Aktif';
                    $page = $_POST['page'] ?? 1;

                    // Get user_id
                    $getLect = $conn->prepare("SELECT user_id FROM lecturers_data WHERE id = ?");
                    $getLect->execute([$id]);
                    $userId = $getLect->fetchColumn();

                    if ($userId) {
                        $conn->prepare("UPDATE personal_profiles SET full_name = ? WHERE user_id = ?")->execute([$name, $userId]);
                    }

                    $stmt = $conn->prepare("UPDATE lecturers_data SET major_id = ?, status = ? WHERE id = ?");
                    if ($stmt->execute([$majorId, $statusStr, $id])) {
                        $_SESSION['crud']['message'] = '<b>' . htmlspecialchars($npp) . '</b> updated successfully.';
                        $response['status'] = 'success';
                        $response['page'] = $page;
                    } else {
                        $_SESSION['crud']['message'] = 'Update Error!';
                        $response['status'] = 'error';
                    }
                } catch (Exception $e) {
                    $_SESSION['crud']['message'] = 'Update Error! err: ' . $e->getMessage();
                    $response['status'] = 'error';
                }
                echo json_encode($response);
                break;

            case 'deleteLecturer':
                try {
                    $id = (int)($_POST['id'] ?? 0);
                    $getLect = $conn->prepare("SELECT user_id FROM lecturers_data WHERE id = ?");
                    $getLect->execute([$id]);
                    $userId = $getLect->fetchColumn();

                    $stmt = $conn->prepare("DELETE FROM lecturers_data WHERE id = ?");
                    if ($stmt->execute([$id])) {
                        if ($userId) {
                            $conn->prepare("DELETE FROM users_credential WHERE id = ?")->execute([$userId]);
                        }
                        $response['status'] = 'success';
                        $response['message'] = 'Data deleted successfully.';
                    } else {
                        $response['status'] = 'error';
                        $response['message'] = "Error deleting lecturer.";
                    }
                } catch (Exception $e) {
                    $response['status'] = 'error';
                    $response['message'] = "An error occurred: " . $e->getMessage();
                }
                echo json_encode($response);
                break;

            case 'fetchLecturers':
                $page = (int)($_POST['page'] ?? 1);
                $maxRow = (int)($_POST['maxRow'] ?? 10);
                $offset = ($page - 1) * $maxRow;

                $sql = "
                    SELECT l.id, l.npp, l.status, l.created_at,
                           p.full_name AS name,
                           m.major_code AS homebase, m.major_name
                    FROM lecturers_data l
                    JOIN users_credential u ON l.user_id = u.id
                    JOIN personal_profiles p ON p.user_id = u.id
                    JOIN majors_data m ON l.major_id = m.id
                    ORDER BY l.id DESC
                    LIMIT ? OFFSET ?
                ";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$maxRow, $offset]);
                $rows = $stmt->fetchAll();

                $totalRows = (int)$conn->query("SELECT COUNT(*) FROM lecturers_data")->fetchColumn();
                $pages = ceil($totalRows / $maxRow);

                $html = "";
                if (count($rows) > 0) {
                    $i = ($page - 1) * $maxRow;
                    foreach ($rows as $row) {
                        $isActive = ($row['status'] === 'Aktif');
                        $html .= '<tr class="align-middle">';
                        $html .= '<td class="text-center">' . ($i + 1) . "</td>";
                        $html .= "<td>" . htmlspecialchars($row['npp']) . "</td>";
                        $html .= "<td>" . htmlspecialchars($row['name']) . "</td>";
                        $html .= "<td class='text-center'>" . htmlspecialchars($row['homebase']) . "</td>";
                        $html .= "<td class='text-center'><span class='badge " . (!$isActive ? 'bg-danger' : 'bg-primary') . "'>" . ($isActive ? 'Active' : 'Inactive') . "</span></td>";
                        $html .= "<td>" . ($row['created_at'] ?? '-') . "</td>";
                        $html .= "<td>" . ($row['created_at'] ?? '-') . "</td>";
                        $html .= "<td>System / Admin</td>";
                        $html .= '<td class="text-center align-middle">';
                        $html .= '<a class="btn btn-outline-info me-1" href="?view=edit_lecturer&req=update&id=' . $row['id'] . '&npp=' . $row['npp'] . '&name=' . urlencode($row['name']) . '&hb=' . $row['homebase'] . '&st=' . ($isActive ? 1 : 0) . '&page=' . $page . '">Edit</a>';
                        $html .= '<button class="btn btn-outline-danger" id="deleteBtn" data-id="' . $row['id'] . '">&times;</button></td></tr>';
                        $i++;
                    }
                    $response['html'] = $html;
                    $response['pages'] = $pages;
                } else {
                    $html .= "<tr><td colspan='9' class='text-center'>No records found.</td></tr>";
                    $response['html'] = $html;
                }
                echo json_encode($response);
                break;

            //
            // Courses Management (courses_data + majors_data)
            //
            case 'insertCourse':
                try {
                    $courseCode1 = $_POST['courseCode1'] ?? '';
                    $courseCode2 = $_POST['courseCode2'] ?? '';
                    $courseCode = $courseCode1 . '.' . $courseCode2;
                    $name = trim($_POST['name'] ?? '');
                    $cType = trim($_POST['cType'] ?? 'Wajib');
                    $sks = (int)($_POST['sks'] ?? 3);
                    $smt = (int)($_POST['smt'] ?? 1);
                    $majorId = getMajorIdFromCode($courseCode1);

                    $stmt = $conn->prepare("INSERT INTO courses_data (major_id, course_code, course_name, course_type, credits, semester, is_active) VALUES (?, ?, ?, ?, ?, ?, TRUE)");
                    if ($stmt->execute([$majorId, $courseCode, $name, $cType, $sks, $smt])) {
                        $_SESSION['crud']['message'] = 'Course: <b>' . htmlspecialchars($name) . '</b> is added successfully.';
                        $response['status'] = 'success';
                    } else {
                        $_SESSION['crud']['message'] = 'Error adding course.';
                        $response['status'] = 'error';
                    }
                } catch (Exception $e) {
                    $_SESSION['crud']['message'] = 'An error occurred! err: ' . $e->getMessage();
                    $response['status'] = 'error';
                }
                echo json_encode($response);
                break;

            case 'updateCourse':
                try {
                    $id = (int)($_POST['id'] ?? 0);
                    $name = trim($_POST['name'] ?? '');
                    $type = trim($_POST['cType'] ?? 'Wajib');
                    $sks = (int)($_POST['sks'] ?? 3);
                    $smt = (int)($_POST['smt'] ?? 1);
                    $page = $_POST['page'] ?? 1;

                    $stmt = $conn->prepare("UPDATE courses_data SET course_name = ?, course_type = ?, credits = ?, semester = ? WHERE id = ?");
                    if ($stmt->execute([$name, $type, $sks, $smt, $id])) {
                        $_SESSION['crud']['message'] = 'Course updated successfully.';
                        $response['status'] = 'success';
                        $response['page'] = $page;
                    } else {
                        $_SESSION['crud']['message'] = 'Update Error!';
                        $response['status'] = 'error';
                    }
                } catch (Exception $e) {
                    $_SESSION['crud']['message'] = 'Update Error! err: ' . $e->getMessage();
                    $response['status'] = 'error';
                }
                echo json_encode($response);
                break;

            case 'deleteCourse':
                try {
                    $id = (int)($_POST['id'] ?? 0);
                    $stmt = $conn->prepare("DELETE FROM courses_data WHERE id = ?");
                    if ($stmt->execute([$id])) {
                        $response['status'] = 'success';
                        $response['message'] = 'Course deleted successfully.';
                    } else {
                        $response['status'] = 'error';
                        $response['message'] = "Error deleting course.";
                    }
                } catch (Exception $e) {
                    $response['status'] = 'error';
                    $response['message'] = "An error occurred: " . $e->getMessage();
                }
                echo json_encode($response);
                break;

            case 'fetchCourses':
                $page = (int)($_POST['page'] ?? 1);
                $maxRow = (int)($_POST['maxRow'] ?? 10);
                $offset = ($page - 1) * $maxRow;

                $sql = "
                    SELECT c.id, c.course_code AS code, c.course_name AS name, c.course_type AS type,
                           c.credits AS sks, c.semester AS smt, m.major_code
                    FROM courses_data c
                    JOIN majors_data m ON c.major_id = m.id
                    ORDER BY c.id DESC
                    LIMIT ? OFFSET ?
                ";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$maxRow, $offset]);
                $rows = $stmt->fetchAll();

                $totalRows = (int)$conn->query("SELECT COUNT(*) FROM courses_data")->fetchColumn();
                $pages = ceil($totalRows / $maxRow);

                $html = "";
                if (count($rows) > 0) {
                    $i = ($page - 1) * $maxRow;
                    foreach ($rows as $row) {
                        $courseCode = $row['code'];
                        $name = $row['name'];
                        $type = $row['type'];
                        $sks = $row['sks'];
                        $smt = $row['smt'];
                        $html .= '<tr class="align-middle">';
                        $html .= '<td class="text-center">' . ($i + 1) . '</td>';
                        $html .= '<td>' . htmlspecialchars($courseCode) . '</td>';
                        $html .= '<td>' . htmlspecialchars($name) . '</td>';
                        $html .= '<td>' . htmlspecialchars($type) . '</td>';
                        $html .= '<td>' . htmlspecialchars($sks) . '</td>';
                        $html .= '<td>' . htmlspecialchars($smt) . '</td>';
                        $html .= '<td class="text-center align-middle">';
                        $html .= '<a class="btn btn-outline-info me-1" href="?view=edit_course&req=update&id=' . $row['id'] . '&cc=' . $courseCode . '&cname=' . urlencode($name) . '&cType=' . $type . '&SKS=' . $sks . '&SMT=' . $smt . '&page=' . $page . '">Edit</a>';
                        $html .= '<button class="btn btn-outline-danger" id="deleteBtn" data-id="' . $row['id'] . '">&times;</button></td></tr>';
                        $i++;
                    }
                    $response['html'] = $html;
                    $response['pages'] = $pages;
                } else {
                    $html .= "<tr><td colspan='7' class='text-center'>No records found.</td></tr>";
                    $response['html'] = $html;
                }
                echo json_encode($response);
                break;

            //
            // KRS offers (krs_offers)
            //
            case 'insertOffer':
                $sched2 = $_GET['sched2isHidden'] ?? 'true';
                $course = $_POST['courses'] ?? '';
                $lecturer = $_POST['lecturers'] ?? '';
                $classGroup = $_POST['classGroup'] ?? '';
                
                $daySched1 = $_POST['daySched1'] ?? '';
                $hourSched1 = $_POST['hourSched1'] ?? '';
                $floorSched1 = $_POST['floorSched1'] ?? '';
                $roomSched1 = $_POST['roomSched1'] ?? '';
                $classRoom1 = $floorSched1 . '.' . $roomSched1;

                if ($sched2 === 'true') {
                    $daySched2 = null;
                    $hourSched2 = null;
                    $classRoom2 = null;
                } else {
                    $daySched2 = $_POST['daySched2'] ?? '';
                    $hourSched2 = $_POST['hourSched2'] ?? '';
                    $floorSched2 = $_POST['floorSched2'] ?? '';
                    $roomSched2 = $_POST['roomSched2'] ?? '';
                    $classRoom2 = $floorSched2 . '.' . $roomSched2;
                }
                $stmt = $conn->prepare("INSERT INTO krs_offers (course, lecturer, class_group, days_sched1, hours_sched1, class_room1, days_sched2, hours_sched2, class_room2) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$course, $lecturer, $classGroup, $daySched1, $hourSched1, $classRoom1, $daySched2, $hourSched2, $classRoom2])) {
                    $response['status'] = 'success';
                    $response['message'] = 'Offer added successfully.';
                } else {
                    $response['status'] = 'error';
                    $response['message'] = "Error adding offer.";
                }
                echo json_encode($response);
                break;

            case 'deleteOffer':
                $id = (int)($_POST['id'] ?? 0);
                $stmt = $conn->prepare("DELETE FROM krs_offers WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $response['status'] = 'success';
                    $response['message'] = 'Data deleted successfully.';
                } else {
                    $response['status'] = 'error';
                    $response['message'] = "Error deleting offer.";
                }
                echo json_encode($response);
                break;

            case 'fetchOffers':
                $page = (int)($_POST['page'] ?? 1);
                $maxRow = (int)($_POST['maxRow'] ?? 10);
                $offset = ($page - 1) * $maxRow;

                $sql = "SELECT * FROM krs_offers ORDER BY id DESC LIMIT ? OFFSET ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$maxRow, $offset]);
                $rows = $stmt->fetchAll();

                $totalRows = (int)$conn->query("SELECT COUNT(*) FROM krs_offers")->fetchColumn();
                $pages = ceil($totalRows / $maxRow);

                $html = "";
                if (count($rows) > 0) {
                    $i = ($page - 1) * $maxRow;
                    foreach ($rows as $row) {
                        $html .= '<tr class="align-middle">';
                        $html .= '<td class="text-center">' . ($i + 1) . '</td>';
                        $html .= '<td>' . htmlspecialchars($row['course']) . '</td>';
                        $html .= '<td>' . htmlspecialchars($row['lecturer']) . '</td>';
                        $html .= '<td class="text-center">' . htmlspecialchars($row['class_group']) . '</td>';
                        $html .= '<td>' . htmlspecialchars($row['days_sched1'] . ' ' . $row['hours_sched1'] . ' ' . $row['class_room1']) . '</td>';
                        $html .= '<td>' . (!empty($row['days_sched2']) ? htmlspecialchars($row['days_sched2'] . ' ' . $row['hours_sched2'] . ' ' . $row['class_room2']) : '-') . '</td>';
                        $html .= '<td class="text-center align-middle">';
                        $html .= '<button class="btn btn-outline-danger" id="deleteBtn" data-id="' . $row['id'] . '">&times;</button></td></tr>';
                        $i++;
                    }
                    $response['html'] = $html;
                    $response['pages'] = $pages;
                } else {
                    $html .= "<tr><td colspan='7' class='text-center'>No records found.</td></tr>";
                    $response['html'] = $html;
                }
                echo json_encode($response);
                break;

            //
            // Users Management (users_credential + personal_profiles)
            //
            case 'getDataUsers':
                $page = (int)($_POST['page'] ?? 1);
                $maxRow = (int)($_POST['maxRow'] ?? 10);
                $offset = ($page - 1) * $maxRow;

                $sql = "
                    SELECT u.id, u.username, u.role, u.status, u.last_login,
                           COALESCE(p.full_name, u.username) AS name
                    FROM users_credential u
                    LEFT JOIN personal_profiles p ON p.user_id = u.id
                    ORDER BY CASE WHEN lower(u.role) = 'superadmin' THEN 1 WHEN lower(u.role) = 'admin' THEN 2 ELSE 3 END, u.id DESC
                    LIMIT ? OFFSET ?
                ";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$maxRow, $offset]);
                $rows = $stmt->fetchAll();

                $totalRows = (int)$conn->query("SELECT COUNT(*) FROM users_credential")->fetchColumn();
                $pages = ceil($totalRows / $maxRow);

                $html = "";
                if (count($rows) > 0) {
                    $i = ($page - 1) * $maxRow;
                    foreach ($rows as $row) {
                        $roleLower = strtolower($row['role']);
                        $badgeColor = ($roleLower === 'superadmin' ? 'danger' : ($roleLower === 'admin' ? 'warning' : 'primary'));
                        $html .= '<tr class="align-middle">';
                        $html .= '<td class="text-center">' . ($i + 1) . '</td>';
                        $html .= "<td>" . htmlspecialchars($row['name']) . "</td>";
                        $html .= "<td>" . htmlspecialchars($row['username']) . "</td>";
                        $html .= "<td class='text-center'><span class='badge text-bg-$badgeColor'>" . htmlspecialchars(ucfirst($row['role'])) . "</span></td>";
                        $html .= "<td class='text-center'><span class='badge text-bg-success'>Active</span></td>";
                        $html .= "<td class='text-center'><span class='badge text-bg-" . ($row['status'] == 0 ? 'secondary' : 'success') . "'>" . ($row['status'] == 0 ? 'Disabled' : 'Enabled') . "</span></td>";
                        $html .= '<td class="text-center align-middle">';
                        if ($roleLower === 'superadmin') {
                            $html .= '-';
                        } elseif ($roleLower === 'student' || $roleLower === 'mahasiswa') {
                            $html .= '<span class="badge text-bg-secondary font-weight-normal" style="font-size: 11px;">Dikelola di Data Mahasiswa</span>';
                        } else {
                            $html .= '<button class="btn btn-sm fs-6 btn-outline-info me-2" id="editBtn" data-id="' . $row['id'] . '">✎</button>';
                            $html .= '<button class="btn btn-sm fs-6 btn-outline-danger" id="deleteBtn" data-id="' . $row['id'] . '">&times;</button>';
                        }
                        $html .= '</td></tr>';
                        $i++;
                    }
                    $response['html'] = $html;
                    $response['pages'] = $pages;
                } else {
                    $html .= "<tr><td colspan='7' class='text-center'>No records found.</td></tr>";
                    $response['html'] = $html;
                }
                echo json_encode($response);
                break;

            case 'deleteUser':
                try {
                    $id = (int)($_POST['id'] ?? 0);
                    $stmt = $conn->prepare("SELECT username, role FROM users_credential WHERE id = ?");
                    $stmt->execute([$id]);
                    $row = $stmt->fetch();
                    if ($row) {
                        $roleLower = strtolower($row['role']);
                        if ($roleLower === 'superadmin') {
                            throw new Exception("Akun Superadmin tidak dapat dihapus!");
                        }
                        if ($roleLower === 'student' || $roleLower === 'mahasiswa') {
                            throw new Exception("Akun Mahasiswa tidak dapat dihapus melalui Data Users. Harap kelola melalui menu Data Mahasiswa!");
                        }

                        $deleteStmt = $conn->prepare("DELETE FROM users_credential WHERE id = ?");
                        if ($deleteStmt->execute([$id])) {
                            $response['status'] = 'success';
                            $response['message'] = 'User berhasil dihapus.';
                        } else {
                            $response['status'] = 'error';
                            $response['message'] = 'Gagal menghapus user.';
                        }
                    } else {
                        $response['status'] = 'error';
                        $response['message'] = 'User tidak ditemukan.';
                    }
                } catch (Exception $e) {
                    $response['status'] = 'error';
                    $response['message'] = "Terjadi kesalahan: " . $e->getMessage();
                }
                echo json_encode($response);
                break;

            //
            // Checks & Helpers
            //
            case 'nimCheck':
                $nim = $_POST['nim'] ?? '';
                $response['message'] = duplicateCheck('students_data', 'nim', $nim) ? 'true' : 'false';
                echo json_encode($response);
                break;

            case 'nppCheck':
                $value = $_POST['npp'] ?? '';
                $response = duplicateCheck('lecturers_data', 'npp', $value);
                echo json_encode($response);
                break;

            case 'courseCodeCheck':
                $value = $_POST['courseCode'] ?? '';
                $response = duplicateCheck('courses_data', 'course_code', $value);
                echo json_encode($response);
                break;

            case 'usernameCheck':
                $value = $_POST['username'] ?? '';
                $response = duplicateCheck('users_credential', 'username', $value);
                echo json_encode($response);
                break;

            case 'courseOption':
                $courseCode = $_POST['courseCode'] ?? '';
                $stmt = $conn->prepare("SELECT credits AS sks FROM courses_data WHERE course_code = ?");
                $stmt->execute([$courseCode]);
                $response = $stmt->fetch() ?: [];
                echo json_encode($response);
                break;

            case 'getData':
                $target = $_POST['target'] ?? '';
                $allowedTargets = ['students_data', 'lecturers_data', 'courses_data', 'krs_offers', 'users_credential'];
                if (in_array($target, $allowedTargets)) {
                    $stmt = $conn->prepare("SELECT * FROM $target");
                    $stmt->execute();
                    $response[$target] = $stmt->fetchAll();
                } else {
                    $response[$target] = [];
                }
                echo json_encode($response);
                break;

            //
            // Authentication (users_credential)
            //
            case 'userLogin':
                $username = trim($_POST['username'] ?? '');
                $userPasswd = trim($_POST['password'] ?? '');
                $loginType = trim($_POST['login_type'] ?? '');

                $targetTab = ($loginType === 'student') ? 'student' : (($loginType === 'staff') ? 'staff' : '');
                $redirectUrl = "../index.php" . ($targetTab ? "?tab={$targetTab}" : "");

                $stmt = $conn->prepare("
                    SELECT u.*, p.full_name, p.photo 
                    FROM users_credential u 
                    LEFT JOIN personal_profiles p ON p.user_id = u.id 
                    WHERE (u.username = ? OR u.email = ?)
                ");
                $stmt->execute([$username, $username]);
                $user = $stmt->fetch();

                // Validation: Ensure user logs in from the correct form/portal
                if ($loginType === 'student') {
                    $_SESSION['active_tab'] = 'student';
                    if (!$user || strtolower($user['role'] ?? '') !== 'student') {
                        $_SESSION['error'] = 'Mahasiswa tidak ditemukan!';
                        header("location: {$redirectUrl}");
                        exit;
                    }
                } elseif ($loginType === 'staff') {
                    $_SESSION['active_tab'] = 'staff';
                    $userRole = strtolower($user['role'] ?? '');
                    if (!$user || in_array($userRole, ['student', 'applicant'])) {
                        $_SESSION['error'] = 'Pegawai tidak ditemukan!';
                        header("location: {$redirectUrl}");
                        exit;
                    }
                } else {
                    if (!$user) {
                        $_SESSION['error'] = 'Username atau password tidak ditemukan!';
                        header("location: {$redirectUrl}");
                        exit;
                    }
                }

                if ($user) {
                    // 1. Cek apakah akun sedang terkunci karena brute force
                    if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
                        $minsLeft = ceil((strtotime($user['locked_until']) - time()) / 60);
                        $_SESSION['error'] = "Akun Anda terkunci sementara karena beberapa kali salah password. Silakan coba lagi dalam {$minsLeft} menit.";
                        header("location: {$redirectUrl}");
                        exit;
                    }

                    // 2. Cek status akun (account_status)
                    if (($user['account_status'] ?? 'active') !== 'active') {
                        $statusMessages = [
                            'suspended'          => 'Akun Anda sedang ditangguhkan (suspended). Silakan hubungi administrator.',
                            'banned'             => 'Akun Anda telah dinonaktifkan permanen (banned).',
                            'archived'           => 'Akun ini telah diarsipkan dan tidak dapat digunakan.',
                            'pending_activation' => 'Akun Anda belum aktif. Silakan lakukan verifikasi terlebih dahulu.'
                        ];
                        $_SESSION['error'] = $statusMessages[$user['account_status']] ?? 'Status akun tidak mengizinkan akses login.';
                        header("location: {$redirectUrl}");
                        exit;
                    }

                    // 3. Verifikasi password
                    if (password_verify($userPasswd, $user['password'])) {
                        $id = $user['id'];
                        $sessionToken = bin2hex(random_bytes(32));

                        // Reset failed attempts, update last_login_at, last_active_at, dan session_token
                        $updateStmt = $conn->prepare("
                            UPDATE users_credential 
                            SET last_login_at = CURRENT_TIMESTAMP, 
                                last_active_at = CURRENT_TIMESTAMP, 
                                session_token = ?, 
                                failed_attempts = 0, 
                                locked_until = NULL 
                            WHERE id = ?
                        ");
                        $updateStmt->execute([$sessionToken, $id]);

                        $_SESSION['user'] = [
                            'id'            => $user['id'],
                            'userid'        => $user['username'],
                            'name'          => !empty($user['full_name']) ? $user['full_name'] : $user['username'],
                            'role'          => $user['role'],
                            'pict'          => $user['photo'] ?? null,
                            'session_token' => $sessionToken,
                        ];
                        $_SESSION['last_activity'] = time();
                        $_SESSION['last_db_heartbeat'] = time();

                        $roleLower = strtolower($user['role']);
                        if ($roleLower === 'applicant') {
                            $getPmb = $conn->prepare("SELECT * FROM pmb_data WHERE user_id = ?");
                            $getPmb->execute([$user['id']]);
                            $pmb = $getPmb->fetch() ?: [];
                            $applicantData = array_merge($user, $pmb);
                            $applicantData['session_token'] = $sessionToken;
                            $_SESSION['applicant'] = $applicantData;
                            header("location: ../portal.php");
                            exit;
                        }

                        header("location: ../portal.php");
                        exit;
                    } else {
                        // Password salah: catat failed_attempts
                        $failedAttempts = (int)($user['failed_attempts'] ?? 0) + 1;
                        if ($failedAttempts >= 5) {
                            $lockStmt = $conn->prepare("UPDATE users_credential SET failed_attempts = ?, locked_until = CURRENT_TIMESTAMP + INTERVAL '15 minutes' WHERE id = ?");
                            $lockStmt->execute([$failedAttempts, $user['id']]);
                            $_SESSION['error'] = 'Terlalu banyak percobaan login gagal. Akun Anda dikunci selama 15 menit.';
                        } else {
                            $remaining = 5 - $failedAttempts;
                            $failStmt = $conn->prepare("UPDATE users_credential SET failed_attempts = ? WHERE id = ?");
                            $failStmt->execute([$failedAttempts, $user['id']]);
                            $_SESSION['error'] = "Username atau password salah! Sisa percobaan: {$remaining} kali.";
                        }
                        header("location: {$redirectUrl}");
                        exit;
                    }
                }
                break;

            case 'forceLogout':
            case 'userLogout':
                try {
                    $currentUserId = $_SESSION['user']['id'] ?? ($_SESSION['applicant']['user_id'] ?? null);
                    if ($currentUserId) {
                        $clearTokenStmt = $conn->prepare("UPDATE users_credential SET session_token = NULL, last_active_at = NULL WHERE id = ?");
                        $clearTokenStmt->execute([$currentUserId]);
                    }
                    $_SESSION = [];
                    if (session_id()) {
                        session_destroy();
                    }
                    $response['message'] = "success";
                } catch (Exception $e) {
                    $response['message'] = "error! " . $e->getMessage();
                }
                echo json_encode($response);
                break;

            case 'setTheme':
                try {
                    $theme = strtolower(trim($_POST['theme'] ?? 'light'));
                    if (!in_array($theme, ['light', 'dark'])) {
                        $theme = 'light';
                    }
                    $username = $_SESSION['user']['userid'] ?? null;
                    $configFile = __DIR__ . '/../config/theme_setting.json';
                    $settings = ['default' => 'light', 'users' => []];

                    if (file_exists($configFile)) {
                        $raw = file_get_contents($configFile);
                        $parsed = json_decode($raw, true);
                        if (is_array($parsed)) {
                            $settings = array_merge($settings, $parsed);
                        }
                    }

                    if ($username) {
                        $settings['users'][$username] = $theme;
                    } else {
                        $settings['default'] = $theme;
                    }

                    file_put_contents($configFile, json_encode($settings, JSON_PRETTY_PRINT), LOCK_EX);
                    $response = ['status' => 'success', 'theme' => $theme, 'message' => 'Theme updated successfully'];
                } catch (Exception $e) {
                    $response = ['status' => 'error', 'message' => $e->getMessage()];
                }
                echo json_encode($response);
                break;

            //
            // PMB Online Actions (users_credential + personal_profiles + pmb_data)
            //
            case 'applicantRegister':
                try {
                    $fullName = trim($_POST['full_name'] ?? '');
                    $email = trim($_POST['email'] ?? '');
                    $username = trim($_POST['username'] ?? '');
                    $password = trim($_POST['password'] ?? '');

                    if (empty($fullName) || empty($email) || empty($username) || empty($password)) {
                        throw new Exception("Harap lengkapi semua kolom pendaftaran (Nama, Email, Username, dan Password)!");
                    }

                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        throw new Exception("Format email tidak valid!");
                    }

                    $checkDuplicateStmt = $conn->prepare("SELECT COUNT(*) FROM users_credential WHERE username = ? OR email = ?");
                    $checkDuplicateStmt->execute([$username, $email]);
                    if ($checkDuplicateStmt->fetchColumn() > 0) {
                        throw new Exception("Email atau Username sudah terdaftar!");
                    }
                    
                    $conn->beginTransaction();

                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    $insertUser = $conn->prepare("INSERT INTO users_credential (username, email, password, role, account_status) VALUES (?, ?, ?, 'applicant', 'active') RETURNING id");
                    $insertUser->execute([$username, $email, $hashedPassword]);
                    $userId = $insertUser->fetchColumn();

                    $insertProfile = $conn->prepare("INSERT INTO personal_profiles (user_id, full_name) VALUES (?, ?)");
                    $insertProfile->execute([$userId, $fullName]);

                    $insertPmb = $conn->prepare("INSERT INTO pmb_data (user_id, application_status) VALUES (?, 'Draft')");
                    $insertPmb->execute([$userId]);
                    
                    $conn->commit();

                    $_SESSION['success'] = "Akun berhasil dibuat! Silakan login menggunakan Email dan Password Anda.";
                    header("location: ../index.php");
                    exit;
                } catch (Exception $e) {
                    if ($conn->inTransaction()) {
                        $conn->rollBack();
                    }
                    $_SESSION['error'] = $e->getMessage();
                    header("location: ../index.php");
                    exit;
                }
                break;

            case 'applicantLogin':
                try {
                    $username = trim($_POST['username'] ?? '');
                    $password = trim($_POST['password'] ?? '');

                    $getStmt = $conn->prepare("
                        SELECT u.*, p.full_name, p.nik, p.gender, p.pob, p.dob, p.religion,
                               p.marital_status, p.job_status, p.phone, p.emerg_phone, p.ktp_address,
                               p.domicile_address, p.photo,
                               pmb.id AS pmb_id, pmb.major_id, pmb.nisn, pmb.mother_name, pmb.father_name,
                               pmb.parent_phone, pmb.high_school_name, pmb.high_school_major,
                               pmb.high_school_address, pmb.high_school_score, pmb.admission_track,
                               pmb.payment_status, pmb.application_status,
                               m.major_code, m.major_name
                        FROM users_credential u
                        JOIN personal_profiles p ON p.user_id = u.id
                        LEFT JOIN pmb_data pmb ON pmb.user_id = u.id
                        LEFT JOIN majors_data m ON pmb.major_id = m.id
                        WHERE (u.username = ? OR u.email = ?)
                    ");
                    $getStmt->execute([$username, $username]);
                    $applicant = $getStmt->fetch();

                    if (!$applicant) {
                        throw new Exception("Username atau password salah!");
                    }

                    // Cek lock brute-force
                    if (!empty($applicant['locked_until']) && strtotime($applicant['locked_until']) > time()) {
                        $minsLeft = ceil((strtotime($applicant['locked_until']) - time()) / 60);
                        throw new Exception("Akun Anda terkunci sementara karena beberapa kali salah password. Silakan coba lagi dalam {$minsLeft} menit.");
                    }

                    // Cek account_status
                    if (($applicant['account_status'] ?? 'active') !== 'active') {
                        throw new Exception("Status akun pendaftar tidak aktif atau ditangguhkan.");
                    }

                    if (password_verify($password, $applicant['password'])) {
                        $sessionToken = bin2hex(random_bytes(32));

                        // Reset failed attempts, update last_login_at, last_active_at, session_token
                        $updateStmt = $conn->prepare("
                            UPDATE users_credential 
                            SET last_login_at = CURRENT_TIMESTAMP, 
                                last_active_at = CURRENT_TIMESTAMP, 
                                session_token = ?, 
                                failed_attempts = 0, 
                                locked_until = NULL 
                            WHERE id = ?
                        ");
                        $updateStmt->execute([$sessionToken, $applicant['user_id'] ?? $applicant['id']]);

                        // Map properties so applicant_portal.php remains fully compatible
                        $applicant['user_id'] = $applicant['id']; // Preserve actual user_id
                        $applicant['id'] = $applicant['pmb_id'] ?? $applicant['id'];
                        $applicant['status'] = $applicant['application_status'] ?? 'Draft';
                        $applicant['pict'] = $applicant['photo'] ?? null;
                        $applicant['address'] = $applicant['domicile_address'] ?? null;
                        $applicant['school_origin'] = $applicant['high_school_name'] ?? null;
                        $applicant['major'] = $applicant['high_school_major'] ?? null;
                        $applicant['school_address'] = $applicant['high_school_address'] ?? null;
                        $applicant['final_score'] = $applicant['high_school_score'] ?? null;
                        $applicant['program_code'] = $applicant['major_code'] ?? null;
                        $applicant['session_token'] = $sessionToken;

                        $_SESSION['applicant'] = $applicant;
                        $_SESSION['last_activity'] = time();
                        $_SESSION['last_db_heartbeat'] = time();
                        header("location: ../portal.php");
                        exit;
                    } else {
                        // Password salah
                        $failedAttempts = (int)($applicant['failed_attempts'] ?? 0) + 1;
                        $uId = $applicant['user_id'] ?? $applicant['id'];
                        if ($failedAttempts >= 5) {
                            $lockStmt = $conn->prepare("UPDATE users_credential SET failed_attempts = ?, locked_until = CURRENT_TIMESTAMP + INTERVAL '15 minutes' WHERE id = ?");
                            $lockStmt->execute([$failedAttempts, $uId]);
                            throw new Exception("Terlalu banyak percobaan gagal. Akun dikunci selama 15 menit.");
                        } else {
                            $remaining = 5 - $failedAttempts;
                            $failStmt = $conn->prepare("UPDATE users_credential SET failed_attempts = ? WHERE id = ?");
                            $failStmt->execute([$failedAttempts, $uId]);
                            throw new Exception("Password salah! Sisa percobaan: {$remaining} kali.");
                        }
                    }
                } catch (Exception $e) {
                    $_SESSION['error'] = $e->getMessage();
                    header("location: ../index.php");
                    exit;
                }
                break;

            case 'applicantLogout':
                $currentUserId = $_SESSION['applicant']['user_id'] ?? ($_SESSION['applicant']['id'] ?? null);
                if ($currentUserId) {
                    $clearTokenStmt = $conn->prepare("UPDATE users_credential SET session_token = NULL, last_active_at = NULL WHERE id = ?");
                    $clearTokenStmt->execute([$currentUserId]);
                }
                unset($_SESSION['applicant']);
                header("location: ../index.php");
                exit;
                break;
                break;

            case 'applicantUpdateProfile':
                try {
                    if (!isset($_SESSION['applicant'])) {
                        throw new Exception("Unauthorized access!");
                    }
                    $userId = $_SESSION['applicant']['user_id'] ?? $_SESSION['applicant']['id'];

                    // Lock profile editing if already Pending or Approved
                    $checkStatusStmt = $conn->prepare("SELECT application_status FROM pmb_data WHERE user_id = ?");
                    $checkStatusStmt->execute([$userId]);
                    $pmbStatus = $checkStatusStmt->fetchColumn();
                    if ($pmbStatus === 'Approved' || $pmbStatus === 'Pending') {
                        throw new Exception("Data diri Anda telah dikunci karena pendaftaran Anda sedang dalam status " . $pmbStatus . ".");
                    }

                    $fullName = trim($_POST['full_name'] ?? '');
                    $nik = trim($_POST['nik'] ?? '');
                    $gender = trim($_POST['gender'] ?? '');
                    $pob = trim($_POST['pob'] ?? '');
                    $dob = !empty($_POST['dob']) ? trim($_POST['dob']) : null;
                    $religion = trim($_POST['religion'] ?? '');
                    $nisn = trim($_POST['nisn'] ?? '');
                    $motherName = trim($_POST['mother_name'] ?? '');
                    $fatherName = trim($_POST['father_name'] ?? '');
                    $fatherPhone = trim($_POST['father_phone'] ?? $_POST['parent_phone'] ?? '');
                    $ktpAddress = trim($_POST['ktp_address'] ?? '');
                    $maritalStatus = trim($_POST['marital_status'] ?? '');
                    $jobStatus = trim($_POST['job_status'] ?? '');
                    $phone = trim($_POST['phone'] ?? '');
                    $address = trim($_POST['address'] ?? '');
                    $schoolOrigin = trim($_POST['school_origin'] ?? '');
                    $schoolMajor = trim($_POST['school_major'] ?? $_POST['major'] ?? '');
                    $schoolAddress = trim($_POST['school_address'] ?? '');
                    $finalScore = !empty($_POST['final_score']) ? (float)$_POST['final_score'] : null;

                    // Function for secure file upload
                    $handleUpload = function($fileInputName, $prefix, $userId, $targetDir, $oldFile = null, $allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf']) {
                        if (!empty($_FILES[$fileInputName]['name']) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
                            $tmpPath = $_FILES[$fileInputName]['tmp_name'];
                            $mime = mime_content_type($tmpPath);
                            if (!in_array($mime, $allowedMime)) {
                                throw new Exception("Tipe file tidak diizinkan untuk " . $fileInputName . "!");
                            }
                            $ext = match($mime) {
                                'image/jpeg' => 'jpg',
                                'image/png' => 'png',
                                'image/webp' => 'webp',
                                'application/pdf' => 'pdf',
                                default => 'bin'
                            };
                            $filename = $prefix . '_' . $userId . '_' . randomizer(8) . '.' . $ext;
                            
                            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                            
                            if (move_uploaded_file($tmpPath, $targetDir . $filename)) {
                                if ($oldFile && file_exists($targetDir . $oldFile) && is_file($targetDir . $oldFile)) {
                                    @unlink($targetDir . $oldFile);
                                }
                                return $filename;
                            }
                        }
                        return null;
                    };

                    // Handle photo upload
                    $getOldData = $conn->prepare("SELECT p.photo, pmb.certificate_file, pmb.payment_proof FROM personal_profiles p LEFT JOIN pmb_data pmb ON p.user_id = pmb.user_id WHERE p.user_id = ?");
                    $getOldData->execute([$userId]);
                    $oldData = $getOldData->fetch();
                    
                    $photoDir = defined('UPLOAD_PATH') ? UPLOAD_PATH . '/' : __DIR__ . '/../public/uploads/user_photos/';
                    $certDir = defined('PMB_CERT_PATH') ? PMB_CERT_PATH . '/' : __DIR__ . '/../public/uploads/pmb_docs/certificates/';
                    $payDir = defined('PMB_PAY_PATH') ? PMB_PAY_PATH . '/' : __DIR__ . '/../public/uploads/pmb_docs/payments/';
                    
                    $filename = $handleUpload('pict', 'app', $userId, $photoDir, $oldData['photo'] ?? null, ['image/jpeg', 'image/png', 'image/webp']);
                    $certFile = $handleUpload('certificate_file', 'cert', $userId, $certDir, $oldData['certificate_file'] ?? null, ['image/jpeg', 'image/png', 'application/pdf']);
                    $paymentProof = $handleUpload('payment_proof', 'pay', $userId, $payDir, $oldData['payment_proof'] ?? null, ['image/jpeg', 'image/png', 'application/pdf']);

                    $conn->beginTransaction();

                    try {
                        // 1. Update personal_profiles
                        $profileQuery = "UPDATE personal_profiles SET full_name = ?, nik = ?, gender = ?, pob = ?, dob = ?, religion = ?, marital_status = ?, job_status = ?, phone = ?, ktp_address = ?, domicile_address = ?";
                        $profileParams = [$fullName, $nik, $gender, $pob, $dob, $religion, $maritalStatus, $jobStatus, $phone, $ktpAddress, $address];
                        
                        if ($filename) {
                            $profileQuery .= ", photo = ?";
                            $profileParams[] = $filename;
                        }
                        $profileQuery .= " WHERE user_id = ?";
                        $profileParams[] = $userId;
                        
                        $updateProfile = $conn->prepare($profileQuery);
                        $updateProfile->execute($profileParams);

                        // 2. Update pmb_data
                        $pmbQuery = "UPDATE pmb_data SET nisn = ?, mother_name = ?, father_name = ?, parent_phone = ?, high_school_name = ?, high_school_major = ?, high_school_address = ?, high_school_score = ?";
                        $pmbParams = [$nisn, $motherName, $fatherName, $fatherPhone, $schoolOrigin, $schoolMajor, $schoolAddress, $finalScore];
                        
                        if ($certFile) {
                            $pmbQuery .= ", certificate_file = ?";
                            $pmbParams[] = $certFile;
                        }
                        if ($paymentProof) {
                            $pmbQuery .= ", payment_proof = ?, payment_status = 'Pending'";
                            $pmbParams[] = $paymentProof;
                        }
                        $pmbQuery .= " WHERE user_id = ?";
                        $pmbParams[] = $userId;

                        $updatePmb = $conn->prepare($pmbQuery);
                        $updatePmb->execute($pmbParams);
                        
                        $conn->commit();
                    } catch (Exception $innerE) {
                        $conn->rollBack();
                        // Special unique violation handling for NIK
                        if ($innerE->getCode() == 23505 && strpos($innerE->getMessage(), 'nik') !== false) {
                            throw new Exception("NIK yang Anda masukkan sudah terdaftar oleh pendaftar lain.");
                        }
                        throw $innerE;
                    }

                    // Refresh session
                    $getStmt = $conn->prepare("
                        SELECT u.*, p.full_name, p.nik, p.gender, p.pob, p.dob, p.religion,
                               p.marital_status, p.job_status, p.phone, p.emerg_phone, p.ktp_address,
                               p.domicile_address, p.photo,
                               pmb.id AS pmb_id, pmb.major_id, pmb.nisn, pmb.mother_name, pmb.father_name,
                               pmb.parent_phone, pmb.high_school_name, pmb.high_school_major,
                               pmb.high_school_address, pmb.high_school_score, pmb.admission_track,
                               pmb.certificate_file, pmb.payment_proof, pmb.payment_status, pmb.application_status,
                               m.major_code, m.major_name
                        FROM users_credential u
                        JOIN personal_profiles p ON p.user_id = u.id
                        LEFT JOIN pmb_data pmb ON pmb.user_id = u.id
                        LEFT JOIN majors_data m ON pmb.major_id = m.id
                        WHERE u.id = ?
                    ");
                    $getStmt->execute([$userId]);
                    $applicant = $getStmt->fetch();
                    if ($applicant) {
                        $applicant['user_id'] = $applicant['id'];
                        $applicant['id'] = $applicant['pmb_id'] ?? $applicant['id'];
                        $applicant['status'] = $applicant['application_status'] ?? 'Draft';
                        $applicant['pict'] = $applicant['photo'] ?? null;
                        $applicant['address'] = $applicant['domicile_address'] ?? null;
                        $applicant['school_origin'] = $applicant['high_school_name'] ?? null;
                        $applicant['major'] = $applicant['high_school_major'] ?? null;
                        $applicant['school_address'] = $applicant['high_school_address'] ?? null;
                        $applicant['final_score'] = $applicant['high_school_score'] ?? null;
                        $applicant['program_code'] = $applicant['major_code'] ?? null;
                        $_SESSION['applicant'] = $applicant;
                    }

                    $_SESSION['success'] = "Data diri berhasil diperbarui!";
                    header("location: ../portal.php");
                    exit;
                } catch (Exception $e) {
                    $_SESSION['error'] = $e->getMessage();
                    header("location: ../portal.php");
                    exit;
                }
                break;

            case 'applicantApplyProgram':
                try {
                    if (!isset($_SESSION['applicant'])) {
                        throw new Exception("Unauthorized access!");
                    }
                    $userId = $_SESSION['applicant']['user_id'] ?? $_SESSION['applicant']['id'];
                    $programCode = trim($_POST['program_code'] ?? '');

                    if (empty($programCode)) {
                        throw new Exception("Harap pilih 1 Program Studi!");
                    }

                    $majorId = getMajorIdFromCode($programCode);

                    // Validate completeness
                    $checkStmt = $conn->prepare("
                        SELECT p.nik, p.dob, p.phone, p.domicile_address, p.photo,
                               pmb.mother_name, pmb.high_school_name, pmb.certificate_file, pmb.payment_proof
                        FROM personal_profiles p 
                        LEFT JOIN pmb_data pmb ON pmb.user_id = p.user_id 
                        WHERE p.user_id = ?
                    ");
                    $checkStmt->execute([$userId]);
                    $data = $checkStmt->fetch();

                    $missing = [];
                    if (empty($data['nik'])) $missing[] = "NIK";
                    if (empty($data['dob'])) $missing[] = "Tanggal Lahir";
                    if (empty($data['phone'])) $missing[] = "Nomor HP";
                    if (empty($data['domicile_address'])) $missing[] = "Alamat Domisili";
                    if (empty($data['photo'])) $missing[] = "Foto Profil";
                    if (empty($data['mother_name'])) $missing[] = "Nama Ibu";
                    if (empty($data['high_school_name'])) $missing[] = "Sekolah Asal";
                    if (empty($data['certificate_file'])) $missing[] = "Sertifikat/Ijazah";
                    if (empty($data['payment_proof'])) $missing[] = "Bukti Pembayaran";

                    if (!empty($missing)) {
                        throw new Exception("Harap lengkapi data berikut sebelum submit: " . implode(', ', $missing) . ".");
                    }

                    $updateStmt = $conn->prepare("UPDATE pmb_data SET major_id = ?, application_status = 'Pending' WHERE user_id = ?");
                    $updateStmt->execute([$majorId, $userId]);

                    $_SESSION['applicant']['status'] = 'Pending';
                    $_SESSION['applicant']['application_status'] = 'Pending';
                    $_SESSION['applicant']['program_code'] = $programCode;

                    $_SESSION['success'] = "Pendaftaran berhasil dikirimkan ke Tata Usaha (TU)! Status saat ini: Pending Verifikasi.";
                    header("location: ../portal.php");
                    exit;
                } catch (Exception $e) {
                    $_SESSION['error'] = $e->getMessage();
                    header("location: ../portal.php");
                    exit;
                }
                break;

            case 'verifyApplicant':
                try {
                    $pmbId = (int)($_POST['applicant_id'] ?? 0);
                    $action = trim($_POST['action'] ?? '');

                    $getStmt = $conn->prepare("
                        SELECT pmb.*, p.full_name, u.email, u.password, m.major_code, m.major_name
                        FROM pmb_data pmb
                        JOIN users_credential u ON pmb.user_id = u.id
                        JOIN personal_profiles p ON p.user_id = u.id
                        LEFT JOIN majors_data m ON pmb.major_id = m.id
                        WHERE pmb.id = ?
                    ");
                    $getStmt->execute([$pmbId]);
                    $applicant = $getStmt->fetch();

                    if (!$applicant) {
                        throw new Exception("Applicant data not found!");
                    }

                    $conn->beginTransaction();

                    if ($action === 'Approve') {
                        $currentYear = date('Y');
                        $programCode = $applicant['major_code'] ?: 'A11';
                        $majorId = (int)($applicant['major_id'] ?: getMajorIdFromCode($programCode));
                        $prefixNim = $programCode . '.' . $currentYear . '.';

                        // Order by id DESC to accurately fetch the latest inserted student sequence
                        $getLatestNimStmt = $conn->prepare("SELECT nim FROM students_data WHERE nim LIKE ? ORDER BY id DESC LIMIT 1");
                        $getLatestNimStmt->execute([$prefixNim . '%']);
                        $latestNim = $getLatestNimStmt->fetchColumn();

                        if ($latestNim) {
                            $parts = explode('.', $latestNim);
                            $lastSequence = (int)end($parts);
                            $nextSequence = $lastSequence + 1;
                        } else {
                            $nextSequence = 1;
                        }

                        $newNim = $prefixNim . str_pad($nextSequence, 5, '0', STR_PAD_LEFT);

                        // 1. Insert into students_data (Official Student Record)
                        $insertStudent = $conn->prepare("
                            INSERT INTO students_data 
                            (user_id, pmb_id, major_id, nim, batch_year, current_semester, academic_status) 
                            VALUES (?, ?, ?, ?, ?, 1, 'Aktif')
                            RETURNING id
                        ");
                        $insertStudent->execute([$applicant['user_id'], $pmbId, $majorId, $newNim, (int)$currentYear]);
                        $newStudentId = (int)$insertStudent->fetchColumn();

                        // 2. Generate Initial UKT Bill in students_bills_data using dynamic Pricelist Config (config/tuition_fees.json)
                        $tuitionConfig = getTuitionFeesConfig();
                        $rateInfo = getStudentUktRate($programCode);

                        $academicYear = $currentYear . '/' . ($currentYear + 1) . ' Ganjil';
                        $billCode = 'INV-' . $currentYear . '1-' . str_replace('.', '', $newNim);
                        $numericNim = preg_replace_callback('/^[A-Za-z]/', function($m) {
                            return ord(strtoupper($m[0])) - 64;
                        }, $newNim);

                        $defaultBank = $tuitionConfig['default_va_bank'] ?? '008';
                        $defaultVa = $defaultBank . '.' . $numericNim;
                        $amount = (float)($rateInfo['ukt_pokok'] ?? 5000000);
                        $dueDays = (int)($tuitionConfig['default_due_days'] ?? 30);
                        $dueDate = date('Y-m-d', strtotime("+$dueDays days"));

                        $sppFlat = number_format((float)($tuitionConfig['shared_fees']['spp_flat'] ?? 2000000), 0, ',', '.');
                        $poliFlat = number_format((float)($tuitionConfig['shared_fees']['polyclinic_flat'] ?? 200000), 0, ',', '.');
                        $billNotes = "Tagihan UKT Pokok Perdana PMB ($programCode - {$rateInfo['major_name']}: Rp " . number_format($amount, 0, ',', '.') . " | SPP FIK: Rp $sppFlat | Poliklinik: Rp $poliFlat)";

                        $conn->prepare("
                            INSERT INTO students_bills_data 
                            (student_id, bill_code, academic_year, bill_type, amount, due_date, payment_status, va_number, notes)
                            VALUES (?, ?, ?, 'UKT Pokok', ?, ?, 'Unpaid', ?, ?)
                        ")->execute([$newStudentId, $billCode, $academicYear, $amount, $dueDate, $defaultVa, $billNotes]);

                        // 3. Update users_credential role to student and username to new NIM
                        $conn->prepare("UPDATE users_credential SET role = 'student', username = ? WHERE id = ?")
                             ->execute([$newNim, $applicant['user_id']]);

                        // 4. Update pmb_data status and set payment_status to Verified upon approval
                        $conn->prepare("UPDATE pmb_data SET application_status = 'Approved', payment_status = 'Verified' WHERE id = ?")
                             ->execute([$pmbId]);

                        $conn->commit();

                        $response = ['status' => 'success', 'message' => "Applicant approved! Official student record created with NIM: $newNim"];
                    } else {
                        // Reject applicant
                        $conn->prepare("UPDATE pmb_data SET application_status = 'Rejected' WHERE id = ?")
                             ->execute([$pmbId]);

                        $conn->commit();

                        $response = ['status' => 'success', 'message' => "Applicant rejected."];
                    }
                } catch (Exception $e) {
                    if ($conn && $conn->inTransaction()) {
                        $conn->rollBack();
                    }
                    $response = ['status' => 'error', 'message' => $e->getMessage()];
                }
                echo json_encode($response);
                break;

            case 'addStudentKrs':
                try {
                    $studentNim = trim($_POST['student_nim'] ?? $_SESSION['user']['userid'] ?? '');
                    $courseId = (int)($_POST['course_id'] ?? $_POST['krs_offer_id'] ?? 0);
                    $academicYear = trim($_POST['academic_year'] ?? '2026/2027 Ganjil');

                    $getStudent = $conn->prepare("SELECT id FROM students_data WHERE nim = ?");
                    $getStudent->execute([$studentNim]);
                    $studentId = $getStudent->fetchColumn();

                    if (!$studentId || !$courseId) {
                        throw new Exception("Invalid Student or Course!");
                    }

                    // Check duplicate KRS
                    $checkKrsStmt = $conn->prepare("SELECT COUNT(*) FROM students_krs_data WHERE student_id = ? AND course_id = ? AND academic_year = ?");
                    $checkKrsStmt->execute([$studentId, $courseId, $academicYear]);
                    if ($checkKrsStmt->fetchColumn() > 0) {
                        throw new Exception("You have already selected this course!");
                    }

                    $insertKrsStmt = $conn->prepare("INSERT INTO students_krs_data (student_id, course_id, academic_year, semester, approval_status) VALUES (?, ?, ?, 1, 'Submitted')");
                    $insertKrsStmt->execute([$studentId, $courseId, $academicYear]);

                    $response = ['status' => 'success', 'message' => 'Course successfully added to KRS!'];
                } catch (Exception $e) {
                    $response = ['status' => 'error', 'message' => $e->getMessage()];
                }
                echo json_encode($response);
                break;

            case 'approveStudentKrs':
                try {
                    $krsId = (int)($_POST['krs_id'] ?? 0);
                    $updateKrsStmt = $conn->prepare("UPDATE students_krs_data SET approval_status = 'Approved', approved_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $updateKrsStmt->execute([$krsId]);

                    $response = ['status' => 'success', 'message' => 'KRS entry approved successfully!'];
                } catch (Exception $e) {
                    $response = ['status' => 'error', 'message' => $e->getMessage()];
                }
                echo json_encode($response);
                break;

            case 'fetchStudentBills':
                try {
                    $page = (int)($_POST['page'] ?? 1);
                    $maxRow = (int)($_POST['maxRow'] ?? 10);
                    $offset = ($page - 1) * $maxRow;

                    $academicYear = trim($_POST['academic_year'] ?? 'all');
                    $majorId = trim($_POST['major_id'] ?? 'all');
                    $paymentStatus = trim($_POST['payment_status'] ?? 'all');
                    $paymentMethod = trim($_POST['payment_method'] ?? 'all');
                    $search = trim($_POST['search'] ?? '');

                    $where = ["1=1"];
                    $params = [];

                    if (!empty($academicYear) && $academicYear !== 'all') {
                        $where[] = "b.academic_year = ?";
                        $params[] = $academicYear;
                    }
                    if (!empty($majorId) && $majorId !== 'all') {
                        $where[] = "s.major_id = ?";
                        $params[] = (int)$majorId;
                    }
                    if (!empty($paymentStatus) && $paymentStatus !== 'all') {
                        $where[] = "b.payment_status = ?";
                        $params[] = $paymentStatus;
                    }
                    if (!empty($paymentMethod) && $paymentMethod !== 'all') {
                        $where[] = "b.payment_method = ?";
                        $params[] = $paymentMethod;
                    }
                    if (!empty($search)) {
                        $where[] = "(s.nim ILIKE ? OR p.full_name ILIKE ? OR b.bill_code ILIKE ? OR b.va_number ILIKE ?)";
                        $term = "%$search%";
                        $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term;
                    }

                    $whereClause = implode(" AND ", $where);

                    // 1. Calculate Metrics
                    $metricSql = "
                        SELECT 
                            COALESCE(SUM(b.amount), 0) AS total_billed_amount,
                            COUNT(b.id) AS total_billed_count,
                            COALESCE(SUM(CASE WHEN b.payment_status = 'Paid' THEN b.amount ELSE 0 END), 0) AS total_paid_amount,
                            COUNT(CASE WHEN b.payment_status = 'Paid' THEN 1 END) AS total_paid_count,
                            COALESCE(SUM(CASE WHEN b.payment_status IN ('Unpaid', 'Pending_Verification') THEN b.amount ELSE 0 END), 0) AS total_unpaid_amount,
                            COUNT(CASE WHEN b.payment_status IN ('Unpaid', 'Pending_Verification') THEN 1 END) AS total_unpaid_count
                        FROM students_bills_data b
                        JOIN students_data s ON b.student_id = s.id
                        JOIN personal_profiles p ON s.user_id = p.user_id
                        WHERE $whereClause
                    ";
                    $metricStmt = $conn->prepare($metricSql);
                    $metricStmt->execute($params);
                    $metrics = $metricStmt->fetch();

                    // 2. Count Total Rows
                    $countSql = "
                        SELECT COUNT(*) 
                        FROM students_bills_data b
                        JOIN students_data s ON b.student_id = s.id
                        JOIN personal_profiles p ON s.user_id = p.user_id
                        WHERE $whereClause
                    ";
                    $countStmt = $conn->prepare($countSql);
                    $countStmt->execute($params);
                    $totalRows = (int)$countStmt->fetchColumn();
                    $pages = ceil($totalRows / $maxRow) ?: 1;

                    // 3. Fetch Rows
                    $dataSql = "
                        SELECT b.*, s.nim, p.full_name, m.major_code, m.major_name, m.degree
                        FROM students_bills_data b
                        JOIN students_data s ON b.student_id = s.id
                        JOIN personal_profiles p ON s.user_id = p.user_id
                        JOIN majors_data m ON s.major_id = m.id
                        WHERE $whereClause
                        ORDER BY b.id DESC
                        LIMIT ? OFFSET ?
                    ";
                    $dataParams = array_merge($params, [$maxRow, $offset]);
                    $dataStmt = $conn->prepare($dataSql);
                    $dataStmt->execute($dataParams);
                    $rows = $dataStmt->fetchAll();

                    $html = "";
                    if (count($rows) > 0) {
                        $i = $offset;
                        foreach ($rows as $r) {
                            $i++;
                            $statusBadge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Belum Lunas</span>';
                            if ($r['payment_status'] === 'Paid') {
                                $statusBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Lunas</span>';
                            } elseif ($r['payment_status'] === 'Pending_Verification') {
                                $statusBadge = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">Menunggu Verifikasi</span>';
                            } elseif ($r['payment_status'] === 'Cancelled') {
                                $statusBadge = '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">Dibatalkan</span>';
                            }

                            $payMethodDisplay = !empty($r['payment_method']) ? '<span class="badge bg-light text-dark border">' . htmlspecialchars($r['payment_method']) . '</span>' : '<span class="text-muted small">-</span>';
                            $vaDisplay = !empty($r['va_number']) ? '<div class="mt-1"><code class="small text-primary">' . htmlspecialchars($r['va_number']) . '</code></div>' : '<span class="text-muted small d-block mt-1">-</span>';

                            // Precalculate all VAs for this student to pass to JS modal
                            $vaList = generateStudentVaList($r['nim']);
                            $r['va_list'] = $vaList;

                            $html .= '<tr>';
                            $html .= '<td class="text-center fw-bold text-secondary">' . $i . '</td>';
                            $html .= '<td><strong class="font-monospace text-primary">' . htmlspecialchars($r['bill_code'] ?? 'INV-' . $r['id']) . '</strong></td>';
                            $html .= '<td>';
                            $html .= '<div class="fw-semibold text-dark">' . htmlspecialchars($r['full_name']) . '</div>';
                            $html .= '<small class="text-muted font-monospace">' . htmlspecialchars($r['nim']) . '</small>';
                            $html .= '</td>';
                            $html .= '<td><span class="badge bg-light text-dark border">' . htmlspecialchars($r['major_code']) . '</span> <small class="text-muted d-block">' . htmlspecialchars($r['major_name']) . '</small></td>';
                            $html .= '<td><div>' . htmlspecialchars($r['bill_type']) . '</div><small class="text-muted">' . htmlspecialchars($r['academic_year']) . '</small></td>';
                            $html .= '<td class="fw-bold text-dark text-nowrap">Rp ' . number_format((float)$r['amount'], 0, ',', '.') . '</td>';
                            $html .= '<td class="small text-muted text-nowrap">' . date('d M Y', strtotime($r['due_date'])) . '</td>';
                            $html .= '<td><div>' . $payMethodDisplay . '</div>' . $vaDisplay . '</td>';
                            $html .= '<td class="text-center">' . $statusBadge . '</td>';
                            $html .= '<td class="text-center text-nowrap">';
                            $html .= '<div class="btn-group btn-group-sm" role="group">';
                            $html .= '<button type="button" class="btn btn-outline-primary btn-bill-detail" data-bill=\'' . htmlspecialchars(json_encode($r), ENT_QUOTES, "UTF-8") . '\' title="Detail Tagihan & Pembayaran VA"><i class="bi bi-wallet2"></i> Detail / VA</button>';
                            if ($r['payment_status'] === 'Paid') {
                                $html .= '<a href="src/reporting/pdf_bill_receipt.php?bill_id=' . $r['id'] . '" target="_blank" class="btn btn-outline-success" title="Cetak Kuitansi Resmi"><i class="bi bi-printer"></i> Kuitansi</a>';
                            } elseif ($r['payment_status'] !== 'Cancelled') {
                                $html .= '<button type="button" class="btn btn-outline-danger btn-cancel-bill" data-id="' . $r['id'] . '" title="Batalkan Tagihan"><i class="bi bi-x-circle"></i></button>';
                            }
                            $html .= '</div>';
                            $html .= '</td>';
                            $html .= '</tr>';
                        }
                    } else {
                        $html .= '<tr><td colspan="10" class="text-center py-4 text-muted">Tidak ada data tagihan mahasiswa yang ditemukan.</td></tr>';
                    }

                    $response = [
                        'status' => 'success',
                        'html' => $html,
                        'pages' => $pages,
                        'metrics' => [
                            'total_billed_amount' => number_format((float)$metrics['total_billed_amount'], 0, ',', '.'),
                            'total_billed_count' => (int)$metrics['total_billed_count'],
                            'total_paid_amount' => number_format((float)$metrics['total_paid_amount'], 0, ',', '.'),
                            'total_paid_count' => (int)$metrics['total_paid_count'],
                            'total_unpaid_amount' => number_format((float)$metrics['total_unpaid_amount'], 0, ',', '.'),
                            'total_unpaid_count' => (int)$metrics['total_unpaid_count'],
                        ]
                    ];
                } catch (Exception $e) {
                    $response = ['status' => 'error', 'message' => $e->getMessage()];
                }
                echo json_encode($response);
                break;

            case 'generateMassBills':
                try {
                    $academicYear = trim($_POST['academic_year'] ?? '');
                    $billType = trim($_POST['bill_type'] ?? 'UKT Pokok');
                    $amount = (float)($_POST['amount'] ?? 0);
                    $dueDate = trim($_POST['due_date'] ?? '');
                    $majorId = trim($_POST['major_id'] ?? 'all');
                    $batchYear = trim($_POST['batch_year'] ?? 'all');

                    if (empty($academicYear) || empty($billType) || $amount <= 0 || empty($dueDate)) {
                        throw new Exception("Harap isi seluruh field wajib (Tahun Akademik, Jenis Tagihan, Nominal, dan Jatuh Tempo)!");
                    }

                    $where = ["academic_status = 'Aktif'"];
                    $params = [];
                    if ($majorId !== 'all' && !empty($majorId)) {
                        $where[] = "major_id = ?";
                        $params[] = (int)$majorId;
                    }
                    if ($batchYear !== 'all' && !empty($batchYear)) {
                        $where[] = "batch_year = ?";
                        $params[] = (int)$batchYear;
                    }

                    $whereClause = implode(" AND ", $where);
                    $stmtStudents = $conn->prepare("SELECT id, nim FROM students_data WHERE $whereClause ORDER BY id ASC");
                    $stmtStudents->execute($params);
                    $students = $stmtStudents->fetchAll();

                    if (empty($students)) {
                        throw new Exception("Tidak ada mahasiswa aktif yang sesuai dengan kriteria filter yang dipilih.");
                    }

                    $conn->beginTransaction();

                    $yearClean = str_replace(['/', ' '], '', explode(' ', $academicYear)[0]);
                    $semCode = (strpos($academicYear, 'Ganjil') !== false) ? '1' : '2';

                    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM students_bills_data WHERE student_id = ? AND academic_year = ? AND bill_type = ?");
                    $insertStmt = $conn->prepare("
                        INSERT INTO students_bills_data 
                        (student_id, bill_code, academic_year, bill_type, amount, due_date, payment_status, va_number, notes)
                        VALUES (?, ?, ?, ?, ?, ?, 'Unpaid', ?, ?)
                    ");

                    $insertedCount = 0;
                    foreach ($students as $st) {
                        $checkStmt->execute([$st['id'], $academicYear, $billType]);
                        if ($checkStmt->fetchColumn() > 0) {
                            continue; // Skip duplicate
                        }

                        $cleanNim = str_replace('.', '', $st['nim']);
                        $billCode = 'INV-' . $yearClean . $semCode . '-' . $cleanNim;

                        $numericNim = preg_replace_callback('/^[A-Za-z]/', function($m) {
                            return ord(strtoupper($m[0])) - 64;
                        }, $st['nim']);
                        $defaultVa = '008.' . $numericNim; // Default Mandiri VA

                        $insertStmt->execute([
                            $st['id'],
                            $billCode,
                            $academicYear,
                            $billType,
                            $amount,
                            $dueDate,
                            $defaultVa,
                            "Tagihan massal $billType periode $academicYear"
                        ]);
                        $insertedCount++;
                    }

                    $conn->commit();

                    $response = [
                        'status' => 'success',
                        'message' => "Berhasil menerbitkan $insertedCount tagihan baru untuk periode $academicYear!"
                    ];
                } catch (Exception $e) {
                    if ($conn && $conn->inTransaction()) {
                        $conn->rollBack();
                    }
                    $response = ['status' => 'error', 'message' => $e->getMessage()];
                }
                echo json_encode($response);
                break;

            case 'createStudentBill':
                try {
                    $nim = trim($_POST['nim'] ?? '');
                    $academicYear = trim($_POST['academic_year'] ?? '');
                    $billType = trim($_POST['bill_type'] ?? 'UKT Pokok');
                    $amount = (float)($_POST['amount'] ?? 0);
                    $dueDate = trim($_POST['due_date'] ?? '');
                    $notes = trim($_POST['notes'] ?? '');

                    if (empty($nim) || empty($academicYear) || empty($billType) || $amount <= 0 || empty($dueDate)) {
                        throw new Exception("Harap lengkapi semua kolom wajib!");
                    }

                    $findStudent = $conn->prepare("SELECT id, nim FROM students_data WHERE nim = ?");
                    $findStudent->execute([$nim]);
                    $st = $findStudent->fetch();
                    if (!$st) {
                        throw new Exception("Mahasiswa dengan NIM $nim tidak ditemukan!");
                    }

                    $checkDuplicate = $conn->prepare("SELECT COUNT(*) FROM students_bills_data WHERE student_id = ? AND academic_year = ? AND bill_type = ?");
                    $checkDuplicate->execute([$st['id'], $academicYear, $billType]);
                    if ($checkDuplicate->fetchColumn() > 0) {
                        throw new Exception("Tagihan $billType untuk mahasiswa ini pada periode $academicYear sudah ada!");
                    }

                    $yearClean = str_replace(['/', ' '], '', explode(' ', $academicYear)[0]);
                    $semCode = (strpos($academicYear, 'Ganjil') !== false) ? '1' : '2';
                    $cleanNim = str_replace('.', '', $st['nim']);
                    $billCode = 'INV-' . $yearClean . $semCode . '-' . $cleanNim . '-' . rand(10, 99);

                    $numericNim = preg_replace_callback('/^[A-Za-z]/', function($m) {
                        return ord(strtoupper($m[0])) - 64;
                    }, $st['nim']);
                    $defaultVa = '008.' . $numericNim;

                    $insertStmt = $conn->prepare("
                        INSERT INTO students_bills_data 
                        (student_id, bill_code, academic_year, bill_type, amount, due_date, payment_status, va_number, notes)
                        VALUES (?, ?, ?, ?, ?, ?, 'Unpaid', ?, ?)
                    ");
                    $insertStmt->execute([$st['id'], $billCode, $academicYear, $billType, $amount, $dueDate, $defaultVa, $notes]);

                    $response = ['status' => 'success', 'message' => "Tagihan perorangan berhasil diterbitkan!"];
                } catch (Exception $e) {
                    $response = ['status' => 'error', 'message' => $e->getMessage()];
                }
                echo json_encode($response);
                break;

            case 'payBillViaVA':
                try {
                    $billId = (int)($_POST['bill_id'] ?? 0);
                    $bankName = trim($_POST['bank_name'] ?? 'Mandiri');

                    if (!$billId) {
                        throw new Exception("ID Tagihan tidak valid!");
                    }

                    $stmt = $conn->prepare("
                        SELECT b.*, s.nim, s.id AS student_id, p.full_name 
                        FROM students_bills_data b
                        JOIN students_data s ON b.student_id = s.id
                        JOIN personal_profiles p ON s.user_id = p.user_id
                        WHERE b.id = ?
                    ");
                    $stmt->execute([$billId]);
                    $bill = $stmt->fetch();

                    if (!$bill) {
                        throw new Exception("Data tagihan tidak ditemukan!");
                    }
                    if ($bill['payment_status'] === 'Paid') {
                        throw new Exception("Tagihan ini sudah lunas sebelumnya!");
                    }

                    // Get bank code from config
                    $config = getPaymentMethodsConfig();
                    $bankCode = '008';
                    if (!empty($config['virtual_accounts'])) {
                        foreach ($config['virtual_accounts'] as $va) {
                            if (strcasecmp($va['name'], $bankName) === 0) {
                                $bankCode = $va['bank_code'];
                                $bankName = $va['name'];
                                break;
                            }
                        }
                    }

                    $numericNim = preg_replace_callback('/^[A-Za-z]/', function($m) {
                        return ord(strtoupper($m[0])) - 64;
                    }, $bill['nim']);
                    $finalVa = $bankCode . '.' . $numericNim;

                    $conn->beginTransaction();

                    // Update bill status to Paid automatically
                    $updateBill = $conn->prepare("
                        UPDATE students_bills_data SET
                            payment_status = 'Paid',
                            payment_method = ?,
                            va_number = ?,
                            paid_at = CURRENT_TIMESTAMP,
                            verified_by = 'VA-System (Auto)',
                            notes = CONCAT(COALESCE(notes, ''), ' [Auto-verify via ' || ? || ' VA ' || ? || ']')
                        WHERE id = ?
                    ");
                    $updateBill->execute([$bankName, $finalVa, $bankName, $finalVa, $billId]);

                    // Sync student status if bill is UKT Pokok or Daftar Ulang
                    if (in_array($bill['bill_type'], ['UKT Pokok', 'Daftar Ulang'])) {
                        $conn->prepare("UPDATE students_data SET payment_status = 'Lunas' WHERE id = ?")
                             ->execute([$bill['student_id']]);
                    }

                    $conn->commit();

                    $response = [
                        'status' => 'success',
                        'message' => "Pembayaran via Virtual Account $bankName ($finalVa) sebesar Rp " . number_format((float)$bill['amount'], 0, ',', '.') . " BERHASIL! Status otomatis terverifikasi LUNAS."
                    ];
                } catch (Exception $e) {
                    if ($conn && $conn->inTransaction()) {
                        $conn->rollBack();
                    }
                    $response = ['status' => 'error', 'message' => $e->getMessage()];
                }
                echo json_encode($response);
                break;

            case 'verifyStudentBill':
                try {
                    $billId = (int)($_POST['bill_id'] ?? 0);
                    $action = trim($_POST['action'] ?? 'Approve');
                    $paymentMethod = trim($_POST['payment_method'] ?? 'Tunai');
                    $notes = trim($_POST['notes'] ?? '');

                    $stmt = $conn->prepare("SELECT b.*, s.id AS student_id FROM students_bills_data b JOIN students_data s ON b.student_id = s.id WHERE b.id = ?");
                    $stmt->execute([$billId]);
                    $bill = $stmt->fetch();

                    if (!$bill) {
                        throw new Exception("Data tagihan tidak ditemukan!");
                    }

                    $adminUsername = $_SESSION['user']['userid'] ?? $_SESSION['user']['username'] ?? 'Admin TU';

                    $conn->beginTransaction();

                    if ($action === 'Approve') {
                        $conn->prepare("
                            UPDATE students_bills_data SET
                                payment_status = 'Paid',
                                payment_method = ?,
                                paid_at = CURRENT_TIMESTAMP,
                                verified_by = ?,
                                notes = CONCAT(COALESCE(notes, ''), ' [Manual verify: ' || ? || ']')
                            WHERE id = ?
                        ")->execute([$paymentMethod, $adminUsername, $notes, $billId]);

                        if (in_array($bill['bill_type'], ['UKT Pokok', 'Daftar Ulang'])) {
                            $conn->prepare("UPDATE students_data SET payment_status = 'Lunas' WHERE id = ?")
                                 ->execute([$bill['student_id']]);
                        }
                        $msg = "Tagihan berhasil diverifikasi Lunas!";
                    } else {
                        // Reject / Reset
                        $conn->prepare("
                            UPDATE students_bills_data SET
                                payment_status = 'Unpaid',
                                payment_proof = NULL,
                                notes = CONCAT(COALESCE(notes, ''), ' [Ditolak TU: ' || ? || ']')
                            WHERE id = ?
                        ")->execute([$notes, $billId]);
                        $msg = "Status pembayaran tagihan telah di-reset menjadi Belum Lunas.";
                    }

                    $conn->commit();
                    $response = ['status' => 'success', 'message' => $msg];
                } catch (Exception $e) {
                    if ($conn && $conn->inTransaction()) {
                        $conn->rollBack();
                    }
                    $response = ['status' => 'error', 'message' => $e->getMessage()];
                }
                echo json_encode($response);
                break;

            case 'cancelStudentBill':
                try {
                    $billId = (int)($_POST['bill_id'] ?? 0);
                    $stmt = $conn->prepare("SELECT payment_status FROM students_bills_data WHERE id = ?");
                    $stmt->execute([$billId]);
                    $status = $stmt->fetchColumn();

                    if (!$status) {
                        throw new Exception("Tagihan tidak ditemukan!");
                    }
                    if ($status === 'Paid') {
                        throw new Exception("Tagihan yang sudah Lunas tidak dapat dibatalkan!");
                    }

                    $conn->prepare("UPDATE students_bills_data SET payment_status = 'Cancelled' WHERE id = ?")->execute([$billId]);
                    $response = ['status' => 'success', 'message' => "Tagihan berhasil dibatalkan."];
                } catch (Exception $e) {
                    $response = ['status' => 'error', 'message' => $e->getMessage()];
                }
                echo json_encode($response);
                break;

            case 'sessionCheck':
                $sessionTimeout = 1600;
                if (isset($_SESSION['last_activity'])) {
                    $inactive = time() - $_SESSION['last_activity'];
                    if ($inactive > $sessionTimeout) {
                        $response['status'] = 'timeout';
                    } else {
                        $response['status'] = 'active';
                    }
                } else {
                    $_SESSION['last_activity'] = time();
                    $response['status'] = 'inactive';
                }
                echo json_encode($response['status']);
                break;

            default:
                echo json_encode('invalid requests!');
                break;
        }
    } else {
        header("location: ../index.php");
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    $req = $_GET['req'] ?? '';
    $redirectActions = ['userLogin', 'applicantLogin', 'applicantRegister', 'applicantLogout', 'applicantUpdateProfile', 'applicantApplyProgram'];
    if (!in_array($req, $redirectActions)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
    header("location: ../index.php");
    exit;
}
