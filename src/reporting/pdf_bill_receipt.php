<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/db.php';

$billId = (int)($_GET['bill_id'] ?? 0);
if (!$billId) {
    die("ID Tagihan tidak valid.");
}

$stmt = $conn->prepare("
    SELECT b.*, s.nim, p.full_name, p.phone, u.email,
           m.major_code, m.major_name, m.degree
    FROM students_bills_data b
    JOIN students_data s ON b.student_id = s.id
    JOIN personal_profiles p ON s.user_id = p.user_id
    JOIN users_credential u ON s.user_id = u.id
    JOIN majors_data m ON s.major_id = m.id
    WHERE b.id = ?
");
$stmt->execute([$billId]);
$bill = $stmt->fetch();

if (!$bill) {
    die("Data tagihan tidak ditemukan.");
}

function terbilang($angka) {
    $angka = abs((float)$angka);
    $baca = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
    $terbilang = '';

    if ($angka < 12) {
        $terbilang = ' ' . $baca[(int)$angka];
    } elseif ($angka < 20) {
        $terbilang = terbilang($angka - 10) . ' Belas';
    } elseif ($angka < 100) {
        $terbilang = terbilang((int)($angka / 10)) . ' Puluh' . terbilang($angka % 10);
    } elseif ($angka < 200) {
        $terbilang = ' Seratus' . terbilang($angka - 100);
    } elseif ($angka < 1000) {
        $terbilang = terbilang((int)($angka / 100)) . ' Ratus' . terbilang($angka % 100);
    } elseif ($angka < 2000) {
        $terbilang = ' Seribu' . terbilang($angka - 1000);
    } elseif ($angka < 1000000) {
        $terbilang = terbilang((int)($angka / 1000)) . ' Ribu' . terbilang($angka % 1000);
    } elseif ($angka < 1000000000) {
        $terbilang = terbilang((int)($angka / 1000000)) . ' Juta' . terbilang($angka % 1000000);
    } elseif ($angka < 1000000000000) {
        $terbilang = terbilang((int)($angka / 1000000000)) . ' Miliar' . terbilang(fmod($angka, 1000000000));
    }

    return trim($terbilang);
}

$paidDate = !empty($bill['paid_at']) ? date('d F Y - H:i:s', strtotime($bill['paid_at'])) : date('d F Y - H:i:s');
$dueDate = !empty($bill['due_date']) ? date('d F Y', strtotime($bill['due_date'])) : '-';
$amountFormatted = number_format((float)$bill['amount'], 0, ',', '.');
$terbilangText = terbilang($bill['amount']) . ' Rupiah';
$isPaid = ($bill['payment_status'] === 'Paid');

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_left' => 15,
    'margin_right' => 15,
    'margin_top' => 15,
    'margin_bottom' => 15
]);

