<?php
$userPict = $_SESSION['user']['pict'] ?? '';
$userPictPath = defined('UPLOAD_URL') ? UPLOAD_URL . $userPict : './public/uploads/user_photos/' . $userPict;
$defaultAvatar = 'https://cdn-icons-png.freepik.com/512/3875/3875148.png?ga=GA1.1.599436757.1735230785';
$uploadDir = defined('UPLOAD_PATH') ? UPLOAD_PATH : __DIR__ . '/../../public/uploads/user_photos';
$avatarSrc = ($userPict && (file_exists($uploadDir . '/' . $userPict))) ? $userPictPath : $defaultAvatar;
$view = $_GET['view'] ?? 'dashboard';
$userRole = strtolower($_SESSION['user']['role'] ?? '');
$userName = $_SESSION['user']['name'] ?? 'User';

$pageMeta = [
    'dashboard' => ['title' => 'Dashboard', 'subtitle' => 'Ringkasan & Metrik Akademik', 'icon' => 'bi-grid-fill'],
    'students' => ['title' => 'Data Mahasiswa', 'subtitle' => 'Manajemen Data Mahasiswa Terdaftar', 'icon' => 'bi-people-fill'],
    'lecturers' => ['title' => 'Data Dosen', 'subtitle' => 'Manajemen Data Dosen Pengajar', 'icon' => 'bi-person-badge-fill'],
    'courses' => ['title' => 'Data Matakuliah', 'subtitle' => 'Kurikulum & Daftar Matakuliah', 'icon' => 'bi-journal-text'],
    'course_schedule' => ['title' => 'Penawaran KRS', 'subtitle' => 'Jadwal & Kuota Matakuliah Semester', 'icon' => 'bi-calendar2-check-fill'],
    'students_krs_items' => ['title' => 'KRS Mahasiswa', 'subtitle' => 'Data Pengambilan Matakuliah Mahasiswa', 'icon' => 'bi-card-checklist'],
    'student_krs' => ['title' => 'KRS Mahasiswa', 'subtitle' => 'Data Pengambilan Matakuliah Mahasiswa', 'icon' => 'bi-card-checklist'],
    'students_bills' => ['title' => 'Tagihan & Keuangan Mahasiswa', 'subtitle' => 'Kelola Tagihan UKT & Virtual Account', 'icon' => 'bi-wallet2'],
    'bills' => ['title' => 'Tagihan & Keuangan Mahasiswa', 'subtitle' => 'Kelola Tagihan UKT & Virtual Account', 'icon' => 'bi-wallet2'],
    'applicants' => ['title' => 'Data Pendaftar (PMB)', 'subtitle' => 'Verifikasi Calon Mahasiswa Baru', 'icon' => 'bi-person-plus-fill'],
    'users' => ['title' => 'Kelola Pengguna', 'subtitle' => 'Manajemen Hak Akses Akun Sistem', 'icon' => 'bi-shield-lock-fill'],
    'data_users' => ['title' => 'Kelola Pengguna', 'subtitle' => 'Manajemen Hak Akses Akun Sistem', 'icon' => 'bi-shield-lock-fill'],
    'insert_student' => ['title' => 'Pendaftaran Mahasiswa', 'subtitle' => 'Informasi Penambahan Mahasiswa', 'icon' => 'bi-person-plus'],
    'insert_lecturer' => ['title' => 'Tambah Dosen Baru', 'subtitle' => 'Registrasi Profil & NPP Dosen', 'icon' => 'bi-person-plus-fill'],
    'insert_course' => ['title' => 'Tambah Matakuliah', 'subtitle' => 'Input Matakuliah Baru', 'icon' => 'bi-journal-plus'],
    'offering_krs' => ['title' => 'Buka Penawaran KRS', 'subtitle' => 'Jadwal Kelas Perkuliahan', 'icon' => 'bi-calendar-plus'],
    'new_user' => ['title' => 'Tambah User Baru', 'subtitle' => 'Pembuatan Kredensial Akun', 'icon' => 'bi-person-gear'],
    'edit_student' => ['title' => 'Update Data Mahasiswa', 'subtitle' => 'Perbarui Informasi Mahasiswa', 'icon' => 'bi-pencil-square'],
    'edit_lecturer' => ['title' => 'Update Data Dosen', 'subtitle' => 'Perbarui Profil Dosen', 'icon' => 'bi-pencil-square'],
    'edit_course' => ['title' => 'Update Data Matakuliah', 'subtitle' => 'Perbarui Data Matakuliah', 'icon' => 'bi-pencil-square'],
    'edit_course_schedule' => ['title' => 'Update Penawaran KRS', 'subtitle' => 'Perbarui Jadwal & Kuota', 'icon' => 'bi-pencil-square']
];

