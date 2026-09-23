<?php
if (isset($_SESSION['user'])) {
    $_SESSION['table']['page'] = 1;
    $page = $_GET['page'] ?? 0;
?>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-body border-bottom p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <form class="d-flex gap-2 align-items-center" id="searchBox">
                    <select class="form-select form-select-sm" id="searchCategory" style="width: 130px;">
                        <option value="course">Course</option>
                        <option value="lecturer">Lecturer</option>
                        <option value="group">Group</option>
                        <option value="days_sched1">Days Sched. 1</option>
                        <option value="days_sched2">Days Sched. 2</option>
                        <option value="class_room1">Room Sched. 1</option>
                        <option value="class_room2">Room Sched. 2</option>
                    </select>
                    <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Search..." aria-label="Search" style="width: 200px;" required>
                    <button class="btn btn-primary btn-sm px-3" type="submit" id="searchButton"><i class="bi bi-search me-1"></i>Search</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="resetButton">Reset</button>
                </form>
                
                <div class="d-flex gap-3 align-items-center">
                    <a type="button" href="src/reporting/pdf_krs_offers.php" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>Print</a>
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
                <table class="table table-hover table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th rowspan="2" class="text-center align-middle ps-4" style="width: 50px;">#</th>
                            <th rowspan="2" class="align-middle">Course</th>
                            <th rowspan="2" class="text-center align-middle">SKS</th>
                            <th rowspan="2" class="align-middle">Lecturer</th>
                            <th rowspan="2" class="text-center align-middle" style="width: 100px;">Group</th>
                            <th colspan="3" class="text-center align-middle">Schedule 1</th>
                            <th colspan="3" class="text-center align-middle">Schedule 2</th>
                            <th rowspan="2" class="text-center align-middle pe-4" style="width: 100px;">Action</th>
                        </tr>
                        <tr class="text-center">
                            <th>Days</th>
                            <th>Hours</th>
                            <th>Room</th>
                            <th>Days</th>
                            <th>Hours</th>
                            <th>Room</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody"></tbody>
                </table>
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
                    url: 'src/api.php?req=fetchOffers',
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

            $('#tableBody').on('click', '#editBtn', function() {
                var id = $(this).data('id');
                window.location.href = '?view=edit_course_schedule&req=update&id=' + id;
            });
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
                $.ajax({
                    url: 'src/api.php?req=searchOffers',
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
                        alert("Server error. [req: searchOffers]");
                    }
                });
            });
            $('#tableBody').on('click', '#deleteBtn', function() {
                var id = $(this).data('id');
                if (confirm("Are you sure to delete this row?")) {
                    $.ajax({
                        url: 'src/api.php?req=deleteOffer',
                        type: 'POST',
                        data: {
                            id: id,
                        },
                        dataType: 'json',
                        success: function(response) {
                            alert(response.message);
                            loadData(page, maxRow);
                        },
                        error: function() {
                            alert("Server error. [req: deleteOffer]");
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
