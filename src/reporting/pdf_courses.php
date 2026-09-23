<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/db.php';

$stmt = $conn->prepare("
    SELECT c.course_code AS code, c.course_name AS name, c.course_type AS type, 
           c.credits AS sks, c.semester AS smt, m.major_code
    FROM courses_data c
    JOIN majors_data m ON c.major_id = m.id
    ORDER BY c.course_name ASC
");
$stmt->execute();
$rows = $stmt->fetchAll();

$mpdf = new \Mpdf\Mpdf();
$filename = 'report-courses-' . date("Y-m-d_H-i-s") . '.pdf';
$i = 1;
$data = '';
foreach ($rows as $row) {
    $data .=
        '<tr>
            <td style="width: 50px; border: 1px solid black; text-align: center;">' . $i . '</td>
            <td style="width: 100px; border: 1px solid black;">' . htmlspecialchars($row['code']) . '</td>
            <td style="border: 1px solid black;">' . htmlspecialchars($row['name']) . '</td>
            <td style="width: 60px; border: 1px solid black; text-align: center;">' . htmlspecialchars($row['type']) . '</td>
            <td style="width: 50px; border: 1px solid black; text-align: center;">' . htmlspecialchars($row['sks']) . '</td>
            <td style="width: 50px; border: 1px solid black; text-align: center;">' . htmlspecialchars($row['smt']) . '</td>
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
        <div style="font-size: 12px; font-weight: normal;">Laporan Data Mata Kuliah</div>
    </div>
    <br>
    <table class="tabledata">
        <thead>
            <tr>
                <th>#</th>
                <th>Kode</th>
                <th>Nama Mata Kuliah</th>
                <th>Tipe</th>
                <th>SKS</th>
                <th>Semester</th>
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
