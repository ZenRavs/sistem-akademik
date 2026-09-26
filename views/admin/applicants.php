<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once defined('CONFIG_PATH') ? CONFIG_PATH . '/db.php' : __DIR__ . '/../../config/db.php';

if (!$conn) {
    echo '<div class="alert alert-danger">Gagal terhubung ke Database Cloud. Pastikan koneksi internet Anda aktif.</div>';
    return;
}

$getApplicantsStmt = $conn->query("
    SELECT pmb.id, pmb.user_id, pmb.nisn, pmb.mother_name, pmb.father_name, pmb.parent_phone,
           pmb.high_school_name AS school_origin, pmb.high_school_major AS major,
           pmb.high_school_address AS school_address, pmb.high_school_score AS final_score,
           pmb.certificate_file, pmb.payment_proof, pmb.payment_status, pmb.admission_track,
           pmb.application_status AS status, pmb.created_at,
           p.full_name, p.nik, p.gender, p.pob, p.dob, p.religion, p.marital_status, p.job_status,
           p.phone, p.ktp_address, p.domicile_address AS address, p.photo AS pict,
           u.username, u.email,
           m.major_code AS program_code, m.major_name
    FROM pmb_data pmb
    JOIN users_credential u ON pmb.user_id = u.id
    JOIN personal_profiles p ON p.user_id = u.id
    LEFT JOIN majors_data m ON pmb.major_id = m.id
    ORDER BY pmb.id DESC
");
$applicantsList = $getApplicantsStmt ? $getApplicantsStmt->fetchAll() : [];

function truncate18($text) {
    $str = trim($text ?? '');
    if (mb_strlen($str) > 18) {
        return mb_substr($str, 0, 18) . '...';
    }
    return $str;
}
?>

<style>
.spin-animation {
    animation: spin 0.8s linear infinite;
    display: inline-block;
}
@keyframes spin {
    100% { transform: rotate(360deg); }
}
</style>

<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-body border-bottom p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="fw-bold mb-1 text-primary"><i class="bi bi-person-lines-fill me-2"></i>Data Pendaftar Mahasiswa Baru (PMB)</h5>
                    <span class="text-body-secondary" style="font-size: 0.85rem;">Kelola dan verifikasi pendaftaran calon mahasiswa baru. Total pendaftar saat ini: <strong id="totalApplicantsCount"><?php echo count($applicantsList); ?></strong></span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm px-3 rounded-pill fw-semibold" id="refreshBtn" title="Refresh Data Tabel">
                        <i class="bi bi-arrow-clockwise me-1" id="refreshIcon"></i>Refresh Data
                    </button>
                </div>
            </div>
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 40px;">No</th>
                            <th class="text-center" style="width: 65px;">Foto</th>
                            <th>Nama & Contact</th>
                            <th>Sekolah & Alamat</th>
                            <th class="text-center">Nilai</th>
                            <th class="text-center">Prodi Pilihan</th>
                            <th class="text-center">Status</th>
                            <th>Waktu Pendaftaran</th>
                            <th class="text-center" style="width: 140px;">Aksi & Detail</th>
                        </tr>
                    </thead>
                    <tbody id="applicantsTableBody">
                        <?php if (empty($applicantsList)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">Belum ada data pendaftar mahasiswa baru.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($applicantsList as $index => $applicant): ?>
                                <?php
                                $pictName = $applicant['pict'] ?? '';
                                $defaultPict = 'https://cdn-icons-png.freepik.com/512/3875/3875148.png?ga=GA1.1.599436757.1735230785';
                                $imgSrc = $defaultPict;
                                if ($pictName) {
                                    $uploadDir = defined('UPLOAD_PATH') ? UPLOAD_PATH : __DIR__ . '/../../public/uploads/user_photos';
                                    $pmbUploadDir = defined('PMB_DOCS_PATH') ? PMB_DOCS_PATH : __DIR__ . '/../../public/uploads/pmb_docs';
                                    if (file_exists($uploadDir . '/' . $pictName)) {
                                        $imgSrc = defined('UPLOAD_URL') ? UPLOAD_URL . $pictName : './public/uploads/user_photos/' . $pictName;
                                    } elseif (file_exists($pmbUploadDir . '/' . $pictName)) {
                                        $imgSrc = defined('PMB_DOCS_URL') ? PMB_DOCS_URL . $pictName : './public/uploads/pmb_docs/' . $pictName;
                                    }
                                }
                                $applicant['img_src'] = $imgSrc;
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $index + 1; ?></td>
                                    <td class="text-center">
                                        <img src="<?php echo $imgSrc; ?>" class="rounded-circle object-fit-cover shadow-sm" width="50" height="50" alt="Foto">
                                    </td>
                                    <td>
                                        <strong title="<?php echo htmlspecialchars($applicant['full_name']); ?>">
                                            <?php echo htmlspecialchars(truncate18($applicant['full_name'])); ?>
                                        </strong><br>
                                        <small class="text-muted" title="<?php echo htmlspecialchars($applicant['email']); ?>">
                                            ✉️ <?php echo htmlspecialchars(truncate18($applicant['email'])); ?>
                                        </small><br>
                                        <small class="text-muted" title="<?php echo htmlspecialchars($applicant['phone'] ?? '-'); ?>">
                                            📞 <?php echo htmlspecialchars(truncate18($applicant['phone'] ?? '-')); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($applicant['school_origin'] ?? '-'); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($applicant['school_address'] ?? '-'); ?></small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-body-secondary text-body border fs-6">
                                            <?php echo htmlspecialchars($applicant['final_score'] ?? '-'); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($applicant['program_code'])): ?>
                                            <span class="badge bg-info text-body fs-6">
                                                <?php echo htmlspecialchars($applicant['program_code']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">Belum Pilih</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($applicant['status'] === 'Draft'): ?>
                                            <span class="badge bg-secondary">Draft</span>
                                        <?php elseif ($applicant['status'] === 'Pending'): ?>
                                            <span class="badge bg-warning text-body">Pending TU</span>
                                        <?php elseif ($applicant['status'] === 'Approved'): ?>
                                            <span class="badge bg-success">Diterima</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Ditolak</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="d-block">Created: <?php echo date('d M Y H:i', strtotime($applicant['created_at'])); ?></small>
                                        <?php if (!empty($applicant['edited_at'])): ?>
                                            <small class="text-muted">Updated: <?php echo date('d M Y H:i', strtotime($applicant['edited_at'])); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-info text-white btn-detail w-100 mb-1" data-applicant='<?php echo htmlspecialchars(json_encode($applicant), ENT_QUOTES, "UTF-8"); ?>'>
                                            🔍 Detail
                                        </button>
                                        <?php if ($applicant['status'] === 'Pending'): ?>
                                            <button class="btn btn-sm btn-success btn-verify mb-1 w-100" data-id="<?php echo $applicant['id']; ?>" data-action="Approve">
                                                ✓ Terima & NIM
                                            </button>
                                            <button class="btn btn-sm btn-danger btn-verify w-100" data-id="<?php echo $applicant['id']; ?>" data-action="Reject">
                                                ✕ Tolak
                                            </button>
                                        <?php elseif ($applicant['status'] === 'Approved'): ?>
                                            <span class="badge bg-success d-block">Resmi Mahasiswa</span>
                                        <?php elseif ($applicant['status'] === 'Draft'): ?>
                                            <span class="text-muted small d-block">Mengisi Berkas</span>
                                        <?php else: ?>
                                            <span class="text-muted small d-block">Selesai</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Pendaftar -->
<div class="modal fade text-body" id="detailApplicantModal" tabindex="-1" aria-labelledby="detailApplicantModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="detailApplicantModalLabel">🔍 Detail Data Pendaftar</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 align-items-stretch">
                    <!-- Photo Card Left Column -->
                    <div class="col-md-4 border-end pe-md-3 text-center mb-3 mb-md-0">
                        <div class="p-3 bg-body-secondary rounded border h-100 d-flex flex-column align-items-center justify-content-center">
                            <img id="detail_img" src="" class="rounded-circle border border-2 shadow-sm object-fit-cover mb-2" width="110" height="110" alt="Foto Pendaftar">
                            <h6 id="detail_full_name" class="fw-bold text-body mb-1"></h6>
                            <span id="detail_status_badge" class="badge bg-secondary mb-2"></span>
                            <small class="text-muted mb-2" id="detail_app_id"></small>
                            <span id="detail_prodi" class="badge bg-primary text-white rounded-pill px-3 py-2 mt-1"></span>
                        </div>
                    </div>

                    <!-- Right Column: Personal Data -->
                    <div class="col-md-8 ps-md-3">
                        <h6 class="text-primary font-weight-bold mb-2">👤 1. Data Diri Mahasiswa</h6>
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <small class="text-muted d-block">NIK (No. KTP)</small>
                                <strong id="detail_nik" class="text-body"></strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">NISN</small>
                                <strong id="detail_nisn" class="text-body"></strong>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Email Utama</small>
                                <strong id="detail_email" class="text-body"></strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">No. HP / WhatsApp</small>
                                <strong id="detail_phone" class="text-body"></strong>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Jenis Kelamin</small>
                                <strong id="detail_gender" class="text-body"></strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Agama</small>
                                <strong id="detail_religion" class="text-body"></strong>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Tempat Lahir</small>
                                <strong id="detail_pob" class="text-body"></strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Tanggal Lahir</small>
                                <strong id="detail_dob" class="text-body"></strong>
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Status Pernikahan</small>
                                <strong id="detail_marital_status" class="text-body"></strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Status Pekerjaan</small>
                                <strong id="detail_job_status" class="text-body"></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-3" style="border-top: 1px solid #000000; opacity: 0.35;">

                <div class="row g-3">
                    <div class="col-md-12">
                        <h6 class="text-primary font-weight-bold mb-2">👨‍👩‍👧 2. Data Orang Tua / Wali</h6>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <small class="text-muted d-block">Nama Ibu Kandung</small>
                                <strong id="detail_mother_name" class="text-body"></strong>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Nama Ayah</small>
                                <strong id="detail_father_name" class="text-body"></strong>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">No. HP / WhatsApp Orang Tua</small>
                                <strong id="detail_father_phone" class="text-body"></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-3" style="border-top: 1px solid #000000; opacity: 0.35;">

                <div class="row g-3">
                    <div class="col-md-6">
                        <h6 class="text-primary font-weight-bold mb-2">🏫 3. Data Sekolah Asal</h6>
                        <small class="text-muted d-block">Nama Sekolah Asal</small>
                        <strong id="detail_school_origin" class="text-body d-block mb-1"></strong>
                        <small class="text-muted d-block">Jurusan Sekolah Asal</small>
                        <strong id="detail_school_jurusan" class="text-body d-block mb-1"></strong>
                        <small class="text-muted d-block">Nilai Akhir Rata-rata</small>
                        <strong id="detail_final_score" class="text-body d-block mb-1"></strong>
                        <small class="text-muted d-block">Alamat Sekolah Asal</small>
                        <span id="detail_school_address" class="text-body"></span>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary font-weight-bold mb-2">🏠 4. Data Tempat Tinggal</h6>
                        <small class="text-muted d-block">Alamat Sesuai KTP</small>
                        <span id="detail_ktp_address" class="text-body d-block mb-2"></span>
                        <small class="text-muted d-block">Alamat Domisili (Saat Ini)</small>
                        <span id="detail_address" class="text-body"></span>
                    </div>
                </div>

                <hr class="my-3" style="border-top: 1px solid #000000; opacity: 0.35;">

                <div class="row g-3">
                    <div class="col-md-6">
                        <h6 class="text-primary font-weight-bold mb-2">📜 5. Berkas Pendaftaran</h6>
                        <small class="text-muted d-block">Sertifikat / Ijazah</small>
                        <div id="detail_cert_container" class="mt-1"></div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary font-weight-bold mb-2">💳 6. Status & Bukti Pembayaran</h6>
                        <small class="text-muted d-block">Status Pembayaran</small>
                        <span id="detail_payment_status" class="badge bg-secondary mb-2"></span>
                        <small class="text-muted d-block">Bukti Pembayaran PMB</small>
                        <div id="detail_pay_container" class="mt-1"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-body-secondary">
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
function truncate18Js(text) {
    let str = (text || '').trim();
    if (str.length > 18) {
        return str.substring(0, 18) + '...';
    }
    return str;
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function renderTableRows(list) {
    let tbody = $('#applicantsTableBody');
    tbody.empty();
    if (!list || list.length === 0) {
        tbody.html('<tr><td colspan="9" class="text-center text-muted py-4">Belum ada data pendaftar mahasiswa baru.</td></tr>');
        return;
    }

    list.forEach(function(applicant, index) {
        let jsonStr = escapeHtml(JSON.stringify(applicant));
        let statusBadgeHtml = '';
        if (applicant.status === 'Draft') {
            statusBadgeHtml = '<span class="badge bg-secondary">Draft</span>';
        } else if (applicant.status === 'Pending') {
            statusBadgeHtml = '<span class="badge bg-warning text-body">Pending TU</span>';
        } else if (applicant.status === 'Approved') {
            statusBadgeHtml = '<span class="badge bg-success">Diterima</span>';
        } else {
            statusBadgeHtml = '<span class="badge bg-danger">Ditolak</span>';
        }

        let prodiBadgeHtml = applicant.program_code 
            ? '<span class="badge bg-info text-body fs-6">' + escapeHtml(applicant.program_code) + '</span>'
            : '<span class="text-muted small">Belum Pilih</span>';

        let actionHtml = '';
        if (applicant.status === 'Pending') {
            actionHtml = '<button class="btn btn-sm btn-success btn-verify mb-1 w-100" data-id="' + applicant.id + '" data-action="Approve">✓ Terima & NIM</button>' +
                         '<button class="btn btn-sm btn-danger btn-verify w-100" data-id="' + applicant.id + '" data-action="Reject">✕ Tolak</button>';
        } else if (applicant.status === 'Approved') {
            actionHtml = '<span class="badge bg-success d-block">Resmi Mahasiswa</span>';
        } else if (applicant.status === 'Draft') {
            actionHtml = '<span class="text-muted small d-block">Mengisi Berkas</span>';
        } else {
            actionHtml = '<span class="text-muted small d-block">Selesai</span>';
        }

        let createdAtFormatted = applicant.created_at ? 'Created: ' + new Date(applicant.created_at).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-';

        let tr = '<tr>' +
            '<td class="text-center">' + (index + 1) + '</td>' +
            '<td class="text-center"><img src="' + escapeHtml(applicant.img_src) + '" class="rounded-circle object-fit-cover shadow-sm" width="50" height="50" alt="Foto"></td>' +
            '<td><strong title="' + escapeHtml(applicant.full_name) + '">' + escapeHtml(truncate18Js(applicant.full_name)) + '</strong><br>' +
                '<small class="text-muted" title="' + escapeHtml(applicant.email) + '">✉️ ' + escapeHtml(truncate18Js(applicant.email)) + '</small><br>' +
                '<small class="text-muted" title="' + escapeHtml(applicant.phone || '-') + '">📞 ' + escapeHtml(truncate18Js(applicant.phone || '-')) + '</small></td>' +
            '<td><strong>' + escapeHtml(applicant.school_origin || '-') + '</strong><br><small class="text-muted">' + escapeHtml(applicant.school_address || '-') + '</small></td>' +
            '<td class="text-center"><span class="badge bg-body-secondary text-body border fs-6">' + escapeHtml(applicant.final_score || '-') + '</span></td>' +
            '<td class="text-center">' + prodiBadgeHtml + '</td>' +
            '<td class="text-center">' + statusBadgeHtml + '</td>' +
            '<td><small class="d-block">' + createdAtFormatted + '</small></td>' +
            '<td class="text-center"><button type="button" class="btn btn-sm btn-info text-white btn-detail w-100 mb-1" data-applicant=\'' + jsonStr + '\'>🔍 Detail</button>' + actionHtml + '</td>' +
            '</tr>';
        tbody.append(tr);
    });
}

function reloadApplicantsTable() {
    let btn = $('#refreshBtn');
    let icon = $('#refreshIcon');
    btn.prop('disabled', true);
    icon.addClass('spin-animation');

    $.ajax({
        url: 'src/api.php?req=fetchApplicants',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            btn.prop('disabled', false);
            icon.removeClass('spin-animation');
            if (response.status === 'success') {
                $('#totalApplicantsCount').text(response.total);
                renderTableRows(response.data);
            } else {
                alert('Gagal merefresh data: ' + (response.message || 'Error'));
            }
        },
        error: function() {
            btn.prop('disabled', false);
            icon.removeClass('spin-animation');
            alert('Gagal terhubung ke server [fetchApplicants].');
        }
    });
}

$(document).ready(function() {
    $(document).on('click', '.btn-detail', function() {
        let applicant = $(this).data('applicant');
        if (typeof applicant === 'string') {
            applicant = JSON.parse(applicant);
        }

        $('#detail_img').attr('src', applicant.img_src || '');
        $('#detail_full_name').text(applicant.full_name || '-');
        $('#detail_app_id').text('#APP-' + String(applicant.id).padStart(4, '0'));
        $('#detail_nik').text(applicant.nik || '-');
        $('#detail_nisn').text(applicant.nisn || '-');
        $('#detail_email').text(applicant.email || '-');
        $('#detail_phone').text(applicant.phone || '-');
        $('#detail_gender').text(applicant.gender || '-');
        $('#detail_religion').text(applicant.religion || '-');
        $('#detail_pob').text(applicant.pob || '-');
        $('#detail_dob').text(applicant.dob || '-');
        $('#detail_marital_status').text(applicant.marital_status || '-');
        $('#detail_job_status').text(applicant.job_status || '-');
        $('#detail_mother_name').text(applicant.mother_name || '-');
        $('#detail_father_name').text(applicant.father_name || '-');
        $('#detail_father_phone').text(applicant.father_phone || '-');
        let prodiText = (applicant.program_code && applicant.major_name) ? (applicant.program_code + ' - ' + applicant.major_name) : (applicant.program_code || '-');
        $('#detail_prodi').text(prodiText);
        $('#detail_school_origin').text(applicant.school_origin || '-');
        $('#detail_school_jurusan').text(applicant.major || '-');
        $('#detail_final_score').text(applicant.final_score || '-');
        $('#detail_school_address').text(applicant.school_address || '-');
        $('#detail_ktp_address').text(applicant.ktp_address || '-');
        $('#detail_address').text(applicant.address || '-');

        // Document links
        let certBaseUrl = '<?php echo defined("PMB_CERT_URL") ? PMB_CERT_URL : "./public/uploads/pmb_docs/certificates/"; ?>';
        let payBaseUrl = '<?php echo defined("PMB_PAY_URL") ? PMB_PAY_URL : "./public/uploads/pmb_docs/payments/"; ?>';
        if (applicant.certificate_file) {
            $('#detail_cert_container').html('<a href="' + certBaseUrl + applicant.certificate_file + '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-pdf"></i> Lihat Sertifikat/Ijazah</a>');
        } else {
            $('#detail_cert_container').html('<span class="text-muted small">Belum diunggah</span>');
        }

        if (applicant.payment_proof) {
            $('#detail_pay_container').html('<a href="' + payBaseUrl + applicant.payment_proof + '" target="_blank" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-check"></i> Lihat Bukti Bayar</a>');
        } else {
            $('#detail_pay_container').html('<span class="text-muted small">Belum diunggah</span>');
        }

        // Payment status badge
        let payBadge = $('#detail_payment_status');
        payBadge.removeClass('bg-secondary bg-warning bg-success bg-danger text-body');
        let payStatus = applicant.payment_status || 'Unpaid';
        if (payStatus === 'Verified' || payStatus === 'Paid') {
            payBadge.addClass('bg-success').text('Verified / Lunas');
        } else if (payStatus === 'Pending') {
            payBadge.addClass('bg-warning text-body').text('Pending Verifikasi');
        } else {
            payBadge.addClass('bg-secondary').text('Unpaid / Belum Bayar');
        }

        let statusBadge = $('#detail_status_badge');
        statusBadge.removeClass('bg-secondary bg-warning bg-success bg-danger text-body');
        if (applicant.status === 'Approved') {
            statusBadge.addClass('bg-success').text('Diterima / Approved');
        } else if (applicant.status === 'Pending') {
            statusBadge.addClass('bg-warning text-body').text('Pending Verifikasi TU');
        } else if (applicant.status === 'Rejected') {
            statusBadge.addClass('bg-danger').text('Ditolak');
        } else {
            statusBadge.addClass('bg-secondary').text('Draft');
        }

        var myModal = new bootstrap.Modal(document.getElementById('detailApplicantModal'));
        myModal.show();
    });

    $(document).on('click', '.btn-verify', function() {
        let applicantId = $(this).data('id');
        let action = $(this).data('action');
        let confirmText = action === 'Approve' ? 'Apakah Anda yakin ingin MENERIMA pendaftar ini dan me-generate NIM otomatis?' : 'Apakah Anda yakin ingin MENOLAK pendaftar ini?';

        if (confirm(confirmText)) {
            $.ajax({
                url: 'src/api.php?req=verifyApplicant',
                type: 'POST',
                data: {
                    applicant_id: applicantId,
                    action: action
                },
                success: function(response) {
                    let res = typeof response === 'object' ? response : JSON.parse(response);
                    if (res.status === 'success') {
                        alert(res.message);
                        reloadApplicantsTable();
                    } else {
                        alert("Error: " + res.message);
                    }
                },
                error: function() {
                    alert("Gagal menghubungi server [verifyApplicant]");
                }
            });
        }
    });

    $('#refreshBtn').on('click', function() {
        reloadApplicantsTable();
    });
});
</script>
