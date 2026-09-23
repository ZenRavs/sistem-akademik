<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/db.php';

$stmt = $conn->prepare("
    SELECT l.npp, p.full_name AS name, m.major_code AS homebase, l.status
    FROM lecturers_data l
    JOIN users_credential u ON l.user_id = u.id
    JOIN personal_profiles p ON p.user_id = u.id
    JOIN majors_data m ON l.major_id = m.id
    ORDER BY p.full_name ASC
");
$stmt->execute();
$rows = $stmt->fetchAll();

$mpdf = new \Mpdf\Mpdf();
$filename = 'report-lecturers-' . date("Y-m-d_H-i-s") . '.pdf';
$i = 1;
$data = '';
foreach ($rows as $row) {
    $data .=
        '<tr>
            <td style="width: 50px; text-align: center;">' . $i . '</td>
            <td>' . htmlspecialchars($row['npp']) . '</td>
            <td>' . htmlspecialchars($row['name']) . '</td>
            <td style="text-align: center;">' . htmlspecialchars($row['homebase']) . '</td>
            <td style="text-align: center;">' . htmlspecialchars($row['status']) . '</td>
        </tr>';
    $i++;
}
$html = '
<!DOCTYPE html>
<html>
<head>
<style>
body { font-family: sans-serif; }
.container { width: 100%; margin: 0 auto; }
.title { text-align: center; font-size: 18px; font-weight: bold; }
.tabledata { border: 1px solid black; border-collapse: collapse; width: 100%; }
th, td { border: 1px solid black; padding: 8px; }
thead tr { background-color: #007bff; color: #ffffff; }
</style>
</head>
<body>
<div class="container">
    <div class="title">
        <div>UNIVERSITAS DIAN NUSWANTORO</div>
        <div>Fakultas Ilmu Komputer</div>
        <div style="font-size: 12px; font-weight: normal;">Laporan Data Dosen</div>
    </div>
    <br>
    <table class="tabledata">
        <thead>
            <tr>
                <th>#</th>
                <th>NPP</th>
                <th>Nama Dosen</th>
                <th>Homebase</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            ' . $data . '
        </tbody>
    </table>
</div>
</body>
</html>
';

$mpdf->WriteHTML($html);
$mpdf->Output($filename, 'I');
