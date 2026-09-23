<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/db.php';

$stmt = $conn->prepare("SELECT * FROM krs_offers ORDER BY id DESC");
$stmt->execute();
$rows = $stmt->fetchAll();

$mpdf = new \Mpdf\Mpdf(['orientation' => 'L']);
$filename = 'report-krs-offers-' . date("Y-m-d_H-i-s") . '.pdf';
$i = 1;
$data = '';
foreach ($rows as $row) {
    // Lookup course from courses_data
    $courseStmt = $conn->prepare("SELECT course_name AS name, credits AS sks FROM courses_data WHERE course_code = ?");
    $courseStmt->execute([$row['course']]);
    $course = $courseStmt->fetch() ?: ['name' => $row['course'], 'sks' => '-'];

    // Lookup lecturer from lecturers_data & personal_profiles
    $lecturerStmt = $conn->prepare("
        SELECT p.full_name AS name 
        FROM lecturers_data l
        JOIN users_credential u ON l.user_id = u.id
        JOIN personal_profiles p ON p.user_id = u.id
        WHERE l.npp = ?
    ");
    $lecturerStmt->execute([$row['lecturer']]);
    $lecturer = $lecturerStmt->fetch() ?: ['name' => $row['lecturer']];

    $data .=
        '<tr>
            <td style="text-align: center;">' . $i . '</td>
            <td>' . htmlspecialchars($course['name']) . '</td>
            <td style="text-align: center;">' . htmlspecialchars($course['sks']) . '</td>
            <td>' . htmlspecialchars($lecturer['name']) . '</td>
            <td style="text-align: center;">' . htmlspecialchars($row['class_group']) . '</td>
            <td style="text-align: center;">' . htmlspecialchars($row['days_sched1'] . ' / ' . $row['hours_sched1'] . ' / ' . $row['class_room1']) . '</td>
            <td style="text-align: center;">' . htmlspecialchars(($row['days_sched2'] ? $row['days_sched2'] . ' / ' . $row['hours_sched2'] . ' / ' . $row['class_room2'] : '-')) . '</td>
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
th, td { border: 1px solid black; padding: 6px; font-size: 11px; }
thead tr { background-color: #007bff; color: #ffffff; }
</style>
</head>
<body>
<div class="container">
    <div class="title">
        <div>UNIVERSITAS DIAN NUSWANTORO</div>
        <div>Fakultas Ilmu Komputer</div>
        <div style="font-size: 12px; font-weight: normal;">Laporan Penawaran KRS (Jadwal Kuliah)</div>
    </div>
    <br>
    <table class="tabledata">
        <thead>
            <tr>
                <th>#</th>
                <th>Mata Kuliah</th>
                <th>SKS</th>
                <th>Dosen Pengampu</th>
                <th>Kelompok</th>
                <th>Jadwal Utama</th>
                <th>Jadwal Tambahan</th>
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
