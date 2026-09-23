<?php
/**
 * Database Migration & Seeder Script
 * Adds bill_code, payment_method, va_number, and notes to students_bills_data
 * Seeds initial UKT bill for student with NIM A12.2026.00001
 */

require_once __DIR__ . '/../config/db.php';

if (!$conn) {
    echo "❌ Error: Gagal terhubung ke Database Cloud.\n";
    exit(1);
}

echo "=== 🚀 MIGRASI STRUKTUR students_bills_data ===\n";

try {
    $conn->beginTransaction();

    // 1. Add Columns
    echo "1. Menambahkan kolom baru ke students_bills_data...\n";
    $conn->exec("ALTER TABLE students_bills_data ADD COLUMN IF NOT EXISTS bill_code VARCHAR(50) UNIQUE");
    $conn->exec("ALTER TABLE students_bills_data ADD COLUMN IF NOT EXISTS payment_method VARCHAR(30) DEFAULT NULL");
    $conn->exec("ALTER TABLE students_bills_data ADD COLUMN IF NOT EXISTS va_number VARCHAR(60) DEFAULT NULL");
    $conn->exec("ALTER TABLE students_bills_data ADD COLUMN IF NOT EXISTS notes TEXT DEFAULT NULL");
    echo "   -> Kolom bill_code, payment_method, va_number, dan notes siap.\n";

    // 2. Add Indexes
    echo "2. Membuat indeks pendukung...\n";
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_bills_student_id ON students_bills_data(student_id)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_bills_status ON students_bills_data(payment_status)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_bills_va ON students_bills_data(va_number)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_bills_code ON students_bills_data(bill_code)");
    echo "   -> Indeks berhasil dikonfigurasi.\n";

    // 3. Seed Tagihan untuk NIM A12.2026.00001
    echo "3. Memeriksa dan seeding data tagihan untuk NIM A12.2026.00001...\n";
    $findStudent = $conn->prepare("SELECT id, nim FROM students_data WHERE nim = ?");
    $findStudent->execute(['A12.2026.00001']);
    $student = $findStudent->fetch();

    if ($student) {
        $studentId = $student['id'];
        $academicYear = '2026/2027 Ganjil';
        $billType = 'UKT Pokok';
        $amount = 5000000; // Rp 5.000.000
        $dueDate = date('Y-m-d', strtotime('+30 days'));
        
        // Konversi NIM ke nomor VA Mandiri default (008.112.2026.00001)
        // A=1, B=2, C=3
        $numericNim = preg_replace_callback('/^[A-Za-z]/', function($m) {
            return ord(strtoupper($m[0])) - 64; // A -> 1
        }, $student['nim']);
        $defaultVa = '008.' . $numericNim;
        $billCode = 'INV-20261-' . str_replace('.', '', $student['nim']);

        // Cek apakah tagihan sudah ada
        $checkBill = $conn->prepare("SELECT id FROM students_bills_data WHERE student_id = ? AND academic_year = ? AND bill_type = ?");
        $checkBill->execute([$studentId, $academicYear, $billType]);
        $existingBillId = $checkBill->fetchColumn();

        if (!$existingBillId) {
            $insertBill = $conn->prepare("
                INSERT INTO students_bills_data 
                (student_id, bill_code, academic_year, bill_type, amount, due_date, payment_status, va_number, notes)
                VALUES (?, ?, ?, ?, ?, ?, 'Unpaid', ?, 'Tagihan UKT Pokok Perdana Semester Ganjil 2026/2027')
            ");
            $insertBill->execute([$studentId, $billCode, $academicYear, $billType, $amount, $dueDate, $defaultVa]);
            echo "   ✅ Tagihan berhasil dibuat untuk NIM {$student['nim']}!\n";
            echo "      - Kode Invoice: {$billCode}\n";
            echo "      - Nominal: Rp " . number_format($amount, 0, ',', '.') . "\n";
            echo "      - VA Mandiri: {$defaultVa}\n";
            echo "      - Status: Unpaid\n";
        } else {
            echo "   ℹ️ Tagihan untuk NIM {$student['nim']} pada {$academicYear} ({$billType}) sudah ada (ID: {$existingBillId}).\n";
        }
    } else {
        echo "   ⚠️ Mahasiswa dengan NIM A12.2026.00001 tidak ditemukan di students_data. Lewati seeding tagihan perorangan.\n";
    }

    $conn->commit();
    echo "\n=== 🎉 MIGRASI & SEEDING SELESAI DENGAN SUKSES! ===\n";
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "❌ Terjadi kesalahan saat migrasi: " . $e->getMessage() . "\n";
    exit(1);
}
