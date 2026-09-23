<?php
$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

// MODE 1: Daftar Seluruh Mahasiswa untuk Pemilihan KRS (Jika student_id tidak disertakan)
if ($studentId <= 0) {
    $searchQuery = trim($_GET['search'] ?? '');
    $params = [];
    $whereSql = "";
    
    if (!empty($searchQuery)) {
        $whereSql = "WHERE (sd.nim ILIKE ? OR pp.full_name ILIKE ?)";
        $params[] = "%$searchQuery%";
        $params[] = "%$searchQuery%";
    }
    
    $stmtStudents = $conn->prepare("
        SELECT 
            sd.id AS student_id,
            sd.nim,
            pp.full_name,
            md.major_name,
            sd.current_semester,
            sd.academic_status,
            COUNT(krs.id) AS total_courses,
            COALESCE(SUM(CASE WHEN krs.approval_status IN ('Approved', 'Submitted') THEN cd.credits ELSE 0 END), 0) AS total_sks
        FROM students_data sd
        JOIN personal_profiles pp ON sd.user_id = pp.user_id
        JOIN majors_data md ON sd.major_id = md.id
        LEFT JOIN students_krs_data krs ON sd.id = krs.student_id
        LEFT JOIN courses_data cd ON krs.course_id = cd.id
        $whereSql
        GROUP BY sd.id, sd.nim, pp.full_name, md.major_name, sd.current_semester, sd.academic_status
        ORDER BY sd.nim ASC
    ");
    $stmtStudents->execute($params);
    $studentsList = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-body border-bottom p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="fw-bold mb-1 text-primary"><i class="bi bi-card-checklist me-2"></i>KRS Mahasiswa</h5>
                    <span class="text-body-secondary" style="font-size: 0.85rem;">Pilih mahasiswa untuk melihat atau memverifikasi detail Kartu Rencana Studi (KRS).</span>
                </div>
                <form method="GET" class="d-flex gap-2">
                    <input type="hidden" name="view" value="students_krs_items">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari NIM atau Nama..." value="<?= htmlspecialchars($searchQuery) ?>" style="min-width: 220px;">
                    <button type="submit" class="btn btn-primary btn-sm px-3"><i class="bi bi-search me-1"></i>Cari</button>
                    <?php if (!empty($searchQuery)): ?>
                        <a href="?view=students_krs_items" class="btn btn-outline-secondary btn-sm">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" width="60">No</th>
                            <th>NIM</th>
                            <th>Nama Mahasiswa</th>
                            <th>Program Studi</th>
                            <th class="text-center">Semester</th>
                            <th class="text-center">Jml Matkul</th>
                            <th class="text-center">Total SKS</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($studentsList)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-body-secondary">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    Tidak ada data mahasiswa ditemukan.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($studentsList as $st): ?>
                                <tr>
                                    <td class="ps-4"><?= $no++ ?></td>
                                    <td><span class="font-monospace fw-semibold text-body"><?= htmlspecialchars($st['nim']) ?></span></td>
                                    <td class="fw-semibold text-body"><?= htmlspecialchars($st['full_name']) ?></td>
                                    <td class="text-body-secondary"><?= htmlspecialchars($st['major_name']) ?></td>
                                    <td class="text-center"><span class="badge bg-secondary-subtle text-secondary">Smt <?= htmlspecialchars($st['current_semester']) ?></span></td>
                                    <td class="text-center text-body"><?= (int)$st['total_courses'] ?> MK</td>
                                    <td class="text-center fw-bold text-primary"><?= (int)$st['total_sks'] ?> SKS</td>
                                    <td class="text-center">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                                            <?= htmlspecialchars($st['academic_status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="?view=students_krs_items&student_id=<?= $st['student_id'] ?>" class="btn btn-sm btn-primary px-3 rounded-2">
                                            <i class="bi bi-eye me-1"></i>Lihat KRS
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
    <?php
    return;
}

// MODE 2: Detail KRS Mahasiswa Spesifik (Jika student_id tersedia)
$stmt = $conn->prepare("
    SELECT 
        sd.nim, 
        pp.full_name, 
        md.major_name,
        sd.current_semester,
        sd.academic_status,
        sd.batch_year
    FROM students_data sd
    JOIN personal_profiles pp ON sd.user_id = pp.user_id
    JOIN majors_data md ON sd.major_id = md.id
    WHERE sd.id = ?
");
$stmt->execute([$studentId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    echo '<div class="alert alert-danger d-flex align-items-center justify-content-between rounded-4 shadow-sm p-3">';
    echo '<span><i class="bi bi-exclamation-triangle me-2"></i>Mahasiswa tidak ditemukan!</span>';
    echo '<a href="?view=students_krs_items" class="btn btn-sm btn-outline-danger">Kembali ke Daftar KRS</a>';
    echo '</div>';
    return;
}

// Get KRS Items
$stmtKrs = $conn->prepare("
    SELECT 
        krs.id AS krs_id,
        cd.course_code,
        cd.course_name,
        cd.credits,
        cd.semester,
        cd.course_type,
        krs.academic_year,
        krs.approval_status,
        ld.npp AS approved_by_npp,
        pp_lec.full_name AS approved_by_name
    FROM students_krs_data krs
    JOIN courses_data cd ON krs.course_id = cd.id
    LEFT JOIN lecturers_data ld ON krs.approved_by = ld.id
    LEFT JOIN personal_profiles pp_lec ON ld.user_id = pp_lec.user_id
    WHERE krs.student_id = ?
    ORDER BY krs.academic_year DESC, cd.semester ASC
");
$stmtKrs->execute([$studentId]);
$krsItems = $stmtKrs->fetchAll(PDO::FETCH_ASSOC);

$totalSks = 0;
foreach ($krsItems as $krs) {
    if (in_array($krs['approval_status'], ['Approved', 'Submitted'])) {
        $totalSks += (int)$krs['credits'];
    }
}
?>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-body border-bottom p-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-1 text-primary"><i class="bi bi-card-checklist me-2"></i>Kartu Rencana Studi (KRS)</h5>
                <span class="text-body-secondary" style="font-size: 0.85rem;">Detail Pengambilan Matakuliah Mahasiswa</span>
            </div>
            <a href="?view=students_krs_items" class="btn btn-outline-secondary btn-sm rounded-3">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar Mahasiswa
            </a>
        </div>
    </div>
    
    <div class="card-body p-4">
        <div class="row mb-4">
            <div class="col-md-6">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td width="150" class="text-body-secondary">Nama Mahasiswa</td><td width="10">:</td><td class="fw-bold text-body"><?= htmlspecialchars($student['full_name']) ?></td></tr>
                    <tr><td class="text-body-secondary">NIM</td><td>:</td><td class="fw-bold text-body font-monospace"><?= htmlspecialchars($student['nim']) ?></td></tr>
                    <tr><td class="text-body-secondary">Program Studi</td><td>:</td><td class="text-body"><?= htmlspecialchars($student['major_name']) ?></td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td width="150" class="text-body-secondary">Angkatan</td><td width="10">:</td><td class="text-body"><?= htmlspecialchars($student['batch_year']) ?></td></tr>
                    <tr><td class="text-body-secondary">Semester Aktif</td><td>:</td><td class="text-body">Semester <?= htmlspecialchars($student['current_semester']) ?></td></tr>
                    <tr><td class="text-body-secondary">Status Akademik</td><td>:</td><td><span class="badge bg-success-subtle text-success border border-success-subtle"><?= htmlspecialchars($student['academic_status']) ?></span></td></tr>
                </table>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle border rounded-3 overflow-hidden">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" width="50">No</th>
                        <th>Kode MK</th>
                        <th>Nama Matakuliah</th>
                        <th class="text-center">SKS</th>
                        <th class="text-center">Smt</th>
                        <th class="text-center">Status KRS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($krsItems)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-body-secondary">
                            <i class="bi bi-folder2-open fs-2 mb-2 d-block"></i>
                            Belum ada matakuliah yang diambil oleh mahasiswa ini.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($krsItems as $row): ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td><span class="fw-semibold text-primary font-monospace"><?= htmlspecialchars($row['course_code']) ?></span></td>
                            <td>
                                <div class="fw-semibold text-body"><?= htmlspecialchars($row['course_name']) ?></div>
                                <div class="text-body-secondary" style="font-size: 0.75rem;">
                                    Tipe: <?= htmlspecialchars($row['course_type']) ?> | TA: <?= htmlspecialchars($row['academic_year']) ?>
                                    <?php if (!empty($row['approved_by_name'])): ?>
                                        | Dosen Wali: <?= htmlspecialchars($row['approved_by_name']) ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-center text-body"><?= htmlspecialchars($row['credits']) ?></td>
                            <td class="text-center text-body"><?= htmlspecialchars($row['semester']) ?></td>
                            <td class="text-center">
                                <?php if ($row['approval_status'] === 'Approved'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Disetujui</span>
                                <?php elseif ($row['approval_status'] === 'Submitted'): ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-clock-history me-1"></i>Menunggu</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><?= htmlspecialchars($row['approval_status']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-light fw-bold">
                            <td colspan="3" class="text-end text-body">Total SKS Diambil :</td>
                            <td class="text-center text-primary fs-6"><?= $totalSks ?> SKS</td>
                            <td colspan="2"></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
