<?php
$studentName = htmlspecialchars($_SESSION['user']['name'] ?? 'Mahasiswa');
$studentNim = htmlspecialchars($_SESSION['user']['userid'] ?? '-');
?>
<div class="row g-4">
    <!-- Welcome Banner Mahasiswa -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 bg-primary text-white p-4">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white bg-opacity-25 text-white mb-2" style="font-size: 0.78rem; width: fit-content;">
                <i class="bi bi-mortarboard-fill"></i>
                <span>Portal Akademik Mahasiswa &bull; Semester Ganjil 2026/2027</span>
            </div>
            <h3 class="fw-bold mb-1">Selamat Datang, <?= $studentName ?>! 🎓</h3>
            <p class="mb-0 opacity-75">NIM: <strong><?= $studentNim ?></strong> &bull; Status: <span class="badge bg-success">Aktif</span></p>
        </div>
    </div>

    <!-- Metrik Akademik Cepat -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <div class="text-body-secondary small mb-1">Total SKS Terambil</div>
            <h3 class="fw-bold text-primary mb-0">21</h3>
            <small class="text-success"><i class="bi bi-check-circle me-1"></i>KRS Disetujui</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <div class="text-body-secondary small mb-1">Indeks Prestasi Kumulatif (IPK)</div>
            <h3 class="fw-bold text-success mb-0">3.82</h3>
            <small class="text-body-secondary">Skala 4.00</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <div class="text-body-secondary small mb-1">Status Pembayaran UKT</div>
            <h3 class="fw-bold text-info mb-0">Lunas</h3>
            <small class="text-success"><i class="bi bi-shield-check me-1"></i>Terverifikasi</small>
        </div>
    </div>

    <!-- Card Info Pengembangan Fitur -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
            <i class="bi bi-tools text-primary fs-1 mb-2"></i>
            <h5 class="fw-bold">Fitur Lengkap Portal Mahasiswa</h5>
            <p class="text-body-secondary mb-3" style="max-width: 600px; margin: 0 auto;">
                Modul perkuliahan mahasiswa sedang dalam tahap integrasi aktif. Anda dapat mengakses fitur Kartu Rencana Studi (KRS) melalui menu navigasi di sebelah kiri.
            </p>
            <div class="d-flex justify-content-center gap-2">
                <a href="?view=krs" class="btn btn-primary"><i class="bi bi-card-checklist me-1"></i>Buka Pengisian KRS</a>
            </div>
        </div>
    </div>
</div>
