<?php
if (isset($_SESSION['user'])) {
    include_once defined('CONFIG_PATH') ? CONFIG_PATH . '/db.php' : __DIR__ . '/../../../config/db.php';
    $request = $_GET['req'] ?? '';
    switch ($request) {
        case 'new_user':
?>
            <div class="container-fluid mt-3">
                <form id="insertForm" enctype="multipart/form-data" onkeypress="return event.keyCode != 13;">
                    <input type="hidden" id="page" name="page" value="<?= $_GET['page'] ?? 1 ?>">
                    <div class="mb-3 col row">
                        <label for="name" class="col-sm-1 col-form-label">Name</label>
                        <div class="col-sm-5">
                            <input type="text" class="form-control shadow-sm" id="name" name="name" placeholder="Name" required>
                        </div>
                    </div>
                    <hr>
                    <div class="mb-3 col row">
                        <label for="username" class="col-sm-1 col-form-label">Username</label>
                        <div class="col-sm-5">
                            <input type="text" class="form-control shadow-sm" id="username" name="username" placeholder="Username" required>
                            <span id="username-error" class="text-danger"></span>
                        </div>
                    </div>
                    <hr>
                    <div class="mb-3 col row">
                        <label for="password" class="col-sm-1 col-form-label">Password</label>
                        <div class="col-3">
                            <input type="password" class="form-control shadow-sm" id="password" name="password" placeholder="Password" required>
                        </div>
                        <div class="col-3">
                            <input type="password" class="form-control shadow-sm" id="password-confirm" name="password-confirm" placeholder="Confirm password" required>
                        </div>
                    </div>
                    <hr>
                    <div class="mb-3 row">
                        <label for="role" class="col-sm-1 col-form-label">User Role</label>
                        <div class="col-md-2">
                            <select class="form-select" id="role" name="role" required>
                                <option value="">User role/level</option>
                                <option value="User">User</option>
                                <option value="Admin">Admin</option>
                                <option value="Superadmin">Superadmin</option>
                            </select>
                        </div>
                    </div>
                    <hr>
                    <div class="mb-3 row">
                        <label for="status" class="col-sm-1 col-form-label">Status</label>
                        <div class="col-md-2">
                            <select class="form-select" id="status" name="status" required>
                                <option value="">User status</option>
                                <option value="1">Active</option>
                                <option value="0">Disabled</option>
                            </select>
                        </div>
                    </div>
                    <hr>
                    <div class="col-sm-6 justify-content-start mb-3">
                        <button class="btn btn-sm btn-outline-danger" id="cancelButton">Cancel</button>
                        <input class="btn btn-primary" id="submitButton" type="submit" value="Submit"></input>
                    </div>
                </form>
            </div>
        <?php
            break;
        case 'update':
            if (($_GET['role'] ?? '') === 'Mahasiswa' || ($_GET['role'] ?? '') === 'Student') {
                echo '<script>alert("Akun Mahasiswa tidak dapat diubah dari Data Users. Harap kelola dari menu Data Mahasiswa!");window.location.href = "portal.php?view=data_users";</script>';
                break;
            }
        ?>
            <div class="container-fluid mt-3">
                <form enctype="multipart/form-data" id="updateForm" onkeypress="return event.keyCode != 13;">
                    <input type="hidden" id="page" name="page" value="<?= $_GET['page'] ?? 1 ?>">
                    <input type="hidden" id="id" name="id" value="<?= $_GET['id'] ?? 'redirect' ?>">
                    <div class="mb-3 row">
                        <label for="username" class="col-sm-2 col-form-label">Username</label>
                        <div class="col-sm-3">
                            <input type="text" class="form-control shadow-sm" id="username" name="username" value="<?= htmlspecialchars($_GET['username'] ?? '') ?>" disabled>
                            <span id="usernameErr" class="text-warning"></span>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="name" class="col-sm-2 col-form-label">Name</label>
                        <div class="col-sm-6">
                            <input type="text" class="form-control shadow-sm" id="name" name="name" value="<?= htmlspecialchars($_GET['name'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="role" class="col-sm-2 col-form-label">User Role</label>
                        <div class="col-md-2">
                            <select class="form-select" id="role" name="role" required>
                                <option value="User" <?= ($_GET['role'] ?? '') == 'User' ? 'selected' : '' ?>>User</option>
                                <option value="Admin" <?= ($_GET['role'] ?? '') == 'Admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="Superadmin" <?= ($_GET['role'] ?? '') == 'Superadmin' ? 'selected' : '' ?>>Superadmin</option>
                            </select>
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

        function usernameCheck(username) {
            $.ajax({
                type: 'POST',
                url: 'src/api.php?req=usernameCheck',
                dataType: 'json',
                data: {
                    username: username,
                    table: 'users',
                    column: 'username'
                },
                success: function(response) {
                    if (response === true) {
                        $('#username-error').text('Username already exists!');
                        $('#submitButton').prop('disabled', true);
                        $('#username').focus();
                    } else {
                        $('#username-error').text('');
                        $('#submitButton').prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    alert('An error occurred: ' + error);
                }
            });
        }

        $('#username').on('keyup', function(e) {
            let username = $(this).val();
            if (username.trim() !== '' && username.length > 3) {
                usernameCheck(username);
            } else {
                $('#username-error').text('4 Letter minimum allowed!');
                $('#submitButton').prop('disabled', true);
            }
        });

        $('#insertForm').on('submit', function(e) {
            e.preventDefault();
            let pass = $('#password').val();
            let confirmPass = $('#password-confirm').val();
            if (pass !== confirmPass) {
                alert('Passwords do not match!');
                return;
            }
            let formData = new FormData($(this)[0]);
            $.ajax({
                type: 'POST',
                url: 'src/api.php?req=insertNewUser',
                data: formData,
                dataType: 'json',
                processData: false,
                contentType: false,
                cache: false,
                success: function(response) {
                    if (response.status == 'success') {
                        alert('Data inserted successfully!');
                        window.location.href = 'portal.php?view=data_users';
                    } else {
                        alert(response.message);
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