$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: sans-serif; font-size: 11pt; color: #1e293b; }
    .header-table { width: 100%; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 20px; }
    .header-table td { vertical-align: middle; }
    .inst-title { font-size: 14pt; font-weight: bold; color: #0f172a; text-transform: uppercase; margin: 0; }
    .inst-sub { font-size: 10pt; color: #475569; margin: 2px 0 0 0; }
    .inst-contact { font-size: 8.5pt; color: #64748b; margin-top: 4px; }
    
    .receipt-title-box { text-align: center; margin: 15px 0 20px 0; }
    .receipt-title { font-size: 13pt; font-weight: bold; color: #0369a1; text-transform: uppercase; letter-spacing: 1px; }
    .receipt-no { font-size: 10pt; font-family: monospace; color: #0f172a; margin-top: 4px; }
    
    .status-badge {
        display: inline-block;
        padding: 6px 14px;
        font-weight: bold;
        font-size: 11pt;
        border-radius: 4px;
        text-align: center;
    }
    .status-paid { background-color: #dcfce7; color: #15803d; border: 1px solid #86efac; }
    .status-unpaid { background-color: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }

    .info-card { width: 100%; border: 1px solid #cbd5e1; border-collapse: collapse; margin-bottom: 15px; }
    .info-card th { background-color: #f1f5f9; color: #334155; text-align: left; padding: 7px 10px; font-size: 9.5pt; border: 1px solid #cbd5e1; }
    .info-card td { padding: 7px 10px; font-size: 10pt; border: 1px solid #cbd5e1; }

    .items-table { width: 100%; border: 1px solid #cbd5e1; border-collapse: collapse; margin-top: 15px; }
    .items-table th { background-color: #0284c7; color: #ffffff; text-align: left; padding: 8px 10px; font-size: 10pt; border: 1px solid #0284c7; }
    .items-table td { padding: 9px 10px; font-size: 10pt; border: 1px solid #cbd5e1; }
    .items-table .total-row td { background-color: #f8fafc; font-weight: bold; border-top: 2px solid #0284c7; }

    .terbilang-box { background-color: #f8fafc; border: 1px dashed #94a3b8; padding: 10px; margin-top: 12px; font-style: italic; font-size: 9.5pt; color: #334155; }
    
    .signature-table { width: 100%; margin-top: 30px; border-collapse: collapse; }
    .signature-table td { vertical-align: top; width: 50%; }
    .footer-note { font-size: 8pt; color: #64748b; line-height: 1.4; }
    .stamp-box { border: 2px dashed #0284c7; border-radius: 8px; padding: 10px; text-align: center; color: #0284c7; font-weight: bold; font-size: 9pt; width: 200px; margin: 0 auto; }
</style>
</head>
<body>

<table class="header-table">
    <tr>
        <td style="width: 70px;">
            <div style="width: 55px; height: 55px; background-color: #0284c7; color: white; border-radius: 50%; text-align: center; line-height: 55px; font-size: 22pt; font-weight: bold;">U</div>
        </td>
        <td>
            <div class="inst-title">UNIVERSITAS DIAN NUSWANTORO</div>
            <div class="inst-sub">Fakultas Ilmu Komputer &bull; Biro Administrasi Keuangan (BAK)</div>
            <div class="inst-contact">Jl. Imam Bonjol No. 207 Semarang | Telp: (024) 3517261 | Email: sekretariat@dinus.ac.id</div>
        </td>
        <td style="text-align: right; width: 150px;">
            <span class="status-badge ' . ($isPaid ? 'status-paid' : 'status-unpaid') . '">
                ' . ($isPaid ? '✓ LUNAS' : '! BELUM LUNAS') . '
            </span>
        </td>
    </tr>
</table>

<div class="receipt-title-box">
    <div class="receipt-title">Bukti Pembayaran Keuangan Mahasiswa (Kuitansi)</div>
    <div class="receipt-no">NO. INVOICE: ' . htmlspecialchars($bill['bill_code'] ?? ('INV-' . $bill['id'])) . '</div>
</div>

<table class="info-card">
    <tr>
        <th style="width: 25%;">Nama Mahasiswa</th>
        <td style="width: 35%;"><strong>' . htmlspecialchars($bill['full_name']) . '</strong></td>
        <th style="width: 20%;">Tanggal Bayar</th>
        <td style="width: 20%;">' . ($isPaid ? $paidDate : '-') . '</td>
    </tr>
    <tr>
        <th>NIM</th>
        <td><strong style="font-family: monospace; font-size: 11pt;">' . htmlspecialchars($bill['nim']) . '</strong></td>
        <th>Metode Bayar</th>
        <td>' . htmlspecialchars($bill['payment_method'] ?? 'Virtual Account') . '</td>
    </tr>
    <tr>
        <th>Program Studi</th>
        <td>' . htmlspecialchars($bill['major_name']) . ' (' . htmlspecialchars($bill['degree']) . ')</td>
        <th>No. Virtual Account</th>
        <td><strong style="font-family: monospace; color: #0369a1;">' . htmlspecialchars($bill['va_number'] ?? '-') . '</strong></td>
    </tr>
    <tr>
        <th>Periode Akademik</th>
        <td>' . htmlspecialchars($bill['academic_year']) . '</td>
        <th>Diverifikasi Oleh</th>
        <td>' . htmlspecialchars($bill['verified_by'] ?? 'Sistem Otomatis') . '</td>
    </tr>
</table>

<table class="items-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">No</th>
            <th style="width: 47%;">Deskripsi Pembayaran</th>
            <th style="width: 20%; text-align: center;">Jatuh Tempo</th>
            <th style="width: 25%; text-align: right;">Jumlah (Rp)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center;">1</td>
            <td>
                <strong>' . htmlspecialchars($bill['bill_type']) . '</strong>
                <div style="font-size: 8.5pt; color: #64748b; margin-top: 3px;">Periode ' . htmlspecialchars($bill['academic_year']) . ' - Mahasiswa ' . htmlspecialchars($bill['nim']) . '</div>
                ' . (!empty($bill['notes']) ? '<div style="font-size: 8.5pt; color: #64748b;">Catatan: ' . htmlspecialchars($bill['notes']) . '</div>' : '') . '
            </td>
            <td style="text-align: center;">' . $dueDate . '</td>
            <td style="text-align: right; font-weight: bold;">Rp ' . $amountFormatted . '</td>
        </tr>
        <tr>
            <td style="text-align: center;">2</td>
            <td>Biaya Transaksi / Administrasi Bank</td>
            <td style="text-align: center;">-</td>
            <td style="text-align: right; font-weight: bold;">Rp 0</td>
        </tr>
        <tr class="total-row">
            <td colspan="3" style="text-align: right; font-size: 11pt;">TOTAL DIBAYAR :</td>
            <td style="text-align: right; font-size: 11pt; color: #0369a1;">Rp ' . $amountFormatted . '</td>
        </tr>
    </tbody>
</table>

<div class="terbilang-box">
    <strong>Terbilang:</strong> <em>' . $terbilangText . '</em>
</div>

<table class="signature-table">
    <tr>
        <td style="padding-right: 20px;">
            <div class="footer-note">
                <strong>Catatan Penting:</strong><br>
                1. Kuitansi ini merupakan bukti pembayaran resmi yang sah dan diterbitkan secara elektronik oleh Sistem Informasi Akademik Universitas Dian Nuswantoro.<br>
                2. Simpan tanda bukti pembayaran ini untuk keperluan administrasi akademik (pengisian KRS, registrasi ulang, dan ujian semester).<br>
                3. Sistem secara otomatis mencatat riwayat transaksi ke database keuangan terpadu.
            </div>
        </td>
        <td style="text-align: center;">
            <div style="font-size: 9.5pt; margin-bottom: 8px;">Semarang, ' . date('d F Y') . '</div>
            <div class="stamp-box">
                TERVERIFIKASI SISTEM<br>
                <span style="font-size: 7.5pt; font-weight: normal; color: #475569;">DIGITALLY SIGNED & VALIDATED</span><br>
                <span style="font-size: 8pt; font-family: monospace;">' . htmlspecialchars($bill['verified_by'] ?? 'VA-System (Auto)') . '</span>
            </div>
            <div style="font-size: 9.5pt; font-weight: bold; margin-top: 8px;">Biro Administrasi Keuangan</div>
        </td>
    </tr>
</table>

</body>
</html>
';

$mpdf->WriteHTML($html);
$pdfFilename = 'Kuitansi_' . preg_replace('/[^A-Za-z0-9]/', '_', $bill['bill_code'] ?? ('INV_' . $bill['id'])) . '.pdf';
$mpdf->Output($pdfFilename, 'I');
