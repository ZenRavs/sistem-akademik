<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once defined('CONFIG_PATH') ? CONFIG_PATH . '/db.php' : __DIR__ . '/../../config/db.php';

if (!$conn) {
    echo '<div class="alert alert-danger">Gagal terhubung ke Database Cloud. Pastikan koneksi internet Anda aktif.</div>';
    return;
}

$currentUserRole = $_SESSION['user']['role'] ?? '';
$currentUserId = $_SESSION['user']['userid'] ?? '';

// Fetch active KRS offers
$getOffersStmt = $conn->query("SELECT * FROM krs_offers ORDER BY id DESC");
$offersList = $getOffersStmt->fetchAll();

// Fetch student KRS entries
if ($currentUserRole === 'Student' || $currentUserRole === 'Mahasiswa') {
    $getKrsStmt = $conn->prepare("
        SELECT sk.*, ko.course, ko.lecturer, ko.class_group, ko.days_sched1, ko.hours_sched1, ko.class_room1 
        FROM student_krs sk 
        JOIN krs_offers ko ON sk.krs_offer_id = ko.id 
        WHERE sk.student_nim = ? 
        ORDER BY sk.id DESC
    ");
    $getKrsStmt->execute([$currentUserId]);
    $myKrsList = $getKrsStmt->fetchAll();
} else {
    // Admin / Lecturer view all entries
    $getKrsStmt = $conn->query("
        SELECT sk.*, ko.course, ko.lecturer, ko.class_group, ko.days_sched1, ko.hours_sched1, ko.class_room1 
        FROM student_krs sk 
        JOIN krs_offers ko ON sk.krs_offer_id = ko.id 
        ORDER BY sk.id DESC
    ");
    $myKrsList = $getKrsStmt->fetchAll();
}
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Kartu Rencana Studi (KRS) Mahasiswa</h4>
        <span class="badge bg-success fs-6">Tahun Akademik: 2026/2027 Ganjil</span>
    </div>

    <?php if ($currentUserRole === 'Student' || $currentUserRole === 'Mahasiswa'): ?>
        <!-- Student Form to Pick Courses from KRS Offers -->
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="m-0">Ambil Mata Kuliah dari Penawaran KRS</h5>
            </div>
            <div class="card-body">
                <form id="formAddKrs">
                    <input type="hidden" name="student_nim" value="<?php echo htmlspecialchars($currentUserId); ?>">
                    <div class="row align-items-end">
                        <div class="col-md-9 mb-2">
                            <label for="krs_offer_id" class="form-label font-weight-bold">Pilih Penawaran Mata Kuliah & Jadwal</label>
                            <select class="form-select" id="krs_offer_id" name="krs_offer_id" required>
                                <option value="" disabled selected>-- Pilih Penawaran Jadwal --</option>
                                <?php foreach ($offersList as $offer): ?>
                                    <option value="<?php echo $offer['id']; ?>">
                                        <?php echo htmlspecialchars($offer['course']); ?> - Kelompok: <?php echo htmlspecialchars($offer['class_group']); ?> (Dosen: <?php echo htmlspecialchars($offer['lecturer']); ?> | Hari: <?php echo htmlspecialchars($offer['days_sched1']); ?> Jam: <?php echo htmlspecialchars($offer['hours_sched1']); ?> Ruang: <?php echo htmlspecialchars($offer['class_room1']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button type="submit" class="btn btn-primary w-100">+ Tambah ke KRS Saya</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- KRS Entries Table -->
    <div class="card shadow">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="m-0">Daftar KRS Yang Diambil</h5>
            <span class="badge bg-secondary"><?php echo count($myKrsList); ?> Mata Kuliah</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>NIM Mahasiswa</th>
                            <th>Mata Kuliah</th>
                            <th>Kelompok</th>
                            <th>Dosen Pengampu</th>
                            <th>Jadwal & Ruang</th>
                            <th>Status Verifikasi</th>
                            <th>Diverifikasi Oleh</th>
                            <?php if ($currentUserRole !== 'Student' && $currentUserRole !== 'Mahasiswa'): ?>
                                <th>Aksi Admin/DPA</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($myKrsList)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">Belum ada mata kuliah yang diambil di KRS.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($myKrsList as $idx => $krs): ?>
                                <tr>
                                    <td><?php echo $idx + 1; ?></td>
                                    <td><strong><?php echo htmlspecialchars($krs['student_nim']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($krs['course']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($krs['class_group']); ?></span></td>
                                    <td><?php echo htmlspecialchars($krs['lecturer']); ?></td>
                                    <td>
                                        <small>
                                            <?php echo htmlspecialchars($krs['days_sched1']); ?>, <?php echo htmlspecialchars($krs['hours_sched1']); ?> (Ruang: <?php echo htmlspecialchars($krs['class_room1']); ?>)
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($krs['status'] === 'Approved'): ?>
                                            <span class="badge bg-success">Approved / Disetujui</span>
                                        <?php elseif ($krs['status'] === 'Submitted'): ?>
                                            <span class="badge bg-warning text-dark">Menunggu Verifikasi DPA</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($krs['approved_by'] ?? '-'); ?></td>
                                    <?php if ($currentUserRole !== 'Student' && $currentUserRole !== 'Mahasiswa'): ?>
                                        <td>
                                            <?php if ($krs['status'] !== 'Approved'): ?>
                                                <button class="btn btn-sm btn-success btn-approve-krs" data-id="<?php echo $krs['id']; ?>">
                                                    Setujui (Approve)
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted small">Sudah Disetujui</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#formAddKrs').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'src/api.php?req=addStudentKrs',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                let res = typeof response === 'object' ? response : JSON.parse(response);
                if (res.status === 'success') {
                    alert(res.message);
                    location.reload();
                } else {
                    alert("Error: " + res.message);
                }
            },
            error: function() {
                alert("Gagal menambahkan KRS [addStudentKrs]");
            }
        });
    });

    $('.btn-approve-krs').on('click', function() {
        let krsId = $(this).data('id');
        if (confirm("Apakah Anda yakin ingin MENYETUJUI KRS ini?")) {
            $.ajax({
                url: 'src/api.php?req=approveStudentKrs',
                type: 'POST',
                data: { krs_id: krsId },
                success: function(response) {
                    let res = typeof response === 'object' ? response : JSON.parse(response);
                    if (res.status === 'success') {
                        alert(res.message);
                        location.reload();
                    } else {
                        alert("Error: " + res.message);
                    }
                },
                error: function() {
                    alert("Gagal menyetujui KRS [approveStudentKrs]");
                }
            });
        }
    });
});
</script>
