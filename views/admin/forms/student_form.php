<?php
if (isset($_SESSION['user'])) {
    include_once defined('CONFIG_PATH') ? CONFIG_PATH . '/db.php' : __DIR__ . '/../../../config/db.php';
    $request = $_GET['req'] ?? '';
    switch ($request) {
        case 'insert':
?>
            <div class="container-fluid mt-3">
                <form method="POST" id="insertForm" enctype="multipart/form-data" onkeypress="return event.keyCode != 13;">
                    <input type="hidden" id="page" name="page" value="<?= $_GET['page'] ?? 1 ?>">
                    <div class="mb-3 row">
                        <label class="col-sm-1 col-form-label">NIM</label>
                        <div class="col-sm-10">
                            <div class="row g-3" id="nim">
                                <div class="col-md-2">
                                    <select class="form-select" id="nim1" name="nim1" required>
                                        <option value="A11">A11</option>
                                        <option value="A12">A12</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select" id="nim2" name="nim2" required>
                                        <?php for ($year = 2020; $year <= 2026; $year++) : ?>
                                            <option value="<?= $year ?>"><?= $year ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="text" class="form-control" id="nim3" name="nim3" placeholder="00000" required>
                                </div>
                            </div>
                            <span id="nimErr" class="text-warning"></span>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="name" class="col-sm-1 col-form-label">Name</label>
                        <div class="col-sm-6">
                            <input type="text" class="form-control shadow-sm" id="name" name="name" placeholder="Fullname" required>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="email" class="col-sm-1 col-form-label">Email</label>
                        <div class="col-sm-6">
                            <input type="email" class="form-control shadow-sm" id="email" name="email" placeholder="Enter your email" required>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="pict" class="col-sm-1 col-form-label">Photo</label>
                        <div class="col-sm-6">
                            <input class="form-control shadow-sm" type="file" name="pict" id="pict" required>
                            <span id="fileErr" class="text-danger"></span>
                        </div>
                    </div>
                    <div class="col-sm-6 justify-content-start mb-3">
                        <button class="btn btn-sm btn-outline-danger" id="cancelButton">Cancel</button>
                        <input class="btn btn-primary" id="submitButton" type="submit" value="Submit"></input>
                    </div>
                </form>
            </div>
        <?php
            break;
        case 'update':
            $studentId = $_GET['id'] ?? 0;
            $getStudentStmt = $conn->prepare("
                SELECT s.*, a.phone, a.address, a.school_origin, a.school_address, a.final_score, a.program_code 
                FROM students s 
                LEFT JOIN applicants a ON (a.email = s.email OR a.username = s.nim) 
                WHERE s.id = ?
            ");
            $getStudentStmt->execute([$studentId]);
            $studentData = $getStudentStmt->fetch();

            if (!$studentData) {
                echo '<script>alert("Data mahasiswa tidak ditemukan!");window.location.href="portal.php?view=students";</script>';
                break;
            }
            $pictName = $studentData['pict'] ?? '';
            $defaultPict = 'https://cdn-icons-png.freepik.com/512/3875/3875148.png?ga=GA1.1.599436757.1735230785';
            $imgSrc = $defaultPict;
            if ($pictName) {
                $uploadDir = defined('UPLOAD_PATH') ? UPLOAD_PATH : __DIR__ . '/../../../public/uploads/user_photos';
                if (file_exists($uploadDir . '/' . $pictName)) {
                    $imgSrc = defined('UPLOAD_URL') ? UPLOAD_URL . $pictName : './public/uploads/user_photos/' . $pictName;
                }
            }
        ?>
            <div class="container-fluid mt-3" style="max-width: 900px;">
                <div class="card shadow border-0 mb-4">
                    <div class="card-header bg-white pt-3">
                        <h5 class="card-title text-primary m-0">✏️ Edit Data Mahasiswa (<?= htmlspecialchars($studentData['nim']) ?>)</h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" enctype="multipart/form-data" id="updateForm" onkeypress="return event.keyCode != 13;">
                            <input type="hidden" id="page" name="page" value="<?= $_GET['page'] ?? 1 ?>">
                            <input type="hidden" id="id" name="id" value="<?= htmlspecialchars($studentData['id']) ?>">
                            <input type="hidden" id="nim" name="nim" value="<?= htmlspecialchars($studentData['nim']) ?>">

                            <!-- Photo Section -->
                            <div class="text-center mb-4">
                                <img src="<?= $imgSrc ?>" class="rounded-circle border border-2 shadow-sm object-fit-cover mb-2" width="120" height="120" alt="Foto Mahasiswa">
                                <div>
                                    <small class="text-muted d-block">Foto Profil Mahasiswa</small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nim_dis" class="form-label font-weight-bold">NIM (Nomor Induk Mahasiswa)</label>
                                    <input type="text" class="form-control bg-light" id="nim_dis" value="<?= htmlspecialchars($studentData['nim']) ?>" disabled>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label font-weight-bold">Nama Lengkap</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($studentData['name']) ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label font-weight-bold">Email Utama</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($studentData['email']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label font-weight-bold">No. HP / WhatsApp</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($studentData['phone'] ?? '') ?>" placeholder="08123456789">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label font-weight-bold">Alamat Rumah Lengkap</label>
                                <textarea class="form-control" id="address" name="address" rows="2" placeholder="Alamat rumah mahasiswa"><?= htmlspecialchars($studentData['address'] ?? '') ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="school_origin" class="form-label font-weight-bold">Asal Sekolah (SMA/SMK)</label>
                                    <input type="text" class="form-control" id="school_origin" name="school_origin" value="<?= htmlspecialchars($studentData['school_origin'] ?? '') ?>" placeholder="Nama sekolah asal">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="final_score" class="form-label font-weight-bold">Nilai Akhir Rata-rata</label>
                                    <input type="number" step="0.01" min="0" max="100" class="form-control" id="final_score" name="final_score" value="<?= htmlspecialchars($studentData['final_score'] ?? '') ?>" placeholder="Nilai akhir pendaftaran">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="school_address" class="form-label font-weight-bold">Alamat Sekolah Asal</label>
                                <textarea class="form-control" id="school_address" name="school_address" rows="2" placeholder="Lokasi/alamat sekolah asal"><?= htmlspecialchars($studentData['school_address'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="pict" class="form-label font-weight-bold">Ganti Foto Profil (Opsional)</label>
                                <input class="form-control" type="file" name="pict" id="pict" accept="image/*">
                                <span id="fileErr" class="text-danger"></span>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <button class="btn btn-outline-secondary px-4" id="cancelButton">Batal</button>
                                <input class="btn btn-primary px-4 font-weight-bold" id="submitButton" type="submit" value="Simpan Perubahan"></input>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
<?php
            break;
        default:
            echo '<script>alert("Invalid!");window.location.href = "portal.php";</script>';
            break;
    }
} else {
    echo '<script>alert("Access Denied!");window.location.href = "index.php";</script>';
}
?>
<script>
    $(document).ready(function() {
        const id = $('#id').val();
        if (id == 'redirect') {
            alert('Invalid request!');
            window.location.href = 'portal.php';
        }

        function nimCheck(nim) {
            $.ajax({
                type: 'POST',
                url: 'src/api.php?req=nimCheck',
                data: {
                    nim: nim
                },
                success: function(response) {
                    let respons = JSON.parse(response);
                    if (respons.message == 'true') {
                        $('#nimErr').text('NIM already exists!');
                        $('#submitButton').prop('disabled', true);
                        $('#nim3').focus();
                    } else {
                        $('#nimErr').text('');
                        $('#submitButton').prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    alert('An error occurred: ' + error);
                }
            });
        }

        $('#nim3').on('keyup', function(e) {
            let nim3Length = $(this).val();
            let nim1 = $('#nim1').val();
            let nim2 = $('#nim2').val();
            let nim3 = $('#nim3').val();
            let nim = nim1 + '.' + nim2 + '.' + nim3;
            if (nim3Length.trim() !== '' && nim3Length.length == 5) {
                nimCheck(nim);
            } else {
                $('#nimErr').text('Must be 5 digits!');
                $('#submitButton').prop('disabled', true);
            }
        });

        $('#insertForm').on('keyup', '#name', function() {
            let email = $(this).val().toLowerCase().replace(/\s+/g, '.') + '@dsn.dinus.ac.id';
            $('#email').val(email);
        });

        $('#nim1, #nim2').on('change', function(e) {
            let nim1 = $('#nim1').val();
            let nim2 = $('#nim2').val();
            let nim3 = $('#nim3').val();
            let nim = nim1 + '.' + nim2 + '.' + nim3;
            nimCheck(nim);
        });

        $('#insertForm').on('submit', function(e) {
            e.preventDefault();
            let formData = new FormData($(this)[0]);
            $.ajax({
                type: 'POST',
                url: 'src/api.php?req=insertStudent',
                data: formData,
                processData: false,
                contentType: false,
                cache: false,
                success: function(response) {
                    let respons = JSON.parse(response);
                    if (respons.message == 'success') {
                        alert('Data inserted successfully!');
                        window.location.href = 'portal.php?view=students';
                    } else {
                        alert('Error inserting data!');
                    }
                },
                error: function(xhr, status, error) {
                    alert('An error occurred: ' + error);
                }
            });
        });

        $('#updateForm').on('submit', function(e) {
            e.preventDefault();
            let formData = new FormData($(this)[0]);
            let nim = $('#nim').val();
            $.ajax({
                type: 'POST',
                url: 'src/api.php?req=updateStudent&nim=' + nim,
                data: formData,
                processData: false,
                contentType: false,
                cache: false,
                success: function(response) {
                    let respons = JSON.parse(response);
                    if (respons.status == 'success') {
                        alert('Data updated successfully!');
                        window.location.href = 'portal.php?view=students&page=' + respons.page;
                    } else {
                        alert('Error updating data!');
                    }
                },
                error: function(xhr, status, error) {
                    alert('An error occurred: ' + error);
                }
            });
        });

        $('#cancelButton').on('click', function(e) {
            e.preventDefault();
            history.back();
        });
    });
</script>
