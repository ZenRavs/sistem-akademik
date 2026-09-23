<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once defined('CONFIG_PATH') ? CONFIG_PATH . '/db.php' : __DIR__ . '/../../config/db.php';

if (!$conn) {
    echo '<div class="alert alert-danger">Gagal terhubung ke Database Cloud. Pastikan koneksi internet Anda aktif.</div>';
    return;
}

// Load payment methods configuration dynamically from JSON
$configPath = defined('CONFIG_PATH') ? CONFIG_PATH . '/payment_methods.json' : __DIR__ . '/../../config/payment_methods.json';
$paymentMethods = [];
if (file_exists($configPath)) {
    $paymentMethods = json_decode(file_get_contents($configPath), true) ?: [];
}

// Load FIK tuition fees pricelist configuration from JSON
$tuitionConfigPath = defined('CONFIG_PATH') ? CONFIG_PATH . '/tuition_fees.json' : __DIR__ . '/../../config/tuition_fees.json';
$tuitionConfig = [];
if (file_exists($tuitionConfigPath)) {
    $tuitionConfig = json_decode(file_get_contents($tuitionConfigPath), true) ?: [];
}

// Fetch list of majors for filter & dropdowns
$majorsList = [];
$mStmt = $conn->query("SELECT id, major_code, major_name, degree FROM majors_data ORDER BY major_code ASC");
if ($mStmt) {
    $majorsList = $mStmt->fetchAll();
}

$initialSearch = htmlspecialchars($_GET['search'] ?? $_GET['nim'] ?? '', ENT_QUOTES, 'UTF-8');
?>

<style>
    /* Styling adjustments for modal contrast across themes */
    #modalBillDetail .modal-content,
    #modalGenerateMassBills .modal-content,
    #modalCreateBill .modal-content {
        background-color: #ffffff !important;
        color: #1e293b !important;
    }
    #modalBillDetail label, 
    #modalBillDetail .form-label,
    #modalGenerateMassBills label, 
    #modalGenerateMassBills .form-label,
    #modalCreateBill label, 
    #modalCreateBill .form-label {
        color: #334155 !important;
        font-weight: 600;
    }
    #modalBillDetail .form-control,
    #modalBillDetail .form-select,
    #modalGenerateMassBills .form-control,
    #modalGenerateMassBills .form-select,
    #modalCreateBill .form-control,
    #modalCreateBill .form-select {
        color: #0f172a !important;
        background-color: #ffffff;
        border: 1px solid #cbd5e1;
    }
    .va-card {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        transition: all 0.2s ease;
        background-color: #f8fafc;
    }
    .va-card:hover {
        border-color: #0284c7;
        background-color: #f0f9ff;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    .va-number-display {
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, Courier, monospace;
        letter-spacing: 0.5px;
    }
</style>

