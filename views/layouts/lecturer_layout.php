<?php
$userPict = $_SESSION['user']['pict'] ?? '';
$userPictPath = defined('UPLOAD_URL') ? UPLOAD_URL . $userPict : './public/uploads/user_photos/' . $userPict;
$defaultAvatar = 'https://cdn-icons-png.freepik.com/512/3875/3875148.png?ga=GA1.1.599436757.1735230785';
$uploadDir = defined('UPLOAD_PATH') ? UPLOAD_PATH : __DIR__ . '/../../public/uploads/user_photos';
$avatarSrc = ($userPict && (file_exists($uploadDir . '/' . $userPict))) ? $userPictPath : $defaultAvatar;
$view = $_GET['view'] ?? 'dashboard';
$userRole = 'Dosen';
$userName = $_SESSION['user']['name'] ?? 'Dosen Pengajar';

$pageMeta = [
    'dashboard' => ['title' => 'Dashboard Dosen', 'subtitle' => 'Beban Kerja & Ringkasan Perwalian', 'icon' => 'bi-grid-fill'],
    'schedule' => ['title' => 'Jadwal Mengajar', 'subtitle' => 'Perkuliahan & Alokasi Ruang Kelas', 'icon' => 'bi-calendar3'],
    'grading' => ['title' => 'Penilaian Mahasiswa', 'subtitle' => 'Input Nilai Tugas, UTS, & UAS', 'icon' => 'bi-pencil-square'],
    'krs_approval' => ['title' => 'Persetujuan KRS Mahasiswa', 'subtitle' => 'Verifikasi Rencana Studi Mahasiswa Bimbingan', 'icon' => 'bi-check2-circle'],
];

$currentMeta = $pageMeta[$view] ?? ['title' => 'Portal Dosen', 'subtitle' => 'Sistem Informasi Akademik', 'icon' => 'bi-person-badge'];
?>

