<?php
if (isset($_SESSION['user'])) {
    $_SESSION['table']['page'] = 1;
    $page = $_GET['page'] ?? 0;
?>
    <style>
        @keyframes spinAnim {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .spin-anim {
            animation: spinAnim 0.6s linear infinite;
            display: inline-block;
        }
    </style>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-body border-bottom p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center">
                    <form class="d-flex gap-2 align-items-center" id="searchBox">
                        <select class="form-select form-select-sm" id="searchCategory" style="width: 120px;">
                            <option value="name">Name</option>
                            <option value="username">Username</option>
                        </select>
                        <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Search..." aria-label="Search" style="width: 200px;" required>
                        <button class="btn btn-primary btn-sm px-3" type="submit" id="searchButton"><i class="bi bi-search me-1"></i>Search</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="resetButton">Reset</button>
                    </form>
                    <div class="d-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-outline-primary btn-sm px-3 fw-semibold" id="refreshBtn" title="Refresh Data Tabel">
                            <i class="bi bi-arrow-clockwise me-1" id="refreshIcon"></i>Refresh
                        </button>
                        <a href="?view=new_user&req=new_user" class="btn btn-primary btn-sm px-3 fw-semibold">
                            <i class="bi bi-plus-lg me-1"></i>Tambah User Baru
                        </a>
                    </div>
                </div>
                
                <div class="d-flex gap-3 align-items-center">
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
                            <th>Identitas</th>
                            <th>Username</th>
                            <th class="text-center" style="width: 140px;">Hak Akses</th>
                            <th class="text-center" style="width: 110px;">Status Login</th>
                            <th class="text-center" style="width: 110px;">Status Akun</th>
                            <th class="text-center pe-4" style="width: 180px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Lihat Detail Kredensial & Terminate Session User -->
    <div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-body border-bottom px-4 py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary text-white p-2 rounded-3">
                            <i class="bi bi-shield-lock-fill fs-4"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-body" id="viewUserModalLabel">Detail Kredensial & Kontrol Akses User</h5>
                            <small class="text-body-secondary" id="view_user_subtitle">Informasi Hak Akses, Status Akun, & Terminate Sesi Aktif</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="v_target_user_id" value="0">

                    <!-- Section 1: Otentikasi & Kredensial -->
                    <h6 class="fw-bold text-primary mb-3"><i class="bi bi-key-fill me-2"></i>1. Kredensial & Hak Akses (users_credential)</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-body-secondary small fw-semibold mb-1">Username</label>
                            <input type="text" class="form-control form-control-sm bg-body-tertiary" id="v_username" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-body-secondary small fw-semibold mb-1">Email Utama</label>
                            <input type="text" class="form-control form-control-sm bg-body-tertiary" id="v_email" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-body-secondary small fw-semibold mb-1">Hak Akses</label>
                            <div><span id="v_role_badge" class="badge"></span></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-body-secondary small fw-semibold mb-1">Status Akun</label>
                            <div><span id="v_status_badge" class="badge"></span></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-body-secondary small fw-semibold mb-1">Status Login</label>
                            <div class="d-flex align-items-center gap-2">
                                <span id="v_login_badge" class="badge"></span>
                                <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 d-none" id="v_terminate_session_btn" style="font-size: 0.75rem;" title="Putus Sesi Aktif Pengguna Secara Paksa">
                                    <i class="bi bi-power me-1"></i> Logout
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Panel Interaktif Kontrol Status Akun -->
                    <div class="p-3 bg-body-tertiary rounded-3 border mb-3">
                        <h6 class="fw-bold text-body mb-2" style="font-size: 0.88rem;"><i class="bi bi-sliders me-1 text-primary"></i> Kontrol Status Akun</h6>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-8">
                                <label for="v_set_account_status" class="form-label small text-body-secondary mb-1">Set Status Akun</label>
                                <select class="form-select form-select-sm" id="v_set_account_status">
                                    <option value="active">Active (Aktif / Enabled)</option>
                                    <option value="suspended">Suspended (Ditangguhkan / Disabled)</option>
                                    <option value="banned">Banned (Non-Aktif Permanen)</option>
                                    <option value="pending_activation">Pending Activation</option>
                                    <option value="archived">Archived (Diarsipkan)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-outline-primary btn-sm w-100" id="saveStatusOnlyBtn">
                                    <i class="bi bi-check-circle me-1"></i> Update Status
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Placeholder Fitur Reset / Lupa Password -->
                    <div class="p-3 bg-body-tertiary rounded-3 border mb-4">
                        <h6 class="fw-bold text-body mb-2" style="font-size: 0.88rem;">
                            <i class="bi bi-shield-lock me-1 text-warning"></i> Reset / Ubah Kata Sandi <span class="badge text-bg-secondary font-weight-normal" style="font-size: 10px;">Fitur Lupa Password</span>
                        </h6>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label for="v_new_password" class="form-label small text-body-secondary mb-1">Kata Sandi Baru</label>
                                <input type="password" class="form-control form-control-sm" id="v_new_password" placeholder="Ketik kata sandi baru (opsional)" minlength="6">
                            </div>
                            <div class="col-md-6">
                                <label for="v_confirm_password" class="form-label small text-body-secondary mb-1">Konfirmasi Kata Sandi Baru</label>
                                <input type="password" class="form-control form-control-sm" id="v_confirm_password" placeholder="Ulangi kata sandi baru">
                            </div>
                        </div>
                        <div class="form-text text-muted" style="font-size: 0.75rem;">
                            Kosongkan kedua kolom jika tidak ingin mengubah kata sandi akun pengguna ini.
                        </div>
                    </div>

                    <!-- Tombol Directing ke Profil Personal -->
                    <div class="mb-4">
                        <a href="#" id="btnGoToPersonalProfile" class="btn btn-outline-primary btn-sm w-100 py-2 fw-semibold rounded-3">
                            <i class="bi bi-person-vcard me-1"></i> Buka Profil Personal Lengkap <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>

                    <hr class="my-3 opacity-25">

                    <!-- Section 2: Audit Trail & Keamanan -->
                    <h6 class="fw-bold text-primary mb-3"><i class="bi bi-clock-history me-2"></i>2. Log Audit & Keamanan Sesi</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label text-body-secondary small fw-semibold mb-1">Tanggal Akun Dibuat</label>
                            <input type="text" class="form-control form-control-sm bg-body-tertiary" id="v_created_at" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-body-secondary small fw-semibold mb-1">Login Terakhir</label>
                            <input type="text" class="form-control form-control-sm bg-body-tertiary" id="v_last_login_at" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-body-secondary small fw-semibold mb-1">Aktivitas Terakhir</label>
                            <input type="text" class="form-control form-control-sm bg-body-tertiary" id="v_last_active_at" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-body-secondary small fw-semibold mb-1">Session Token Active</label>
                            <input type="text" class="form-control form-control-sm bg-body-tertiary font-monospace" id="v_session_token" readonly style="font-size: 0.75rem;">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-body-secondary small fw-semibold mb-1">Percobaan Gagal</label>
                            <input type="text" class="form-control form-control-sm bg-body-tertiary" id="v_failed_attempts" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-body-secondary small fw-semibold mb-1">Kunci Akun s/d</label>
                            <input type="text" class="form-control form-control-sm bg-body-tertiary" id="v_locked_until" readonly>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-body border-top px-4 py-2 d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary btn-sm rounded-3 px-4" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary btn-sm rounded-3 px-4" id="saveStatusStateBtn">
                        <i class="bi bi-save me-1"></i> Simpan Perubahan Status & Password
                    </button>
                </div>
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
                    url: 'src/api.php?req=getDataUsers',
                    type: 'POST',
                    data: {
                        page: page,
                        maxRow: maxRow
                    },
                    dataType: 'json',
                    success: function(response) {
                        let data = (typeof response === 'object') ? response : JSON.parse(response);
                        if (data.status === 'error') {
                            alert(data.message);
                            return;
                        }
                        $('#tableBody').html(data.html);
                        pages = data.pages || 1;
                        loadOption(pages);
                    },
                    error: function(xhr, status, error) {
                        alert('Error fetching data: ' + error);
                    }
                });
            }

            function loadOption(pages) {
                $("#pageOption").empty();
                for (let i = 1; i <= pages; i++) {
                    $('#pageOption').append('<option value="' + i + '">' + i + '</option>');
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

            $('#refreshBtn').on('click', function() {
                let icon = $('#refreshIcon');
                icon.addClass('spin-anim');
                let searchInput = $('#searchInput').val().trim();
                if (searchInput !== '') {
                    $('#searchBox').trigger('submit');
                } else {
                    loadData(page, maxRow);
                }
                setTimeout(function() {
                    icon.removeClass('spin-anim');
                }, 600);
            });

            $('#searchBox').on('submit', function(e) {
                e.preventDefault();
                var searchCategory = $('#searchCategory').val();
                var searchInput = $('#searchInput').val();
                $("#tableBody").empty();
                $.ajax({
                    url: 'src/api.php?req=searchUser',
                    type: 'POST',
                    data: {
                        page: page,
                        maxRow: maxRow,
                        searchCategory: searchCategory,
                        searchInput: searchInput
                    },
                    dataType: 'json',
                    success: function(response) {
                        let respons = (typeof response === 'object') ? response : JSON.parse(response);
                        if (respons.status === 'error') {
                            alert(respons.message);
                        } else {
                            $('#tableBody').html(respons.html);
                            pages = respons.pages || 1;
                            loadOption(pages);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert("Server error. [req: searchUser]");
                    }
                });
            });

            // Modal Lihat Detail Kredensial & Set Status/Password User
            $('#tableBody').on('click', '#viewBtn', function() {
                let id = $(this).data('id');
                $('#v_target_user_id').val(id);

                // Reset password input fields
                $('#v_new_password').val('');
                $('#v_confirm_password').val('');

                $.ajax({
                    url: 'src/api.php?req=getUserCredentialDetail',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            let d = response.data;
                            $('#view_user_subtitle').text('Account: @' + d.username + ' (' + (d.full_name || d.username) + ')');
                            $('#v_username').val(d.username || '-');
                            $('#v_email').val(d.email || '-');

                            // Directing link ke Profil Personal berdasarkan Role
                            let roleLower = (d.role || '').toLowerCase();
                            let profileUrl = '?view=edit_user&req=update&id=' + d.id;
                            if (roleLower === 'student' || roleLower === 'mahasiswa') {
                                profileUrl = '?view=students';
                            } else if (roleLower === 'lecturer' || roleLower === 'dosen') {
                                profileUrl = '?view=lecturers';
                            } else if (roleLower === 'applicant' || roleLower === 'pendaftar') {
                                profileUrl = '?view=applicants';
                            }
                            $('#btnGoToPersonalProfile').attr('href', profileUrl);

                            // Status Akun Select
                            $('#v_set_account_status').val(d.account_status || 'active');

                            // Login state badge & Terminate Session Button
                            let isOnline = (d.session_token && d.last_active_at && ((new Date() - new Date(d.last_active_at)) / 1000 <= 1800));
                            $('#v_login_badge').attr('class', isOnline ? 'badge text-bg-success' : 'badge text-bg-secondary').text(isOnline ? 'ONLINE' : 'OFFLINE');

                            if (isOnline) {
                                $('#v_terminate_session_btn').removeClass('d-none');
                            } else {
                                $('#v_terminate_session_btn').addClass('d-none');
                            }

                            // Role badge
                            let roleColor = (roleLower === 'superadmin' ? 'danger' : (roleLower === 'admin' ? 'warning' : 'primary'));
                            $('#v_role_badge').attr('class', 'badge text-bg-' + roleColor).text(d.role ? d.role.toUpperCase() : '-');

                            // Account status badge
                            let statusLower = (d.account_status || 'active').toLowerCase();
                            let statusColor = (statusLower === 'active' ? 'success' : (statusLower === 'suspended' ? 'warning' : 'danger'));
                            $('#v_status_badge').attr('class', 'badge text-bg-' + statusColor).text(statusLower.toUpperCase());

                            // Audit fields
                            $('#v_created_at').val(d.created_at || '-');
                            $('#v_last_login_at').val(d.last_login_at || '-');
                            $('#v_last_active_at').val(d.last_active_at || '-');
                            $('#v_session_token').val(d.session_token || 'Tidak ada sesi aktif');
                            $('#v_failed_attempts').val(d.failed_attempts || 0);
                            $('#v_locked_until').val(d.locked_until || 'Tidak Terkunci');

                            $('#viewUserModal').modal('show');
                        } else {
                            alert(response.message || 'Gagal memuat detail user.');
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Gagal mengambil data kredensial dari server: ' + error);
                    }
                });
            });

            // Action Terminate Session (Force Logout)
            $('#v_terminate_session_btn').on('click', function() {
                let targetId = $('#v_target_user_id').val();
                if (!confirm("Apakah Anda yakin ingin memutus sesi aktif pengguna ini secara paksa (Force Logout)?")) {
                    return;
                }

                $(this).prop('disabled', true);

                $.ajax({
                    url: 'src/api.php?req=terminateUserSession',
                    type: 'POST',
                    data: { id: targetId },
                    dataType: 'json',
                    success: function(response) {
                        $('#v_terminate_session_btn').prop('disabled', false);
                        if (response.status === 'success') {
                            alert(response.message);
                            $('#v_login_badge').attr('class', 'badge text-bg-secondary').text('OFFLINE');
                            $('#v_session_token').val('Sesi telah diputus (Offline)');
                            $('#v_terminate_session_btn').addClass('d-none');
                            loadData(page, maxRow);
                        } else {
                            alert(response.message || 'Gagal memutus sesi.');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#v_terminate_session_btn').prop('disabled', false);
                        alert('Gagal memutus sesi pengguna: ' + error);
                    }
                });
            });

            // Action Simpan Status Akun & Password (Button di Footer & Card)
            $('#saveStatusStateBtn, #saveStatusOnlyBtn').on('click', function() {
                let targetId = $('#v_target_user_id').val();
                let accountStatus = $('#v_set_account_status').val();
                let newPass = $('#v_new_password').val().trim();
                let confirmPass = $('#v_confirm_password').val().trim();

                if (!targetId || targetId <= 0) {
                    alert('Target user tidak valid.');
                    return;
                }

                if (newPass !== '' || confirmPass !== '') {
                    if (newPass !== confirmPass) {
                        alert('Konfirmasi kata sandi baru tidak cocok!');
                        return;
                    }
                    if (newPass.length < 6) {
                        alert('Kata sandi baru minimal 6 karakter!');
                        return;
                    }
                }

                $('#saveStatusStateBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...');

                $.ajax({
                    url: 'src/api.php?req=updateUserStatusAndState',
                    type: 'POST',
                    data: {
                        id: targetId,
                        account_status: accountStatus,
                        new_password: newPass
                    },
                    dataType: 'json',
                    success: function(response) {
                        $('#saveStatusStateBtn').prop('disabled', false).html('<i class="bi bi-save me-1"></i> Simpan Perubahan Status & Password');
                        if (response.status === 'success') {
                            alert(response.message);
                            $('#viewUserModal').modal('hide');
                            loadData(page, maxRow);
                        } else {
                            alert(response.message || 'Gagal menyimpan status.');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#saveStatusStateBtn').prop('disabled', false).html('<i class="bi bi-save me-1"></i> Simpan Perubahan Status & Password');
                        alert('Gagal memperbarui status user: ' + error);
                    }
                });
            });

            $('#tableBody').on('click', '#editBtn', function() {
                var id = $(this).data('id');
                window.location.href = '?view=edit_user&req=update&id=' + id;
            });

            $('#tableBody').on('click', '#deleteBtn', function() {
                var id = $(this).data('id');
                if (confirm("Apakah Anda yakin ingin menghapus user ini?")) {
                    $.ajax({
                        url: 'src/api.php?req=deleteUser',
                        type: 'POST',
                        data: {
                            id: id,
                        },
                        dataType: 'json',
                        success: function(response) {
                            let respons = (typeof response === 'object') ? response : JSON.parse(response);
                            alert(respons.message);
                            if (respons.status === 'success') {
                                loadData(page, maxRow);
                            }
                        },
                        error: function() {
                            alert("Server error. [req: deleteUser]");
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
?>
