<?php
// Placeholder Kartu Hasil Studi (KHS) & Nilai
?>
<div class="card border-0 shadow-sm rounded-4 p-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-1"><i class="bi bi-file-earmark-spreadsheet-fill text-primary me-2"></i>Kartu Hasil Studi (KHS) & Transkrip</h5>
            <span class="text-body-secondary" style="font-size: 0.85rem;">Histori penilaian mata kuliah dan indeks prestasi per semester</span>
        </div>
        <button class="btn btn-outline-primary btn-sm disabled"><i class="bi bi-printer me-1"></i>Cetak KHS</button>
    </div>
    <div class="alert alert-info d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi bi-info-circle-fill fs-5"></i>
        <div>Modul rekapitulasi nilai sedang disinkronkan dengan tabel <code>students_score_data</code>. Rincian nilai semester akan ditampilkan di sini.</div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Kode Matakuliah</th>
                    <th>Nama Matakuliah</th>
                    <th class="text-center">SKS</th>
                    <th class="text-center">Nilai Angka</th>
                    <th class="text-center">Grade</th>
                    <th class="text-center">Bobot</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="7" class="text-center py-4 text-body-secondary">
                        <i class="bi bi-journal-x fs-2 d-block mb-2 text-muted"></i>
                        Belum ada nilai semester yang dipublikasikan oleh dosen wali.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
