<?php
require_once __DIR__ . '/paths.php';

// Function loader sederhana untuk membaca file .env tanpa composer dependency
if (!function_exists('loadEnv')) {
    function loadEnv($filePath) {
        if (!file_exists($filePath)) {
            return;
        }
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv("$name=$value");
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }
}

// Load file .env dari folder root
$envPath = __DIR__ . '/../.env';
loadEnv($envPath);

$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '5432';
$dbname = getenv('DB_NAME') ?: 'neondb';
$user = getenv('DB_USER') ?: 'postgres';
$pass = getenv('DB_PASS') ?: '';
$sslmode = getenv('DB_SSLMODE') ?: 'require';

// Neon PostgreSQL requires endpoint ID option if SNI is not enabled in local libpq
$endpoint = explode('.', $host)[0];

$conn = null;

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // PDO Connection String ke PostgreSQL Neon Cloud dengan Neon Endpoint Option
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=$sslmode;options='endpoint=$endpoint'";
    $conn = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => true,
    ]);

    date_default_timezone_set('Asia/Jakarta');
    // Deallocate any cached query plans to prevent PostgreSQL SQLSTATE[0A000] cached plan result type error
    try {
        $conn->exec("SET TIME ZONE 'Asia/Jakarta';");
        $conn->exec("DEALLOCATE ALL;");
    } catch (Exception $e) {
        // Silently ignore if no cached plans
    }

    // Inisialisasi arsitektur 10 tabel terstandarisasi
    // Eksekusi skrip migrasi mandiri bila diperlukan: php migrate_to_10_tables.php

} catch (PDOException $e) {
    $conn = null;
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    error_log("Database Connection Error: " . $e->getMessage());
    $_SESSION['error'] = "Gagal terhubung ke Database Cloud. Pastikan koneksi internet Anda aktif.";
}
