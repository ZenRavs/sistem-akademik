<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once defined('CONFIG_PATH') ? CONFIG_PATH . '/db.php' : __DIR__ . '/../../config/db.php';

if (!$conn) {
    if (empty($_SESSION['error'])) {
        $_SESSION['error'] = 'Gagal terhubung ke Database Cloud. Pastikan koneksi internet Anda aktif.';
    }
    header("location: index.php");
    exit;
}

if (!isset($_SESSION['applicant'])) {
    $_SESSION['error'] = "Silakan login sebagai pendaftar terlebih dahulu!";
    header("location: index.php");
    exit;
}

// Cek Idle Timeout (30 menit inaktif)
$idleTimeoutSeconds = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $idleTimeoutSeconds)) {
    $timeoutUserId = $_SESSION['applicant']['user_id'] ?? ($_SESSION['applicant']['id'] ?? null);
    if ($timeoutUserId) {
        $conn->prepare("UPDATE users_credential SET session_token = NULL, last_active_at = NULL WHERE id = ?")->execute([$timeoutUserId]);
    }
    $_SESSION = [];
    if (session_id()) session_destroy();
    session_start();
    $_SESSION['error'] = 'Sesi pendaftaran Anda telah berakhir karena tidak ada aktivitas selama 30 menit. Silakan login kembali.';
    header("location: index.php");
    exit;
}

