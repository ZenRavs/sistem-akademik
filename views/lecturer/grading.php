<?php
// Placeholder Form Penilaian Mahasiswa oleh Dosen
?>
<div class="card border-0 shadow-sm rounded-4 p-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-1"><i class="bi bi-pencil-square text-primary me-2"></i>Entry Nilai Perkuliahan</h5>
            <span class="text-body-secondary" style="font-size: 0.85rem;">Input nilai Tugas, UTS, UAS, dan publikasi nilai akhir mahasiswa</span>
        </div>
    </div>
    <div class="alert alert-info d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi bi-info-circle-fill fs-5"></i>
        <div>Modul penilaian terhubung langsung ke tabel <code>students_score_data</code>. Penilaian dapat diinput per kelas atau diimpor via Excel.</div>
    </div>
    <div class="card bg-body-tertiary border p-4 text-center rounded-4">
        <i class="bi bi-clipboard-data text-secondary fs-1 mb-2"></i>
        <h6 class="fw-bold">Pilih Kelas Perkuliahan untuk Mengisi Nilai</h6>
        <p class="text-body-secondary small mb-0">Silakan pilih kelas melalui jadwal mengajar untuk menginput nilai mahasiswa terdaftar.</p>
    </div>
</div>