<div class="container-fluid px-0">
    <!-- Top KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 border-start border-primary border-4 h-100">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-uppercase text-body-secondary fw-semibold small" style="font-size: 0.75rem; letter-spacing: 0.5px;">Total Tagihan Terbit</div>
                        <h4 class="fw-bold mb-0 text-body mt-1">Rp <span id="metricTotalBilled">0</span></h4>
                        <small class="text-body-secondary"><span id="metricCountBilled" class="fw-semibold text-primary">0</span> tagihan tercatat</small>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle">
                        <i class="bi bi-wallet2 fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 border-start border-success border-4 h-100">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-uppercase text-body-secondary fw-semibold small" style="font-size: 0.75rem; letter-spacing: 0.5px;">Pembayaran Diterima (Lunas)</div>
                        <h4 class="fw-bold mb-0 text-success mt-1">Rp <span id="metricTotalPaid">0</span></h4>
                        <small class="text-body-secondary"><span id="metricCountPaid" class="fw-semibold text-success">0</span> transaksi terverifikasi</small>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle">
                        <i class="bi bi-check-circle-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 border-start border-danger border-4 h-100">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-uppercase text-body-secondary fw-semibold small" style="font-size: 0.75rem; letter-spacing: 0.5px;">Total Tagihan Tertunggak</div>
                        <h4 class="fw-bold mb-0 text-danger mt-1">Rp <span id="metricTotalUnpaid">0</span></h4>
                        <small class="text-body-secondary"><span id="metricCountUnpaid" class="fw-semibold text-danger">0</span> tagihan belum lunas</small>
                    </div>
                    <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle">
                        <i class="bi bi-exclamation-octagon-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Toolbar Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <!-- Search Input -->
                <div class="col-lg-3 col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-body-tertiary border-end-0"><i class="bi bi-search text-secondary"></i></span>
                        <input type="text" class="form-control border-start-0" id="billSearchInput" placeholder="Cari NIM, Nama, Invoice..." value="<?= $initialSearch ?>">
                    </div>
                </div>

                <!-- Academic Year Filter -->
                <div class="col-lg-2 col-md-3 col-6">
                    <select class="form-select form-select-sm" id="filterAcademicYear">
                        <option value="all">Semua Periode</option>
                        <option value="2026/2027 Ganjil" selected>2026/2027 Ganjil</option>
                        <option value="2026/2027 Genap">2026/2027 Genap</option>
                        <option value="2025/2026 Ganjil">2025/2026 Ganjil</option>
                        <option value="2025/2026 Genap">2025/2026 Genap</option>
                    </select>
                </div>

                <!-- Major Filter -->
                <div class="col-lg-2 col-md-3 col-6">
                    <select class="form-select form-select-sm" id="filterMajor">
                        <option value="all">Semua Program Studi</option>
                        <?php foreach ($majorsList as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['major_code']) ?> - <?= htmlspecialchars($m['major_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Payment Status Filter -->
                <div class="col-lg-2 col-md-4 col-6">
                    <select class="form-select form-select-sm" id="filterStatus">
                        <option value="all">Semua Status</option>
                        <option value="Unpaid">Belum Lunas</option>
                        <option value="Paid">Lunas</option>
                        <option value="Pending_Verification">Menunggu Verifikasi</option>
                        <option value="Cancelled">Dibatalkan</option>
                    </select>
                </div>

                <!-- Payment Method Filter (Dynamically loaded from JSON) -->
                <div class="col-lg-1 col-md-4 col-6">
                    <select class="form-select form-select-sm" id="filterPaymentMethod" title="Metode Pembayaran">
                        <option value="all">Semua Metode</option>
                        <?php if (!empty($paymentMethods['virtual_accounts'])): ?>
                            <optgroup label="Virtual Account">
                                <?php foreach ($paymentMethods['virtual_accounts'] as $va): ?>
                                    <option value="<?= htmlspecialchars($va['name']) ?>"><?= htmlspecialchars($va['name']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                        <?php if (!empty($paymentMethods['manual_channels'])): ?>
                            <optgroup label="Metode Manual">
                                <?php foreach ($paymentMethods['manual_channels'] as $m): ?>
                                    <option value="<?= htmlspecialchars($m['name']) ?>"><?= htmlspecialchars($m['name']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Action Buttons: Mass Generate, Manual Add & Pricelist -->
                <div class="col-lg-3 col-md-4 d-flex gap-1 justify-content-end">
                    <button type="button" class="btn btn-sm btn-outline-info d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalPricelistFIK" title="Lihat Tarif & Pricelist Keuangan FIK">
                        <i class="bi bi-tags-fill"></i> Pricelist FIK
                    </button>
                    <button type="button" class="btn btn-sm btn-primary d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalGenerateMassBills" title="Terbitkan tagihan massal untuk angkatan/mahasiswa">
                        <i class="bi bi-lightning-charge-fill"></i> Massal
                    </button>
                    <button type="button" class="btn btn-sm btn-success d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalCreateBill" title="Tambah tagihan individual untuk 1 mahasiswa">
                        <i class="bi bi-plus-lg"></i> Tambah
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnResetFilters" title="Reset Semua Filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-body py-3 d-flex justify-content-between align-items-center border-bottom">
            <div class="fw-bold text-body d-flex align-items-center gap-2">
                <i class="bi bi-table text-primary"></i> Daftar Tagihan Keuangan Mahasiswa
            </div>
            <div class="d-flex align-items-center gap-2">
                <label for="maxRowsSelect" class="form-label mb-0 small text-body-secondary">Tampilkan:</label>
                <select class="form-select form-select-sm" id="maxRowsSelect" style="width: 80px;">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="billsTable">
                    <thead class="table-light">
                        <tr class="text-nowrap" style="font-size: 0.83rem;">
                            <th class="text-center" style="width: 45px;">#</th>
                            <th style="width: 170px;">Kode Tagihan</th>
                            <th>Mahasiswa</th>
                            <th>Prodi</th>
                            <th>Jenis & Periode</th>
                            <th>Nominal</th>
                            <th>Jatuh Tempo</th>
                            <th>Metode & VA</th>
                            <th class="text-center" style="width: 130px;">Status</th>
                            <th class="text-center" style="width: 170px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tableBillsBody">
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                Memuat data tagihan keuangan...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Pagination Footer -->
        <div class="card-footer bg-body py-2 d-flex flex-column flex-md-row justify-content-between align-items-center border-top">
            <small class="text-body-secondary mb-2 mb-md-0" id="paginationInfo">Memuat info halaman...</small>
            <nav>
                <ul class="pagination pagination-sm mb-0 gap-1" id="paginationList"></ul>
            </nav>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 1: DETAIL TAGIHAN & PEMBAYARAN VIRTUAL ACCOUNT (AUTO-VERIFY)       -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalBillDetail" tabindex="-1" aria-labelledby="modalBillDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-wallet2 fs-5"></i>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="modalBillDetailLabel">Detail Tagihan & Virtual Account</h6>
                        <small class="text-white-50" style="font-size: 0.75rem;" id="modal_bill_code_subtitle">Invoice #</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="modal_detail_bill_id">

                <!-- Status Banner -->
                <div id="modal_detail_status_banner" class="alert mb-3 py-2 px-3 small d-flex align-items-center justify-content-between">
                    <span id="modal_detail_status_text">Status Tagihan</span>
                    <span id="modal_detail_status_badge" class="badge">Status</span>
                </div>

                <!-- Student & Bill Information Grid -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6 border-end">
                        <h6 class="text-primary fw-bold small text-uppercase mb-2"><i class="bi bi-person-fill"></i> Data Mahasiswa</h6>
                        <table class="table table-sm table-borderless mb-0" style="font-size: 0.85rem;">
                            <tr>
                                <td class="text-muted" style="width: 100px;">Nama:</td>
                                <td class="fw-bold text-dark" id="modal_detail_student_name">-</td>
                            </tr>
                            <tr>
                                <td class="text-muted">NIM:</td>
                                <td class="font-monospace text-dark fw-bold" id="modal_detail_student_nim">-</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Prodi:</td>
                                <td class="text-dark" id="modal_detail_student_major">-</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary fw-bold small text-uppercase mb-2"><i class="bi bi-receipt"></i> Data Tagihan</h6>
                        <table class="table table-sm table-borderless mb-0" style="font-size: 0.85rem;">
                            <tr>
                                <td class="text-muted" style="width: 110px;">Periode:</td>
                                <td class="fw-semibold text-dark" id="modal_detail_academic_year">-</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Jenis:</td>
                                <td class="fw-semibold text-dark" id="modal_detail_bill_type">-</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Nominal:</td>
                                <td class="fw-bold text-primary fs-6" id="modal_detail_amount">Rp 0</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Jatuh Tempo:</td>
                                <td class="text-danger fw-semibold" id="modal_detail_due_date">-</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Virtual Account Channels (Mandiri, BRI, BCA) -->
                <div class="mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="text-primary fw-bold small text-uppercase mb-0">
                            <i class="bi bi-credit-card-2-front-fill me-1"></i> Nomor Virtual Account Mahasiswa
                        </h6>
                        <small class="text-muted" style="font-size: 0.75rem;">Rumus: <code>{KodeBank}.{NIM_Numerik}</code></small>
                    </div>

                    <div class="row g-2" id="vaCardsContainer">
                        <!-- Populated dynamically via JS -->
                    </div>
                </div>

                <!-- Action Section: Paid State vs Unpaid State -->
                <div id="sectionBillPaid" class="p-3 bg-light rounded border border-success-subtle d-none">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <div class="fw-bold text-success"><i class="bi bi-check-circle-fill me-1"></i> Tagihan Ini Telah LUNAS</div>
                            <small class="text-muted d-block" id="modal_detail_paid_info">Dibayar pada - melalui -</small>
                            <small class="text-muted d-block" id="modal_detail_verifier_info">Diverifikasi oleh -</small>
                        </div>
                        <div>
                            <a href="#" target="_blank" id="btnReceiptDownload" class="btn btn-success btn-sm px-3">
                                <i class="bi bi-printer me-1"></i> Cetak Kuitansi Resmi (PDF)
                            </a>
                        </div>
                    </div>
                </div>

                <div id="sectionBillUnpaid" class="border rounded p-3 bg-light">
                    <!-- Nav Tabs for Payment Mode -->
                    <ul class="nav nav-pills nav-fill mb-3" id="paymentTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active py-2 small fw-bold" id="va-sim-tab" data-bs-toggle="tab" data-bs-target="#tab-va-sim" type="button" role="tab">
                                ⚡ Simulasi Bayar VA (Auto-Verify)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-2 small fw-bold" id="manual-verify-tab" data-bs-toggle="tab" data-bs-target="#tab-manual-verify" type="button" role="tab">
                                🏢 Verifikasi Manual (Kasir / TU)
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="paymentTabContent">
                        <!-- TAB 1: Auto-Verify VA Simulation -->
                        <div class="tab-pane fade show active" id="tab-va-sim" role="tabpanel">
                            <div class="alert alert-info py-2 px-3 small mb-3">
                                <i class="bi bi-info-circle-fill me-1"></i> <strong>Sistem Otomatis (Instant Realtime)</strong>: Begitu pembayaran Virtual Account dilakukan, server secara otomatis memvalidasi tagihan menjadi <strong>Lunas</strong> dan mencatat <code>verified_by = 'VA-System (Auto)'</code> tanpa butuh persetujuan manual petugas TU.
                            </div>
                            <div class="row g-2 align-items-end">
                                <div class="col-md-7">
                                    <label class="form-label small mb-1">Pilih Bank Virtual Account:</label>
                                    <select class="form-select form-select-sm" id="sim_va_bank">
                                        <?php if (!empty($paymentMethods['virtual_accounts'])): ?>
                                            <?php foreach ($paymentMethods['virtual_accounts'] as $va): ?>
                                                <option value="<?= htmlspecialchars($va['name']) ?>"><?= htmlspecialchars($va['name']) ?> (Kode <?= htmlspecialchars($va['bank_code']) ?>)</option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <option value="Mandiri">Bank Mandiri (008)</option>
                                            <option value="BRI">Bank BRI (002)</option>
                                            <option value="BCA">Bank BCA (014)</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <button type="button" class="btn btn-primary btn-sm w-100 fw-bold" id="btnSubmitVaSim">
                                        <i class="bi bi-lightning-charge-fill me-1"></i> Bayar Sekarang (Auto-Verify)
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: Manual Verification by Staff -->
                        <div class="tab-pane fade" id="tab-manual-verify" role="tabpanel">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small mb-1">Metode Pembayaran:</label>
                                    <select class="form-select form-select-sm" id="manual_payment_method">
                                        <option value="Tunai / Kasir TU" selected>Tunai / Kasir TU</option>
                                        <option value="Transfer Bank Manual">Transfer Bank Manual</option>
                                        <option value="EDC / Kartu Debit">EDC / Kartu Debit</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small mb-1">Catatan / No. Referensi:</label>
                                    <input type="text" class="form-control form-control-sm" id="manual_payment_notes" placeholder="Misal: Diterima lunas tunai di loket TU">
                                </div>
                                <div class="col-12 mt-2 text-end">
                                    <button type="button" class="btn btn-outline-success btn-sm fw-bold px-3" id="btnSubmitManualVerify">
                                        <i class="bi bi-check2-circle me-1"></i> Verifikasi Manual (Tandai Lunas)
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 2: TERBITKAN TAGIHAN MASSAL (GENERASI OTOMATIS)                   -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalGenerateMassBills" tabindex="-1" aria-labelledby="modalGenerateMassBillsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <form id="formGenerateMassBills">
                <div class="modal-header bg-primary text-white py-3">
                    <h6 class="modal-title fw-bold mb-0" id="modalGenerateMassBillsLabel">
                        <i class="bi bi-lightning-charge-fill me-1"></i> Terbitkan Tagihan Massal
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-secondary py-2 px-3 small mb-3">
                        <i class="bi bi-info-circle me-1"></i> Tagihan akan diterbitkan untuk seluruh mahasiswa aktif sesuai kriteria filter di bawah. Mahasiswa yang sudah memiliki tagihan sejenis pada periode yang sama akan dilewati secara otomatis.
                    </div>

                    <div class="mb-3">
                        <label class="form-label small mb-1">Tahun Akademik <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" name="academic_year" required>
                            <option value="2026/2027 Ganjil" selected>2026/2027 Ganjil</option>
                            <option value="2026/2027 Genap">2026/2027 Genap</option>
                            <option value="2025/2026 Ganjil">2025/2026 Ganjil</option>
                            <option value="2025/2026 Genap">2025/2026 Genap</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small mb-1">Jenis Tagihan <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" name="bill_type" required>
                            <option value="UKT Pokok" selected>UKT Pokok (Uang Kuliah Tunggal)</option>
                            <option value="Daftar Ulang">Daftar Ulang Mahasiswa Baru</option>
                            <option value="Biaya Praktikum">Biaya Praktikum Laboratorium</option>
                            <option value="Biaya Wisuda">Biaya Wisuda & Kelulusan</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Target Program Studi</label>
                            <select class="form-select form-select-sm" name="major_id">
                                <option value="all" selected>Semua Program Studi</option>
                                <?php foreach ($majorsList as $m): ?>
                                    <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['major_code']) ?> - <?= htmlspecialchars($m['major_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Target Angkatan</label>
                            <select class="form-select form-select-sm" name="batch_year">
                                <option value="all" selected>Semua Angkatan</option>
                                <option value="2026">Angkatan 2026</option>
                                <option value="2025">Angkatan 2025</option>
                                <option value="2024">Angkatan 2024</option>
                                <option value="2023">Angkatan 2023</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Nominal Tagihan (Rp) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm" name="amount" value="5000000" min="10000" step="50000" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Tanggal Jatuh Tempo <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" name="due_date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3" id="btnSubmitMass">
                        <i class="bi bi-send-check me-1"></i> Terbitkan Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 3: TAMBAH TAGIHAN MANUAL (PERORANGAN)                             -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalCreateBill" tabindex="-1" aria-labelledby="modalCreateBillLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <form id="formCreateBill">
                <div class="modal-header bg-success text-white py-3">
                    <h6 class="modal-title fw-bold mb-0" id="modalCreateBillLabel">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Tagihan Perorangan
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small mb-1">NIM Mahasiswa <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm font-monospace" name="nim" placeholder="Contoh: A12.2026.00001" required>
                        <small class="text-muted" style="font-size: 0.75rem;">Pastikan NIM sudah terdaftar aktif di sistem akademik.</small>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Tahun Akademik <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="academic_year" required>
                                <option value="2026/2027 Ganjil" selected>2026/2027 Ganjil</option>
                                <option value="2026/2027 Genap">2026/2027 Genap</option>
                                <option value="2025/2026 Ganjil">2025/2026 Ganjil</option>
                                <option value="2025/2026 Genap">2025/2026 Genap</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Jenis Tagihan <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="bill_type" required>
                                <option value="UKT Pokok" selected>UKT Pokok</option>
                                <option value="Daftar Ulang">Daftar Ulang</option>
                                <option value="Biaya Remidi / Semester Antara">Biaya Remidi / SP</option>
                                <option value="Biaya Praktikum">Biaya Praktikum</option>
                                <option value="Biaya Wisuda">Biaya Wisuda</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Nominal (Rp) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm" name="amount" placeholder="Contoh: 5000000" min="10000" step="50000" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Jatuh Tempo <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" name="due_date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small mb-1">Catatan Tambahan (Opsional)</label>
                        <input type="text" class="form-control form-control-sm" name="notes" placeholder="Misal: Tagihan penyesuaian gelombang 2">
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm px-3" id="btnSubmitCreate">
                        <i class="bi bi-save me-1"></i> Simpan Tagihan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 4: PRICELIST & TARIF KEUANGAN FIK (CONFIG DRIVEN)                   -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalPricelistFIK" tabindex="-1" aria-labelledby="modalPricelistFIKLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-info text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-tags-fill fs-5"></i>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="modalPricelistFIKLabel">Daftar Tarif & Pricelist Keuangan FIK</h6>
                        <small class="text-white-50" style="font-size: 0.75rem;">Konfigurasi Dinamis: <code>config/tuition_fees.json</code></small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info py-2 px-3 small mb-4">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    <strong>Ketentuan Tarif Fakultas Ilmu Komputer (FIK)</strong>: Biaya SPP dan Poliklinik bersifat <em>tarif rata (flat rate)</em> untuk seluruh Program Studi di lingkup FIK, sedangkan UKT Pokok disesuaikan per Program Studi (Prodi).
                </div>

                <!-- Shared Flat Rates Grid -->
                <h6 class="text-primary fw-bold small text-uppercase mb-3"><i class="bi bi-gear-fill me-1"></i> 1. Tarif Rata & Biaya Per SKS (Komponen Bersama FIK)</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded border text-center">
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.72rem;">Biaya Per SKS</small>
                            <h5 class="fw-bold text-dark mb-0 mt-1">Rp <?= number_format((float)($tuitionConfig['shared_fees']['cost_per_sks'] ?? 300000), 0, ',', '.') ?></h5>
                            <small class="text-muted" style="font-size: 0.72rem;">per SKS matakuliah</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded border text-center">
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.72rem;">SPP (Tarif Rata FIK)</small>
                            <h5 class="fw-bold text-primary mb-0 mt-1">Rp <?= number_format((float)($tuitionConfig['shared_fees']['spp_flat'] ?? 2000000), 0, ',', '.') ?></h5>
                            <small class="text-muted" style="font-size: 0.72rem;">per semester</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded border text-center">
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.72rem;">Poliklinik (Tarif Rata FIK)</small>
                            <h5 class="fw-bold text-success mb-0 mt-1">Rp <?= number_format((float)($tuitionConfig['shared_fees']['polyclinic_flat'] ?? 200000), 0, ',', '.') ?></h5>
                            <small class="text-muted" style="font-size: 0.72rem;">layanan kesehatan kampus</small>
                        </div>
                    </div>
                </div>

                <!-- Major Rates Table -->
                <h6 class="text-primary fw-bold small text-uppercase mb-3"><i class="bi bi-mortarboard-fill me-1"></i> 2. Pricelist UKT Pokok Per Program Studi FIK</h6>
                <div class="table-responsive rounded border">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 80px;">Kode</th>
                                <th>Program Studi</th>
                                <th style="width: 90px;" class="text-center">Jenjang</th>
                                <th class="text-end">Tarif UKT Pokok</th>
                                <th class="text-center" style="width: 130px;">Aksi Cepat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($tuitionConfig['rates_by_major'])): ?>
                                <?php foreach ($tuitionConfig['rates_by_major'] as $code => $rate): ?>
                                    <?php if ($code === 'default') continue; ?>
                                    <tr>
                                        <td><span class="badge bg-primary-subtle text-primary border font-monospace"><?= htmlspecialchars($rate['major_code']) ?></span></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($rate['major_name']) ?></td>
                                        <td class="text-center"><span class="badge bg-light text-dark border"><?= htmlspecialchars($rate['degree']) ?></span></td>
                                        <td class="text-end fw-bold text-primary">Rp <?= number_format((float)$rate['ukt_pokok'], 0, ',', '.') ?></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-xs btn-outline-primary btn-apply-rate py-0 px-2" data-amount="<?= $rate['ukt_pokok'] ?>" style="font-size: 0.75rem;" title="Gunakan nominal ini untuk terbitkan tagihan">
                                                Gunakan
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center text-muted py-3">Konfigurasi pricelist belum dimuat.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT LOGIC & AJAX HANDLERS                                         -->
<!-- ========================================================================= -->
<script>
$(document).ready(function() {
    let currentPage = 1;
    let currentMaxRow = parseInt($('#maxRowsSelect').val()) || 10;
    let currentBillData = null;

    // Load initial data
    loadBills(currentPage);

    // Apply Rate from Pricelist Modal to Create Bill Modal
    $(document).on('click', '.btn-apply-rate', function() {
        let amount = $(this).data('amount');
        let pricelistModal = bootstrap.Modal.getInstance(document.getElementById('modalPricelistFIK'));
        if (pricelistModal) pricelistModal.hide();
        
        $('#formCreateBill input[name="amount"]').val(amount);
        let createModal = new bootstrap.Modal(document.getElementById('modalCreateBill'));
        createModal.show();
    });

    function loadBills(page = 1) {
        currentPage = page;
        let academicYear = $('#filterAcademicYear').val();
        let majorId = $('#filterMajor').val();
        let paymentStatus = $('#filterStatus').val();
        let paymentMethod = $('#filterPaymentMethod').val();
        let search = $('#billSearchInput').val().trim();

        $('#tableBillsBody').html(`
            <tr>
                <td colspan="10" class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                    Memuat data tagihan keuangan...
                </td>
            </tr>
        `);

        $.ajax({
            url: 'src/api.php?req=fetchStudentBills',
            type: 'POST',
            data: {
                page: currentPage,
                maxRow: currentMaxRow,
                academic_year: academicYear,
                major_id: majorId,
                payment_status: paymentStatus,
                payment_method: paymentMethod,
                search: search
            },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    $('#tableBillsBody').html(res.html);
                    renderPagination(res.pages, currentPage);

                    // Update KPI Metric Cards
                    if (res.metrics) {
                        $('#metricTotalBilled').text(res.metrics.total_billed_amount);
                        $('#metricCountBilled').text(res.metrics.total_billed_count);
                        $('#metricTotalPaid').text(res.metrics.total_paid_amount);
                        $('#metricCountPaid').text(res.metrics.total_paid_count);
                        $('#metricTotalUnpaid').text(res.metrics.total_unpaid_amount);
                        $('#metricCountUnpaid').text(res.metrics.total_unpaid_count);
                    }
                } else {
                    $('#tableBillsBody').html(`<tr><td colspan="10" class="text-center py-4 text-danger">${res.message || 'Gagal memuat data'}</td></tr>`);
                }
            },
            error: function() {
                $('#tableBillsBody').html('<tr><td colspan="10" class="text-center py-4 text-danger">Terjadi kesalahan pada server saat mengambil data.</td></tr>');
            }
        });
    }

    function renderPagination(totalPages, activePage) {
        let container = $('#paginationList');
        container.empty();

        if (totalPages <= 1) {
            $('#paginationInfo').text(`Menampilkan halaman 1 dari 1`);
            return;
        }

        $('#paginationInfo').text(`Menampilkan halaman ${activePage} dari ${totalPages}`);

        // Prev button
        let prevDisabled = (activePage === 1) ? 'disabled' : '';
        container.append(`
            <li class="page-item ${prevDisabled}">
                <button class="page-link" data-page="${activePage - 1}" aria-label="Previous">&laquo;</button>
            </li>
        `);

        // Page buttons (max 5 pages shown)
        let startPage = Math.max(1, activePage - 2);
        let endPage = Math.min(totalPages, startPage + 4);
        if (endPage - startPage < 4) {
            startPage = Math.max(1, endPage - 4);
        }

        for (let p = startPage; p <= endPage; p++) {
            let active = (p === activePage) ? 'active' : '';
            container.append(`
                <li class="page-item ${active}">
                    <button class="page-link" data-page="${p}">${p}</button>
                </li>
            `);
        }

        // Next button
        let nextDisabled = (activePage === totalPages) ? 'disabled' : '';
        container.append(`
            <li class="page-item ${nextDisabled}">
                <button class="page-link" data-page="${activePage + 1}" aria-label="Next">&raquo;</button>
            </li>
        `);
    }

    // Pagination Click
    $(document).on('click', '#paginationList .page-link', function(e) {
        e.preventDefault();
        let targetPage = parseInt($(this).data('page'));
        if (targetPage && targetPage !== currentPage) {
            loadBills(targetPage);
        }
    });

    // Filter Change Listeners
    $('#filterAcademicYear, #filterMajor, #filterStatus, #filterPaymentMethod, #maxRowsSelect').on('change', function() {
        currentMaxRow = parseInt($('#maxRowsSelect').val()) || 10;
        loadBills(1);
    });

    // Search Input with Debounce
    let searchTimeout = null;
    $('#billSearchInput').on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            loadBills(1);
        }, 400);
    });

    // Reset Filters
    $('#btnResetFilters').on('click', function() {
        $('#billSearchInput').val('');
        $('#filterAcademicYear').val('2026/2027 Ganjil');
        $('#filterMajor').val('all');
        $('#filterStatus').val('all');
        $('#filterPaymentMethod').val('all');
        loadBills(1);
    });

    // OPEN BILL DETAIL MODAL
    $(document).on('click', '.btn-bill-detail', function() {
        let billRaw = $(this).attr('data-bill');
        try {
            currentBillData = JSON.parse(billRaw);
        } catch (e) {
            console.error("Parse error on bill data", e);
            alert("Gagal membaca data tagihan.");
            return;
        }

        let b = currentBillData;
        $('#modal_detail_bill_id').val(b.id);
        $('#modal_bill_code_subtitle').text(`Invoice #${b.bill_code || b.id}`);
        $('#modal_detail_student_name').text(b.full_name || '-');
        $('#modal_detail_student_nim').text(b.nim || '-');
        $('#modal_detail_student_major').text(`${b.major_name || '-'} (${b.degree || '-'})`);
        $('#modal_detail_academic_year').text(b.academic_year || '-');
        $('#modal_detail_bill_type').text(b.bill_type || '-');
        $('#modal_detail_amount').text(`Rp ${new Intl.NumberFormat('id-ID').format(b.amount)}`);
        $('#modal_detail_due_date').text(b.due_date ? new Date(b.due_date).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'}) : '-');

        // Render Status Banner
        let banner = $('#modal_detail_status_banner');
        let statusBadge = $('#modal_detail_status_badge');
        let statusText = $('#modal_detail_status_text');
        banner.removeClass('alert-success alert-danger alert-warning alert-secondary');
        statusBadge.removeClass('bg-success bg-danger bg-warning text-dark bg-secondary');

        if (b.payment_status === 'Paid') {
            banner.addClass('alert-success');
            statusBadge.addClass('bg-success').text('LUNAS');
            statusText.html(`<strong>Status Pembayaran:</strong> Pembayaran telah selesai dan diverifikasi.`);
            $('#sectionBillPaid').removeClass('d-none');
            $('#sectionBillUnpaid').addClass('d-none');
            $('#modal_detail_paid_info').text(`Dibayar pada: ${b.paid_at || '-'} via ${b.payment_method || 'Virtual Account'}`);
            $('#modal_detail_verifier_info').text(`Diverifikasi oleh: ${b.verified_by || 'VA-System (Auto)'}`);
            $('#btnReceiptDownload').attr('href', `src/reporting/pdf_bill_receipt.php?bill_id=${b.id}`);
        } else if (b.payment_status === 'Cancelled') {
            banner.addClass('alert-secondary');
            statusBadge.addClass('bg-secondary').text('DIBATALKAN');
            statusText.html(`<strong>Status Pembayaran:</strong> Tagihan telah dibatalkan.`);
            $('#sectionBillPaid').addClass('d-none');
            $('#sectionBillUnpaid').addClass('d-none');
        } else {
            banner.addClass('alert-danger');
            statusBadge.addClass('bg-danger').text('BELUM LUNAS');
            statusText.html(`<strong>Status Pembayaran:</strong> Tagihan masih aktif dan menunggu pembayaran.`);
            $('#sectionBillPaid').addClass('d-none');
            $('#sectionBillUnpaid').removeClass('d-none');
        }

        // Render VA Cards
        let vaContainer = $('#vaCardsContainer');
        vaContainer.empty();
        if (b.va_list && Array.isArray(b.va_list)) {
            b.va_list.forEach(function(va) {
                let badgeColor = (va.bank_name === 'Mandiri') ? 'bg-primary' : (va.bank_name === 'BRI' ? 'bg-info text-dark' : 'bg-dark');
                vaContainer.append(`
                    <div class="col-md-4">
                        <div class="va-card p-2 text-center h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="badge ${badgeColor} py-1 px-2" style="font-size: 0.7rem;">${va.bank_name}</span>
                                    <span class="text-muted" style="font-size: 0.7rem;">Kode: ${va.bank_code}</span>
                                </div>
                                <div class="va-number-display fw-bold text-dark my-2" style="font-size: 0.88rem;">
                                    ${va.va_number}
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-copy-va py-0 px-2" data-va="${va.va_number}" style="font-size: 0.75rem;">
                                <i class="bi bi-clipboard"></i> Salin
                            </button>
                        </div>
                    </div>
                `);
            });
        }

        let modalEl = new bootstrap.Modal(document.getElementById('modalBillDetail'));
        modalEl.show();
    });

    // Copy to Clipboard Handler
    $(document).on('click', '.btn-copy-va', function() {
        let vaNum = $(this).data('va');
        let btn = $(this);
        navigator.clipboard.writeText(vaNum).then(function() {
            let originalHtml = btn.html();
            btn.removeClass('btn-outline-secondary').addClass('btn-success').html('<i class="bi bi-check"></i> Tersalin!');
            setTimeout(function() {
                btn.removeClass('btn-success').addClass('btn-outline-secondary').html(originalHtml);
            }, 1800);
        }).catch(function() {
            alert('Nomor VA: ' + vaNum);
        });
    });

    // SUBMIT VA AUTO-VERIFY SIMULATION
    $('#btnSubmitVaSim').on('click', function() {
        if (!currentBillData) return;
        let bankName = $('#sim_va_bank').val();

        if (confirm(`Simulasikan pembayaran tagihan ${currentBillData.bill_code || currentBillData.id} via Virtual Account ${bankName} sebesar Rp ${new Intl.NumberFormat('id-ID').format(currentBillData.amount)}?`)) {
            let btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Memproses VA...');

            $.ajax({
                url: 'src/api.php?req=payBillViaVA',
                type: 'POST',
                data: {
                    bill_id: currentBillData.id,
                    bank_name: bankName
                },
                dataType: 'json',
                success: function(res) {
                    btn.prop('disabled', false).html('<i class="bi bi-lightning-charge-fill me-1"></i> Bayar Sekarang (Auto-Verify)');
                    if (res.status === 'success') {
                        alert(res.message);
                        let modalInstance = bootstrap.Modal.getInstance(document.getElementById('modalBillDetail'));
                        if (modalInstance) modalInstance.hide();
                        loadBills(currentPage);
                    } else {
                        alert('Gagal: ' + res.message);
                    }
                },
                error: function() {
                    btn.prop('disabled', false).html('<i class="bi bi-lightning-charge-fill me-1"></i> Bayar Sekarang (Auto-Verify)');
                    alert('Gagal terhubung ke server [payBillViaVA]');
                }
            });
        }
    });

    // SUBMIT MANUAL VERIFICATION BY STAFF
    $('#btnSubmitManualVerify').on('click', function() {
        if (!currentBillData) return;
        let method = $('#manual_payment_method').val();
        let notes = $('#manual_payment_notes').val();

        if (confirm(`Verifikasi manual tagihan ini menjadi LUNAS via ${method}?`)) {
            let btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...');

            $.ajax({
                url: 'src/api.php?req=verifyStudentBill',
                type: 'POST',
                data: {
                    bill_id: currentBillData.id,
                    action: 'Approve',
                    payment_method: method,
                    notes: notes
                },
                dataType: 'json',
                success: function(res) {
                    btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Verifikasi Manual (Tandai Lunas)');
                    if (res.status === 'success') {
                        alert(res.message);
                        let modalInstance = bootstrap.Modal.getInstance(document.getElementById('modalBillDetail'));
                        if (modalInstance) modalInstance.hide();
                        loadBills(currentPage);
                    } else {
                        alert('Gagal: ' + res.message);
                    }
                },
                error: function() {
                    btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Verifikasi Manual (Tandai Lunas)');
                    alert('Gagal terhubung ke server [verifyStudentBill]');
                }
            });
        }
    });

    // CANCEL BILL HANDLER
    $(document).on('click', '.btn-cancel-bill', function() {
        let billId = $(this).data('id');
        if (confirm('Apakah Anda yakin ingin membatalkan tagihan ini? Status tagihan akan diubah menjadi Cancelled.')) {
            $.ajax({
                url: 'src/api.php?req=cancelStudentBill',
                type: 'POST',
                data: { bill_id: billId },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        alert(res.message);
                        loadBills(currentPage);
                    } else {
                        alert('Gagal membatalkan tagihan: ' + res.message);
                    }
                },
                error: function() {
                    alert('Gagal terhubung ke server [cancelStudentBill]');
                }
            });
        }
    });

    // FORM GENERATE MASS BILLS SUBMIT
    $('#formGenerateMassBills').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btnSubmitMass');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menerbitkan...');

        $.ajax({
            url: 'src/api.php?req=generateMassBills',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-send-check me-1"></i> Terbitkan Sekarang');
                if (res.status === 'success') {
                    alert(res.message);
                    let modalInstance = bootstrap.Modal.getInstance(document.getElementById('modalGenerateMassBills'));
                    if (modalInstance) modalInstance.hide();
                    $('#formGenerateMassBills')[0].reset();
                    loadBills(1);
                } else {
                    alert('Gagal: ' + res.message);
                }
            },
            error: function() {
                btn.prop('disabled', false).html('<i class="bi bi-send-check me-1"></i> Terbitkan Sekarang');
                alert('Gagal terhubung ke server [generateMassBills]');
            }
        });
    });

    // FORM CREATE INDIVIDUAL BILL SUBMIT
    $('#formCreateBill').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btnSubmitCreate');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...');

        $.ajax({
            url: 'src/api.php?req=createStudentBill',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i> Simpan Tagihan');
                if (res.status === 'success') {
                    alert(res.message);
                    let modalInstance = bootstrap.Modal.getInstance(document.getElementById('modalCreateBill'));
                    if (modalInstance) modalInstance.hide();
                    $('#formCreateBill')[0].reset();
                    loadBills(1);
                } else {
                    alert('Gagal: ' + res.message);
                }
            },
            error: function() {
                btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i> Simpan Tagihan');
                alert('Gagal terhubung ke server [createStudentBill]');
            }
        });
    });
});
</script>