<div class="d-flex min-vh-100 position-relative" id="appLayout">
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Sidebar Dosen -->
    <aside class="app-sidebar shadow-sm" id="appSidebar">
        <div class="sidebar-header d-flex align-items-center justify-content-between p-3 border-bottom">
            <a href="?view=dashboard" class="d-flex align-items-center gap-2 text-decoration-none">
                <div class="brand-icon bg-primary text-white shadow-sm">
                    <i class="bi bi-mortarboard-fill fs-5"></i>
                </div>
                <div class="brand-text">
                    <span class="fw-bold fs-6 text-body">SIAKAD FIK</span>
                    <small class="text-body-secondary d-block" style="font-size: 0.72rem;">Portal Dosen</small>
                </div>
            </a>
            <button class="btn btn-sm btn-subtle-toggle d-none d-lg-flex align-items-center justify-content-center p-1 rounded-circle border-0" id="sidebarPinBtn" title="Toggle Sidebar">
                <i class="bi bi-chevron-left sidebar-collapse-icon" style="font-size: 0.85rem;"></i>
            </button>
        </div>

        <div class="sidebar-profile p-3 border-bottom d-flex align-items-center gap-3">
            <img src="<?= $avatarSrc ?>" class="rounded-circle object-fit-cover shadow-xs border flex-shrink-0" width="40" height="40" alt="Avatar">
            <div class="profile-info overflow-hidden">
                <div class="fw-semibold text-truncate text-body fs-6" style="max-width: 140px;"><?= htmlspecialchars($userName) ?></div>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0" style="font-size: 0.68rem;">Dosen Pengajar</span>
            </div>
        </div>

        <div class="sidebar-nav py-2 px-2 flex-grow-1 overflow-y-auto">
            <div class="nav-section-title px-2 pt-2 pb-1 text-uppercase text-body-secondary fw-bold" style="font-size: 0.68rem; letter-spacing: 0.5px;">Utama</div>
            <nav class="nav flex-column">
                <a class="nav-link <?= ($view === 'dashboard') ? 'active' : '' ?>" href="?view=dashboard" title="Dashboard">
                    <i class="bi bi-grid-fill nav-icon"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </nav>

            <div class="nav-section-title px-2 pt-3 pb-1 text-uppercase text-body-secondary fw-bold" style="font-size: 0.68rem; letter-spacing: 0.5px;">Pengajaran & Bimbingan</div>
            <nav class="nav flex-column">
                <a class="nav-link <?= ($view === 'schedule') ? 'active' : '' ?>" href="?view=schedule" title="Jadwal Mengajar">
                    <i class="bi bi-calendar3 nav-icon"></i>
                    <span class="nav-text">Jadwal Mengajar</span>
                </a>
                <a class="nav-link <?= ($view === 'grading') ? 'active' : '' ?>" href="?view=grading" title="Entry Nilai">
                    <i class="bi bi-pencil-square nav-icon"></i>
                    <span class="nav-text">Entry Nilai</span>
                </a>
                <a class="nav-link <?= ($view === 'krs_approval') ? 'active' : '' ?>" href="?view=krs_approval" title="Bimbingan KRS">
                    <i class="bi bi-check2-circle nav-icon"></i>
                    <span class="nav-text">Persetujuan KRS</span>
                </a>
            </nav>
        </div>

        <div class="sidebar-footer p-3 border-top mt-auto">
            <button type="button" class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-2 btn-logout py-2 rounded-3" title="Keluar">
                <i class="bi bi-box-arrow-right"></i>
                <span class="nav-text">Logout</span>
            </button>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-grow-1 d-flex flex-column min-vh-100 overflow-hidden" id="appMainArea">
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
                    <small class="text-body-secondary d-none d-md-inline" style="font-size: 0.75rem;"><?= $currentMeta['subtitle'] ?></small>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <div class="d-none d-lg-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-body-tertiary text-body-secondary border" style="font-size: 0.78rem;">
                    <i class="bi bi-calendar3 text-primary"></i>
                    <span><?= date('l, d M Y') ?></span>
                </div>
                <button type="button" class="btn btn-subtle-toggle rounded-circle border-0 p-2 d-flex align-items-center justify-content-center" id="themeToggleBtn" title="Ganti Tema" style="width: 38px; height: 38px;">
                    <i class="bi <?= ($currentTheme === 'dark') ? 'bi-moon-stars-fill text-warning' : 'bi-sun-fill text-warning' ?>" id="themeToggleIcon" style="font-size: 1.15rem;"></i>
                </button>
                <div class="dropdown">
                    <button class="btn btn-subtle-toggle d-flex align-items-center gap-2 border-0 p-1 rounded-pill" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?= $avatarSrc ?>" class="rounded-circle object-fit-cover border" width="34" height="34" alt="Avatar">
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 mt-2 p-2" style="min-width: 200px;">
                        <li class="px-3 py-2 border-bottom">
                            <span class="d-block fw-bold text-body text-truncate"><?= htmlspecialchars($userName) ?></span>
                            <small class="text-body-secondary text-truncate d-block">Dosen Pengajar FIK</small>
                        </li>
                        <li><a class="dropdown-item text-danger py-2 btn-logout" href="javascript:void(0)"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="flex-grow-1 p-3 p-md-4 overflow-y-auto bg-body-tertiary" id="main_content" style="min-height: calc(100vh - var(--topbar-height));">
            <?php
            $lecturerViewsDir = __DIR__ . '/../lecturer';
            switch ($view) {
                case 'dashboard':
                    echo '<title>Dashboard Dosen - Sistem Akademik FIK</title>';
                    include $lecturerViewsDir . '/dashboard.php';
                    break;
                case 'schedule':
                    echo '<title>Jadwal Mengajar - Sistem Akademik FIK</title>';
                    include $lecturerViewsDir . '/schedule.php';
                    break;
                case 'grading':
                    echo '<title>Entry Nilai Mahasiswa - Sistem Akademik FIK</title>';
                    include $lecturerViewsDir . '/grading.php';
                    break;
                case 'krs_approval':
                    echo '<title>Persetujuan KRS - Sistem Akademik FIK</title>';
                    include $lecturerViewsDir . '/krs_approval.php';
                    break;
                default:
                    echo '<div class="card p-5 text-center border-0 shadow-sm rounded-4">';
                    echo '<i class="bi bi-file-earmark-x text-secondary fs-1 mb-2"></i>';
                    echo '<h3 class="fw-bold text-body">404 Halaman Tidak Ditemukan</h3>';
                    echo '<p class="text-body-secondary">Halaman dosen yang Anda tuju tidak tersedia.</p>';
                    echo '<a href="?view=dashboard" class="btn btn-primary mx-auto">Kembali ke Dashboard</a>';
                    echo '</div>';
                    break;
            }
            ?>
        </main>
    </div>
</div>
