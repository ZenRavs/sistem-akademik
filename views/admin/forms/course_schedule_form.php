<?php
if (isset($_SESSION['user'])) {
    include_once defined('CONFIG_PATH') ? CONFIG_PATH . '/db.php' : __DIR__ . '/../../../config/db.php';
    $request = $_GET['req'] ?? '';
    switch ($request) {
        case 'insert':
?>
            <div class="container-fluid mt-3">
                <form id="insertForm" enctype="multipart/form-data" onkeypress="return event.keyCode != 13;">
                    <input type="hidden" id="page" name="page" value="<?= $_GET['page'] ?? 1 ?>">
                    <div class="mb-3 row">
                        <div class="col">
                            <div class="mb-3 row">
                                <label for="courses" class="col-sm-3 col-form-label">Course Name</label>
                                <div class="col">
                                    <select class="form-select" id="courses" name="courses" required>
                                        <option value="">select course...</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="sks" class="col-sm-3 col-form-label">SKS</label>
                                <div class="col-3">
                                    <input type="text" class="form-control" id="sks" name="sks" readonly>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="lecturers" class="col-sm-3 col-form-label">Lecturer</label>
                                <div class="col">
                                    <select class="form-select" id="lecturers" name="lecturers" required>
                                        <option value="">select lecturer...</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="classGroup" class="col-sm-3 col-form-label">Group</label>
                                <div class="col">
                                    <select class="form-select" id="classGroup" name="classGroup" required></select>
                                </div>
                            </div>
                        </div>
                        <div class="col" id="schedsArea">
                            <div id="schedError" hidden>
                                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                    Error Test
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            </div>
                            <div class="card bg-dark align-middle scheds" id="sched1">
                                <div class="card-header text-center text-light">
                                    <p class="m-0 fs-5" id="cardTitle">Schedule 1</p>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3 row">
                                        <label for="daySched1" class="col-sm-2 col-form-label">Days</label>
                                        <div class="col">
                                            <select class="form-select" id="daySched1" name="daySched1" required>
                                                <option value="Monday">Monday</option>
                                                <option value="Tuesday">Tuesday</option>
                                                <option value="Wednesday">Wednesday</option>
                                                <option value="Thursday">Thursday</option>
                                                <option value="Friday">Friday</option>
                                            </select>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="mb-3 row">
                                        <label for="hourSched1" class="col-sm-2 col-form-label">Hours</label>
                                        <div class="col">
                                            <select class="form-select" id="hourSched1" name="hourSched1" required></select>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="mb-3 row">
                                        <label for="floorSched1" class="col-sm-2 col-form-label">Floor</label>
                                        <div class="col">
                                            <select class="form-select" id="floorSched1" name="floorSched1" required>
                                                <?php for ($i = 3; $i <= 7; $i++) : ?>
                                                    <option value="H<?= $i ?>">H<?= $i ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <label for="roomSched1" class="col-sm-2 text-center col-form-label">Room</label>
                                        <div class="col">
                                            <select class="form-select" id="roomSched1" name="roomSched1" required>
                                                <?php for ($i = 1; $i <= 12; $i++) : ?>
                                                    <option value="<?= $i ?>"><?= $i ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="card bg-dark align-middle scheds" id="sched2" hidden>
                                <div class="card-header text-center text-light">
                                    <p class="m-0 fs-5" id="cardTitle">Schedule 2</p>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3 row">
                                        <label for="daySched2" class="col-sm-2 col-form-label">Days</label>
                                        <div class="col">
                                            <select class="form-select" id="daySched2" name="daySched2">
                                                <option value="Monday">Monday</option>
                                                <option value="Tuesday">Tuesday</option>
                                                <option value="Wednesday">Wednesday</option>
                                                <option value="Thursday">Thursday</option>
                                                <option value="Friday">Friday</option>
                                            </select>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="mb-3 row">
                                        <label for="hourSched2" class="col-sm-2 col-form-label">Hours</label>
                                        <div class="col">
                                            <select class="form-select" id="hourSched2" name="hourSched2"></select>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="mb-3 row">
                                        <label for="floorSched2" class="col-sm-2 col-form-label">Floor</label>
                                        <div class="col">
                                            <select class="form-select" id="floorSched2" name="floorSched2">
                                                <?php for ($i = 3; $i <= 7; $i++) : ?>
                                                    <option value="H<?= $i ?>">H<?= $i ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <label for="roomSched2" class="col-sm-2 text-center col-form-label">Room</label>
                                        <div class="col">
                                            <select class="form-select" id="roomSched2" name="roomSched2">
                                                <?php for ($i = 1; $i <= 12; $i++) : ?>
                                                    <option value="<?= $i ?>"><?= $i ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col d-flex justify-content-end mb-3">
                        <button class="btn btn-outline-danger me-3" id="cancelButton">Cancel</button>
                        <input class="btn btn-primary" id="submitButton" type="submit" value="Submit"></input>
                    </div>
                </form>
            </div>
        <?php
            break;
        case 'update':
        ?>
            <div class="container-fluid mt-3">
                <form enctype="multipart/form-data" id="updateForm" onkeypress="return event.keyCode != 13;">
                    <input type="hidden" id="page" name="page" value="<?= $_GET['page'] ?? 1 ?>">
                    <input type="hidden" id="id" name="id" value="<?= $_GET['id'] ?? 'redirect' ?>">
                    <div class="mb-3 row">
                        <div class="col">
                            <div class="mb-3 row">
                                <label for="courses" class="col-sm-3 col-form-label">Course Name</label>
                                <div class="col">
                                    <select class="form-select" id="courses" name="courses" required></select>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="sks" class="col-sm-3 col-form-label">SKS</label>
                                <div class="col-3">
                                    <input type="text" class="form-control" id="sks" name="sks" readonly>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="lecturer" class="col-sm-3 col-form-label">Lecturer</label>
                                <div class="col">
                                    <select class="form-select" id="lecturer" name="lecturer" required></select>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="classGroup" class="col-sm-3 col-form-label">Group</label>
                                <div class="col">
                                    <select class="form-select" id="classGroup" name="classGroup" required></select>
                                </div>
                            </div>
                        </div>
                        <div class="col" id="schedsArea">
                            <div class="card bg-dark align-middle scheds" id="sched1">
                                <div class="card-header text-center text-light">
                                    <p class="m-0 fs-5" id="cardTitle">Schedule 1</p>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3 row">
                                        <label for="daySched1" class="col-sm-2 col-form-label">Days</label>
                                        <div class="col">
                                            <select class="form-select" id="daySched1" name="daySched1" required>
                                                <option value="">select...</option>
                                                <option value="Monday">Monday</option>
                                                <option value="Tuesday">Tuesday</option>
                                                <option value="Wednesday">Wednesday</option>
                                                <option value="Thursday">Thursday</option>
                                                <option value="Friday">Friday</option>
                                            </select>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="mb-3 row">
                                        <label for="hourSched1" class="col-sm-2 col-form-label">Hours</label>
                                        <div class="col">
                                            <select class="form-select" id="hourSched1" name="hourSched1" required></select>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="mb-3 row">
                                        <label for="floorSched1" class="col-sm-2 col-form-label">Floor</label>
                                        <div class="col">
                                            <select class="form-select" id="floorSched1" name="floorSched1" required>
                                                <?php for ($i = 3; $i <= 7; $i++) : ?>
                                                    <option value="H<?= $i ?>">H<?= $i ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <label for="roomSched1" class="col-sm-2 text-center col-form-label">Room</label>
                                        <div class="col">
                                            <select class="form-select" id="roomSched1" name="roomSched1" required>
                                                <?php for ($i = 1; $i <= 12; $i++) : ?>
                                                    <option value="<?= $i ?>"><?= $i ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
        function generateClassGroup(courseCode) {
            let classGroup = '#classGroup';
            if (courseCode) {
                $(classGroup).empty();
                const prefix = courseCode.substring(0, 3);
                const fifthDigit = courseCode.substring(4, 6);
                for (let i = 1; i <= 10; i++) {
                    const serialNum = String(i).padStart(2, '0');
                    const groupOption = `${prefix}.${fifthDigit}${serialNum}`;
                    $(classGroup).append(`<option value="${groupOption}">${groupOption}</option>`);
                }
            } else {
                $(classGroup).empty();
            }
        }

        function getCoursesOption() {
            $.ajax({
                url: 'src/api.php?req=getData',
                type: 'POST',
                data: {
                    target: 'courses'
                },
                dataType: 'json',
                success: function(response) {
                    $('#courses').empty();
                    if (response.courses) {
                        response.courses.forEach(course => {
                            $('#courses').append(`<option value="${course.code}">${course.name} (${course.code})</option>`);
                        });
                    }
                },
                error: function(xhr, status, error) {
                    alert('An error occurred while processing the request!');
                }
            });
        }

        $('#courses').on('focus', function() {
            getCoursesOption();
        });

        function getLecturersOption() {
            $.ajax({
                url: 'src/api.php?req=getData',
                type: 'POST',
                data: {
                    target: 'lecturers'
                },
                dataType: 'json',
                success: function(response) {
                    $('#lecturers').empty();
                    if (response.lecturers) {
                        response.lecturers.forEach(lecturer => {
                            if (lecturer.status != 0) {
                                $('#lecturers').append(`<option value="${lecturer.npp}">${lecturer.name} (${lecturer.npp})</option>`);
                            }
                        });
                    }
                },
                error: function(xhr, status, error) {
                    alert('An error occurred while processing the request!');
                }
            });
        }

        $('#lecturers').on('focus', function() {
            getLecturersOption();
        });

        function generateHourSched(hourScheds, sks) {
            let startTime = new Date();
            startTime.setHours(7, 0, 0);
            let endTime = new Date();
            endTime.setHours(21, 0, 0);

            function formatTime(date) {
                return date.toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
            if (hourScheds && sks) {
                let interval = (sks == 2 || sks == 4) ? 100 : (sks == 3) ? 150 : 0;
                $(hourScheds).empty();
                while (startTime < endTime) {
                    if (startTime.getHours() === 12 || startTime.getHours() === 15 && startTime.getMinutes() === 0) {
                        startTime.setMinutes(startTime.getMinutes() + 30);
                    }
                    let endInterval = new Date(startTime.getTime() + interval * 60000);
                    if (endInterval > endTime) {
                        endInterval = endTime;
                    }
                    let timeStart = formatTime(startTime);
                    let timeEnd = formatTime(endInterval);
                    $(hourScheds).append(`<option value="${timeStart} - ${timeEnd}">${timeStart} - ${timeEnd}</option>`);
                    startTime = endInterval;
                }
            } else {
                $(hourScheds).empty();
            }
        }

        function manageRoom(floorScheds, roomScheds) {
            $(roomScheds).empty();
            const floorValue = $(floorScheds).val();
            const roomOptions = {
                'H3': Array.from({ length: 12 }, (_, i) => i + 1),
                'H4': Array.from({ length: 12 }, (_, i) => i + 1),
                'H5': Array.from({ length: 10 }, (_, i) => i + 1),
                'H6': Array.from({ length: 5 }, (_, i) => i + 1),
                'H7': Array.from({ length: 3 }, (_, i) => i + 1)
            };
            if (floorValue in roomOptions) {
                roomOptions[floorValue].forEach(room => {
                    $(roomScheds).append('<option value=' + room + '>' + room + '</option>');
                });
            }
        }

        $('#floorSched1').change(function() {
            manageRoom(this, "#roomSched1");
        });

        $('#courses').change(function() {
            let courseCode = $(this).val();
            generateClassGroup(courseCode);
            let hourScheds;
            if (courseCode) {
                $.ajax({
                    url: 'src/api.php?req=courseOption',
                    type: 'POST',
                    data: {
                        courseCode: courseCode,
                    },
                    dataType: 'json',
                    success: function(response) {
                        $('#sks').val(response.sks);
                        if (response.sks == '2' || response.sks == '3') {
                            hourScheds = "#hourSched1";
                            $('#sched2').attr('hidden', true);
                        } else if (response.sks == '4') {
                            hourScheds = "#hourSched1, #hourSched2";
                            $('#sched2').removeAttr('hidden');
                            manageRoom("#floorSched2", "#roomSched2");
                        }
                        generateHourSched(hourScheds, response.sks);
                    },
                    error: function(xhr, status, error) {
                        alert('An error occurred while processing the request!');
                    }
                });
            } else {
                $('#sks').val('');
                $('#sched2').attr('hidden', true);
                $('#hourSched1').empty();
            }
        });

        $('#insertForm').on('submit', function(e) {
            e.preventDefault();
            let formData = new FormData($(this)[0]);
            let sched2Hidden = $('#sched2').is(':hidden');
            $.ajax({
                type: 'POST',
                url: 'src/api.php?req=insertOffer&sched2isHidden=' + sched2Hidden,
                data: formData,
                processData: false,
                contentType: false,
                cache: false,
                dataType: 'json',
                success: function(response) {
                    if (response.status == 'success') {
                        alert('Data inserted successfully!');
                        window.location.href = 'portal.php?view=course_schedule';
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
