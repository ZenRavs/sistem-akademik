<?php
require_once __DIR__ . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!$conn) {
    if (empty($_SESSION['error'])) {
        $_SESSION['error'] = 'Gagal terhubung ke Database Cloud. Pastikan koneksi internet Anda aktif.';
    }
    header("location: index.php");
    exit;
}

// 1. Cek sesi untuk Calon Mahasiswa (PMB Online)
if (isset($_SESSION['applicant'])) {
    include __DIR__ . '/views/layouts/applicant_layout.php';
    exit;
}

// 2. Cek sesi untuk Pengguna Terdaftar (Admin, Superadmin, Dosen, Mahasiswa)
if (!isset($_SESSION['user'])) {
    $_SESSION['error'] = 'Silakan login terlebih dahulu untuk mengakses portal [portal.php].';
    header("location: index.php");
    exit;
}

// Cek Idle Timeout (30 menit inaktif)
$idleTimeoutSeconds = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $idleTimeoutSeconds)) {
    $timeoutUserId = $_SESSION['user']['id'] ?? null;
    if ($timeoutUserId) {
        $conn->prepare("UPDATE users_credential SET session_token = NULL, last_active_at = NULL WHERE id = ?")->execute([$timeoutUserId]);
    }
    $_SESSION = [];
    if (session_id()) session_destroy();
    session_start();
    $_SESSION['error'] = 'Sesi Anda telah berakhir karena tidak ada aktivitas selama 30 menit. Silakan login kembali.';
    header("location: index.php");
    exit;
}

