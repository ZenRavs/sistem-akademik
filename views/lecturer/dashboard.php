<?php
$lecturerName = htmlspecialchars($_SESSION['user']['name'] ?? 'Dosen Pengajar');
$lecturerNpp = htmlspecialchars($_SESSION['user']['userid'] ?? '-');
?>
<div class="row g-4">
    <!-- Welcome Banner Dosen -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 bg-primary text-white p-4">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white bg-opacity-25 text-white mb-2" style="font-size: 0.78rem; width: fit-content;">
                <i class="bi bi-person-badge-fill"></i>
                <span>Portal Akademik Dosen &bull; Semester Ganjil 2026/2027</span>
            </div>
            <h3 class="fw-bold mb-1">Selamat Datang, <?= $lecturerName ?>! 👨‍🏫</h3>
            <p class="mb-0 opacity-75">NPP: <strong><?= $lecturerNpp ?></strong> &bull; Homebase: <span class="badge bg-light text-dark">Fakultas Ilmu Komputer</span></p>
        </div>
    </div>

    <!-- Metrik Cepat Dosen -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <div class="text-body-secondary small mb-1">Kelas Mengajar Aktif</div>
            <h3 class="fw-bold text-primary mb-0">4 Kelas</h3>
            <small class="text-body-secondary">12 SKS Beban Mengajar</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <div class="text-body-secondary small mb-1">Mahasiswa Bimbingan Akademik</div>
            <h3 class="fw-bold text-success mb-0">24 Mahasiswa</h3>
            <small class="text-success"><i class="bi bi-check2-all me-1"></i>Dosen Wali</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <div class="text-body-secondary small mb-1">KRS Menunggu Persetujuan</div>
            <h3 class="fw-bold text-warning mb-0">3 Pengajuan</h3>
            <small class="text-warning"><i class="bi bi-clock-history me-1"></i>Perlu Review</small>
        </div>
    </div>

    <!-- Info Card -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
            <i class="bi bi-tools text-primary fs-1 mb-2"></i>
            <h5 class="fw-bold">Fitur Lengkap Portal Dosen</h5>
            <p class="text-body-secondary mb-3" style="max-width: 600px; margin: 0 auto;">
                Modul jadwal mengajar, penilaian, dan persetujuan KRS mahasiswa bimbingan sedang dalam tahap pengembangan aktif.
            </p>
            <div class="d-flex justify-content-center gap-2">
                <a href="?view=schedule" class="btn btn-outline-primary"><i class="bi bi-calendar3 me-1"></i>Jadwal Mengajar</a>
                <a href="?view=krs_approval" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Bimbingan KRS</a>
            </div>
        </div>
    </div>
</div>
