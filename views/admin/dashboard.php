<?php
// Query metrik dashboard dari Neon PostgreSQL
$totalStudents = 0;
$totalLecturers = 0;
$totalCourses = 0;
$totalApplicants = 0;
$pendingApplicants = 0;
$recentApplicants = [];

if (isset($conn) && $conn) {
    try {
        $totalStudents = (int)$conn->query("SELECT COUNT(*) FROM students_data")->fetchColumn();
    } catch (Exception $e) {}
    try {
        $totalLecturers = (int)$conn->query("SELECT COUNT(*) FROM lecturers_data")->fetchColumn();
    } catch (Exception $e) {}
    try {
        $totalCourses = (int)$conn->query("SELECT COUNT(*) FROM courses_data")->fetchColumn();
    } catch (Exception $e) {}
    try {
        $totalApplicants = (int)$conn->query("SELECT COUNT(*) FROM pmb_data")->fetchColumn();
        $pendingApplicants = (int)$conn->query("SELECT COUNT(*) FROM pmb_data WHERE application_status IN ('Pending', 'Draft')")->fetchColumn();
        
        $recStmt = $conn->query("
            SELECT p.id, pr.full_name, m.major_name, p.application_status, p.created_at 
            FROM pmb_data p
            LEFT JOIN users_credential u ON u.id = p.user_id
            LEFT JOIN personal_profiles pr ON pr.user_id = u.id
            LEFT JOIN majors_data m ON m.id = p.major_id
            ORDER BY p.id DESC
            LIMIT 5
        ");
        $recentApplicants = $recStmt ? $recStmt->fetchAll() : [];
    } catch (Exception $e) {}
}

$userName = htmlspecialchars($_SESSION['user']['name'] ?? 'Admin');
$userRole = htmlspecialchars($_SESSION['user']['role'] ?? 'Administrator');
?>

<!-- Welcome Banner -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-primary text-white position-relative overflow-hidden">
    <div class="card-body p-4 p-md-4 position-relative z-1">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white bg-opacity-25 text-white mb-2" style="font-size: 0.78rem;">
                    <i class="bi bi-stars"></i>
                    <span>Tahun Akademik 2026/2027 &bull; Semester Ganjil</span>
                </div>
                <h3 class="fw-bold mb-1">Selamat Datang, <?= $userName ?>! 👋</h3>
                <p class="text-white text-opacity-75 mb-0" style="font-size: 0.95rem;">
                    Portal terintegrasi pengelolaan data mahasiswa, dosen, kurikulum perkuliahan, dan penerimaan mahasiswa baru FIK.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <a href="?view=applicants" class="btn btn-light text-primary fw-semibold px-3 py-2 rounded-3 shadow-sm me-2">
                    <i class="bi bi-person-check me-1"></i> Verifikasi PMB
                </a>
                <a href="?view=students" class="btn btn-outline-light px-3 py-2 rounded-3">
                    <i class="bi bi-people me-1"></i> Data Mahasiswa
                </a>
            </div>
        </div>
    </div>
    <!-- Subtle Decorative Background Circles -->
    <div class="position-absolute end-0 bottom-0 translate-middle-y me-n4 mb-n5 rounded-circle bg-white opacity-10" style="width: 250px; height: 250px; pointer-events: none;"></div>
</div>

<!-- 4 KPI Stat Cards -->
<div class="row g-3 mb-4">
    <!-- Mahasiswa Aktif -->
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="?view=students" class="text-decoration-none">
            <div class="card metric-card h-100 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-body-secondary fw-semibold" style="font-size: 0.85rem;">Mahasiswa Terdaftar</span>
                        <div class="icon-box bg-primary-subtle text-primary">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <h3 class="fw-bold mb-0 text-body"><?= number_format($totalStudents) ?></h3>
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill" style="font-size: 0.72rem;">Aktif</span>
                    </div>
                    <small class="text-body-secondary mt-1 d-block" style="font-size: 0.78rem;">
                        <i class="bi bi-arrow-right-short text-primary"></i> Kelola data mahasiswa &raquo;
                    </small>
                </div>
            </div>
        </a>
    </div>

    <!-- Total Dosen -->
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="?view=lecturers" class="text-decoration-none">
            <div class="card metric-card h-100 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-body-secondary fw-semibold" style="font-size: 0.85rem;">Dosen Pengajar</span>
                        <div class="icon-box bg-success-subtle text-success">
                            <i class="bi bi-person-badge-fill"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <h3 class="fw-bold mb-0 text-body"><?= number_format($totalLecturers) ?></h3>
                        <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill" style="font-size: 0.72rem;">Pengajar</span>
                    </div>
                    <small class="text-body-secondary mt-1 d-block" style="font-size: 0.78rem;">
                        <i class="bi bi-arrow-right-short text-success"></i> Kelola profil dosen &raquo;
                    </small>
                </div>
            </div>
        </a>
    </div>

    <!-- Total Matakuliah -->
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="?view=courses" class="text-decoration-none">
            <div class="card metric-card h-100 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-body-secondary fw-semibold" style="font-size: 0.85rem;">Matakuliah</span>
                        <div class="icon-box bg-info-subtle text-info">
                            <i class="bi bi-journal-text"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <h3 class="fw-bold mb-0 text-body"><?= number_format($totalCourses) ?></h3>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill" style="font-size: 0.72rem;">Kurikulum</span>
                    </div>
                    <small class="text-body-secondary mt-1 d-block" style="font-size: 0.78rem;">
                        <i class="bi bi-arrow-right-short text-info"></i> Daftar matakuliah &raquo;
                    </small>
                </div>
            </div>
        </a>
    </div>

    <!-- Pendaftar PMB Baru -->
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="?view=applicants" class="text-decoration-none">
            <div class="card metric-card h-100 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-body-secondary fw-semibold" style="font-size: 0.85rem;">Pendaftar PMB</span>
                        <div class="icon-box bg-warning-subtle text-warning">
                            <i class="bi bi-person-plus-fill"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <h3 class="fw-bold mb-0 text-body"><?= number_format($totalApplicants) ?></h3>
                        <?php if ($pendingApplicants > 0): ?>
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill" style="font-size: 0.72rem;"><?= $pendingApplicants ?> Menunggu</span>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill" style="font-size: 0.72rem;">Terverifikasi</span>
                        <?php endif; ?>
                    </div>
                    <small class="text-body-secondary mt-1 d-block" style="font-size: 0.78rem;">
                        <i class="bi bi-arrow-right-short text-warning"></i> Verifikasi pendaftaran &raquo;
                    </small>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Section: Quick Actions & Recent Applicants -->
<div class="row g-3">
    <!-- Quick Actions Menu -->
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-body">
            <div class="card-header bg-transparent border-0 pt-3 pb-2 px-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0 text-body"><i class="bi bi-lightning-charge-fill text-warning me-2"></i>Aksi Cepat</h6>
                    <span class="badge bg-secondary-subtle text-body-secondary rounded-pill" style="font-size: 0.7rem;">Shortcut</span>
                </div>
            </div>
            <div class="card-body p-3 pt-0">
                <div class="d-grid gap-2">
                    <a href="?view=applicants" class="btn btn-outline-secondary d-flex align-items-center justify-content-between p-2 rounded-3 text-start">
                        <div class="d-flex align-items-center gap-2">
                            <div class="icon-box bg-warning-subtle text-warning" style="width: 36px; height: 36px; font-size: 1.1rem;">
                                <i class="bi bi-clipboard2-check"></i>
                            </div>
                            <div>
                                <div class="fw-semibold text-body" style="font-size: 0.85rem;">Verifikasi PMB</div>
                                <small class="text-body-secondary" style="font-size: 0.75rem;">Periksa berkas & pendaftar</small>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-body-secondary"></i>
                    </a>

                    <a href="?view=course_schedule" class="btn btn-outline-secondary d-flex align-items-center justify-content-between p-2 rounded-3 text-start">
                        <div class="d-flex align-items-center gap-2">
                            <div class="icon-box bg-primary-subtle text-primary" style="width: 36px; height: 36px; font-size: 1.1rem;">
                                <i class="bi bi-calendar-event"></i>
                            </div>
                            <div>
                                <div class="fw-semibold text-body" style="font-size: 0.85rem;">Penawaran Matakuliah KRS</div>
                                <small class="text-body-secondary" style="font-size: 0.75rem;">Jadwal & kuota semester</small>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-body-secondary"></i>
                    </a>

                    <a href="?view=insert_course&req=insert" class="btn btn-outline-secondary d-flex align-items-center justify-content-between p-2 rounded-3 text-start">
                        <div class="d-flex align-items-center gap-2">
                            <div class="icon-box bg-info-subtle text-info" style="width: 36px; height: 36px; font-size: 1.1rem;">
                                <i class="bi bi-journal-plus"></i>
                            </div>
                            <div>
                                <div class="fw-semibold text-body" style="font-size: 0.85rem;">Tambah Matakuliah Baru</div>
                                <small class="text-body-secondary" style="font-size: 0.75rem;">Input kode, nama & SKS</small>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-body-secondary"></i>
                    </a>

                    <a href="?view=insert_lecturer&req=insert" class="btn btn-outline-secondary d-flex align-items-center justify-content-between p-2 rounded-3 text-start">
                        <div class="d-flex align-items-center gap-2">
                            <div class="icon-box bg-success-subtle text-success" style="width: 36px; height: 36px; font-size: 1.1rem;">
                                <i class="bi bi-person-badge"></i>
                            </div>
                            <div>
                                <div class="fw-semibold text-body" style="font-size: 0.85rem;">Tambah Dosen Pengajar</div>
                                <small class="text-body-secondary" style="font-size: 0.75rem;">Registrasi NPP & Homebase</small>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-body-secondary"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent PMB Applicants Table -->
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-body">
            <div class="card-header bg-transparent border-0 pt-3 pb-2 px-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold mb-0 text-body"><i class="bi bi-clock-history text-primary me-2"></i>Pendaftar PMB Terbaru</h6>
                        <small class="text-body-secondary" style="font-size: 0.78rem;">Pendaftaran masuk terkini</small>
                    </div>
                    <a href="?view=applicants" class="btn btn-sm btn-subtle-toggle rounded-pill px-3" style="font-size: 0.78rem;">
                        Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3 py-2 border-0">Nama Lengkap</th>
                                <th class="py-2 border-0">Program Studi</th>
                                <th class="py-2 border-0">Tanggal Daftar</th>
                                <th class="py-2 border-0 text-center">Status</th>
                                <th class="pe-3 py-2 border-0 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentApplicants)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-body-secondary">
                                        <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                                        Belum ada data pendaftar terbaru.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentApplicants as $applicant): 
                                    $status = $applicant['application_status'] ?? 'Draft';
                                    $badgeClass = match ($status) {
                                        'Approved' => 'bg-success-subtle text-success border border-success-subtle',
                                        'Pending' => 'bg-warning-subtle text-warning border border-warning-subtle',
                                        'Rejected' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                        default => 'bg-secondary-subtle text-secondary border border-secondary-subtle'
                                    };
                                ?>
                                    <tr>
                                        <td class="ps-3 py-2 fw-semibold text-body">
                                            <?= htmlspecialchars($applicant['full_name'] ?? 'Pendaftar') ?>
                                        </td>
                                        <td class="py-2 text-body-secondary">
                                            <?= htmlspecialchars($applicant['major_name'] ?? '-') ?>
                                        </td>
                                        <td class="py-2 text-body-secondary">
                                            <?= !empty($applicant['created_at']) ? date('d M Y, H:i', strtotime($applicant['created_at'])) : '-' ?>
                                        </td>
                                        <td class="py-2 text-center">
                                            <span class="badge rounded-pill <?= $badgeClass ?> px-2 py-1" style="font-size: 0.72rem;">
                                                <?= htmlspecialchars($status) ?>
                                            </span>
                                        </td>
                                        <td class="pe-3 py-2 text-end">
                                            <a href="?view=applicants" class="btn btn-sm btn-outline-primary py-0 px-2 rounded-2" style="font-size: 0.75rem;">
                                                Periksa
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