$currentMeta = $pageMeta[$view] ?? ['title' => 'Sistem Akademik', 'subtitle' => 'Portal Akademik', 'icon' => 'bi-app'];
?>

<div class="d-flex min-vh-100 position-relative" id="appLayout">
    <!-- Backdrop untuk navigasi mobile -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Sidebar Navigasi -->
    <aside class="app-sidebar shadow-sm" id="appSidebar">
        <!-- Header Sidebar: Logo & Brand -->
        <div class="sidebar-header d-flex align-items-center justify-content-between p-3 border-bottom">
            <a href="?view=dashboard" class="d-flex align-items-center gap-2 text-decoration-none">
                <div class="brand-icon bg-primary text-white shadow-sm">
                    <i class="bi bi-mortarboard-fill fs-5"></i>
                </div>
                <div class="brand-text">
                    <span class="fw-bold fs-6 text-body">SIAKAD FIK</span>
                    <small class="text-body-secondary d-block" style="font-size: 0.72rem;">Portal Akademik</small>
                </div>
            </a>
            <!-- Tombol pin collapse di desktop -->
            <button class="btn btn-sm btn-subtle-toggle d-none d-lg-flex align-items-center justify-content-center p-1 rounded-circle border-0" id="sidebarPinBtn" title="Toggle Sidebar (Expand / Collapse)">
                <i class="bi bi-chevron-left sidebar-collapse-icon" style="font-size: 0.85rem;"></i>
            </button>
        </div>

        <!-- Menu Navigasi Sidebar -->
        <div class="sidebar-nav py-2 px-2 flex-grow-1 overflow-y-auto">
            <!-- Section: Utama -->
            <div class="nav-section-title px-2 pt-2 pb-1 text-uppercase text-body-secondary fw-bold" style="font-size: 0.68rem; letter-spacing: 0.5px;">
                Utama
            </div>
            <nav class="nav flex-column">
                <a class="nav-link <?= ($view === 'dashboard') ? 'active' : '' ?>" href="?view=dashboard" title="Dashboard">
                    <i class="bi bi-grid-fill nav-icon"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </nav>

            <!-- Section: Akademik -->
            <div class="nav-section-title px-2 pt-3 pb-1 text-uppercase text-body-secondary fw-bold" style="font-size: 0.68rem; letter-spacing: 0.5px;">
                Akademik
            </div>
            <nav class="nav flex-column">
                <a class="nav-link <?= (in_array($view, ['students', 'insert_student', 'edit_student'])) ? 'active' : '' ?>" href="?view=students" title="Data Mahasiswa">
                    <i class="bi bi-people-fill nav-icon"></i>
                    <span class="nav-text">Data Mahasiswa</span>
                </a>
                <a class="nav-link <?= (in_array($view, ['lecturers', 'insert_lecturer', 'edit_lecturer'])) ? 'active' : '' ?>" href="?view=lecturers" title="Data Dosen">
                    <i class="bi bi-person-badge-fill nav-icon"></i>
                    <span class="nav-text">Data Dosen</span>
                </a>
                <a class="nav-link <?= (in_array($view, ['courses', 'insert_course', 'edit_course'])) ? 'active' : '' ?>" href="?view=courses" title="Data Matakuliah">
                    <i class="bi bi-journal-text nav-icon"></i>
                    <span class="nav-text">Matakuliah</span>
                </a>
                <a class="nav-link <?= (in_array($view, ['course_schedule', 'offering_course_schedule', 'edit_course_schedule'])) ? 'active' : '' ?>" href="?view=course_schedule" title="Penawaran KRS">
                    <i class="bi bi-calendar2-check-fill nav-icon"></i>
                    <span class="nav-text">Penawaran KRS</span>
                </a>
                <a class="nav-link <?= (in_array($view, ['students_krs_items', 'student_krs'])) ? 'active' : '' ?>" href="?view=students_krs_items" title="KRS Mahasiswa">
                    <i class="bi bi-card-checklist nav-icon"></i>
                    <span class="nav-text">KRS Mahasiswa</span>
                </a>
                <a class="nav-link <?= (in_array($view, ['students_bills', 'bills'])) ? 'active' : '' ?>" href="?view=students_bills" title="Tagihan UKT">
                    <i class="bi bi-wallet2 nav-icon"></i>
                    <span class="nav-text">Tagihan UKT</span>
                </a>
            </nav>

            <!-- Section: PMB Online -->
            <div class="nav-section-title px-2 pt-3 pb-1 text-uppercase text-body-secondary fw-bold" style="font-size: 0.68rem; letter-spacing: 0.5px;">
                Penerimaan (PMB)
            </div>
            <nav class="nav flex-column">
                <a class="nav-link <?= ($view === 'applicants') ? 'active' : '' ?>" href="?view=applicants" title="Data Pendaftar (PMB)">
                    <i class="bi bi-person-plus-fill nav-icon"></i>
                    <span class="nav-text">Data Pendaftar</span>
                </a>
            </nav>

            <!-- Section: Pengaturan (Superadmin) -->
            <?php if ($userRole === 'superadmin') : ?>
            <div class="nav-section-title px-2 pt-3 pb-1 text-uppercase text-body-secondary fw-bold" style="font-size: 0.68rem; letter-spacing: 0.5px;">
                Sistem
            </div>
            <nav class="nav flex-column">
                <a class="nav-link <?= (in_array($view, ['users', 'data_users', 'new_user'])) ? 'active' : '' ?>" href="?view=users" title="Kelola User">
                    <i class="bi bi-shield-lock-fill nav-icon"></i>
                    <span class="nav-text">Kelola User</span>
                </a>
            </nav>
            <?php endif; ?>
        </div>

        <!-- Sidebar Footer: Logout -->
        <div class="sidebar-footer p-3 border-top mt-auto">
            <button type="button" class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-2 btn-logout py-2 rounded-3" title="Keluar dari sistem">
                <i class="bi bi-box-arrow-right"></i>
                <span class="nav-text">Logout</span>
            </button>
        </div>
    </aside>

    <!-- Main Content Area (Topbar + View Body) -->
    <div class="flex-grow-1 d-flex flex-column min-vh-100 overflow-hidden" id="appMainArea">
        <!-- Topbar Sticky -->
        <header class="sticky-top border-bottom px-3 px-md-4 py-2 d-flex align-items-center justify-content-between bg-body shadow-xs" style="min-height: var(--topbar-height); z-index: 1020;">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-subtle-toggle border-0 rounded-3 p-2 d-flex align-items-center justify-content-center" id="sidebarToggleBtn" title="Toggle Navigasi Sidebar">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div>
                    <div class="fw-bold fs-6 text-body d-flex align-items-center gap-2">
                        <i class="bi <?= $currentMeta['icon'] ?> text-primary d-none d-sm-inline"></i>
                        <span><?= $currentMeta['title'] ?></span>
                    </div>
                    <small class="text-body-secondary d-none d-md-inline" style="font-size: 0.75rem;">
                        <?= $currentMeta['subtitle'] ?>
                    </small>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <!-- Date Badge -->
                <div class="d-none d-lg-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-body-tertiary text-body-secondary border" style="font-size: 0.78rem;">
                    <i class="bi bi-calendar3 text-primary"></i>
                    <span><?= date('l, d M Y') ?></span>
                </div>

                <!-- Theme Toggle Button (Light / Dark) -->
                <button type="button" class="btn btn-subtle-toggle rounded-circle border-0 p-2 d-flex align-items-center justify-content-center" id="themeToggleBtn" title="Ganti Tema (Light / Dark)" style="width: 38px; height: 38px;">
                    <i class="bi <?= ($currentTheme === 'dark') ? 'bi-moon-stars-fill text-warning' : 'bi-sun-fill text-warning' ?>" id="themeToggleIcon" style="font-size: 1.15rem;"></i>
                </button>

                <div class="vr my-2 mx-1 text-secondary opacity-25"></div>

                <!-- Quick User Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-sm d-flex align-items-center gap-2 border-0 bg-transparent dropdown-toggle p-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?= $avatarSrc ?>" class="rounded-circle object-fit-cover shadow-xs border" width="34" height="34" alt="Avatar">
                        <span class="d-none d-md-inline fw-semibold text-body" style="font-size: 0.85rem;">
                            <?= htmlspecialchars($userName) ?>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border rounded-3 mt-2">
                        <li class="px-3 py-2 border-bottom">
                            <div class="fw-semibold text-body"><?= htmlspecialchars($userName) ?></div>
                            <small class="text-body-secondary"><?= htmlspecialchars($_SESSION['user']['role'] ?? 'Role') ?></small>
                        </li>
                        <li><a class="dropdown-item py-2" href="?view=dashboard"><i class="bi bi-grid-fill me-2 text-primary"></i>Dashboard</a></li>
                        <li><a class="dropdown-item py-2" href="?view=students"><i class="bi bi-people-fill me-2 text-info"></i>Data Mahasiswa</a></li>
                        <li><a class="dropdown-item py-2 btn-open-edit-my-profile" href="javascript:void(0)"><i class="bi bi-person-gear me-2 text-warning"></i>Edit Profile</a></li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item text-danger py-2 btn-logout" href="javascript:void(0)"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-grow-1 p-3 p-md-4 overflow-y-auto bg-body-tertiary" id="main_content" style="min-height: calc(100vh - var(--topbar-height));">
            <?php
            $_SESSION['user']['status'] = 'active';

            // Render Header untuk halaman selain dashboard utama
            if ($view !== 'dashboard') {
                echo '<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between pb-3 mb-3 border-bottom">';
                echo '<div>';
                echo '<h4 class="fw-bold mb-1 text-body"><i class="bi ' . $currentMeta['icon'] . ' text-primary me-2"></i>' . $currentMeta['title'] . '</h4>';
                echo '<span class="text-body-secondary" style="font-size: 0.85rem;">' . $currentMeta['subtitle'] . '</span>';
                echo '</div>';
                echo '</div>';
            }

            $adminViewsDir = __DIR__ . '/../admin';
            switch ($view) {
                case 'dashboard':
                    echo '<title>Dashboard - Sistem Akademik FIK</title>';
                    include $adminViewsDir . '/dashboard.php';
                    break;
                case 'students':
                    echo '<title>Data Mahasiswa - Sistem Akademik FIK</title>';
                    include $adminViewsDir . '/students.php';
                    break;
                case 'lecturers':
                    echo '<title>Data Dosen - Sistem Akademik FIK</title>';
                    include $adminViewsDir . '/lecturers.php';
                    break;
                case 'courses':
                    echo '<title>Data Matakuliah - Sistem Akademik FIK</title>';
                    include $adminViewsDir . '/courses.php';
                    break;
                case 'course_schedule':
                    echo '<title>Penawaran KRS - Sistem Akademik FIK</title>';
                    include $adminViewsDir . '/course_schedule.php';
                    break;
                case 'applicants':
                    echo '<title>Data Pendaftar PMB - Sistem Akademik FIK</title>';
                    include $adminViewsDir . '/applicants.php';
                    break;
                case 'students_krs_items':
                case 'student_krs':
                    echo '<title>KRS Mahasiswa - Sistem Akademik FIK</title>';
                    include $adminViewsDir . '/students_krs_items.php';
                    break;
                case 'students_bills':
                case 'bills':
                    echo '<title>Tagihan UKT Mahasiswa - Sistem Akademik FIK</title>';
                    include $adminViewsDir . '/students_bills.php';
                    break;
                case 'users':
                case 'data_users':
                    echo '<title>Kelola Pengguna - Sistem Akademik FIK</title>';
                    if ($userRole === 'superadmin') {
                        include $adminViewsDir . '/users.php';
                    } else {
                        echo '<div class="alert alert-danger">Akses ditolak: Hanya Superadmin yang diizinkan mengakses menu ini.</div>';
                    }
                    break;
                case 'insert_student':
                    echo '<div class="alert alert-warning" role="alert">Fitur tambah mahasiswa manual telah dinonaktifkan. Mahasiswa baru mendaftar mandiri via PMB Online dan diverifikasi di menu <a href="?view=applicants" class="alert-link">Data Pendaftar (PMB)</a>.</div>';
                    break;
                case 'insert_lecturer':
                    include $adminViewsDir . '/forms/lecturer_form.php';
                    break;
                case 'insert_course':
                    include $adminViewsDir . '/forms/course_form.php';
                    break;
                case 'offering_course_schedule':
                    include $adminViewsDir . '/forms/course_schedule_form.php';
                    break;
                case 'new_user':
                    include $adminViewsDir . '/forms/new_users_credential.php';
                    break;
                case 'edit_user':
                    include $adminViewsDir . '/forms/edit_users_credential.php';
                    break;
                case 'edit_student':
                    include $adminViewsDir . '/forms/student_form.php';
                    break;
                case 'edit_lecturer':
                    include $adminViewsDir . '/forms/lecturer_form.php';
                    break;
                case 'edit_course':
                    include $adminViewsDir . '/forms/course_form.php';
                    break;
                case 'edit_course_schedule':
                    include $adminViewsDir . '/forms/course_schedule_form.php';
                    break;
                default:
                    echo '<div class="card p-5 text-center border-0 shadow-sm rounded-4">';
                    echo '<i class="bi bi-file-earmark-x text-secondary fs-1 mb-2"></i>';
                    echo '<h3 class="fw-bold text-body">404 Halaman Tidak Ditemukan</h3>';
                    echo '<p class="text-body-secondary">Halaman atau tampilan yang Anda tuju tidak tersedia.</p>';
                    echo '<a href="?view=dashboard" class="btn btn-primary mx-auto">Kembali ke Dashboard</a>';
                    echo '</div>';
                    break;
            }
            ?>
        </main>
    </div>
</div>
