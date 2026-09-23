<?php
// Placeholder Jadwal Mengajar Dosen
?>
<div class="card border-0 shadow-sm rounded-4 p-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-1"><i class="bi bi-calendar3 text-primary me-2"></i>Jadwal Perkuliahan & Mengajar</h5>
            <span class="text-body-secondary" style="font-size: 0.85rem;">Daftar kelas perkuliahan yang diampu pada semester aktif</span>
        </div>
    </div>
    <div class="alert alert-info d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi bi-info-circle-fill fs-5"></i>
        <div>Jadwal mengajar diambil langsung dari tabel <code>krs_offers</code> yang ditugaskan kepada NPP Anda.</div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Mata Kuliah</th>
                    <th>Kelas / Kelompok</th>
                    <th class="text-center">SKS</th>
                    <th>Hari & Jam</th>
                    <th>Ruang</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="7" class="text-center py-4 text-body-secondary">
                        <i class="bi bi-calendar-x fs-2 d-block mb-2 text-muted"></i>
                        Belum ada kelas perkuliahan yang ditugaskan pada semester ini.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
