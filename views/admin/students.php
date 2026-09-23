<?php
if (isset($_SESSION['user'])) {
    $_SESSION['table']['page'] = 1;
    $page = $_GET['page'] ?? 0;
?>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-body border-bottom p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <form class="d-flex gap-2 align-items-center" id="searchBox">
                    <select class="form-select form-select-sm" id="searchCategory" title="Select the searching categories..." style="width: 120px;">
                        <option value="name">Name</option>
                        <option value="nim">NIM</option>
                        <option value="email">Email</option>
                    </select>
                    <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Search..." aria-label="Search" style="width: 200px;" title="Search for Name, NIM, or Email." required>
                    <button class="btn btn-primary btn-sm px-3" type="submit" id="searchButton"><i class="bi bi-search me-1"></i>Search</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="resetButton">Reset</button>
                </form>
                
                <div class="d-flex gap-3 align-items-center">
                    <a type="button" href="src/reporting/pdf_students.php" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>Print</a>
                    <div class="d-flex align-items-center gap-2">
                        <label for="maxRow" class="small text-body-secondary text-nowrap mb-0">Show:</label>
                        <select class="form-select form-select-sm" id="maxRow" style="width: 70px;">
                            <option value="2">2</option>
                            <option value="10" selected="selected">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <button type="button" id="prevBtn" class="btn btn-outline-secondary btn-sm">&lt;</button>
                        <select class="form-select form-select-sm" id="pageOption" style="width: 70px;"></select>
                        <button type="button" id="nextBtn" class="btn btn-outline-secondary btn-sm">&gt;</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-body p-0">
            <?php
            if (isset($_SESSION['crud'])) {
            ?>
                <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                    <?= $_SESSION['crud']['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php
                unset($_SESSION['crud']);
            } ?>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="align-middle text-nowrap">
                            <th class="text-center ps-4" style="width: 50px;">#</th>
                            <th style="width: 140px;">NIM</th>
                            <th>Nama Mahasiswa</th>
                            <th style="width: 200px;">Prodi Mahasiswa</th>
                            <th class="text-center" style="width: 110px;">Status</th>
                            <th>Kontak</th>
                            <th class="text-center pe-4" style="width: 140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        @media (min-width: 768px) {
            #viewStudentModal .border-end-md-black,
            #editStudentModal .border-end-md-black {
                border-right: 1px solid var(--bs-border-color) !important;
            }
        }
    </style>

    <!-- Modal Popup Lihat Detail Data Mahasiswa -->
    <div class="modal fade text-body" id="viewStudentModal" tabindex="-1" aria-labelledby="viewStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title d-flex align-items-center gap-2" id="viewStudentModalLabel">
                        <span>Detail Data Mahasiswa</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Top section: Photo, Basic Info, and Audit Metadata (Created/Author/Edited) -->
                    <div class="row g-3">
                        <div class="col-md-4 border-end-md-black pe-md-3 text-center mb-3 mb-md-0">
                            <div class="p-3 bg-body-secondary rounded border border-secondary-subtle h-100 d-flex flex-column align-items-center justify-content-center">
                                <img id="view_student_img" src="" class="rounded-circle border border-3 border-primary shadow-sm object-fit-cover mb-2" width="105" height="105" alt="Foto Mahasiswa">
                                <h6 id="view_student_name_top" class="fw-bold text-body mb-1"></h6>
                                <span id="view_student_nim_badge" class="badge bg-dark font-monospace fs-6 mb-1"></span>
                                <span id="view_student_prodi_badge" class="badge bg-primary-subtle text-primary border border-primary-subtle mb-1"></span>
                                <span id="view_student_status_badge" class="badge bg-success-subtle text-success border border-success-subtle mb-3">Status: Aktif</span>
                                
                                <!-- Audit Metadata Box (Relocated from Table) -->
                                <div class="w-100 p-2 bg-body rounded border text-start small">
                                    <div class="mb-1">
                                        <small class="text-muted d-block" style="font-size: 0.72rem;">📅 Dibuat Pada (Created):</small>
                                        <span id="view_student_created_at" class="fw-semibold text-body" style="font-size: 0.8rem;">-</span>
                                    </div>
                                    <div class="mb-1">
                                        <small class="text-muted d-block" style="font-size: 0.72rem;">🕒 Terakhir Diubah (Edited):</small>
                                        <span id="view_student_edited_at" class="fw-semibold text-body" style="font-size: 0.8rem;">-</span>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block" style="font-size: 0.72rem;">👤 Dibuat Oleh (Author):</small>
                                        <span id="view_student_author" class="badge bg-secondary-subtle text-secondary-emphasis" style="font-size: 0.75rem;">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right section: 1. Data Diri Mahasiswa -->
                        <div class="col-md-8 ps-md-3">
                            <h6 class="text-primary font-weight-bold mb-2 small text-uppercase" style="letter-spacing: 0.5px;">👤 1. Data Diri Mahasiswa</h6>
                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Program Studi Kampus</small>
                                    <strong id="view_student_prodi_display" class="text-body"></strong>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Status Mahasiswa</small>
                                    <strong id="view_student_status_display" class="text-body"></strong>
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <small class="text-muted d-block">NIM</small>
                                    <strong id="view_student_nim" class="text-body"></strong>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">NIK (No. KTP)</small>
                                    <strong id="view_student_nik" class="text-body"></strong>
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Nama Lengkap</small>
                                    <strong id="view_student_name" class="text-body"></strong>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Email Utama</small>
                                    <strong id="view_student_email" class="text-body"></strong>
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Jenis Kelamin</small>
                                    <strong id="view_student_gender" class="text-body"></strong>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Agama</small>
                                    <strong id="view_student_religion" class="text-body"></strong>
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Tempat Lahir</small>
                                    <strong id="view_student_pob" class="text-body"></strong>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Tanggal Lahir</small>
                                    <strong id="view_student_dob" class="text-body"></strong>
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-md-12">
                                    <small class="text-muted d-block">No. HP / WhatsApp Mahasiswa</small>
                                    <strong id="view_student_phone" class="text-body"></strong>
                                </div>
                            </div>

                            <div class="row g-2">
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Status Pernikahan</small>
                                    <strong id="view_student_marital_status" class="text-body"></strong>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Status Pekerjaan</small>
                                    <strong id="view_student_job_status" class="text-body"></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sekat Pemisah 1 -->
                    <hr class="my-3" style="border-top: 1px solid #000000; opacity: 0.25;">

                    <!-- Sekelompok 2: Data Orang Tua / Wali -->
                    <div class="mb-3">
                        <h6 class="text-primary font-weight-bold mb-2 small text-uppercase" style="letter-spacing: 0.5px;">👨‍👩‍👧 2. Data Orang Tua / Wali</h6>
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Nama Ibu Kandung</small>
                                <strong id="view_student_mother_name" class="text-body"></strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Nama Ayah</small>
                                <strong id="view_student_father_name" class="text-body"></strong>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-12">
                                <small class="text-muted d-block">No. HP / WhatsApp Orang Tua (Ayah / Ibu / Wali)</small>
                                <strong id="view_student_parent_phone" class="text-body"></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Sekat Pemisah 2 -->
                    <hr class="my-3" style="border-top: 1px solid #000000; opacity: 0.25;">

                    <!-- Sekelompok 3: Data Sekolah Asal -->
                    <div class="mb-3">
                        <h6 class="text-primary font-weight-bold mb-2 small text-uppercase" style="letter-spacing: 0.5px;">🏫 3. Data Sekolah Asal</h6>
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <small class="text-muted d-block">NISN</small>
                                <strong id="view_student_nisn" class="text-body"></strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Jurusan (Sekolah Asal)</small>
                                <strong id="view_student_major" class="text-body"></strong>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Asal Sekolah (SMA/SMK)</small>
                                <strong id="view_student_school_origin" class="text-body"></strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Nilai Akhir Rata-rata</small>
                                <strong id="view_student_final_score" class="text-body"></strong>
                            </div>
                        </div>

                        <div class="mb-2">
                            <small class="text-muted d-block">Alamat Sekolah Asal</small>
                            <span id="view_student_school_address" class="text-body"></span>
                        </div>
                    </div>

                    <!-- Sekat Pemisah 3 -->
                    <hr class="my-3" style="border-top: 1px solid #000000; opacity: 0.25;">

                    <!-- Sekelompok 4: Data Tempat Tinggal / Alamat -->
                    <div>
                        <h6 class="text-primary font-weight-bold mb-2 small text-uppercase" style="letter-spacing: 0.5px;">🏠 4. Data Tempat Tinggal / Alamat</h6>
                        <div class="mb-2">
                            <small class="text-muted d-block">Alamat Sesuai KTP</small>
                            <span id="view_student_ktp_address" class="text-body d-block"></span>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted d-block">Alamat Domisili (Saat Ini)</small>
                            <span id="view_student_address" class="text-body d-block"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-body-secondary border-top border-secondary-subtle d-flex justify-content-between">
                    <div class="d-flex gap-2">
                        <a href="#" id="view_student_krs_link" class="btn btn-sm btn-success">
                            📄 Buka KRS Mahasiswa
                        </a>
                        <a href="#" id="view_student_bill_link" class="btn btn-sm btn-warning text-body fw-semibold">
                            💳 Tagihan UKT
                        </a>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" id="view_student_edit_btn" class="btn btn-sm btn-warning">
                            ✏️ Edit Data
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Popup Edit Data Mahasiswa -->
    <div class="modal fade text-body" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editStudentModalLabel">✏️ Edit Data Mahasiswa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editStudentModalForm" enctype="multipart/form-data">
                    <div class="modal-body p-4">
                        <input type="hidden" id="modal_student_id" name="id">
                        <input type="hidden" id="modal_student_nim" name="nim">

                        <!-- Upper Section: Photo (Left) & Data Diri (Right) -->
                        <div class="row g-3">
                            <!-- Left Column: Compact Photo Card (Ringkas, Tanpa Stretching h-100) -->
                            <div class="col-md-4 border-end-md-black pe-md-3 mb-3 mb-md-0">
                                <div class="p-3 bg-body-secondary rounded border border-secondary-subtle text-center">
                                    <label class="form-label font-weight-bold text-body small mb-2">Foto Profil Mahasiswa</label>
                                    <div class="d-flex justify-content-center mb-2">
                                        <img id="modal_student_img" src="" class="rounded-circle border border-2 shadow-sm object-fit-cover" width="85" height="85" alt="Foto Mahasiswa">
                                    </div>
                                    <small class="text-muted d-block mb-2" style="font-size: 0.75rem;">JPG, JPEG, PNG, WEBP</small>
                                    <div class="text-start">
                                        <label for="modal_student_pict" class="form-label font-weight-bold text-body small mb-1">Ganti Foto (Opsional)</label>
                                        <input class="form-control form-control-sm" type="file" name="pict" id="modal_student_pict" accept="image/*">
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Categorized Fields -->
                            <div class="col-md-8 ps-md-3">
                                <!-- SEKELOMPOK 1: Data Diri Mahasiswa -->
                                <h6 class="text-primary font-weight-bold mb-2 small text-uppercase" style="letter-spacing: 0.5px;">👤 1. Data Diri Mahasiswa</h6>
                                <div class="row g-2 mb-2">
                                    <div class="col-md-6">
                                        <label for="modal_student_nim_dis" class="form-label font-weight-bold text-body small mb-1">NIM (Locked 🔒)</label>
                                        <input type="text" class="form-control form-control-sm bg-body-secondary" id="modal_student_nim_dis" disabled readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="modal_student_nik_dis" class="form-label font-weight-bold text-body small mb-1">NIK (Locked 🔒)</label>
                                        <input type="text" class="form-control form-control-sm bg-body-secondary" id="modal_student_nik_dis" placeholder="16-Digit NIK" disabled readonly>
                                    </div>
                                </div>

                                <div class="row g-2 mb-2">
                                    <div class="col-md-6">
                                        <label for="modal_student_name" class="form-label font-weight-bold text-body small mb-1">Nama Lengkap</label>
                                        <input type="text" class="form-control form-control-sm" id="modal_student_name" name="name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="modal_student_email" class="form-label font-weight-bold text-body small mb-1">Email Utama</label>
                                        <input type="email" class="form-control form-control-sm" id="modal_student_email" name="email" required>
                                    </div>
                                </div>

                                <div class="row g-2 mb-2">
                                    <div class="col-md-6">
                                        <label for="modal_student_gender" class="form-label font-weight-bold text-body small mb-1">Jenis Kelamin</label>
                                        <select class="form-select form-select-sm" id="modal_student_gender" name="gender" required>
                                            <option value="" disabled>-- Pilih Jenis Kelamin --</option>
                                            <option value="Laki-laki">Laki-laki</option>
                                            <option value="Perempuan">Perempuan</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="modal_student_religion" class="form-label font-weight-bold text-body small mb-1">Agama</label>
                                        <select class="form-select form-select-sm" id="modal_student_religion" name="religion" required>
                                            <option value="" disabled>-- Pilih Agama --</option>
                                            <option value="Islam">Islam</option>
                                            <option value="Kristen">Kristen</option>
                                            <option value="Katolik">Katolik</option>
                                            <option value="Hindu">Hindu</option>
                                            <option value="Buddha">Buddha</option>
                                            <option value="Khonghucu">Khonghucu</option>
                                            <option value="Lainnya">Lainnya</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row g-2 mb-2">
                                    <div class="col-md-6">
                                        <label for="modal_student_pob" class="form-label font-weight-bold text-body small mb-1">Tempat Lahir</label>
                                        <input type="text" class="form-control form-control-sm" id="modal_student_pob" name="pob" placeholder="Kota kelahiran" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="modal_student_dob" class="form-label font-weight-bold text-body small mb-1">Tanggal Lahir</label>
                                        <input type="date" class="form-control form-control-sm" id="modal_student_dob" name="dob" required>
                                    </div>
                                </div>

                                <div class="row g-2 mb-2">
                                    <div class="col-md-12">
                                        <label for="modal_student_phone" class="form-label font-weight-bold text-body small mb-1">No. HP / WhatsApp Mahasiswa</label>
                                        <input type="text" class="form-control form-control-sm" id="modal_student_phone" name="phone" placeholder="08123456789">
                                    </div>
                                </div>

                                <div class="row g-2 mb-2">
                                    <div class="col-md-6">
                                        <label for="modal_student_prodi" class="form-label font-weight-bold text-body small mb-1">Program Studi Mahasiswa</label>
                                        <select class="form-select form-select-sm" id="modal_student_prodi" name="prodi">
                                            <option value="Teknik Informatika (S1)">A11 - Teknik Informatika (S1)</option>
                                            <option value="Sistem Informasi (S1)">A12 - Sistem Informasi (S1)</option>
                                            <option value="Desain Komunikasi Visual (S1)">A14 - Desain Komunikasi Visual (S1)</option>
                                            <option value="Ilmu Komunikasi (S1)">A15 - Ilmu Komunikasi (S1)</option>
                                            <option value="Teknik Informatika (D3)">A22 - Teknik Informatika (D3)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="modal_student_status" class="form-label font-weight-bold text-body small mb-1">Status Mahasiswa</label>
                                        <select class="form-select form-select-sm" id="modal_student_status" name="status">
                                            <option value="Aktif">Aktif</option>
                                            <option value="Cuti">Cuti</option>
                                            <option value="Lulus">Lulus</option>
                                            <option value="Non-Aktif">Non-Aktif</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label for="modal_student_marital_status" class="form-label font-weight-bold text-body small mb-1">Status Pernikahan</label>
                                        <select class="form-select form-select-sm" id="modal_student_marital_status" name="marital_status">
                                            <option value="Belum Menikah">Belum Menikah</option>
                                            <option value="Menikah">Menikah</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="modal_student_job_status" class="form-label font-weight-bold text-body small mb-1">Status Pekerjaan</label>
                                        <select class="form-select form-select-sm" id="modal_student_job_status" name="job_status">
                                            <option value="Belum Bekerja">Belum Bekerja</option>
                                            <option value="Bekerja">Bekerja</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SEKAT PEMISAH 1 -->
                        <hr class="my-3" style="border-top: 1px solid #000000; opacity: 0.25;">

                        <!-- SEKELOMPOK 2: Data Orang Tua / Wali -->
                        <div class="mb-2">
                            <h6 class="text-primary font-weight-bold mb-2 small text-uppercase" style="letter-spacing: 0.5px;">👨‍👩‍👧 2. Data Orang Tua / Wali</h6>
                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <label for="modal_student_mother_name" class="form-label font-weight-bold text-body small mb-1">Nama Ibu Kandung</label>
                                    <input type="text" class="form-control form-control-sm" id="modal_student_mother_name" name="mother_name" placeholder="Nama ibu kandung" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="modal_student_father_name" class="form-label font-weight-bold text-body small mb-1">Nama Ayah</label>
                                    <input type="text" class="form-control form-control-sm" id="modal_student_father_name" name="father_name" placeholder="Nama ayah">
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-12">
                                    <label for="modal_student_parent_phone" class="form-label font-weight-bold text-body small mb-1">No. HP / WhatsApp Orang Tua (Bebas diisi Ayah / Ibu / Wali)</label>
                                    <input type="text" class="form-control form-control-sm" id="modal_student_parent_phone" name="parent_phone" placeholder="08123456789">
                                </div>
                            </div>
                        </div>

                        <!-- SEKAT PEMISAH 2 -->
                        <hr class="my-3" style="border-top: 1px solid #000000; opacity: 0.25;">

                        <!-- SEKELOMPOK 3: Data Sekolah Asal -->
                        <div class="mb-2">
                            <h6 class="text-primary font-weight-bold mb-2 small text-uppercase" style="letter-spacing: 0.5px;">🏫 3. Data Sekolah Asal</h6>
                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <label for="modal_student_nisn" class="form-label font-weight-bold text-body small mb-1">NISN (10 Digit)</label>
                                    <input type="text" class="form-control form-control-sm" id="modal_student_nisn" name="nisn" maxlength="10" placeholder="00xxxxxxxx">
                                </div>
                                <div class="col-md-6">
                                    <label for="modal_student_major" class="form-label font-weight-bold text-body small mb-1">Jurusan Sekolah Asal</label>
                                    <input type="text" class="form-control form-control-sm" id="modal_student_major" name="major" placeholder="Contoh: IPA / IPS / TKJ">
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <label for="modal_student_school_origin" class="form-label font-weight-bold text-body small mb-1">Asal Sekolah (SMA/SMK)</label>
                                    <input type="text" class="form-control form-control-sm" id="modal_student_school_origin" name="school_origin" placeholder="Nama sekolah asal">
                                </div>
                                <div class="col-md-6">
                                    <label for="modal_student_final_score" class="form-label font-weight-bold text-body small mb-1">Nilai Akhir Rata-rata</label>
                                    <input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm" id="modal_student_final_score" name="final_score" placeholder="0.00">
                                </div>
                            </div>

                            <div class="mb-2">
                                <label for="modal_student_school_address" class="form-label font-weight-bold text-body small mb-1">Alamat Sekolah Asal</label>
                                <textarea class="form-control form-control-sm" id="modal_student_school_address" name="school_address" rows="2" placeholder="Lokasi/alamat sekolah asal"></textarea>
                            </div>
                        </div>

                        <!-- SEKAT PEMISAH 3 -->
                        <hr class="my-3" style="border-top: 1px solid #000000; opacity: 0.25;">

                        <!-- SEKELOMPOK 4: Data Domisili / Alamat -->
                        <div>
                            <h6 class="text-primary font-weight-bold mb-2 small text-uppercase" style="letter-spacing: 0.5px;">🏠 4. Data Tempat Tinggal / Alamat</h6>
                            <div class="mb-2">
                                <label for="modal_student_ktp_address" class="form-label font-weight-bold text-body small mb-1">Alamat Sesuai KTP</label>
                                <textarea class="form-control form-control-sm" id="modal_student_ktp_address" name="ktp_address" rows="2" placeholder="Alamat lengkap sesuai KTP"></textarea>
                            </div>
                            <div class="mb-2">
                                <label for="modal_student_address" class="form-label font-weight-bold text-body small mb-1">Alamat Domisili (Saat Ini)</label>
                                <textarea class="form-control form-control-sm" id="modal_student_address" name="address" rows="2" placeholder="Alamat domisili saat ini"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-body-secondary border-top border-secondary-subtle">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 font-weight-bold">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            const urlParams = new URLSearchParams(window.location.search);
            var page = $('select#pageOption').val() ? parseInt(urlParams.get('page')) : 1;
            var maxRow = $('select#maxRow').val();
            var pages = 0;
            loadData(page, maxRow);

            function loadData(page, maxRow) {
                $("#tableBody").empty();
                $.ajax({
                    url: 'src/api.php?req=fetchStudents',
                    type: 'POST',
                    data: {
                        page: page,
                        maxRow: maxRow
                    },
                    success: function(response) {
                        let data = JSON.parse(response);
                        $('#tableBody').html(data.html);
                        pages = data.pages;
                        loadOption(pages);
                    },
                    error: function() {
                        alert('Error fetching data');
                    }
                });
            }

            function loadOption(pages) {
                $("#pageOption").empty();
                for (let i = 1; i <= pages; i++) {
                    $('#pageOption').append('<option value=' + i + '>' + i + '</option>');
                }
                $('#pageOption').find('option[value="' + page + '"]').attr('selected', true);
            }

            $('#maxRow').on('change', function() {
                $("#tableBody").empty();
                maxRow = $('#maxRow').val();
                page = 1;
                loadData(page, maxRow);
            });

            $('#pageOption').on('change', function() {
                $("#tableBody").empty();
                var selectedOption = $(this).val();
                page = selectedOption;
                loadData(page, maxRow);
            });

            $('#prevBtn').on('click', () => {
                if (page > 1) {
                    page--;
                    loadData(page, maxRow);
                } else {
                    alert("You're already at the first page!");
                }
            });

            $('#nextBtn').on('click', () => {
                if (page < pages) {
                    page++;
                    loadData(page, maxRow);
                } else {
                    alert("Last page reached!");
                }
            });

            $('#resetButton').on('click', () => {
                $("#tableBody").empty();
                $('#searchInput').val('');
                page = 1;
                loadData(page, maxRow);
            });

            $('#searchBox').on('submit', function(e) {
                e.preventDefault();
                var searchCategory = $('#searchCategory').val();
                var searchInput = $('#searchInput').val();
                $("#tableBody").empty();
                $("#pageOption").empty();
                $.ajax({
                    url: 'src/api.php?req=searchStudent',
                    type: 'POST',
                    data: {
                        page: page,
                        searchCategory: searchCategory,
                        searchInput: searchInput
                    },
                    success: function(response) {
                        var respons = JSON.parse(response);
                        if (respons.status == 'error') {
                            alert(respons.message);
                        } else {
                            $('#tableBody').html(respons.html);
                        }
                    },
                    error: function() {
                        alert("Server error. [req: searchStudent]");
                    }
                });
            });

            // Trigger Modal Lihat Detail Data Mahasiswa
            $('#tableBody').on('click', '.viewStudentModalBtn', function() {
                var id = $(this).data('id');
                $.ajax({
                    url: 'src/api.php?req=getStudentDetail',
                    type: 'POST',
                    data: { id: id },
                    success: function(response) {
                        let res = JSON.parse(response);
                        if (res.status === 'success') {
                            let d = res.data;
                            $('#view_student_img').attr('src', d.img_src || '');
                            $('#view_student_name_top').text(d.name || '-');
                            $('#view_student_nim_badge').text(d.nim || '-');
                            $('#view_student_prodi_badge').text(d.prodi_display || d.prodi || '-');
                            $('#view_student_status_badge').text('Status: ' + (d.status || 'Aktif'));
                            $('#view_student_created_at').text(d.created_at_formatted || d.created_at || '-');
                            $('#view_student_edited_at').text(d.edited_at_formatted || d.edited_at || 'Belum pernah diubah');
                            $('#view_student_author').text(d.author_display || d.author || 'System / Admin');

                            $('#view_student_prodi_display').text(d.prodi_display || d.prodi || '-');
                            $('#view_student_status_display').text(d.status || 'Aktif');
                            $('#view_student_nim').text(d.nim || '-');
                            $('#view_student_nik').text(d.nik || '-');
                            $('#view_student_name').text(d.name || '-');
                            $('#view_student_email').text(d.email || '-');
                            $('#view_student_gender').text(d.gender || '-');
                            $('#view_student_religion').text(d.religion || '-');
                            $('#view_student_pob').text(d.pob || '-');
                            $('#view_student_dob').text(d.dob || '-');
                            $('#view_student_phone').text(d.phone || '-');
                            $('#view_student_marital_status').text(d.marital_status || 'Belum Menikah');
                            $('#view_student_job_status').text(d.job_status || 'Belum Bekerja');

                            $('#view_student_mother_name').text(d.mother_name || '-');
                            $('#view_student_father_name').text(d.father_name || '-');
                            $('#view_student_parent_phone').text(d.parent_phone || d.father_phone || '-');

                            $('#view_student_nisn').text(d.nisn || '-');
                            $('#view_student_major').text(d.major || d.prodi || '-');
                            $('#view_student_school_origin').text(d.school_origin || '-');
                            $('#view_student_final_score').text(d.final_score || '-');
                            $('#view_student_school_address').text(d.school_address || '-');

                            $('#view_student_ktp_address').text(d.ktp_address || '-');
                            $('#view_student_address').text(d.address || '-');

                            $('#view_student_krs_link').attr('href', '?view=students_krs_items&student_id=' + d.id);
                            $('#view_student_bill_link').attr('href', '?view=students_bills&search=' + encodeURIComponent(d.nim));
                            $('#view_student_edit_btn').data('id', d.id);

                            var viewModal = new bootstrap.Modal(document.getElementById('viewStudentModal'));
                            viewModal.show();
                        } else {
                            alert(res.message);
                        }
                    },
                    error: function() {
                        alert('Gagal mengambil detail data mahasiswa.');
                    }
                });
            });

            function openEditStudentModal(id) {
                $.ajax({
                    url: 'src/api.php?req=getStudentDetail',
                    type: 'POST',
                    data: { id: id },
                    success: function(response) {
                        let res = JSON.parse(response);
                        if (res.status === 'success') {
                            let d = res.data;
                            $('#modal_student_id').val(d.id);
                            $('#modal_student_nim').val(d.nim);
                            $('#modal_student_nim_dis').val(d.nim);
                            $('#modal_student_nik_dis').val(d.nik || '-');
                            $('#modal_student_name').val(d.name);
                            $('#modal_student_email').val(d.email);
                            $('#modal_student_gender').val(d.gender || '');
                            $('#modal_student_religion').val(d.religion || '');
                            $('#modal_student_pob').val(d.pob || '');
                            $('#modal_student_dob').val(d.dob || '');
                            $('#modal_student_marital_status').val(d.marital_status || 'Belum Menikah');
                            $('#modal_student_job_status').val(d.job_status || 'Belum Bekerja');
                            $('#modal_student_prodi').val(d.prodi_display || d.prodi || 'Teknik Informatika (S1)');
                            $('#modal_student_status').val(d.status || 'Aktif');
                            $('#modal_student_nisn').val(d.nisn || '');
                            $('#modal_student_mother_name').val(d.mother_name || '');
                            $('#modal_student_father_name').val(d.father_name || '');
                            $('#modal_student_parent_phone').val(d.parent_phone || d.father_phone || '');
                            $('#modal_student_major').val(d.major || '');
                            $('#modal_student_phone').val(d.phone || '');
                            $('#modal_student_ktp_address').val(d.ktp_address || '');
                            $('#modal_student_address').val(d.address || '');
                            $('#modal_student_school_origin').val(d.school_origin || '');
                            $('#modal_student_final_score').val(d.final_score || '');
                            $('#modal_student_school_address').val(d.school_address || '');
                            $('#modal_student_img').attr('src', d.img_src);
                            $('#modal_student_pict').val('');

                            var myModal = new bootstrap.Modal(document.getElementById('editStudentModal'));
                            myModal.show();
                        } else {
                            alert(res.message);
                        }
                    },
                    error: function() {
                        alert('Server error fetching student details.');
                    }
                });
            }

            // Shortcut to open Edit Modal from Detail Modal
            $('#view_student_edit_btn').on('click', function() {
                var studentId = $(this).data('id');
                var viewModalEl = document.getElementById('viewStudentModal');
                var viewModalInstance = bootstrap.Modal.getInstance(viewModalEl);
                if (viewModalInstance) {
                    viewModalInstance.hide();
                }
                openEditStudentModal(studentId);
            });

            // Trigger Modal Edit Student (if button present)
            $('#tableBody').on('click', '.editStudentModalBtn', function() {
                var id = $(this).data('id');
                openEditStudentModal(id);
            });

            // Submit Edit Student Modal Form
            $('#editStudentModalForm').on('submit', function(e) {
                e.preventDefault();
                let formData = new FormData($(this)[0]);
                let nim = $('#modal_student_nim').val();
                $.ajax({
                    type: 'POST',
                    url: 'src/api.php?req=updateStudent&nim=' + nim,
                    data: formData,
                    processData: false,
                    contentType: false,
                    cache: false,
                    success: function(response) {
                        let res = JSON.parse(response);
                        if (res.status === 'success') {
                            var modalEl = document.getElementById('editStudentModal');
                            var modalInstance = bootstrap.Modal.getInstance(modalEl);
                            if (modalInstance) modalInstance.hide();
                            alert('Data mahasiswa berhasil diperbarui!');
                            loadData(page, maxRow);
                        } else {
                            alert('Gagal memperbarui data mahasiswa!');
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('An error occurred: ' + error);
                    }
                });
            });

            $('#tableBody').on('click', '#deleteBtn', function() {
                var id = $(this).data('id');
                if (confirm("Are you sure to delete this row?")) {
                    $.ajax({
                        url: 'src/api.php?req=deleteStudent',
                        type: 'POST',
                        data: {
                            id: id,
                            page: page
                        },
                        success: function(response) {
                            var respons = JSON.parse(response);
                            alert(respons.message);
                            loadData(page, maxRow);
                        },
                        error: function() {
                            alert("Server error. [req: deleteStudent]");
                        }
                    });
                }
            });
        });
    </script>
<?php
} else {
    echo "Access denied!";
}