$getUserStmt = $conn->prepare("
    SELECT u.*, p.full_name, p.photo 
    FROM users_credential u 
    LEFT JOIN personal_profiles p ON p.user_id = u.id 
    WHERE u.username = ?
");
$getUserStmt->execute([$_SESSION['user']['userid']]);
$userLoginState = $getUserStmt->fetch();

// Validasi status akun (account_status)
if (!$userLoginState || ($userLoginState['account_status'] ?? '') !== 'active') {
    $_SESSION = [];
    if (session_id()) session_destroy();
    session_start();
    $statusMsg = ($userLoginState && ($userLoginState['account_status'] ?? '') === 'suspended')
        ? 'Akun Anda sedang ditangguhkan (suspended). Silakan hubungi administrator.'
        : (($userLoginState && ($userLoginState['account_status'] ?? '') === 'banned')
            ? 'Akun Anda telah dinonaktifkan permanen (banned).'
            : 'Akun tidak aktif atau tidak ditemukan [portal.php]');
    $_SESSION['error'] = $statusMsg;
    header("location: index.php");
    exit;
}

// Single-Device Enforcement & Validasi Sesi Aktif (validasi session_token)
if (isset($_SESSION['user']['session_token'])) {
    if (empty($userLoginState['session_token']) || $_SESSION['user']['session_token'] !== $userLoginState['session_token']) {
        $_SESSION = [];
        if (session_id()) session_destroy();
        session_start();
        $_SESSION['error'] = 'Sesi Anda telah berakhir, dihentikan oleh administrator, atau akun telah masuk dari peramban lain.';
        header("location: index.php");
        exit;
    }
}

// Perbarui timestamp aktivitas (di database di-throttle minimal setiap 60 detik)
$_SESSION['last_activity'] = time();
if (!isset($_SESSION['last_db_heartbeat']) || (time() - $_SESSION['last_db_heartbeat'] > 60)) {
    $_SESSION['last_db_heartbeat'] = time();
    $updateHeartbeat = $conn->prepare("UPDATE users_credential SET last_active_at = CURRENT_TIMESTAMP WHERE id = ?");
    $updateHeartbeat->execute([$userLoginState['id']]);
}

// Ambil preferensi tema per-user dari config/theme_setting.json
$currentTheme = 'light';
$themeConfigFile = __DIR__ . '/config/theme_setting.json';
if (file_exists($themeConfigFile)) {
    $themeSettings = json_decode(file_get_contents($themeConfigFile), true);
    $activeUsername = $_SESSION['user']['userid'] ?? '';
    if (!empty($activeUsername) && !empty($themeSettings['users'][$activeUsername])) {
        $currentTheme = $themeSettings['users'][$activeUsername];
    } elseif (!empty($themeSettings['default'])) {
        $currentTheme = $themeSettings['default'];
    }
}
if (!in_array($currentTheme, ['light', 'dark'])) {
    $currentTheme = 'light';
}
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="<?= htmlspecialchars($currentTheme) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="https://cdn-icons-png.freepik.com/512/7021/7021308.png?ga=GA1.1.599436757.1735230785" type="image/x-icon">
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3.3 & Bootstrap Icons CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <title>Portal Sistem Informasi Akademik</title>

    <style>
        :root {
            --app-font: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            --sidebar-width: 260px;
            --sidebar-collapsed-width: 76px;
            --topbar-height: 64px;
        }

        body {
            font-family: var(--app-font);
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
            min-height: 100vh;
            overflow-x: hidden;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        /* Sidebar Styling */
        .app-sidebar {
            width: var(--sidebar-width);
            min-width: var(--sidebar-width);
            height: 100vh;
            position: sticky;
            top: 0;
            z-index: 1030;
            display: flex;
            flex-direction: column;
            background-color: var(--bs-body-bg);
            border-right: 1px solid var(--bs-border-color);
            transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1), min-width 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            white-space: nowrap;
        }

        .brand-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* Collapsed Sidebar on Desktop */
        body.sidebar-collapsed .app-sidebar {
            width: var(--sidebar-collapsed-width);
            min-width: var(--sidebar-collapsed-width);
        }

        body.sidebar-collapsed .brand-text,
        body.sidebar-collapsed .profile-info,
        body.sidebar-collapsed .nav-section-title,
        body.sidebar-collapsed .nav-text,
        body.sidebar-collapsed .sidebar-footer .nav-text {
            display: none !important;
        }

        body.sidebar-collapsed .sidebar-header {
            justify-content: center !important;
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
        }

        body.sidebar-collapsed .sidebar-profile {
            justify-content: center !important;
            padding: 0.75rem 0.5rem !important;
        }

        body.sidebar-collapsed .nav-link {
            justify-content: center !important;
            padding: 0.7rem 0 !important;
        }

        body.sidebar-collapsed .nav-link .nav-icon {
            margin-right: 0 !important;
            font-size: 1.25rem;
        }

        body.sidebar-collapsed .sidebar-footer {
            padding: 0.75rem 0.5rem !important;
        }

        body.sidebar-collapsed .sidebar-footer .btn {
            padding: 0.5rem 0 !important;
            justify-content: center !important;
        }

        body.sidebar-collapsed .sidebar-collapse-icon {
            transform: rotate(180deg);
        }

        /* Navigation Links */
        .app-sidebar .nav-link {
            display: flex;
            align-items: center;
            padding: 0.55rem 0.85rem;
            color: var(--bs-secondary-color);
            border-radius: 0.55rem;
            margin-bottom: 0.2rem;
            font-weight: 500;
            font-size: 0.88rem;
            transition: all 0.15s ease-in-out;
            text-decoration: none;
        }

        .app-sidebar .nav-link:hover {
            color: var(--bs-primary);
            background-color: var(--bs-tertiary-bg);
            transform: translateX(2px);
        }

        .app-sidebar .nav-link.active {
            color: #ffffff !important;
            background-color: var(--bs-primary) !important;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.25);
            transform: none;
        }

        .app-sidebar .nav-link .nav-icon {
            font-size: 1.1rem;
            margin-right: 0.75rem;
            width: 22px;
            text-align: center;
            flex-shrink: 0;
        }

        /* Sidebar Mobile Drawer */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.45);
            z-index: 1040;
            backdrop-filter: blur(2px);
        }

        @media (max-width: 991.98px) {
            .app-sidebar {
                position: fixed;
                left: calc(-1 * var(--sidebar-width));
                top: 0;
                z-index: 1045;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            }

            body.sidebar-open-mobile .app-sidebar {
                left: 0;
            }

            body.sidebar-open-mobile .sidebar-backdrop {
                display: block;
            }

            body.sidebar-collapsed .app-sidebar {
                width: var(--sidebar-width);
                min-width: var(--sidebar-width);
            }

            body.sidebar-collapsed .brand-text,
            body.sidebar-collapsed .profile-info,
            body.sidebar-collapsed .nav-section-title,
            body.sidebar-collapsed .nav-text,
            body.sidebar-collapsed .sidebar-footer .nav-text {
                display: block !important;
            }

            body.sidebar-collapsed .nav-link {
                justify-content: flex-start !important;
                padding: 0.55rem 0.85rem !important;
            }

            body.sidebar-collapsed .nav-link .nav-icon {
                margin-right: 0.75rem !important;
            }
        }

        /* Modern Subtle Card & Elements */
        .metric-card {
            border-radius: 14px;
            border: 1px solid var(--bs-border-color);
            background-color: var(--bs-body-bg);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
        }

        .icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
        }

        .btn-subtle-toggle {
            background-color: var(--bs-tertiary-bg);
            color: var(--bs-body-color);
            transition: all 0.2s ease;
        }

        .btn-subtle-toggle:hover {
            background-color: var(--bs-secondary-bg);
            transform: rotate(15deg);
        }

        .sidebar-collapse-icon {
            transition: transform 0.25s ease;
        }
    </style>
