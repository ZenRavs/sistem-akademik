# 🚀 PROGRESSION - Sistem Informasi Akademik (SIA) & PMB Online

Dokumen ini mencatat **progres harian pengerjaan sistem**, pencapaian terkini, catatan teknis penting, serta **rencana dan agenda kerja harian (Daily Sprint/Action Plan)**.

> 📂 Dokumen ini juga disinkronkan di [docs/PROGRESSION.md](file:///d:/PROJECTS/app_web/sistem_akademik/docs/PROGRESSION.md).

---

## 📌 Ringkasan Status Sistem
* **Status Saat Ini**: `v0.8.0` (Stable Foundation & PMB Online Secured)
* **Basis Data**: PostgreSQL (Neon Cloud DB) - Arsitektur 10 Tabel
* **Fokus Aktif**: Manajemen Keuangan & Tagihan Mahasiswa (*Students Billing Management*)

---

## 📅 Log Progres Harian

### 🕒 [2026-09-22 - 2026-09-23]
#### ✅ Pencapaian Hari Ini (Today's Accomplishments)
1. **Refactoring & Penguatan Logika PMB Online (`v0.8.0`)**:
   - **Database Transactions**: Menerapkan `$conn->beginTransaction()`, `$conn->commit()`, dan `$conn->rollBack()` pada pembuatan akun, pembaruan profil pendaftar, dan verifikasi kelulusan untuk menjamin integritas data (ACID).
   - **Fix Bug Sesi & User ID**: Memperbaiki pemetaan `$applicant['user_id']` pada login agar ID akun `users_credential` tidak tertimpa oleh ID `pmb_data`.
   - **Keamanan Upload Berkas PMB**:
     - Pengecekan MIME type asli (`mime_content_type`) dan whitelist ekstensi aman (`jpg`, `jpeg`, `png`, `webp`, `pdf`) untuk mencegah eksploitasi RCE.
     - Pemisahan direktori fisik berkas menjadi `public/uploads/pmb_docs/certificates/` dan `public/uploads/pmb_docs/payments/`.
     - Penambahan konstanta URL & Path terpusat di [config/paths.php](file:///d:/PROJECTS/app_web/sistem_akademik/config/paths.php).
   - **Panel Admin PMB (`views/admin/applicants.php`)**:
     - Penambahan seksi berkas pendaftaran dan bukti pembayaran pada modal detail pendaftar.
     - Perbaikan auto-generate NIM (`ORDER BY id DESC`) agar pembuatan NIM urut dan bebas konflik.
2. **Refactoring Keamanan Sesi & Status Akun (`v0.7.0`)**:
   - Standardisasi `account_status` (`active`, `suspended`, `banned`, `pending_activation`, `archived`).
   - Single-device login enforcement (`session_token`), auto-logout idle 30 menit, dan proteksi brute-force 5x percobaan salah (`failed_attempts` & `locked_until`).
   - Sinkronisasi timezone seragam `Asia/Jakarta` (WIB) pada PHP dan PostgreSQL Neon.
3. **Penyelarasan Modular Views & Navigasi (`v0.5.0` - `v0.6.0`)**:
   - Restrukturisasi folder tampilan per peran: `views/admin/`, `views/student/`, `views/lecturer/`, `views/applicant/`.
   - Centralized dispatcher di `portal.php`.
4. **Dokumentasi & Arsitektur**:
   - Peninjauan menyeluruh riwayat rilis pada [docs/CHANGELOGS.md](file:///d:/PROJECTS/app_web/sistem_akademik/docs/CHANGELOGS.md).
   - Diskusi perancangan PRD (Product Requirements Document) dan standarisasi dokumen pelacak progres (`PROGRESSION.md`).

---

## 🎯 Rencana & Agenda Besok (Tomorrow's Agenda)

### 💳 Fokus: Modul Tagihan & Pembayaran Mahasiswa di Role Admin (`students_bills_data`)
Target utama adalah membangun fungsionalitas pengelolaan keuangan/tagihan akademik mahasiswa yang dikontrol penuh oleh Admin/TU.

#### 1. Antarmuka Manajemen Tagihan Admin (`views/admin/bills.php` / `students_bills.php`)
- [ ] Membuat tampilan data master tagihan mahasiswa dengan filter berdasarkan:
  - Tahun Angkatan / Tahun Akademik
  - Program Studi (`majors_data`)
  - Semester Berjalan (Ganjil / Genap)
  - Status Pembayaran (`Belum Lunas`, `Lunas`, `Menunggu Verifikasi`, `Dibatalkan`)
- [ ] Menampilkan ringkasan metrik keuangan: Total Tagihan Terbit, Total Pembayaran Diterima, dan Total Tagihan Tertunggak.

#### 2. Fitur Penerbitan Tagihan (Generate Bills)
- [ ] **Generate Tagihan Massal**: Admin dapat menerbitkan tagihan UKT/SPP sekaligus untuk satu angkatan/prodi/semester tertentu.
- [ ] **Terbitkan Tagihan Individual**: Form modal untuk menambahkan tagihan khusus (misal: denda, cuti akademik, praktikum tambahan, atau biaya daftar ulang konversi).
- [ ] Validasi pencegahan tagihan ganda untuk mahasiswa dan semester yang sama.

#### 3. Alur Verifikasi Pembayaran & Pencatatan Bukti Bayar
- [ ] Modal verifikasi pembayaran untuk admin:
  - Input tanggal bayar, metode pembayaran (Transfer Bank / Tunai / Virtual Account), nomor referensi/transaksi, dan catatan admin.
  - Opsi unggah / tinjau bukti transfer mahasiswa.
- [ ] Tombol aksi cepat: **Verifikasi Lunas** (`status = 'Lunas'`) dan **Batalkan Tagihan**.

#### 4. Integrasi & Sinkronisasi dengan Status Akademik
- [ ] Saat tagihan semester aktif disetujui/lunas:
  - Otomatis memperbarui kolom `payment_status = 'Lunas'` di tabel `students_data`.
  - Membuka kunci hak akses pengisian KRS mahasiswa untuk semester bersangkutan (prasyarat KRS).
- [ ] Jika tagihan belum lunas, berikan indikator peringatan penangguhan KRS.

#### 5. Reporting / Cetak Kuitansi Pembayaran
- [ ] Menyiapkan modul cetak bukti lunas / kuitansi tagihan PDF ([src/reporting/pdf_bill_receipt.php](file:///d:/PROJECTS/app_web/sistem_akademik/src/reporting/pdf_bill_receipt.php)) yang dapat dicetak oleh Admin maupun Mahasiswa.

---

## 📋 Catatan Teknis & Hal Penting (Developer Notes)
* **Tabel Terkait**:
  - `students_bills_data`: (`id`, `student_id`, `bill_code`, `semester`, `academic_year`, `amount`, `payment_status`, `payment_date`, `payment_proof`, `verified_by`, `notes`, `created_at`, `updated_at`).
  - `students_data`: sinkronisasi `payment_status`.
* **Koneksi Neon PostgreSQL**: Selalu gunakan transaksi PDO (`beginTransaction()` & `commit()`) saat melakukan verifikasi pembayaran dan mutasi status mahasiswa.
* **Format Kode Tagihan Baku**: Contoh: `INV-{TAHUN}-{SEMESTER}-{NIM}` (misal: `INV-20261-A11202600001`).

---

## 🔭 Backlog Mendatang (Next Milestones)
- [ ] Penyusunan dokumen resmi `docs/PRD.md`.
- [ ] Antarmuka Portal Tagihan & Pembayaran di sisi Mahasiswa (`views/student/bills.php`).
- [ ] Modul Perwalian & Approval KRS oleh Dosen Wali (`views/lecturer/krs_approval.php`).
- [ ] Modul Input Nilai Perkuliahan & Cetak KHS (`views/lecturer/grading.php` & `views/student/grades.php`).
