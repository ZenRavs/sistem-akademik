<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user']) || isset($_SESSION['applicant'])) {
    header("Location: portal.php");
    exit;
}

$loginError = $_SESSION['error'] ?? null;
$loginSuccess = $_SESSION['success'] ?? null;
$activeTab = $_GET['tab'] ?? $_SESSION['active_tab'] ?? 'staff';
if (!in_array($activeTab, ['staff', 'student', 'applicant'])) {
    $activeTab = 'staff';
}

if ($loginError) unset($_SESSION['error']);
if ($loginSuccess) unset($_SESSION['success']);
if (isset($_SESSION['active_tab'])) unset($_SESSION['active_tab']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="https://cdn-icons-png.freepik.com/512/7935/7935909.png?ga=GA1.1.599436757.1735230785" type="image/x-icon">
    <title>Portal Sistem AKADEMIK FIK</title>
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #0f4c92;
            min-height: 100vh;
        }
        .portal-header {
            background-color: #ffffff;
            color: #212529;
            padding: 15px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        .nav-tabs .nav-link {
            font-weight: 600;
            font-size: 0.92rem;
            color: #64748b;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 12px 18px;
            transition: all 0.2s ease;
        }
        .nav-tabs .nav-link:hover {
            color: #0f4c92;
            border-color: transparent;
        }
        .nav-tabs .nav-link.active {
            color: #0f4c92;
            background: transparent;
            border-bottom: 3px solid #0f4c92;
        }
        .btn-accent {
            background-color: #0f4c92;
            border-color: #0f4c92;
            color: #ffffff;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .btn-accent:hover, .btn-accent:focus {
            background-color: #0b3a70;
            border-color: #0b3a70;
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(15, 76, 146, 0.25);
        }
        .text-accent {
            color: #0f4c92 !important;
        }
        .auth-card {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        .form-floating > .form-control:focus ~ label {
            color: #0f4c92;
        }
        .form-floating > .form-control:focus {
            border-color: #0f4c92;
            box-shadow: 0 0 0 0.25rem rgba(15, 76, 146, 0.15);
        }
        .form-floating.password-wrapper {
            position: relative;
        }
        .form-floating.password-wrapper .form-control {
            padding-right: 3rem;
        }
        .btn-toggle-password {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            background: none;
            border: none;
            color: #64748b;
            padding: 0.35rem 0.5rem;
            cursor: pointer;
            line-height: 1;
            font-size: 1.15rem;
            transition: color 0.15s ease-in-out;
        }
        .btn-toggle-password:hover {
            color: #0f4c92;
        }
        .btn-toggle-password:focus {
            outline: none;
            box-shadow: none;
        }
    </style>
</head>

<body>
    <header class="portal-header mb-4">
        <div class="container d-flex align-items-center justify-content-between">
            <a class="navbar-brand d-flex align-items-center text-decoration-none" href="index.php">
                <img src="https://portal.dinus.ac.id/assets/images/logo_dinus_new.png" alt="Logo UDINUS" width="70">
                <div class="ms-3">
                    <h4 class="m-0 fw-bold text-dark">Sistem AKADEMIK FIK</h4>
                    <small class="text-secondary" style="font-size: 0.8rem;">Faculty of Computer Science Portal</small>
                </div>
            </a>
        </div>
    </header>

    <div class="container mb-5 pb-5" style="max-width: 540px;">
        <?php if ($loginError): ?>
            <div class="alert alert-danger alert-dismissible fade show text-center rounded-3 shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <span><?= htmlspecialchars($loginError) ?></span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($loginSuccess): ?>
            <div class="alert alert-success alert-dismissible fade show text-center rounded-3 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <span><?= htmlspecialchars($loginSuccess) ?></span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card auth-card border-0 bg-white">
            <div class="card-header bg-white pt-3 pb-0 px-3">
                <!-- 3 Portal Menu Tabs -->
                <ul class="nav nav-tabs justify-content-center" id="portalTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= ($activeTab === 'staff') ? 'active' : '' ?>" id="staff-tab" data-bs-toggle="tab" data-bs-target="#staff-pane" type="button" role="tab">
                            <i class="bi bi-person-badge me-1"></i> Pegawai
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= ($activeTab === 'student') ? 'active' : '' ?>" id="student-tab" data-bs-toggle="tab" data-bs-target="#student-pane" type="button" role="tab">
                            <i class="bi bi-mortarboard me-1"></i> Mahasiswa
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= ($activeTab === 'applicant') ? 'active' : '' ?>" id="applicant-tab" data-bs-toggle="tab" data-bs-target="#applicant-pane" type="button" role="tab">
                            <i class="bi bi-person-plus me-1"></i> Pendaftaran
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4 pt-4">
                <div class="tab-content" id="portalTabsContent">
                    <!-- Tab 1: Login Pegawai & Dosen -->
                    <div class="tab-pane fade <?= ($activeTab === 'staff') ? 'show active' : '' ?>" id="staff-pane" role="tabpanel">
                        <div class="text-center mb-4">
                            <h5 class="fw-bold text-accent mb-1">Portal Login Pegawai & Dosen</h5>
                            <small class="text-secondary">Masuk menggunakan akun pegawai atau dosen terdaftar.</small>
                        </div>
                        <form action="src/api.php?req=userLogin" method="POST">
                            <input type="hidden" name="login_type" value="staff">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="staff_username" name="username" placeholder="Username / NPP" required>
                                <label for="staff_username"><i class="bi bi-person me-1"></i>Username / NPP</label>
                            </div>
                            <div class="form-floating mb-3 password-wrapper">
                                <input type="password" class="form-control" id="staff_password" name="password" placeholder="Password" required>
                                <label for="staff_password"><i class="bi bi-key me-1"></i>Password</label>
                                <button type="button" class="btn-toggle-password" data-target="staff_password" title="Tampilkan / Sembunyikan Password" tabindex="-1">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="d-grid gap-2 mt-4">
                                <button class="btn btn-accent py-2" type="submit">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> Login Pegawai
                                </button>
                            </div>
                        </form>
                        <!-- <div class="d-flex justify-content-center mt-3 pt-2">
                            <button class="btn btn-sm btn-outline-secondary border-0 text-muted" type="button" data-bs-toggle="collapse" data-bs-target="#quickAccessCollapse" style="font-size: 0.78rem;">
                                <i class="bi bi-shield-lock me-1"></i> Quick Access (Superadmin Testing)
                            </button>
                        </div> -->
                        <div class="collapse mt-2" id="quickAccessCollapse">
                            <div class="card card-body bg-light border-0 rounded-3 py-2 px-3" style="font-size: 0.8rem;">
                                <code>
                                    <div><strong>Username:</strong> 0000.0001</div>
                                    <div><strong>Password:</strong> localadmin001</div>
                                </code>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Login Mahasiswa -->
                    <div class="tab-pane fade <?= ($activeTab === 'student') ? 'show active' : '' ?>" id="student-pane" role="tabpanel">
                        <div class="text-center mb-4">
                            <h5 class="fw-bold text-accent mb-1">Portal Login Mahasiswa</h5>
                            <small class="text-secondary">Gunakan Nomor Induk Mahasiswa (NIM) untuk mengakses SIAKAD.</small>
                        </div>
                        <form action="src/api.php?req=userLogin" method="POST">
                            <input type="hidden" name="login_type" value="student">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control font-monospace" id="student_nim" name="username" placeholder="NIM Mahasiswa" required>
                                <label for="student_nim"><i class="bi bi-card-heading me-1"></i>NIM Mahasiswa (contoh: A11.2026.00001)</label>
                            </div>
                            <div class="form-floating mb-3 password-wrapper">
                                <input type="password" class="form-control" id="student_password" name="password" placeholder="Password" required>
                                <label for="student_password"><i class="bi bi-key me-1"></i>Password Akun</label>
                                <button type="button" class="btn-toggle-password" data-target="student_password" title="Tampilkan / Sembunyikan Password" tabindex="-1">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="d-grid gap-2 mt-4">
                                <button class="btn btn-accent py-2" type="submit">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> Login Mahasiswa
                                </button>
                            </div>
                        </form>
                        <div class="text-center mt-3 pt-2">
                            <small class="text-muted" style="font-size: 0.78rem;">
                                <i class="bi bi-info-circle me-1"></i>Lupa password? Hubungi Biro Akademik / TU Fakultas.
                            </small>
                        </div>
                    </div>

                    <!-- Tab 3: Pendaftaran (Login Pendaftar & Buat Akun Terpadu) -->
                    <div class="tab-pane fade" id="applicant-pane" role="tabpanel">
                        <!-- Sub-Form 1: Login Pendaftar (Default) -->
                        <div id="applicantLoginBox">
                            <div class="text-center mb-4">
                                <h5 class="fw-bold text-accent mb-1">Portal Masuk Pendaftaran</h5>
                                <small class="text-secondary">Silakan masuk menggunakan akun pendaftaran Anda.</small>
                            </div>
                            <form action="src/api.php?req=applicantLogin" method="POST">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="app_username" name="username" placeholder="Username / Email" required>
                                    <label for="app_username"><i class="bi bi-envelope me-1"></i>Username / Email Pendaftar</label>
                                </div>
                                <div class="form-floating mb-3 password-wrapper">
                                    <input type="password" class="form-control" id="app_password" name="password" placeholder="Password" required>
                                    <label for="app_password"><i class="bi bi-key me-1"></i>Password</label>
                                    <button type="button" class="btn-toggle-password" data-target="app_password" title="Tampilkan / Sembunyikan Password" tabindex="-1">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="d-grid gap-2 mt-4">
                                    <button class="btn btn-accent py-2" type="submit">
                                        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Portal Pendaftar
                                    </button>
                                </div>
                            </form>
                            <div class="text-center mt-3 pt-3 border-top">
                                <span class="text-secondary" style="font-size: 0.88rem;">Belum punya akun?</span>
                                <a href="javascript:void(0)" id="toggleRegisterBtn" class="fw-semibold text-accent text-decoration-none ms-1">
                                    Buat akun
                                </a>
                            </div>
                        </div>

                        <!-- Sub-Form 2: Buat Akun Baru (Muncul saat 'Buat akun' diklik) -->
                        <div id="applicantRegisterBox" style="display: none;">
                            <div class="text-center mb-3">
                                <h5 class="fw-bold text-accent mb-1">Buat Akun Pendaftar Baru</h5>
                                <small class="text-secondary">Daftar akun untuk memulai pengisian formulir PMB Online.</small>
                            </div>
                            


                            <form action="src/api.php?req=applicantRegister" method="POST">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="reg_full_name" name="full_name" placeholder="Nama Lengkap" required>
                                    <label for="reg_full_name"><i class="bi bi-person me-1"></i>Nama Lengkap</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <input type="email" class="form-control" id="reg_email" name="email" placeholder="nama@email.com" required>
                                    <label for="reg_email"><i class="bi bi-envelope me-1"></i>Alamat Email</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="reg_username" name="username" placeholder="Username" required>
                                    <label for="reg_username"><i class="bi bi-person-badge me-1"></i>Username</label>
                                </div>
                                <div class="form-floating mb-3 password-wrapper">
                                    <input type="password" class="form-control" id="reg_password" name="password" placeholder="Password" minlength="4" required>
                                    <label for="reg_password"><i class="bi bi-lock me-1"></i>Password Akun</label>
                                    <button type="button" class="btn-toggle-password" data-target="reg_password" title="Tampilkan / Sembunyikan Password" tabindex="-1">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="d-grid gap-2 mt-4">
                                    <button class="btn btn-accent py-2" type="submit">
                                        <i class="bi bi-check2-circle me-1"></i> Buat Akun Sekarang
                                    </button>
                                </div>
                            </form>
                            <div class="text-center mt-3 pt-3 border-top">
                                <span class="text-secondary" style="font-size: 0.88rem;">Sudah punya akun?</span>
                                <a href="javascript:void(0)" id="toggleLoginBtn" class="fw-semibold text-accent text-decoration-none ms-1">
                                    Masuk di sini
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Putih Kanan Bawah -->
    <footer class="fixed-bottom bg-white py-2 px-4 border-top shadow-sm">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <small class="text-secondary" style="font-size: 11px;">Universitas Dian Nuswantoro &bull; FIK</small>
            <small class="text-muted fw-semibold" style="font-size: 11px;">sia_v0.1.2026</small>
        </div>
    </footer>

    <script>
        $(document).ready(function() {
            // Toggle antara Form Login Pendaftar dan Form Buat Akun
            $('#toggleRegisterBtn').on('click', function(e) {
                e.preventDefault();
                $('#applicantLoginBox').slideUp(180, function() {
                    $('#applicantRegisterBox').slideDown(180);
                });
            });

            $('#toggleLoginBtn').on('click', function(e) {
                e.preventDefault();
                $('#applicantRegisterBox').slideUp(180, function() {
                    $('#applicantLoginBox').slideDown(180);
                });
            });

            // Reset kembali ke Login Box jika berganti tab
            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                if (e.target.id === 'applicant-tab') {
                    $('#applicantRegisterBox').hide();
                    $('#applicantLoginBox').show();
                }
            });

            // Handler Force Logout
            $(document).on('click', '#logout', function(e) {
                e.preventDefault();
                let username = $(this).attr('data-id') || '';
                $.ajax({
                    url: 'src/api.php?req=forceLogout',
                    type: 'POST',
                    data: { username: username },
                    success: function(response) {
                        let respons = (typeof response === 'object') ? response : JSON.parse(response);
                        if (respons.message == 'success') {
                            alert('Status login berhasil di-reset! Silakan login kembali.');
                            window.location.href = 'index.php';
                        } else {
                            alert(respons.message);
                        }
                    },
                    error: function() {
                        alert("Server error. [req: forceLogout]");
                    }
                });
            });

            // Toggle Show/Hide Password
            $(document).on('click', '.btn-toggle-password', function(e) {
                e.preventDefault();
                let targetId = $(this).data('target');
                let input = $('#' + targetId);
                let icon = $(this).find('i');
                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    icon.removeClass('bi-eye').addClass('bi-eye-slash');
                } else {
                    input.attr('type', 'password');
                    icon.removeClass('bi-eye-slash').addClass('bi-eye');
                }
            });
        });
    </script>
</body>

</html>