$userId = $_SESSION['applicant']['user_id'] ?? $_SESSION['applicant']['id'];
$getApplicantStmt = $conn->prepare("
    SELECT pmb.id AS pmb_id, pmb.user_id, pmb.nisn, pmb.mother_name, pmb.father_name, pmb.parent_phone,
           pmb.high_school_name AS school_origin, pmb.high_school_major AS major,
           pmb.high_school_address AS school_address, pmb.high_school_score AS final_score,
           pmb.application_status AS status, pmb.created_at,
           pmb.certificate_file, pmb.payment_proof, pmb.payment_status,
           p.full_name, p.nik, p.gender, p.pob, p.dob, p.religion, p.marital_status, p.job_status,
           p.phone, p.ktp_address, p.domicile_address AS address, p.photo AS pict,
           u.username, u.email, u.account_status, u.session_token,
           m.major_code AS program_code, m.major_name,
           sd.nim AS official_nim
    FROM users_credential u
    JOIN personal_profiles p ON p.user_id = u.id
    LEFT JOIN pmb_data pmb ON pmb.user_id = u.id
    LEFT JOIN majors_data m ON pmb.major_id = m.id
    LEFT JOIN students_data sd ON p.user_id = sd.user_id
    WHERE u.id = ? OR pmb.id = ?
");
$getApplicantStmt->execute([$userId, $userId]);
$applicant = $getApplicantStmt->fetch();
if ($applicant) {
    $applicant['id'] = $applicant['pmb_id'] ?? $applicant['user_id'];
}

if (!$applicant || ($applicant['account_status'] ?? 'active') !== 'active') {
    $_SESSION = [];
    if (session_id()) session_destroy();
    session_start();
    $_SESSION['error'] = 'Akun pendaftar tidak aktif atau tidak ditemukan.';
    header("location: index.php");
    exit;
}

// Single-Device Enforcement untuk pendaftar
if (!empty($applicant['session_token']) && isset($_SESSION['applicant']['session_token'])) {
    if ($_SESSION['applicant']['session_token'] !== $applicant['session_token']) {
        $_SESSION = [];
        if (session_id()) session_destroy();
        session_start();
        $_SESSION['error'] = 'Akun pendaftar telah masuk dari perangkat atau peramban lain. Sesi ini telah ditutup.';
        header("location: index.php");
        exit;
    }
}

// Update Activity Heartbeat
$_SESSION['last_activity'] = time();
if (!isset($_SESSION['last_db_heartbeat']) || (time() - $_SESSION['last_db_heartbeat'] > 60)) {
    $_SESSION['last_db_heartbeat'] = time();
    $conn->prepare("UPDATE users_credential SET last_active_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$applicant['user_id'] ?? $applicant['id']]);
}

$portalSuccess = $_SESSION['success'] ?? null;
$portalError = $_SESSION['error'] ?? null;
if ($portalSuccess) unset($_SESSION['success']);
if ($portalError) unset($_SESSION['error']);

// Ambil daftar program studi dari database
$majorsStmt = $conn->prepare("SELECT major_code, major_name FROM majors_data ORDER BY major_code ASC");
$majorsStmt->execute();
$majorsList = $majorsStmt->fetchAll();

// Kunci data jika status Pending atau Approved
$isLocked = in_array($applicant['status'], ['Pending', 'Approved']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="https://cdn-icons-png.freepik.com/512/7935/7935909.png?ga=GA1.1.599436757.1735230785" type="image/x-icon">
    <title>Portal Calon Mahasiswa - FIK</title>
    <!-- Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <style>
        body {
            background-color: #f4f6f9 !important;
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            padding-bottom: 60px;
        }
        .portal-header {
            background-color: #ffffff;
            padding: 15px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .btn-accent {
            background-color: #0f4c92;
            border-color: #0f4c92;
            color: #ffffff;
        }
        .btn-accent:hover, .btn-accent:focus {
            background-color: #0b3a70;
            border-color: #0b3a70;
            color: #ffffff;
        }
        .bg-accent {
            background-color: #0f4c92 !important;
        }
        .text-accent {
            color: #0f4c92 !important;
        }
        .section-header {
            font-size: 0.95rem;
            letter-spacing: 0.5px;
            color: #0f4c92;
        }
        .field-divider {
            border-top: 1px solid #000000;
            opacity: 0.1;
            margin: 1.5rem 0;
        }
        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 15px;
        }
        .card {
            border-radius: 12px;
            overflow: hidden;
        }
        
        /* Stepper Styles */
        .stepper-wrapper {
            position: relative;
            margin: 1rem 2rem 3rem 2rem;
        }
        .stepper-line {
            height: 4px;
            background-color: #e9ecef;
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            transform: translateY(-50%);
            z-index: 1;
        }
        .stepper-progress {
            height: 4px;
            background-color: #0f4c92;
            position: absolute;
            top: 50%;
            left: 0;
            transform: translateY(-50%);
            z-index: 2;
            transition: width 0.3s ease;
        }
        .stepper-item {
            position: relative;
            z-index: 3;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .stepper-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 0.5rem;
            border: 4px solid #fff;
        }
        .stepper-item.active .stepper-circle {
            background-color: #0f4c92;
            color: #fff;
        }
        .stepper-item.danger .stepper-circle {
            background-color: #dc3545;
            color: #fff;
        }
        .stepper-label {
            position: absolute;
            top: 45px;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
            width: 120px;
            color: #495057;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="portal-header mb-4">
        <div class="container d-flex align-items-center justify-content-between">
            <a class="navbar-brand d-flex align-items-center text-decoration-none" href="#">
                <img src="https://portal.dinus.ac.id/assets/images/logo_dinus_new.png" alt="Logo UDINUS" width="70">
                <div class="ms-3">
                    <h5 class="m-0 fw-bold text-dark">Portal Pendaftaran Mahasiswa</h5>
                    <small class="text-muted">Fakultas Ilmu Komputer</small>
                </div>
            </a>
            <div class="d-flex align-items-center">
                <span class="fs-5 text-dark me-3 d-none d-md-inline fw-semibold">Halo, <strong><?php echo htmlspecialchars($applicant['full_name']); ?></strong>! 👋</span>
                <a href="src/api.php?req=applicantLogout" class="btn btn-accent btn-sm px-3 fw-bold rounded-pill">Logout</a>
            </div>
        </div>
    </header>

    <div class="container my-4">
        <?php if ($portalSuccess): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <strong>Berhasil:</strong> <?php echo htmlspecialchars($portalSuccess); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($portalError): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <strong>Error:</strong> <?php echo htmlspecialchars($portalError); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Progress Stepper -->
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body py-4">
                <?php
                    $progressWidth = '0%';
                    $step1Class = 'active';
                    $step2Class = '';
                    $step3Class = '';
                    
                    if ($applicant['status'] === 'Draft') {
                        $progressWidth = '0%';
                    } elseif ($applicant['status'] === 'Pending') {
                        $progressWidth = '50%';
                        $step2Class = 'active';
                    } elseif ($applicant['status'] === 'Approved') {
                        $progressWidth = '100%';
                        $step2Class = 'active';
                        $step3Class = 'active';
                    } elseif ($applicant['status'] === 'Rejected') {
                        $progressWidth = '100%';
                        $step2Class = 'active';
                        $step3Class = 'danger';
                    }
                ?>
                <div class="stepper-wrapper d-flex justify-content-between">
                    <div class="stepper-line"></div>
                    <div class="stepper-progress" style="width: <?php echo $progressWidth; ?>;"></div>
                    
                    <div class="stepper-item <?php echo $step1Class; ?>">
                        <div class="stepper-circle">1</div>
                        <div class="stepper-label">Lengkapi Data</div>
                    </div>
                    <div class="stepper-item <?php echo $step2Class; ?>">
                        <div class="stepper-circle">2</div>
                        <div class="stepper-label">Verifikasi TU</div>
                    </div>
                    <div class="stepper-item <?php echo $step3Class; ?>">
                        <div class="stepper-circle">3</div>
                        <div class="stepper-label">Hasil Akhir</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Banner -->
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body d-flex justify-content-between align-items-center py-3">
                <div>
                    <h5 class="m-0 text-accent fw-bold">Status Pendaftaran Anda:</h5>
                    <small class="text-muted">ID Pendaftaran: #APP-<?php echo str_pad($applicant['id'], 4, '0', STR_PAD_LEFT); ?></small>
                </div>
                <div>
                    <?php if ($applicant['status'] === 'Draft'): ?>
                        <span class="badge bg-secondary fs-6 px-3 py-2 rounded-pill">Draft (Belum Submit)</span>
                    <?php elseif ($applicant['status'] === 'Pending'): ?>
                        <span class="badge bg-warning text-dark fs-6 px-3 py-2 rounded-pill">Pending Verifikasi TU</span>
                    <?php elseif ($applicant['status'] === 'Approved'): ?>
                        <span class="badge bg-success fs-6 px-3 py-2 rounded-pill">Diterima / Approved</span>
                    <?php else: ?>
                        <span class="badge bg-danger fs-6 px-3 py-2 rounded-pill">Ditolak</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column: Form Kelompok Data Diri Pendaftar -->
            <div class="col-md-7 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white pt-4 pb-3 border-bottom">
                        <h5 class="card-title m-0 text-accent fw-bold">📑 Kelengkapan Data Pendaftar</h5>
                    </div>
                    <div class="card-body p-4">
                        <!-- Photo Section -->
                        <div class="text-center mb-4 p-4 bg-light rounded-3 border-0 shadow-sm">
                            <?php
                            $pictName = $applicant['pict'] ?? '';
                            $defaultPict = 'https://cdn-icons-png.freepik.com/512/3875/3875148.png?ga=GA1.1.599436757.1735230785';
                            $imgSrc = $defaultPict;
                            if ($pictName) {
                                $uploadDir = defined('UPLOAD_PATH') ? UPLOAD_PATH : __DIR__ . '/../../public/uploads/user_photos';
                                $pmbUploadDir = defined('PMB_DOCS_PATH') ? PMB_DOCS_PATH : __DIR__ . '/../../public/uploads/pmb_docs';
                                if (file_exists($uploadDir . '/' . $pictName)) {
                                    $imgSrc = defined('UPLOAD_URL') ? UPLOAD_URL . $pictName : './public/uploads/user_photos/' . $pictName;
                                } elseif (file_exists($pmbUploadDir . '/' . $pictName)) {
                                    $imgSrc = defined('PMB_DOCS_URL') ? PMB_DOCS_URL . $pictName : './public/uploads/pmb_docs/' . $pictName;
                                }
                            }
                            ?>
                            <img src="<?php echo $imgSrc; ?>" class="rounded-circle border border-3 shadow-sm object-fit-cover mb-3" width="130" height="130" alt="Foto Pendaftar">
                            <div>
                                <small class="text-muted d-block fw-semibold">Foto Profil Formal Pendaftar</small>
                            </div>
                        </div>

                        <form action="src/api.php?req=applicantUpdateProfile" method="POST" enctype="multipart/form-data">
                            <div class="mb-4">
                                <label class="form-label fw-bold text-dark small">Upload / Ganti Foto Profil</label>
                                <input type="file" class="form-control" name="pict" accept="image/*" <?php echo $isLocked ? 'disabled' : ''; ?>>
                            </div>

                            <!-- SEKELOMPOK 1: Data Diri Mahasiswa -->
                            <h6 class="section-header fw-bold text-uppercase mb-3">👤 1. Data Diri Mahasiswa</h6>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark small">Nama Lengkap</label>
                                <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($applicant['full_name']); ?>" required <?php echo $isLocked ? 'readonly disabled' : ''; ?>>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-dark small">NIK (No. KTP 16-Digit) 🔒</label>
                                    <input type="text" class="form-control" name="nik" maxlength="16" pattern="\d{16}" title="NIK harus 16 angka" value="<?php echo htmlspecialchars($applicant['nik'] ?? ''); ?>" placeholder="3374xxxxxxxxxxxx" required <?php echo $isLocked ? 'readonly disabled' : ''; ?>>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-dark small">NISN (10-Digit)</label>
                                    <input type="text" class="form-control" name="nisn" maxlength="10" pattern="\d{10}" title="NISN harus 10 angka" value="<?php echo htmlspecialchars($applicant['nisn'] ?? ''); ?>" placeholder="00xxxxxxxx" <?php echo $isLocked ? 'readonly disabled' : ''; ?>>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-dark small">Email Utama</label>
                                    <input type="email" class="form-control bg-light" value="<?php echo htmlspecialchars($applicant['email']); ?>" readonly disabled>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-dark small">No. HP / WhatsApp</label>
                                    <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($applicant['phone'] ?? ''); ?>" placeholder="08123456789" required <?php echo $isLocked ? 'readonly disabled' : ''; ?>>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-dark small">Jenis Kelamin</label>
                                    <select class="form-select" name="gender" required <?php echo $isLocked ? 'disabled' : ''; ?>>
                                        <option value="" disabled <?php echo empty($applicant['gender']) ? 'selected' : ''; ?>>-- Pilih Jenis Kelamin --</option>
                                        <option value="Laki-laki" <?php echo (($applicant['gender'] ?? '') === 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                                        <option value="Perempuan" <?php echo (($applicant['gender'] ?? '') === 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-dark small">Agama</label>
                                    <select class="form-select" name="religion" required <?php echo $isLocked ? 'disabled' : ''; ?>>
                                        <option value="" disabled <?php echo empty($applicant['religion']) ? 'selected' : ''; ?>>-- Pilih Agama --</option>
                                        <option value="Islam" <?php echo (($applicant['religion'] ?? '') === 'Islam') ? 'selected' : ''; ?>>Islam</option>
                                        <option value="Kristen" <?php echo (($applicant['religion'] ?? '') === 'Kristen') ? 'selected' : ''; ?>>Kristen</option>
                                        <option value="Katolik" <?php echo (($applicant['religion'] ?? '') === 'Katolik') ? 'selected' : ''; ?>>Katolik</option>
                                        <option value="Hindu" <?php echo (($applicant['religion'] ?? '') === 'Hindu') ? 'selected' : ''; ?>>Hindu</option>
                                        <option value="Buddha" <?php echo (($applicant['religion'] ?? '') === 'Buddha') ? 'selected' : ''; ?>>Buddha</option>
                                        <option value="Khonghucu" <?php echo (($applicant['religion'] ?? '') === 'Khonghucu') ? 'selected' : ''; ?>>Khonghucu</option>
                                        <option value="Lainnya" <?php echo (($applicant['religion'] ?? '') === 'Lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-dark small">Tempat Lahir</label>
                                    <input type="text" class="form-control" name="pob" value="<?php echo htmlspecialchars($applicant['pob'] ?? ''); ?>" placeholder="Kota Kelahiran" required <?php echo $isLocked ? 'readonly disabled' : ''; ?>>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-dark small">Tanggal Lahir</label>
                                    <input type="date" class="form-control" name="dob" value="<?php echo htmlspecialchars($applicant['dob'] ?? ''); ?>" required <?php echo $isLocked ? 'readonly disabled' : ''; ?>>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-dark small">Status Pernikahan</label>
                                    <select class="form-select" name="marital_status" <?php echo $isLocked ? 'disabled' : ''; ?>>
                                        <option value="Belum Menikah" <?php echo (($applicant['marital_status'] ?? '') === 'Belum Menikah' || empty($applicant['marital_status'])) ? 'selected' : ''; ?>>Belum Menikah</option>
                                        <option value="Menikah" <?php echo (($applicant['marital_status'] ?? '') === 'Menikah') ? 'selected' : ''; ?>>Menikah</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-dark small">Status Pekerjaan</label>
                                    <select class="form-select" name="job_status" <?php echo $isLocked ? 'disabled' : ''; ?>>
                                        <option value="Belum Bekerja" <?php echo (($applicant['job_status'] ?? '') === 'Belum Bekerja' || empty($applicant['job_status'])) ? 'selected' : ''; ?>>Belum Bekerja</option>
                                        <option value="Bekerja" <?php echo (($applicant['job_status'] ?? '') === 'Bekerja') ? 'selected' : ''; ?>>Bekerja</option>
                                    </select>
                                </div>
                            </div>

                            <hr class="field-divider">

                            <!-- SEKELOMPOK 2: Data Orang Tua / Wali -->
                            <h6 class="section-header fw-bold text-uppercase mb-3">👨‍👩‍👧 2. Data Orang Tua / Wali</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-dark small">Nama Ibu Kandung</label>
                                    <input type="text" class="form-control" name="mother_name" value="<?php echo htmlspecialchars($applicant['mother_name'] ?? ''); ?>" placeholder="Nama lengkap ibu kandung" required <?php echo $isLocked ? 'readonly disabled' : ''; ?>>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-dark small">Nama Ayah</label>
                                    <input type="text" class="form-control" name="father_name" value="<?php echo htmlspecialchars($applicant['father_name'] ?? ''); ?>" placeholder="Nama lengkap ayah" <?php echo $isLocked ? 'readonly disabled' : ''; ?>>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark small">No. HP / WhatsApp Orang Tua</label>
                                <input type="text" class="form-control" name="parent_phone" value="<?php echo htmlspecialchars($applicant['parent_phone'] ?? ''); ?>" placeholder="08123456789" <?php echo $isLocked ? 'readonly disabled' : ''; ?>>
                            </div>

                            <hr class="field-divider">

                            <!-- SEKELOMPOK 3: Data Sekolah Asal & Akademik -->
                            <h6 class="section-header fw-bold text-uppercase mb-3">🏫 3. Data Sekolah Asal & Akademik</h6>
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label class="form-label fw-bold text-dark small">Asal Sekolah (SMA/SMK)</label>
                                    <input type="text" class="form-control" name="school_origin" value="<?php echo htmlspecialchars($applicant['school_origin'] ?? ''); ?>" placeholder="Contoh: SMAN 1 Semarang" required <?php echo $isLocked ? 'readonly disabled' : ''; ?>>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-dark small">Jurusan Sekolah Asal</label>
                                    <input type="text" class="form-control" name="major" value="<?php echo htmlspecialchars($applicant['major'] ?? ''); ?>" placeholder="Contoh: IPA / IPS / TKJ / RPL" <?php echo $isLocked ? 'readonly disabled' : ''; ?>>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label class="form-label fw-bold text-dark small">Nilai Akhir Rata-rata</label>
                                    <input type="number" step="0.01" min="0" max="100" class="form-control" name="final_score" value="<?php echo htmlspecialchars($applicant['final_score'] ?? ''); ?>" placeholder="Contoh: 88.50" required <?php echo $isLocked ? 'readonly disabled' : ''; ?>>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-dark small">Alamat Sekolah Asal</label>
                                    <textarea class="form-control" name="school_address" rows="1" placeholder="Alamat kota/lokasi sekolah asal" required <?php echo $isLocked ? 'readonly disabled' : ''; ?>><?php echo htmlspecialchars($applicant['school_address'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <hr class="field-divider">

                            <!-- SEKELOMPOK 4: Data Domisili / Alamat -->
                            <h6 class="section-header fw-bold text-uppercase mb-3">🏠 4. Data Tempat Tinggal / Alamat</h6>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark small">Alamat Sesuai KTP</label>
                                <textarea class="form-control" name="ktp_address" rows="2" placeholder="Alamat lengkap sesuai KTP" <?php echo $isLocked ? 'readonly disabled' : ''; ?>><?php echo htmlspecialchars($applicant['ktp_address'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold text-dark small">Alamat Domisili (Tempat Tinggal Saat Ini)</label>
                                <textarea class="form-control" name="address" rows="2" placeholder="Alamat domisili saat ini" required <?php echo $isLocked ? 'readonly disabled' : ''; ?>><?php echo htmlspecialchars($applicant['address'] ?? ''); ?></textarea>
                            </div>

                            <hr class="field-divider">

                            <!-- SEKELOMPOK 5: Berkas Pendaftaran & Pembayaran -->
                            <h6 class="section-header fw-bold text-uppercase mb-3">📄 5. Berkas Pendaftaran & Pembayaran</h6>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark small">Scan Ijazah / SKL (PDF)</label>
                                <?php if(!empty($applicant['certificate_file'])): ?>
                                    <?php $certUrl = defined('PMB_CERT_URL') ? PMB_CERT_URL : './public/uploads/pmb_docs/certificates/'; ?>
                                    <div class="mb-2"><a href="<?php echo $certUrl . htmlspecialchars($applicant['certificate_file']); ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">Lihat File Tersimpan</a></div>
                                <?php endif; ?>
                                <input type="file" class="form-control" name="certificate_file" accept=".pdf" <?php echo $isLocked ? 'disabled' : ''; ?>>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold text-dark small">Bukti Pembayaran Pendaftaran (Image/PDF)</label>
                                <?php if(!empty($applicant['payment_proof'])): ?>
                                    <?php $payUrl = defined('PMB_PAY_URL') ? PMB_PAY_URL : './public/uploads/pmb_docs/payments/'; ?>
                                    <div class="mb-2">
                                        <a href="<?php echo $payUrl . htmlspecialchars($applicant['payment_proof']); ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-2">Lihat Bukti Tersimpan</a>
                                        <span class="badge <?php echo ($applicant['payment_status'] === 'Verified') ? 'bg-success' : 'bg-warning text-dark'; ?> rounded-pill">
                                            Status Pembayaran: <?php echo htmlspecialchars($applicant['payment_status'] ?? 'Pending'); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" name="payment_proof" accept="image/*,.pdf" <?php echo $isLocked ? 'disabled' : ''; ?>>
                            </div>

                            <?php if ($isLocked): ?>
                                <div class="alert alert-secondary text-center py-3 mb-0 rounded-3">
                                    <small>🔒 <strong>Data Diri Dikunci:</strong> Pendaftaran Anda sedang dalam proses atau telah disetujui. Perubahan data diri hanya dapat dilakukan melalui Tata Usaha (TU).</small>
                                </div>
                            <?php else: ?>
                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-accent py-3 fw-bold rounded-3 fs-6">Simpan Perubahan Data Diri & Berkas</button>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Column: Apply Jurusan & Status -->
            <div class="col-md-5">
                <div class="card shadow-sm border-0 mb-4 sticky-md-top" style="top: 2rem;">
                    <div class="card-header bg-white pt-4 pb-3 border-bottom">
                        <h5 class="card-title m-0 text-accent fw-bold">🎯 Pengajuan Pilihan Program Studi</h5>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($applicant['status'] === 'Approved'): ?>
                            <div class="alert alert-success text-center py-4 mb-0 rounded-3 border-0 shadow-sm">
                                <h5 class="fw-bold mb-3">🎉 Selamat! Anda Diterima!</h5>
                                <p class="mb-1">Program Studi:</p>
                                <p class="fw-bold fs-5 mb-3 text-success"><?php echo htmlspecialchars($applicant['program_code'] . ' - ' . $applicant['major_name']); ?></p>
                                <p class="mb-1">NIM Resmi Anda:</p>
                                <p class="fw-bold fs-4 mb-3"><?php echo htmlspecialchars($applicant['official_nim'] ?? '-'); ?></p>
                                <hr>
                                <small class="d-block mt-2">Gunakan NIM dan password Anda untuk login ke Portal Mahasiswa.</small>
                            </div>
                        <?php elseif ($applicant['status'] === 'Pending'): ?>
                            <div class="alert alert-warning text-center py-4 mb-0 rounded-3 border-0 shadow-sm">
                                <h5 class="fw-bold mb-3">⏳ Berkas Sedang Diverifikasi</h5>
                                <p class="mb-1">Program Studi Pilihan:</p>
                                <p class="fw-bold fs-5 mb-3 text-dark"><?php echo htmlspecialchars($applicant['program_code'] . ' - ' . $applicant['major_name']); ?></p>
                                <hr>
                                <small class="d-block mt-2">Tim Tata Usaha (TU) sedang meninjau kelengkapan berkas dan bukti pembayaran Anda.</small>
                            </div>
                        <?php elseif ($applicant['status'] === 'Rejected'): ?>
                            <div class="alert alert-danger text-center py-4 mb-0 rounded-3 border-0 shadow-sm">
                                <h5 class="fw-bold mb-3">❌ Mohon Maaf, Pendaftaran Ditolak</h5>
                                <p class="mb-1">Program Studi Pilihan:</p>
                                <p class="fw-bold fs-5 mb-3"><?php echo htmlspecialchars($applicant['program_code'] . ' - ' . $applicant['major_name']); ?></p>
                                <hr>
                                <small class="d-block mt-2">Silakan hubungi pihak admisi kampus untuk informasi lebih lanjut mengenai status pendaftaran Anda.</small>
                            </div>
                        <?php else: ?>
                            <form action="src/api.php?req=applicantApplyProgram" method="POST">
                                <div class="mb-4">
                                    <label for="program_code" class="form-label fw-bold text-dark">Pilih 1 Program Studi Pilihan</label>
                                    <select class="form-select form-select-lg" id="program_code" name="program_code" required>
                                        <option value="" disabled selected>-- Pilih Program Studi --</option>
                                        <?php foreach ($majorsList as $m): ?>
                                            <option value="<?php echo htmlspecialchars($m['major_code']); ?>" <?php echo (($applicant['program_code'] ?? '') === $m['major_code']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($m['major_code'] . ' - ' . $m['major_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="alert alert-info py-3 rounded-3" style="font-size: 0.85rem;">
                                    <strong><i class="bi bi-info-circle"></i> Catatan Penting:</strong><br> 
                                    Pastikan seluruh <strong>Data Diri</strong> dan <strong>Berkas Pembayaran</strong> di sebelah kiri telah diisi dan disimpan dengan benar sebelum menekan tombol submit pendaftaran.
                                </div>
                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-success py-3 fw-bold rounded-3 fs-6">Submit Pendaftaran ke TU</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="fixed-bottom bg-white py-2 px-4 border-top shadow-sm">
        <div class="container-fluid d-flex justify-content-end align-items-center">
            <small class="text-muted fw-semibold" style="font-size: 11px;">sia_v0.1.2026</small>
        </div>
    </footer>
</body>
</html>