</head>

<body>
    <div>
        <?php
        $currentRole = strtolower($_SESSION['user']['role'] ?? '');
        
        // Central Role-Based Layout Dispatcher
        if (in_array($currentRole, ['admin', 'superadmin'])) {
            $_SESSION['user']['status'] = 'active';
            include __DIR__ . '/views/layouts/admin_layout.php';
        } elseif (in_array($currentRole, ['student', 'mahasiswa'])) {
            $_SESSION['user']['status'] = 'active';
            include __DIR__ . '/views/layouts/student_layout.php';
        } elseif (in_array($currentRole, ['lecturer', 'dosen'])) {
            $_SESSION['user']['status'] = 'active';
            include __DIR__ . '/views/layouts/lecturer_layout.php';
        } elseif (in_array($currentRole, ['applicant', 'pendaftar'])) {
            $_SESSION['user']['status'] = 'active';
            include __DIR__ . '/views/layouts/applicant_layout.php';
        } else {
            echo '<div class="d-flex justify-content-center align-items-center min-vh-100 bg-body-tertiary">';
            echo '<div class="card p-4 shadow-sm border text-center" style="max-width: 450px;">';
            echo '<i class="bi bi-exclamation-triangle text-danger fs-1 mb-2"></i>';
            echo '<h5 class="fw-bold">Role Tidak Dikenali</h5>';
            echo '<p class="text-body-secondary mb-3">Role akun Anda (' . htmlspecialchars($currentRole) . ') tidak memiliki izin akses sistem.</p>';
            echo '<a href="javascript:void(0)" class="btn btn-secondary btn-logout">Kembali ke Halaman Login</a>';
            echo '</div></div>';
        }
        ?>
    </div>

    <script>
        $(document).ready(function() {
            // Logout logic
            function logout() {
                $.ajax({
                    url: 'src/api.php?req=userLogout',
                    success: function(response) {
                        let respons = (typeof response === 'object') ? response : JSON.parse(response);
                        if (respons.message == 'success') {
                            window.location.href = 'index.php';
                        } else {
                            alert(respons.message);
                        }
                    },
                    error: function() {
                        alert("Server error. [req: userLogout]");
                    }
                });
            }

            $('.btn-logout, #logout').on('click', function(e) {
                e.preventDefault();
                if (confirm("Apakah Anda yakin ingin keluar dari sistem?")) {
                    logout();
                }
            });

            // Session check timer
            setInterval(function() {
                $.ajax({
                    url: 'src/api.php?req=sessionCheck',
                    dataType: 'json',
                    success: function(response) {
                        if (response == 'timeout' || response == 'terminated') {
                            alert(response == 'terminated' ? 'Sesi Anda telah dihentikan atau login di peramban lain. Silakan login kembali.' : 'Sesi Anda telah berakhir karena inaktif. Silakan login kembali.');
                            logout();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX error:", status, error);
                    }
                });
            }, 10000);

            // Inisialisasi status Sidebar dari localStorage
            function initSidebarState() {
                let isCollapsed = localStorage.getItem('siakad_sidebar_collapsed');
                if (isCollapsed === 'true' && $(window).width() >= 992) {
                    $('body').addClass('sidebar-collapsed');
                }
            }
            initSidebarState();

            // Toggle Expand/Collapse Sidebar
            $(document).on('click', '#sidebarToggleBtn, #sidebarPinBtn', function() {
                if ($(window).width() < 992) {
                    $('body').toggleClass('sidebar-open-mobile');
                } else {
                    $('body').toggleClass('sidebar-collapsed');
                    let collapsed = $('body').hasClass('sidebar-collapsed');
                    localStorage.setItem('siakad_sidebar_collapsed', collapsed);
                }
            });

            // Tutup sidebar mobile saat klik backdrop atau link menu di mobile
            $(document).on('click', '#sidebarBackdrop, .app-sidebar .nav-link', function() {
                if ($(window).width() < 992) {
                    $('body').removeClass('sidebar-open-mobile');
                }
            });

            // Theme Switcher Logic
            function applyTheme(theme) {
                $('html').attr('data-bs-theme', theme);
                if (theme === 'dark') {
                    $('#themeToggleIcon').removeClass('bi-sun-fill text-warning').addClass('bi-moon-stars-fill text-warning');
                    $('#themeToggleBtn').attr('title', 'Ganti ke Light Mode');
                } else {
                    $('#themeToggleIcon').removeClass('bi-moon-stars-fill text-warning').addClass('bi-sun-fill text-warning');
                    $('#themeToggleBtn').attr('title', 'Ganti ke Dark Mode');
                }
            }

            $(document).on('click', '#themeToggleBtn', function() {
                let current = $('html').attr('data-bs-theme') || 'light';
                let nextTheme = (current === 'dark') ? 'light' : 'dark';

                // Perubahan visual instan
                applyTheme(nextTheme);

                // Simpan ke config/theme_setting.json via AJAX
                $.ajax({
                    url: 'src/api.php?req=setTheme',
                    type: 'POST',
                    data: { theme: nextTheme },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status !== 'success') {
                            console.error("Gagal menyimpan preferensi tema:", res.message);
                        }
                    },
                    error: function(err) {
                        console.error("Error AJAX setTheme:", err);
                    }
                });
            });

            // Event Handler: Buka Modal Edit Profile Saya
            $(document).on('click', '.btn-open-edit-my-profile', function(e) {
                e.preventDefault();
                $('#modalProfileAlert').addClass('d-none');
                $('#chkChangeMyPassword').prop('checked', false);
                $('#secChangeMyPassword').addClass('d-none');
                $('#my_profile_current_password, #my_profile_new_password, #my_profile_confirm_password').val('');
                $('#my_profile_photo').val('');

                $.ajax({
                    url: 'src/api.php?req=getMyProfileDetail',
                    type: 'GET',
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success' && res.data) {
                            let d = res.data;
                            $('#my_profile_username').val(d.username || '');
                            $('#my_profile_role').val(d.role || '');
                            $('#my_profile_email').val(d.email || '');
                            $('#my_profile_full_name').val(d.full_name || '');
                            $('#my_profile_nik').val(d.nik || '');
                            $('#my_profile_gender').val(d.gender || '');
                            $('#my_profile_pob').val(d.pob || '');
                            $('#my_profile_dob').val(d.dob || '');
                            $('#my_profile_religion').val(d.religion || '');
                            $('#my_profile_marital_status').val(d.marital_status || '');
                            $('#my_profile_job_status').val(d.job_status || '');
                            $('#my_profile_phone').val(d.phone || '');
                            $('#my_profile_ktp_address').val(d.ktp_address || '');
                            $('#my_profile_domicile_address').val(d.domicile_address || '');

                            let avatarUrl = d.photo ? 'public/uploads/user_photos/' + d.photo : 'https://cdn-icons-png.freepik.com/512/3135/3135715.png';
                            $('#my_profile_avatar_preview').attr('src', avatarUrl);

                            let modalEl = document.getElementById('editMyProfileModal');
                            let modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                            modal.show();
                        } else {
                            alert(res.message || "Gagal mengambil data profil.");
                        }
                    },
                    error: function(xhr, status, error) {
                        alert("Terjadi kesalahan jaringan saat mengambil data profil.");
                    }
                });
            });

            // Toggle Tampilan Form Ubah Password
            $(document).on('change', '#chkChangeMyPassword', function() {
                if ($(this).is(':checked')) {
                    $('#secChangeMyPassword').removeClass('d-none');
                } else {
                    $('#secChangeMyPassword').addClass('d-none');
                    $('#my_profile_current_password, #my_profile_new_password, #my_profile_confirm_password').val('');
                }
            });

            // Submit Handler Form Edit Profile Saya
            $(document).on('submit', '#formEditMyProfile', function(e) {
                e.preventDefault();
                let formData = new FormData(this);
                let btn = $('#btnSaveMyProfile');
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Menyimpan...');

                $.ajax({
                    url: 'src/api.php?req=updateMyProfile',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    success: function(res) {
                        btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Simpan Perubahan Profil');
                        if (res.status === 'success') {
                            alert(res.message);
                            let modalEl = document.getElementById('editMyProfileModal');
                            let modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();
                            location.reload();
                        } else {
                            $('#modalProfileAlertMsg').text(res.message);
                            $('#modalProfileAlert').removeClass('d-none alert-success').addClass('alert-danger');
                        }
                    },
                    error: function(xhr, status, error) {
                        btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Simpan Perubahan Profil');
                        $('#modalProfileAlertMsg').text("Terjadi kesalahan server saat memperbarui profil.");
                        $('#modalProfileAlert').removeClass('d-none alert-success').addClass('alert-danger');
                    }
                });
            });
        });
    </script>

    <!-- Modal Edit Profile Saya -->
    <div class="modal fade" id="editMyProfileModal" tabindex="-1" aria-labelledby="editMyProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <form class="modal-content border-0 shadow" id="formEditMyProfile" enctype="multipart/form-data">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-header-title mb-0 fw-bold d-flex align-items-center gap-2" id="editMyProfileModalLabel">
                        <i class="bi bi-person-gear"></i> Edit Profil Saya
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Alert Pesan Error/Sukses Modal -->
                    <div id="modalProfileAlert" class="d-none alert alert-dismissible fade show mb-3" role="alert">
                        <span id="modalProfileAlertMsg"></span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>

                    <!-- Card 1: Informasi Akun (users_credential) -->
                    <div class="card mb-4 border">
                        <div class="card-header bg-body-tertiary fw-semibold py-2">
                            <i class="bi bi-shield-lock text-primary me-2"></i>Informasi Akun & Kredensial (users_credential)
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small text-body-secondary mb-1">Username</label>
                                    <input type="text" class="form-control form-control-sm bg-body-tertiary" id="my_profile_username" readonly disabled>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small text-body-secondary mb-1">Peran / Role</label>
                                    <input type="text" class="form-control form-control-sm bg-body-tertiary text-capitalize" id="my_profile_role" readonly disabled>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control form-control-sm" name="email" id="my_profile_email" required>
                                </div>

                                <!-- Toggle Ubah Password -->
                                <div class="col-12 mt-3">
                                    <div class="p-3 bg-body-tertiary rounded-3 border">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="chkChangeMyPassword">
                                            <label class="form-check-label fw-semibold small text-body" for="chkChangeMyPassword">
                                                <i class="bi bi-key-fill text-warning me-1"></i> Ganti Password Akun
                                            </label>
                                        </div>
                                        <div id="secChangeMyPassword" class="row g-2 mt-2 d-none">
                                            <div class="col-md-4">
                                                <label class="form-label small text-body-secondary mb-1">Password Saat Ini</label>
                                                <input type="password" class="form-control form-control-sm" name="current_password" id="my_profile_current_password" placeholder="Password lama">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small text-body-secondary mb-1">Password Baru</label>
                                                <input type="password" class="form-control form-control-sm" name="new_password" id="my_profile_new_password" placeholder="Password baru (min 6 char)">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small text-body-secondary mb-1">Konfirmasi Password Baru</label>
                                                <input type="password" class="form-control form-control-sm" name="confirm_password" id="my_profile_confirm_password" placeholder="Ulangi password baru">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Data Diri / Profil Personal (personal_profiles) -->
                    <div class="card border">
                        <div class="card-header bg-body-tertiary fw-semibold py-2">
                            <i class="bi bi-person-vcard text-success me-2"></i>Data Diri & Profil Personal (personal_profiles)
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <!-- Foto Profil -->
                                <div class="col-12 d-flex align-items-center gap-3 pb-3 border-bottom">
                                    <div class="position-relative">
                                        <img src="" id="my_profile_avatar_preview" class="rounded-circle object-fit-cover border shadow-sm" width="70" height="70" alt="Foto Profil">
                                    </div>
                                    <div>
                                        <label class="form-label fw-semibold small mb-1">Foto Profil Baru (Opsional)</label>
                                        <input type="file" class="form-control form-control-sm" name="photo" id="my_profile_photo" accept="image/jpeg,image/png,image/webp">
                                        <small class="text-body-secondary d-block mt-1" style="font-size: 0.75rem;">Format: JPG, PNG, WEBP (Maks. 2MB)</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm" name="full_name" id="my_profile_full_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">NIK (No. KTP) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm" name="nik" id="my_profile_nik" maxlength="16" pattern="[0-9]{16}" inputmode="numeric" placeholder="16 digit NIK" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Jenis Kelamin</label>
                                    <select class="form-select form-select-sm" name="gender" id="my_profile_gender">
                                        <option value="">-- Pilih Jenis Kelamin --</option>
                                        <option value="L">Laki-Laki</option>
                                        <option value="P">Perempuan</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Tempat Lahir</label>
                                    <input type="text" class="form-control form-control-sm" name="pob" id="my_profile_pob">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Tanggal Lahir</label>
                                    <input type="date" class="form-control form-control-sm" name="dob" id="my_profile_dob">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Agama</label>
                                    <select class="form-select form-select-sm" name="religion" id="my_profile_religion">
                                        <option value="">-- Pilih Agama --</option>
                                        <option value="Islam">Islam</option>
                                        <option value="Kristen">Kristen</option>
                                        <option value="Katolik">Katolik</option>
                                        <option value="Hindu">Hindu</option>
                                        <option value="Buddha">Buddha</option>
                                        <option value="Konghucu">Konghucu</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Status Pernikahan</label>
                                    <select class="form-select form-select-sm" name="marital_status" id="my_profile_marital_status">
                                        <option value="">-- Pilih Status --</option>
                                        <option value="Belum Menikah">Belum Menikah</option>
                                        <option value="Menikah">Menikah</option>
                                        <option value="Duda/Janda">Duda/Janda</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Status Pekerjaan</label>
                                    <select class="form-select form-select-sm" name="job_status" id="my_profile_job_status">
                                        <option value="">-- Pilih Pekerjaan --</option>
                                        <option value="Belum Bekerja">Belum Bekerja</option>
                                        <option value="Bekerja">Bekerja</option>
                                        <option value="Wiraswasta">Wiraswasta</option>
                                        <option value="Pelajar/Mahasiswa">Pelajar/Mahasiswa</option>
                                    </select>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label fw-semibold small mb-1">No. HP / WhatsApp <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm" name="phone" id="my_profile_phone" inputmode="numeric" placeholder="Contoh: 081234567890" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Alamat KTP</label>
                                    <textarea class="form-control form-control-sm" name="ktp_address" id="my_profile_ktp_address" rows="2"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Alamat Domisili</label>
                                    <textarea class="form-control form-control-sm" name="domicile_address" id="my_profile_domicile_address" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-body-tertiary">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary px-3" id="btnSaveMyProfile">
                        <i class="bi bi-check-circle me-1"></i> Simpan Perubahan Profil
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
