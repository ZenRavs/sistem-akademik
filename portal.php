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

// Single-Device Enforcement (validasi session_token)
if (!empty($userLoginState['session_token']) && isset($_SESSION['user']['session_token'])) {
    if ($_SESSION['user']['session_token'] !== $userLoginState['session_token']) {
        $_SESSION = [];
        if (session_id()) session_destroy();
        session_start();
        $_SESSION['error'] = 'Akun Anda telah masuk dari perangkat atau peramban lain. Sesi ini telah ditutup.';
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
                        if (response == 'timeout') {
                            alert('Sesi Anda telah berakhir. Silakan login kembali.');
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
        });
    </script>
</body>

</html>
