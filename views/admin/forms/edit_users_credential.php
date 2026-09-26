<?php
if (isset($_SESSION['user'])) {
    $userRole = strtolower($_SESSION['user']['role'] ?? '');
    if ($userRole !== 'superadmin') {
        echo '<div class="alert alert-danger m-4 shadow-sm rounded-3"><i class="bi bi-shield-x me-2"></i>Akses Ditolak: Hanya <strong>Superadmin</strong> yang diizinkan mengubah data user admin.</div>';
        exit;
    }
    $userId = (int)($_GET['id'] ?? 0);
    if ($userId <= 0) {
        echo '<script>alert("ID User tidak valid!"); window.location.href="portal.php?view=users";</script>';
        exit;
    }
?>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-body border-bottom p-4 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary text-white p-2 rounded-3">
                    <i class="bi bi-pencil-square fs-4"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-body">Update User Admin (TU)</h5>
                    <small class="text-body-secondary">Kelola Status Keaktifan & Profile Kredensial Administrator</small>
                </div>
            </div>
            <a href="?view=users" class="btn btn-outline-secondary btn-sm rounded-3">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Kelola User
            </a>
        </div>

        <div class="card-body p-4">
            <!-- Alert Informasi Aturan Edit -->
            <div class="alert alert-info border-0 bg-info-subtle text-info-emphasis rounded-3 p-3 mb-4" role="alert">
                <div class="d-flex gap-2 align-items-center">
                    <i class="bi bi-shield-lock-fill fs-5 text-info"></i>
                    <div>
                        <strong>Aturan Edit User:</strong> Hanya pengguna ber-role <strong>Admin (TU)</strong> yang diizinkan diubah dari menu ini. Akun Mahasiswa dikelola di <em>Data Mahasiswa</em>, Dosen di <em>Data Dosen</em>, dan Superadmin dilindungi sistem.
                    </div>
                </div>
            </div>

            <form id="updateUserForm" novalidate>
                <input type="hidden" id="user_id" name="id" value="<?= $userId ?>">

                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-key-fill me-2"></i>1. Kredensial & Status Akun (users_credential)</h6>
                <div class="row g-3 mb-4">
                    <!-- Username (Disabled) -->
                    <div class="col-md-4">
                        <label for="username" class="form-label fw-semibold">Username / Identitas Log</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-at"></i></span>
                            <input type="text" class="form-control bg-body-tertiary" id="username" name="username" readonly disabled>
                        </div>
                        <div class="form-text text-muted">Username bersifat unik & tidak dapat diubah.</div>
                    </div>

                    <!-- Role Pengguna (Fixed to Admin) -->
                    <div class="col-md-4">
                        <label for="role" class="form-label fw-semibold">Role / Tingkat Akses</label>
                        <input type="text" class="form-control bg-body-tertiary" id="role" name="role" value="Admin" readonly disabled>
                    </div>

                    <!-- Status Akun (Enabled / Disabled / Suspended) -->
                    <div class="col-md-4">
                        <label for="account_status" class="form-label fw-semibold">Status Akun <span class="text-danger">*</span></label>
                        <select class="form-select" id="account_status" name="account_status" required>
                            <option value="active">Active (Aktif / Enabled)</option>
                            <option value="suspended">Suspended (Ditangguhkan / Disabled)</option>
                            <option value="banned">Banned (Non-Aktif Permanen)</option>
                            <option value="pending_activation">Pending Activation</option>
                        </select>
                    </div>

                    <!-- Email Utama -->
                    <div class="col-md-6">
                        <label for="email" class="form-label fw-semibold">Email Utama <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>

                    <!-- Reset Password (Opsional) -->
                    <div class="col-md-6">
                        <label for="password" class="form-label fw-semibold">Reset Password <span class="text-muted font-weight-normal">(Opsional)</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Kosongkan jika tidak ingin mengubah password">
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-card-heading me-2"></i>2. Data Diri & Profil Pengguna (personal_profiles)</h6>
                <div class="row g-3">
                    <!-- Nama Lengkap -->
                    <div class="col-md-6">
                        <label for="full_name" class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>

                    <!-- NIK -->
                    <div class="col-md-6">
                        <label for="nik" class="form-label fw-semibold">NIK (No. KTP 16 Digit)</label>
                        <input type="text" class="form-control" id="nik" name="nik" placeholder="Opsional (16 Digit)" maxlength="16">
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
                        <input type="text" class="form-control" id="pob" name="pob">
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
                        <input type="text" class="form-control" id="phone" name="phone">
                    </div>

                    <!-- Alamat KTP -->
                    <div class="col-md-6">
                        <label for="ktp_address" class="form-label fw-semibold">Alamat Sesuai KTP</label>
                        <textarea class="form-control" id="ktp_address" name="ktp_address" rows="2"></textarea>
                    </div>

                    <!-- Alamat Domisili -->
                    <div class="col-md-6">
                        <label for="domicile_address" class="form-label fw-semibold">Alamat Domisili saat ini</label>
                        <textarea class="form-control" id="domicile_address" name="domicile_address" rows="2"></textarea>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="?view=users" class="btn btn-outline-secondary px-4 rounded-3">Batal</a>
                    <button type="submit" class="btn btn-primary px-4 rounded-3" id="submitButton">
                        <i class="bi bi-save me-1"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            const userId = $('#user_id').val();

            function loadUserDetail() {
                $.ajax({
                    type: 'POST',
                    url: 'src/api.php?req=getUserDetail',
                    data: { id: userId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            let data = response.data;
                            $('#username').val(data.username);
                            $('#email').val(data.email);
                            $('#account_status').val(data.account_status || 'active');
                            $('#full_name').val(data.full_name || '');
                            $('#nik').val(data.nik || '');
                            $('#gender').val(data.gender || '');
                            $('#pob').val(data.pob || '');
                            $('#dob').val(data.dob || '');
                            $('#religion').val(data.religion || '');
                            $('#phone').val(data.phone || '');
                            $('#ktp_address').val(data.ktp_address || '');
                            $('#domicile_address').val(data.domicile_address || '');
                        } else {
                            alert(response.message || 'Gagal memuat detail user.');
                            window.location.href = 'portal.php?view=users';
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Gagal mengambil data user dari server: ' + error);
                        window.location.href = 'portal.php?view=users';
                    }
                });
            }

            loadUserDetail();

            $('#updateUserForm').on('submit', function(e) {
                e.preventDefault();
                let formData = $(this).serialize();
                $('#submitButton').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...');

                $.ajax({
                    type: 'POST',
                    url: 'src/api.php?req=updateUser',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            alert(response.message);
                            window.location.href = 'portal.php?view=users';
                        } else {
                            alert(response.message || 'Gagal menyimpan perubahan.');
                            $('#submitButton').prop('disabled', false).html('<i class="bi bi-save me-1"></i>Simpan Perubahan');
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Terjadi kesalahan server: ' + error);
                        $('#submitButton').prop('disabled', false).html('<i class="bi bi-save me-1"></i>Simpan Perubahan');
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
