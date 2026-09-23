<?php
// Placeholder Bimbingan & Persetujuan KRS Dosen Wali
?>
<div class="card border-0 shadow-sm rounded-4 p-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-1"><i class="bi bi-check2-circle text-primary me-2"></i>Bimbingan & Persetujuan KRS Mahasiswa</h5>
            <span class="text-body-secondary" style="font-size: 0.85rem;">Verifikasi dan persetujuan kartu rencana studi mahasiswa perwalian</span>
        </div>
    </div>
    <div class="alert alert-info d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi bi-info-circle-fill fs-5"></i>
        <div>Fitur approval terhubung ke <code>students_krs_data</code>. Dosen wali dapat menyetujui, memberi catatan revisi, atau menolak pengambilan mata kuliah.</div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>NIM</th>
                    <th>Nama Mahasiswa</th>
                    <th class="text-center">SKS Diambil</th>
                    <th>IPK Terakhir</th>
                    <th class="text-center">Status KRS</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="7" class="text-center py-4 text-body-secondary">
                        <i class="bi bi-person-check fs-2 d-block mb-2 text-muted"></i>
                        Tidak ada pengajuan KRS baru yang membutuhkan persetujuan saat ini.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
