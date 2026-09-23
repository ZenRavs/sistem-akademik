<?php
require_once __DIR__ . '/config/db.php';

echo "<h2>Inisialisasi Database Neon PostgreSQL...</h2>";

try {
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    if (!$sql) {
        throw new Exception("File schema.sql tidak ditemukan!");
    }

    if (!$conn) {
        $err = isset($_SESSION['error']) ? $_SESSION['error'] : 'Gagal terhubung ke database. Periksa konfigurasi .env!';
        throw new Exception($err);
    }
    $conn->exec($sql);
    echo "<p style='color: green;'><strong>BERHASIL:</strong> Tabel-tabel dan data awal admin berhasil dibuat di Neon Database!</p>";
    echo "<hr>";
    echo "<p>Akun Admin Default:</p>";
    echo "<ul>";
    echo "<li><strong>Username:</strong> 0000.0001</li>";
    echo "<li><strong>Password:</strong> localadmin001</li>";
    echo "</ul>";
    echo "<p><a href='index.php'>Ke Halaman Login</a></p>";
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>GAGAL:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
