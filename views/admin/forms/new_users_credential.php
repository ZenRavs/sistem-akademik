<?php
if (isset($_SESSION['user'])) {
    $userRole = strtolower($_SESSION['user']['role'] ?? '');
    if ($userRole !== 'superadmin') {
        echo '<div class="alert alert-danger m-4 shadow-sm rounded-3"><i class="bi bi-shield-x me-2"></i>Akses Ditolak: Hanya <strong>Superadmin</strong> yang diizinkan menambah user baru ke dalam sistem.</div>';
        exit;
    }
?>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-body border-bottom p-4 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary text-white p-2 rounded-3">
                    <i class="bi bi-person-gear fs-4"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-body">Tambah User Kredensial Baru</h5>
                    <small class="text-body-secondary">Registrasi Akun Administrator Terpusat (users_credential & personal_profiles)</small>
                </div>
            </div>
            <a href="?view=users" class="btn btn-outline-secondary btn-sm rounded-3">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Kelola User
            </a>
        </div>

        <div class="card-body p-4">
            <!-- Informasi Pembatasan Hak Akses -->
            <div class="alert alert-warning border-0 bg-warning-subtle text-warning-emphasis rounded-3 p-3 mb-4" role="alert">
                <div class="d-flex gap-2 align-items-start">
                    <i class="bi bi-exclamation-triangle-fill fs-5 mt-1"></i>
                    <div>
                        <strong>Kebijakan Pembuatan Akun Manual:</strong>
                        <ul class="mb-0 mt-1 ps-3" style="font-size: 0.9rem;">
                            <li>Pembuatan akun manual dari menu ini <strong>hanya dibatasi untuk peranan Admin (TU) dan Superadmin</strong>.</li>
                            <li>Akun <strong>Mahasiswa (Student)</strong> dibuat secara otomatis melalui alur pendaftaran <strong>PMB Online</strong> (disertai penerbitan NIM dan data akademik).</li>
                            <li>Akun <strong>Dosen (Lecturer)</strong> ditambahkan melalui menu <strong>Data Dosen</strong> (disertai NPP, NIDN, dan Homebase Fakultas).</li>
                        </ul>
                    </div>
                </div>
            </div>

            <form id="insertUserForm" novalidate>
                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-key-fill me-2"></i>1. Kredensial & Hak Akses Akun (users_credential)</h6>
                <div class="row g-3 mb-4">
                    <!-- Username -->
                    <div class="col-md-6">
                        <label for="username" class="form-label fw-semibold">Username / Identitas Log <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-at"></i></span>
                            <input type="text" class="form-control" id="username" name="username" placeholder="contoh: admin_tu01" required>
                        </div>
                        <div id="username-error" class="form-text text-danger"></div>
                    </div>

                    <!-- Email Utama -->
                    <div class="col-md-6">
                        <label for="email" class="form-label fw-semibold">Email Utama <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="contoh: admin@dinus.ac.id" required>
                        </div>
                        <div id="email-error" class="form-text text-danger"></div>
                    </div>

                    <!-- Role Pengguna (Dibatasi Admin & Superadmin) -->
                    <div class="col-md-6">
                        <label for="role" class="form-label fw-semibold">Role / Tingkat Akses <span class="text-danger">*</span></label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="" selected disabled>-- Pilih Role Akses --</option>
                            <option value="admin">Admin (Tata Usaha / Staff)</option>
                            <option value="superadmin">Superadmin (Administrator Utama)</option>
                        </select>
                        <div class="form-text text-muted">Hanya role Admin dan Superadmin yang diizinkan dibuat secara manual.</div>
                    </div>

                    <!-- Status Akun -->
                    <div class="col-md-6">
                        <label for="account_status" class="form-label fw-semibold">Status Akun Awal <span class="text-danger">*</span></label>
                        <select class="form-select" id="account_status" name="account_status" required>
                            <option value="active" selected>Active (Aktif)</option>
                            <option value="suspended">Suspended (Ditangguhkan)</option>
                            <option value="banned">Banned (Dinonaktifkan)</option>
                            <option value="pending_activation">Pending Activation</option>
                        </select>
                    </div>

                    <!-- Password -->
                    <div class="col-md-6">
                        <label for="password" class="form-label fw-semibold">Kata Sandi (Password) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Minimal 6 karakter" minlength="6" required>
                        </div>
                    </div>

                    <!-- Konfirmasi Password -->
                    <div class="col-md-6">
                        <label for="password-confirm" class="form-label fw-semibold">Konfirmasi Kata Sandi <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-check2-square"></i></span>
                            <input type="password" class="form-control" id="password-confirm" name="password-confirm" placeholder="Ulangi kata sandi" required>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-card-heading me-2"></i>2. Data Diri & Profil Pengguna (personal_profiles)</h6>
                <div class="row g-3">
                    <!-- Nama Lengkap -->
                    <div class="col-md-6">
                        <label for="full_name" class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="full_name" name="full_name" placeholder="Nama lengkap sesuai KTP/Identitas" required>
                    </div>

                    <!-- NIK -->
                    <div class="col-md-6">
                        <label for="nik" class="form-label fw-semibold">NIK (No. KTP 16 Digit)</label>
                        <input type="text" class="form-control" id="nik" name="nik" placeholder="contoh: 3374012304950001" maxlength="16">
                    </div>

                    <!-- Jenis Kelamin -->
                    <div class="col-md-4">
                        <label for="gender" class="form-label fw-semibold">Jenis Kelamin</label>
                        <select class="form-select" id="gender" name="gender">
                            <option value="">-- Pilih Jenis Kelamin --</option>
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>

                    <!-- Tempat Lahir -->
                    <div class="col-md-4">
                        <label for="pob" class="form-label fw-semibold">Tempat Lahir</label>
                        <input type="text" class="form-control" id="pob" name="pob" placeholder="Kota lahir">
                    </div>

                    <!-- Tanggal Lahir -->
                    <div class="col-md-4">
                        <label for="dob" class="form-label fw-semibold">Tanggal Lahir</label>
                        <input type="date" class="form-control" id="dob" name="dob">
                    </div>

                    <!-- Agama -->
                    <div class="col-md-6">
                        <label for="religion" class="form-label fw-semibold">Agama</label>
                        <select class="form-select" id="religion" name="religion">
                            <option value="">-- Pilih Agama --</option>
                            <option value="Islam">Islam</option>
                            <option value="Kristen">Kristen</option>
                            <option value="Katolik">Katolik</option>
                            <option value="Hindu">Hindu</option>
                            <option value="Buddha">Buddha</option>
                            <option value="Khonghucu">Khonghucu</option>
                        </select>
                    </div>

                    <!-- No. Telepon / WA -->
                    <div class="col-md-6">
                        <label for="phone" class="form-label fw-semibold">No. HP / WhatsApp Utama</label>
                        <input type="text" class="form-control" id="phone" name="phone" placeholder="contoh: 081234567890">
                    </div>

                    <!-- Alamat KTP -->
                    <div class="col-md-6">
                        <label for="ktp_address" class="form-label fw-semibold">Alamat Sesuai KTP</label>
                        <textarea class="form-control" id="ktp_address" name="ktp_address" rows="2" placeholder="Alamat lengkap sesuai KTP"></textarea>
                    </div>

                    <!-- Alamat Domisili -->
                    <div class="col-md-6">
                        <label for="domicile_address" class="form-label fw-semibold">Alamat Domisili saat ini</label>
                        <textarea class="form-control" id="domicile_address" name="domicile_address" rows="2" placeholder="Alamat tempat tinggal saat ini"></textarea>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="?view=users" class="btn btn-outline-secondary px-4 rounded-3">Batal</a>
                    <button type="submit" class="btn btn-primary px-4 rounded-3" id="submitButton">
                        <i class="bi bi-check-circle me-1"></i>Simpan User Baru
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            function usernameCheck(username) {
                $.ajax({
                    type: 'POST',
                    url: 'src/api.php?req=usernameCheck',
                    dataType: 'json',
                    data: { username: username },
                    success: function(response) {
                        if (response === true || response.message === 'true' || response === 'true') {
                            $('#username-error').text('Username sudah digunakan! Harap gunakan username lain.');
                            $('#submitButton').prop('disabled', true);
                        } else {
                            $('#username-error').text('');
                            $('#submitButton').prop('disabled', false);
                        }
                    }
                });
            }

            $('#username').on('keyup blur', function() {
                let val = $(this).val().trim();
                if (val.length >= 3) {
                    usernameCheck(val);
                } else if (val.length > 0) {
                    $('#username-error').text('Minimal 3 karakter.');
                    $('#submitButton').prop('disabled', true);
                } else {
                    $('#username-error').text('');
                    $('#submitButton').prop('disabled', false);
                }
            });

            $('#insertUserForm').on('submit', function(e) {
                e.preventDefault();
                let pass = $('#password').val();
                let confirmPass = $('#password-confirm').val();
                let role = $('#role').val();
                
                if (!role || (role !== 'admin' && role !== 'superadmin')) {
                    alert('Pembuatan akun manual hanya diperbolehkan untuk role Admin atau Superadmin!');
                    return;
                }

                if (pass !== confirmPass) {
                    alert('Konfirmasi kata sandi tidak cocok dengan kata sandi!');
                    return;
                }

                let formData = $(this).serialize();
                $('#submitButton').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...');

                $.ajax({
                    type: 'POST',
                    url: 'src/api.php?req=insertNewUser',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            alert(response.message);
                            window.location.href = 'portal.php?view=users';
                        } else {
                            alert(response.message || 'Gagal menyimpan user.');
                            $('#submitButton').prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>Simpan User Baru');
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Terjadi kesalahan server: ' + error);
                        $('#submitButton').prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>Simpan User Baru');
                    }
                });
            });
        });
    </script>
<?php
} else {
    echo "Akses ditolak!";
}
?>
