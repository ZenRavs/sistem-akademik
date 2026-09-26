# 📜 CHANGELOG - Sistem Informasi Akademik (SIA) & PMB Online

Semua perubahan penting pada proyek **Sistem Informasi Akademik & PMB Online** didokumentasikan di dalam file ini.

Format dokumen ini mengacu pada standar [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) dan mengikuti kaidah **GitHub Flavored Markdown (GFM)**.

---

## 📑 Daftar Isi
- [v0.9.3 - 2026-09-26](#v093---2026-09-26)
- [v0.9.2 - 2026-09-25](#v092---2026-09-25)
- [v0.9.1 - 2026-09-23](#v091---2026-09-23)
- [v0.9.0 - 2026-09-23](#v090---2026-09-23)
- [v0.8.0 - 2026-09-22](#v080---2026-09-22)
- [v0.7.0 - 2026-09-22](#v070---2026-09-22)
- [v0.6.0 - 2026-09-22](#v060---2026-09-22)
- [v0.5.0 - 2026-09-22](#v050---2026-09-22)
- [v0.4.0 - 2026-09-22](#v040---2026-09-22)
- [v0.3.0 - 2026-09-21](#v030---2026-09-21)
- [v0.2.0 - 2026-09-21](#v020---2026-09-21)
- [v0.1.2026 - September 2026](#v012026---2026-09-20)
- [v0.1.1 - Agustus 2026](#v011---2026-08-15)
- [v0.1.0 - Inisialisasi Proyek](#v010---2025-02-14)

---

## [v0.9.3] - 2026-09-26

### 🔐 Manajemen Kredensial User, Proteksi Role, & Realtime Terminate Session
- **Pembaruan Skema Database (`schema.sql` & `scripts/add_pswd_reset_column.php`)**:
  - Menambahkan kolom `pswd_reset` (`VARCHAR(255) NULL`) ke tabel `users_credential` sebagai persiapan infrastruktur reset password.
  - Membuat skrip migrasi `scripts/add_pswd_reset_column.php` untuk eksekusi penambahan kolom secara aman pada database aktif.
- **Form Kredensial Baru & Integrasi Profil Personal (`views/admin/form/new_users_credential.php`)**:
  - Mengganti form lama `user_form.php` menjadi `new_users_credential.php` dan menyelaraskan struktur input dengan skema tabel `users_credential` & `personal_profiles`.
  - Menghubungkan tombol *"Tambah User Baru"* di halaman `views/admin/users.php` langsung ke form `new_users_credential.php`.
  - Menambahkan dukungan pengisian data profil personal awal (`full_name`, `nik`, `gender`, `pob`, `dob`, `religion`, `phone`, `ktp_address`) saat pembuatan akun manual.
  - Membatasi pembuatan akun manual hanya untuk peran `admin` dan `superadmin` (dan hanya `superadmin` yang diizinkan menambah user baru).
- **Aturan Proteksi Peran & Pembatasan Hapus Akun (`src/api.php` & `views/admin/users.php`)**:
  - Menambahkan proteksi backend: Menolak penghapusan permanen untuk akun ber-role `dosen`, `pegawai` (`admin`/`superadmin`), dan `mahasiswa`. Role utama ini hanya dapat dinonaktifkan (`suspended`/`banned`) melalui modal edit.
  - Menolak pengeditan manual untuk akun di luar role `admin` dan `superadmin`.
  - Menghilangkan tombol aksi (edit/hapus/terminate) pada tabel untuk akun pengguna yang sedang login (*akun sendiri*).
  - Merapikan tabel pengguna dengan menghapus badge *"Dikelola di Master Data"*.
- **Penyempurnaan Modal Admin & Kontrol Sesi (`views/admin/users.php`)**:
  - Modal *"Lihat Kredensial User"*: Menampilkan data kredensial non-editable lengkap dengan fitur toggle mata (*censored/view password*).
  - Modal *"Edit User Credential"*: Mengganti field password utama dengan field *Update New Password* & *Confirm Password* sebagai placeholder fitur lupa/reset password.
  - Tombol *"Personal Profile"*: Diarahkan langsung ke halaman profil personal akun terkait.
  - Tombol *"Terminate Sesi (Force Logout)"*: Fitur interaktif khusus `superadmin` untuk memutus sesi aktif pengguna secara paksa.
  - Tombol *"Refresh Data Tabel"*: Menambahkan tombol Refresh interaktif (`#refreshBtn`) berikon putar animasi CSS (`bi-arrow-clockwise`) di header tabel untuk memuat ulang data pengguna secara realtime tanpa perlu reload seluruh halaman.
- **Perbaikan Keamanan Sesi Aktif & Realtime Invalidation (`portal.php` & `src/api.php`)**:
  - **Fix Bug Invalidation Token (`portal.php`)**: Memperbaiki logika *Single-Device Enforcement*. Ketika `session_token` di DB bernilai `NULL` (akibat force logout atau pengosongan DB), sistem kini secara otomatis menghancurkan sesi browser dan me-redirect pengguna ke `index.php` saat refresh.
  - **Realtime AJAX Background Session Check (`src/api.php?req=sessionCheck`)**: Meng-update endpoint `sessionCheck` untuk me-verifikasi `session_token` dan `account_status` ke database setiap 10 detik. Jika token di-clear, API mengembalikan respon `'terminated'` dan JavaScript langsung me-logout pengguna secara instan tanpa perlu refresh.
- **Fitur Edit Profil Mandiri (`portal.php`, `views/layouts/*`, `src/api.php`)**:
  - **Opsi Dropdown Quick User**: Menambahkan item menu `"Edit Profile"` ke dalam dropdown user avatar (`<!-- Quick User Dropdown -->`) di topbar header untuk semua layout (`admin_layout.php`, `student_layout.php`, `lecturer_layout.php`).
  - **Modal Interaktif `editMyProfileModal`**: Menyediakan modal mandiri berisi bidang data akun `users_credential` (Username, Role, Email, & Toggle Ganti Password dengan verifikasi password lama) dan data diri `personal_profiles` (Upload Pas Foto Profil Baru, Nama Lengkap, NIK 16 digit, Jenis Kelamin, Tempat/Tgl Lahir, Agama, Status Nikah/Kerja, No. WA, Alamat KTP & Domisili).
  - **Endpoint Backend Atomis (`getMyProfileDetail` & `updateMyProfile`)**: Menangani pembacaan & pembaruan data kredensial serta profil personal secara atomic dengan validasi duplikasi email, format NIK, sanitasi upload foto profil, dan pembaruan instan pada variabel sesi PHP (`$_SESSION['user']`).

---

## [v0.9.2] - 2026-09-25

### 🛡️ Pengetatan Validasi Form, Keyboard Numerik, & Integritas Transaksi Berkas PMB
- **Form Pendaftaran Mahasiswa Baru (`views/applicant/portal.php`)**:
  - **Penerapan All Fields Mandatory**: Seluruh field (termasuk NIK 16-digit, NISN 10-digit, Nama Ibu, Nama Ayah, No. WA Orang Tua, Asal Sekolah, Jurusan, Nilai Akhir, Alamat KTP, Alamat Domisili, dan Berkas Upload) kini wajib diisi (`required`) disertai indikator `<span class="text-danger">*</span>`.
  - **Unselected Default Status**: Dropdown `marital_status` dan `job_status` pada portal pendaftar kini secara default bernilai unselected (`-- Pilih Status --`) saat data awal bernilai `NULL`.
  - **Numeric Keyboard & Realtime Filter**: Menambahkan atribut `inputmode="numeric"`/`inputmode="decimal"`, `pattern`, `maxlength`, dan filter JavaScript `oninput` pada seluruh field angka (NIK, NISN, Phone, Parent Phone, Nilai Akhir) untuk memicu keyboard numerik di seluler dan melarang pengetikan huruf/karakter non-angka.
  - **Ekstensi Upload Ijazah**: Menyelaraskan atribut `accept` pada `certificate_file` menjadi `.pdf,image/jpeg,image/png,image/webp`.
- **Panel Modal Admin Pendaftar (`views/admin/applicants.php`)**:
  - **Tombol Refresh Data**: Menambahkan tombol *"Refresh Data"* (`#refreshBtn`) berdesain pill outline primary dengan ikon putar (`bi-arrow-clockwise`) pada header kartu untuk memuat ulang data pendaftar terkini secara instan.
  - **Relokasi Badge Prodi Pilihan**: Menghapus seksi *"🎯 3. Program Studi Pilihan"* dari grid kanan dan memindahkan nama Program Studi Pilihan langsung ke bawah ID Pendaftar (`#detail_app_id`) pada kartu profil kiri sebagai badge aksen biru (`badge bg-primary rounded-pill px-3 py-2`).
  - **Pembaruan Label**: Mengubah label *"No. HP / WhatsApp Ayah"* menjadi **"No. HP / WhatsApp Orang Tua"**.
  - **Fallback Default Standard**: Mengubah nilai fallback `detail_marital_status` dan `detail_job_status` pada modal detail pendaftar menjadi **`"-"`** jika data null/kosong.
  - **Penataan Penomoran Modal**: Merapikan penomoran seksi modal detail yang tersisa menjadi Seksi 1 hingga Seksi 6.
- **Backend Core Handler (`src/api.php`)**:
  - **Sanitasi & Validasi Regex Ketat (`applicantUpdateProfile`)**:
    - Menerapkan fungsi sanitasi `trim(strip_tags(...))` pada seluruh variabel teks.
    - Menambahkan validasi regex PHP ketat: NIK (harus 16 angka), NISN (harus 10 angka), No. HP & Parent Phone (harus 10-15 angka), Nilai Akhir (rentang 0.00 - 100.00).
    - Memastikan penanganan `nik` unik di PostgreSQL agar aman dari exception `SQLSTATE[23505]`.
  - **Integritas Berkas Deferred File Operations**:
    - Menunda proses penghapusan berkas lama (`@unlink`) hingga transaksi database `commit()` berhasil dijalankan.
    - Menambahkan pembersihan berkas baru (`@unlink`) secara otomatis jika terjadi `rollBack()` pada transaksi DB untuk mencegah *broken links* dan file sampah (*orphaned files*).
  - **Perbaikan Atomisitas Generator NIM (`verifyApplicant`)**:
    - Menggunakan fungsi SQL `MAX(CAST(SPLIT_PART(nim, '.', 3) AS INTEGER))` untuk mengambil urutan numerik NIM terbesar secara atomic, mencegah terjadinya duplikasi NIM akibat race condition.

---

## [v0.9.1] - 2026-09-23

### 🎨 Perombakan Antarmuka & Modul Data Utama (Bootstrap 5.3 Modernization)
- **Portal Applicant (`views/applicant/portal.php`)**:
  - Tampilan diperbarui penuh menggunakan layout kartu bersudut membulat (*rounded-4*) dan skema bayangan modern (*shadow-sm*).
  - Implementasi *Progress Stepper* adaptif, form unggah dokumen, serta *Data Locking* untuk mencegah perubahan data saat pendaftaran diproses (*Pending/Approved*).
  - Dinamisasi dropdown pilihan Program Studi yang langsung diambil dari tabel `majors_data` pada database.
- **Standarisasi Tampilan Data Administrasi Admin**:
  - Halaman `students.php`, `lecturers.php`, `courses.php`, `course_schedule.php`, `users.php`, dan `applicants.php` ditingkatkan layoutnya dengan format `card` seragam yang bersih.
  - Implementasi warna adaptif Light/Dark Mode menyeluruh: menghapus *hardcode* warna kelas lama (seperti `.table-dark`, `#ffffff`) dan beralih ke variabel semantik `.bg-body`, `.text-body`, `.table-light` pada header tabel.
  - Penambahan placeholder aksi *"Tambah User Baru"* pada halaman manajemen (*users*).
- **Perbaikan Minor (Bugfixes)**:
  - **Fix Bug Separasi Login Role Mahasiswa vs Pegawai**:
    - Menambahkan elemen tersembunyi `login_type` (`staff` vs `student`) pada form login [index.php](file:///d:/PROJECTS/app_web/sistem_akademik/index.php).
    - Memperketat validasi otentikasi pada endpoint `userLogin` di [src/api.php](file:///d:/PROJECTS/app_web/sistem_akademik/src/api.php) untuk mengisolasi form login per peran.
    - Pegawai/Dosen yang mencoba login dari Form Mahasiswa akan ditolak dengan pesan kesalahan `"Mahasiswa tidak ditemukan!"`.
    - Mahasiswa yang mencoba login dari Form Pegawai akan ditolak dengan pesan kesalahan `"Pegawai tidak ditemukan!"`.
    - Menjaga tab aktif saat terjadi kesalahan login dengan parameter `?tab=student` atau `?tab=staff`.
  - Mengatasi masalah *white space* (ruang kosong berlebih) pada bagian bawah (*footer*) Portal Applicant saat digulir ke bawah, serta merapikan label keterangan footer demo.

---

## [v0.9.0] - 2026-09-23

### 💳 Modul Manajemen Tagihan & Virtual Account Mahasiswa (Auto-Verify Engine)
- **Konfigurasi Metode Pembayaran Dinamis Berbasis JSON (`config/payment_methods.json`)**:
  - Memisahkan konfigurasi channel pembayaran dari kode PHP hardcoded ke berkas konfigurasi mandiri [config/payment_methods.json](file:///d:/PROJECTS/app_web/sistem_akademik/config/payment_methods.json).
  - Menyediakan konfigurasi terpusat untuk Virtual Account (Bank Mandiri `008`, BRI `002`, BCA `014`) serta metode manual (Tunai/Kasir TU, Transfer Bank Manual).
- **Formula Virtual Account Baku & Konversi Huruf NIM Otomatis**:
  - Menerapkan format standar Virtual Account: `{kode_bank}.{nim_numerik}`.
  - Mengonversi otomatis huruf awalan NIM ke nilai numerik (`A` = `1`, `B` = `2`, `C` = `3`, dst.) sehingga NIM `A12.2026.00001` menjadi `112.2026.00001`.
  - Generator otomatis `generateStudentVaList($nim)` menyusun daftar nomor VA:
    - **Bank Mandiri**: `008.112.2026.00001`
    - **Bank BRI**: `002.112.2026.00001`
    - **Bank BCA**: `014.112.2026.00001`
- **Engine Auto-Verify Virtual Account Realtime (`payBillViaVA` di `src/api.php`)**:
  - Mengimplementasikan endpoint simulasi pembayaran Virtual Account instan: saat dibayar melalui salah satu nomor VA, status tagihan langsung bermutasi menjadi `'Paid'`, nomor VA tercatat, `paid_at = CURRENT_TIMESTAMP`, dan `verified_by = 'VA-System (Auto)'` tanpa memerlukan verifikasi manual petugas TU.
  - Sinkronisasi otomatis ke status akademik: jika tagihan adalah UKT Pokok atau Daftar Ulang, sistem otomatis mengeksekusi `UPDATE students_data SET payment_status = 'Lunas'` untuk membuka hak akses akademik mahasiswa.
  - Menyediakan endpoint alternatif `verifyStudentBill` sebagai opsi pencatatan manual kasir TU (Tunai / EDC).
- **Penerbitan Tagihan Otomatis & Massal**:
  - Integrasi otomatis saat pendaftar PMB disetujui (*Approve*) di `verifyApplicant`: sistem langsung menerbitkan tagihan perdana UKT Pokok beserta kode VA bawaan.
  - Endpoint `generateMassBills`: Admin dapat menerbitkan tagihan massal untuk satu angkatan/prodi/semester sekaligus dengan validasi anti-duplikasi.
  - Endpoint `createStudentBill`: Form penambahan tagihan khusus/perorangan.
  - Endpoint `cancelStudentBill`: Pembatalan tagihan yang belum lunas.
- **Antarmuka Admin Tagihan Keuangan Mahasiswa (`views/admin/students_bills.php`)**:
  - KPI Metric Cards realtime: Total Tagihan Terbit, Total Pembayaran Diterima (Lunas), Total Tagihan Tertunggak.
  - Filter interaktif: Tahun Akademik, Program Studi, Metode Pembayaran (dinamis dari JSON), Status Tagihan, dan pencarian cepat (NIM, Nama, No. Invoice, No. VA).
  - Modal interaktif Detail Tagihan & Virtual Account:
    - Menampilkan kartu informasi masing-masing bank VA lengkap dengan tombol salin cepat (*copy-to-clipboard*).
    - Tab simulasi bayar VA realtime (*Auto-Verify*) dan tab verifikasi manual kasir.
  - Integrasi navigasi menu baru **"Tagihan UKT"** (`bi-wallet2`) di [views/layouts/admin_layout.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/layouts/admin_layout.php).
  - Integrasi tombol aksi cepat **`UKT`** pada tabel data mahasiswa [views/admin/students.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/admin/students.php) dan API `fetchStudents`/`searchStudent`.
- **Modul Cetak Kuitansi Resmi PDF (`src/reporting/pdf_bill_receipt.php`)**:
  - Menerbitkan bukti kuitansi pembayaran sah dengan format standar Universitas Dian Nuswantoro berbasis mPDF.
  - Dilengkapi rincian pembayaran, nominal terbilang otomatis Bahasa Indonesia, rincian VA/Metode, dan stempel tanda tangan elektronik terverifikasi sistem.
- **Konfigurasi Pricelist Keuangan FIK Dinamis (`config/tuition_fees.json`)**:
  - Menyusun file pricelist terpusat [config/tuition_fees.json](file:///d:/PROJECTS/app_web/sistem_akademik/config/tuition_fees.json) untuk Fakultas Ilmu Komputer (FIK).
  - Mendukung tarif UKT Pokok yang berbeda untuk tiap Program Studi (Prodi):
    - **Teknik Informatika (S1 - A11)**: Rp 5.500.000
    - **Sistem Informasi (S1 - A12)**: Rp 5.000.000
    - **Desain Komunikasi Visual (S1 - A14)**: Rp 6.000.000
    - **Ilmu Komunikasi (S1 - A15)**: Rp 4.500.000
    - **Teknik Informatika (D3 - A22)**: Rp 4.000.000
  - Mengonfigurasi tarif rata (*flat rate*) khusus lingkup FIK:
    - **Biaya Per SKS**: Rp 300.000 / SKS
    - **Biaya SPP Flat FIK**: Rp 2.000.000 / semester
    - **Biaya Poliklinik Flat FIK**: Rp 200.000 / semester
  - Menghubungkan fungsi helper `getTuitionFeesConfig()` dan `getStudentUktRate($majorCode)` pada [src/api.php](file:///d:/PROJECTS/app_web/sistem_akademik/src/api.php) sehingga penerbitan tagihan PMB disesuaikan otomatis dengan prodi calon mahasiswa.
  - Menambahkan Modal interaktif **"Pricelist FIK"** pada antarmuka admin [views/admin/students_bills.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/admin/students_bills.php) untuk memudahkan petugas mengecek dan memilih nominal tarif.
- **Database Schema Migration & Seeding**:
  - Memperbarui skema [schema.sql](file:///d:/PROJECTS/app_web/sistem_akademik/schema.sql) dan mengeksekusi migrasi database Cloud PostgreSQL Neon untuk tabel `students_bills_data`:
    - Kolom baru: `bill_code VARCHAR(50)`, `payment_method VARCHAR(50)`, `va_number VARCHAR(50)`, `notes TEXT`.
    - Indeks performa: `idx_students_bills_code`, `idx_students_bills_va`, `idx_students_bills_status`.
  - Seeding tagihan UKT Pokok perdana untuk mahasiswa NIM `A12.2026.00001` (No. Invoice: `INV-20261-A12202600001`, Nominal: Rp 5.000.000, Status: `Unpaid`, VA Mandiri: `008.112.2026.00001`).

---

## [v0.8.0] - 2026-09-22

### 🛠️ Refactoring & Penguatan Logika PMB Online (`src/api.php`)
- **Penanganan Transaksi Database (`DB Transactions`)**:
  - Mengimplementasikan `$conn->beginTransaction()` dan `$conn->commit()` dengan penanganan error `$conn->rollBack()` pada pembuatan akun pendaftar (`applicantRegister`) dan update profil pendaftar (`applicantUpdateProfile`).
  - Menyelaraskan query registrasi pendaftar dengan skema `account_status = 'active'`.
- **Perbaikan Bug Sesi & Identitas User (`Session ID Mapping`)**:
  - Memperbaiki pemetaan `$applicant['user_id']` pada `applicantLogin` agar ID akun `users_credential` tidak hilang/tertimpa oleh `pmb_id`, mencegah salah sasaran saat update profil.
- **Pencegahan Celah Keamanan RCE & Pengunggahan Berkas Lengkap**:
  - Menambahkan pengamanan file upload dengan pemeriksaan MIME Type (`mime_content_type`) dan pembatasan ekstensi aman (`jpg`, `jpeg`, `png`, `webp`, `pdf`).
  - Menambahkan dukungan pengunggahan sertifikat (`certificate_file`) dan bukti pembayaran pendaftaran (`payment_proof`).
- **Standarisasi Direktori Unggah Berkas PMB (`public/uploads/pmb_docs/`)**:
  - Menambahkan konstanta terpusat `PMB_DOCS_PATH`, `PMB_CERT_PATH`, `PMB_PAY_PATH`, `PMB_DOCS_URL`, `PMB_CERT_URL`, dan `PMB_PAY_URL` di [config/paths.php](file:///d:/PROJECTS/app_web/sistem_akademik/config/paths.php).
  - Memisahkan penyimpanan berkas PMB menjadi 2 folder terisolasi di [src/api.php](file:///d:/PROJECTS/app_web/sistem_akademik/src/api.php):
    - `public/uploads/pmb_docs/certificates/` untuk ijazah/sertifikat (`certificate_file`).
    - `public/uploads/pmb_docs/payments/` untuk bukti pembayaran PMB (`payment_proof`).
  - Menyelaraskan tautan file tersimpan di halaman pendaftar [views/applicant/portal.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/applicant/portal.php) dan modal detail panel admin [views/admin/applicants.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/admin/applicants.php) ke URL folder masing-masing (`PMB_CERT_URL` dan `PMB_PAY_URL`).
  - Memindahkan berkas fisik ke folder baru secara otomatis.
- **Panel Administrasi Pendaftar PMB (`views/admin/applicants.php`)**:
  - Menambahkan pemanggilan kolom `certificate_file`, `payment_proof`, `payment_status`, dan `admission_track` pada query database.
  - Memperbaiki `PHP Notice` terkait referensi variabel `$applicant['edited_at']`.
  - Mengoreksi pemetaan Javascript `school_jurusan` agar jurusan sekolah asal pendaftar dapat ditampilkan dengan benar di modal detail.
  - Menambahkan seksi baru **"📜 6. Berkas Pendaftaran"** dan **"💳 7. Status & Bukti Pembayaran"** pada Modal Detail dengan link akses langsung ke file sertifikat/ijazah dan bukti transfer.
- **Perbaikan Backend Verifikasi Pendaftar (`verifyApplicant` di `src/api.php`)**:
  - Mengimplementasikan `$conn->beginTransaction()` dan `$conn->commit()` dengan penanganan `$conn->rollBack()` saat memproses penerimaan (*Approve*) atau penolakan (*Reject*) pendaftar.
  - Memperbaiki bug urutan *sequence* auto-generate NIM (`ORDER BY id DESC`) agar pembuatan NIM otomatis bertambah secara berurutan dan tidak bentrok saat mahasiswa berjumlah > 10.
  - Secara otomatis mengeset `payment_status = 'Verified'` di tabel `pmb_data` ketika pendaftar disetujui (*Approved*).

---

## [v0.7.0] - 2026-09-22

### 🛡️ Refactoring Skema Status Akun & Keamanan Sesi (`users_credential`)
- **Migrasi Kolom `status` ke `account_status`**:
  - Mengganti kolom generik `status SMALLINT (1/0)` menjadi `account_status VARCHAR(30) DEFAULT 'active'`.
  - Menerapkan batasan nilai standar domain (*CHECK constraint*): `'active'`, `'suspended'`, `'banned'`, `'archived'`, `'pending_activation'`.
  - Menghilangkan *magic numbers* dan kebingungan terminologi status di seluruh ekosistem aplikasi.
- **Implementasi Fitur Keamanan Login State & Sesi**:
  - **`last_active_at` (TIMESTAMP)**: Mencatat timestamp aktivitas terakhir pengguna secara realtime (*throttled* hemat query per 60 detik) untuk keperluan deteksi status *Online/Offline* dan mekanisme *Auto-Logout*.
  - **Auto-Logout Idle Timeout (30 Menit)**: Diterapkan di middleware [portal.php](file:///d:/PROJECTS/app_web/sistem_akademik/portal.php) dan [views/applicant/portal.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/applicant/portal.php). Sesi pengguna yang ditinggalkan atau tidak beraktivitas selama $\ge 30$ menit otomatis dihancurkan dan dialihkan ke halaman login dengan notifikasi informatif.
  - **`session_token` (VARCHAR(255)) & Single-Device Enforcement**: Saat login berhasil, server menerbitkan token kriptografis acak `bin2hex(random_bytes(32))`. Jika akun yang sama masuk di peramban/perangkat lain, sesi lama otomatis ter-*kick* (*force logout*) saat melakukan navigasi berikutnya.
  - **`failed_attempts` (SMALLINT) & `locked_until` (TIMESTAMP)**: Perlindungan dari serangan *brute force* tebak kata sandi. Jika pengguna salah memasukkan password sebanyak 5 kali beruntun, akun otomatis dikunci sementara selama 15 menit.
  - **Standardisasi Kolom `last_login_at`**: Mengubah nama kolom `last_login` menjadi `last_login_at` untuk konsistensi penamaan timestamp audit.
- **Sinkronisasi Zona Waktu Terpadu (WIB / Asia/Jakarta)**:
  - Mengonfigurasi `date_default_timezone_set('Asia/Jakarta')` dan sesi PostgreSQL `SET TIME ZONE 'Asia/Jakarta'` di [config/db.php](file:///d:/PROJECTS/app_web/sistem_akademik/config/db.php) agar perbandingan waktu PHP dan PostgreSQL 100% sinkron.
- **Pembaruan Skrip & Dokumentasi**:
  - [schema.sql](file:///d:/PROJECTS/app_web/sistem_akademik/schema.sql) & [docs/architecture/db_refactoring.md](file:///d:/PROJECTS/app_web/sistem_akademik/docs/architecture/db_refactoring.md) dimutakhirkan.
  - [scripts/migrate_account_status_and_security.php](file:///d:/PROJECTS/app_web/sistem_akademik/scripts/migrate_account_status_and_security.php) dibuat untuk migrasi DDL pada basis data Neon PostgreSQL.
  - [scripts/clean_database.php](file:///d:/PROJECTS/app_web/sistem_akademik/scripts/clean_database.php) dan [scripts/migrate_to_10_tables.php](file:///d:/PROJECTS/app_web/sistem_akademik/scripts/migrate_to_10_tables.php) disesuaikan menggunakan `account_status`.
  - [scripts/test_security_and_sessions.php](file:///d:/PROJECTS/app_web/sistem_akademik/scripts/test_security_and_sessions.php) dibuat sebagai suite pengujian otomatis (13/13 tes passed).

---

## [v0.6.0] - 2026-09-22

### 🔄 Refactoring & Standarisasi Path dan File
- **Standarisasi Routing Parameter `view`**: 
  - Mengubah seluruh format routing parameter view berbasis tanda hubung (`-`) menjadi garis bawah (`_`). Contoh: `?view=insert-student` menjadi `?view=insert_student`. Diterapkan secara menyeluruh pada file layouts (`admin_layout.php`, `lecturer_layout.php`, dll), file view terkait, serta links di dalam API.
- **Refaktor Modul Penawaran KRS (`krs_offers` -> `course_schedule`)**:
  - Mengubah penamaan file `views/admin/krs_offers.php` menjadi `views/admin/course_schedule.php`.
  - Mengubah form terkait di `views/admin/forms/krs_form.php` menjadi `course_schedule_form.php`.
  - Mengubah file export PDF dari `src/reporting/pdf_krs_offers.php` menjadi `src/reporting/pdf_course_schedule.php`.
- **Refaktor Modul KRS Mahasiswa (`krs.php` -> `krs_items.php`)**:
  - Mengubah nama file view mahasiswa dari `views/student/krs.php` menjadi `views/student/krs_items.php`.
- **Pemisahan View Detail KRS untuk Admin**:
  - Membuat file baru `views/admin/students_krs_items.php` yang secara khusus dikonfigurasi untuk admin melihat detail KRS seorang mahasiswa.
  - Mengubah target aksi tombol "KRS" di halaman Data Mahasiswa (`views/admin/students.php`) untuk mengarah ke view baru tersebut (`?view=students_krs_items&student_id=...`).
  - Mengubah label menu di sidebar admin dari *"Pengisian KRS"* menjadi **"KRS Mahasiswa"** dengan target rute `?view=students_krs_items`.
  - Meng-upgrade `views/admin/students_krs_items.php` menjadi *dual-mode*: menampilkan daftar mahasiswa jika dibuka langsung dari sidebar tanpa parameter `student_id`, dan menampilkan rincian matakuliah jika `student_id` dipilih.
- **Migrasi Entry Point Utama ke `portal.php`**:
  - Memastikan seluruh routing dan otorisasi sesi terpusat di `portal.php`.
  - Menghapus secara permanen file usang `mainpage.php`.
- **Pembersihan Direktori Fallback Foto (`userpict`)**:
  - Menghapus secara permanen direktori legacy `public/uploads/userpict/` demi konsistensi dan menstandarkan direktori upload menjadi `public/uploads/user_photos/`.
  - Menghapus kode pengecekan fallback (`$fallbackDir`, `$legacyDir`, dll) dari file shell layouts (`admin`, `student`, `lecturer`), view pendaftar, edit mahasiswa, dan modul API backend utama (`src/api.php`).
- **Pembaruan Portal Login Terpadu ([index.php](file:///d:/PROJECTS/app_web/sistem_akademik/index.php) & [src/api.php](file:///d:/PROJECTS/app_web/sistem_akademik/src/api.php))**:
  - Menambahkan tab khusus **Login Mahasiswa** (otentikasi berbasis NIM dan password akun mahasiswa).
  - Menggabungkan form registrasi ke dalam tab **Pendaftaran** (PMB Online).
  - Tampilan default tab Pendaftaran memuat form login (Username/Email dan Password) beserta tombol *"Belum punya akun? Buat akun"*.
  - Menampilkan form pendaftaran ringkas (Nama Lengkap, Email, Password) saat *"Buat akun"* diklik, di mana username digenerate secara otomatis di backend dan dapat disesuaikan kemudian.

---

## [v0.5.0] - 2026-09-22


### 🏛️ Arsitektur Tampilan Berbasis Peran (Role-Based Views & Layouts Architecture)
- **Restrukturisasi Modular Direktori `views/`**:
  - Seluruh file tampilan flat lama di dalam direktori `views/` telah direorganisasi ke dalam subdirektori modular sesuai peran (*separation of concerns*):
    - **`views/layouts/` (Shell & Navigasi Per-Peran)**:
      - [views/layouts/admin_layout.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/layouts/admin_layout.php): Shell layout utama untuk `admin` dan `superadmin` (migrasi dan standarisasi dari `views/dashboard.php`). Dilengkapi sidebar dinamis collapsible, topbar sticky, date badge, toggle tema instan (☀️/🌙), serta router admin internal.
      - [views/layouts/student_layout.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/layouts/student_layout.php): Shell layout khusus untuk peran `student` / `mahasiswa` dengan menu navigasi spesifik: Dashboard, Pengisian KRS, KHS & Nilai, dan Tagihan UKT.
      - [views/layouts/lecturer_layout.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/layouts/lecturer_layout.php): Shell layout khusus untuk peran `lecturer` / `dosen` dengan menu: Dashboard Dosen, Jadwal Mengajar, Entry Nilai Mahasiswa, dan Persetujuan KRS Wali.
      - [views/layouts/applicant_layout.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/layouts/applicant_layout.php): Wrapper mandiri untuk calon mahasiswa pendaftar PMB Online.
    - **`views/admin/` (Panel Administrasi & Akademik)**:
      - [views/admin/dashboard.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/admin/dashboard.php): Beranda metrik riil KPI (mahasiswa, dosen, matakuliah, PMB) dan tabel pendaftar terbaru (migrasi dari `views/welcome.php`).
      - [views/admin/students.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/admin/students.php): Master data mahasiswa terintegrasi dengan filter pencarian dan cetak PDF.
      - [views/admin/lecturers.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/admin/lecturers.php): Master data dosen pengajar.
      - [views/admin/courses.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/admin/courses.php): Master data kurikulum & matakuliah.
      - [views/admin/krs_offers.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/admin/krs_offers.php): Pengelolaan jadwal dan kuota penawaran kelas KRS.
      - [views/admin/applicants.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/admin/applicants.php): Panel verifikasi berkas dan penerimaan calon mahasiswa baru (PMB).
      - [views/admin/users.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/admin/users.php): Kelola akun kredensial dan hak akses (khusus Superadmin).
      - [views/admin/forms/](file:///d:/PROJECTS/app_web/sistem_akademik/views/admin/forms/): Seluruh form CRUD modal/halaman ([course_form.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/admin/forms/course_form.php), [krs_form.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/admin/forms/krs_form.php), [lecturer_form.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/admin/forms/lecturer_form.php), [student_form.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/admin/forms/student_form.php), [user_form.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/admin/forms/user_form.php)) diperbarui untuk menggunakan konstanta `CONFIG_PATH` dan pengalihan ke `portal.php`.
    - **`views/student/` (Portal Mahasiswa)**:
      - [views/student/dashboard.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/student/dashboard.php): Beranda akademik personal mahasiswa (kartu status semester, IPK kumulatif, total SKS lulus, jadwal aktif).
      - [views/student/krs.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/student/krs.php): Antarmuka pemilihan paket matakuliah semester aktif dan ringkasan SKS (migrasi dari `views/student_krs.php`).
      - [views/student/grades.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/student/grades.php): *[Placeholder]* Tampilan Kartu Hasil Studi (KHS), transkrip nilai, dan cetak KHS semester.
      - [views/student/bills.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/student/bills.php): *[Placeholder]* Informasi tagihan UKT, kode Virtual Account pembayaran, dan riwayat pelunasan.
    - **`views/lecturer/` (Portal Dosen Pengajar)**:
      - [views/lecturer/dashboard.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/lecturer/dashboard.php): *[Placeholder]* Beranda dosen, agenda mengajar hari ini, serta rekapitulasi mahasiswa bimbingan akademik.
      - [views/lecturer/schedule.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/lecturer/schedule.php): *[Placeholder]* Jadwal mengajar mingguan dan ruang kuliah.
      - [views/lecturer/grading.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/lecturer/grading.php): *[Placeholder]* Form entri nilai (Tugas, UTS, UAS) mahasiswa per kelas.
      - [views/lecturer/krs_approval.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/lecturer/krs_approval.php): *[Placeholder]* Panel verifikasi dan persetujuan (approval) KRS mahasiswa bimbingan.
    - **`views/applicant/` (Portal Calon Mahasiswa)**:
      - [views/applicant/portal.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/applicant/portal.php): Dashboard mandiri pendaftar PMB (migrasi dari `views/applicant_portal.php`), dengan penyesuaian dependensi `src/api.php` dan `index.php` yang berbasis root-relative.

### 🚪 Dispatcher Terpusat Baru: `portal.php` & Backward Compatibility Shim
- **Dispatcher Multi-Role Terpadu ([portal.php](file:///d:/PROJECTS/app_web/sistem_akademik/portal.php))**:
  - Menggantikan fungsi ganda `mainpage.php` dan `applicant_portal.php` sebagai satu-satunya *single point of authenticated entry*.
  - Menjalankan otentikasi sesi, validasi status akun, resolusi preferensi tema gelap/terang, dan secara otomatis mendispatch pengguna ke layout shell yang tepat berdasarkan `role` (`superadmin`, `admin`, `student`, `lecturer`, `applicant`).
- **Shim Kompatibilitas Mundur ([mainpage.php](file:///d:/PROJECTS/app_web/sistem_akademik/mainpage.php))**:
  - Berfungsi sebagai 302 Redirect Shim cerdas. Semua request yang masih mengarah ke `mainpage.php` diteruskan secara otomatis ke `portal.php` dengan query string tetap utuh (`mainpage.php?view=students` ➔ `portal.php?view=students`).
- **Penyelarasan Entry Point & Backend Redirects**:
  - [index.php](file:///d:/PROJECTS/app_web/sistem_akademik/index.php): Auto-redirect untuk sesi aktif diarahkan langsung ke `portal.php`.
  - [src/api.php](file:///d:/PROJECTS/app_web/sistem_akademik/src/api.php): Seluruh `header("location: ...")` setelah login dan aksi form dialihkan ke `../portal.php`.

---

## [v0.4.0] - 2026-09-22

### 📁 Standarisasi Path & Struktur Direktori (Centralized Paths Architecture)
- **Konfigurasi Path Terpusat ([config/paths.php](file:///d:/PROJECTS/app_web/sistem_akademik/config/paths.php))**:
  - Dibuat modul path resolution terpusat dengan konstanta global (`APP_ROOT`, `CONFIG_PATH`, `PUBLIC_PATH`, `UPLOAD_PATH`, `VIEWS_PATH`, `SRC_PATH`, `REPORTING_PATH`, `DOCS_PATH`, `SCRIPTS_PATH`, dan `UPLOAD_URL`).
  - Terintegrasi langsung ke dalam [config/db.php](file:///d:/PROJECTS/app_web/sistem_akademik/config/db.php) sehingga seluruh view, API, dan skrip otomatis mewarisi konstanta path terstandarisasi tanpa hardcode relative path (`../..`).
- **Standarisasi Direktori Upload Foto Profil**:
  - Folder penyimpanan foto profil pengguna dan calon mahasiswa distandarisasi ke `public/uploads/user_photos/` menggantikan singkatan non-standar `public/uploads/userpict/`.
  - Backend ([src/api.php](file:///d:/PROJECTS/app_web/sistem_akademik/src/api.php)) dan view ([views/dashboard.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/dashboard.php), [views/applicant_portal.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/applicant_portal.php), [views/applicants.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/applicants.php), [views/forms/student_form.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/forms/student_form.php)) menerapkan resolusi foto cerdas dengan dual-fallback untuk menjamin nol foto rusak (*zero broken avatar*).

### 📄 Standarisasi Penamaan File Reporting (`src/reporting/`)
- **Penamaan Format Baku `pdf_{entity}.php`**:
  - Mengganti penamaan lama yang inkonsisten (camelCase, singkatan tidak baku, campur bahasa Inggris & Indonesia, dan prefiks nama vendor `mpdf...`):
    - `mpdfMhs.php` ➔ **[src/reporting/pdf_students.php](file:///d:/PROJECTS/app_web/sistem_akademik/src/reporting/pdf_students.php)**
    - `mpdfLect.php` ➔ **[src/reporting/pdf_lecturers.php](file:///d:/PROJECTS/app_web/sistem_akademik/src/reporting/pdf_lecturers.php)**
    - `mpdfCourses.php` ➔ **[src/reporting/pdf_courses.php](file:///d:/PROJECTS/app_web/sistem_akademik/src/reporting/pdf_courses.php)**
    - `mpdfKrsOffers.php` ➔ **[src/reporting/pdf_krs_offers.php](file:///d:/PROJECTS/app_web/sistem_akademik/src/reporting/pdf_krs_offers.php)**
  - Query database di dalam modul reporting dimodernisasi agar sinkron 100% dengan skema 10 tabel terstandarisasi (`students_data`, `lecturers_data`, `courses_data`, `krs_offers`).
  - Tombol aksi cetak pada seluruh tampilan ([views/students.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/students.php), [views/lecturers.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/lecturers.php), [views/courses.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/courses.php), [views/krs_offers.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/krs_offers.php)) telah diperbarui ke nama file baru.

### 🧹 Pembersihan & Restrukturisasi File (Clean Architecture)
- **Migrasi View Beranda ([views/welcome.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/welcome.php))**:
  - Mengubah ekstensi `views/welcome.html` menjadi `views/welcome.php` karena file tersebut memuat logika server-side PHP (query metrik riil Neon DB).
- **Eliminasi File Yatim / Obsolete**:
  - Menghapus file `views/register.php` yang sudah usang dan menggunakan endpoint mati (`req=registerApplicant`). Alur pendaftaran akun PMB mandiri kini terpusat pada Tab 3 di [index.php](file:///d:/PROJECTS/app_web/sistem_akademik/index.php).
- **Relokasi Dokumentasi Arsitektur**:
  - Memindahkan cetak biru perancangan database dari folder konfigurasi `config/ai_context/db_refactoring.md` ke folder dokumentasi resmi **[docs/architecture/db_refactoring.md](file:///d:/PROJECTS/app_web/sistem_akademik/docs/architecture/db_refactoring.md)**.
- **Relokasi Skrip Utilitas & Migrasi**:
  - Mengisolasi skrip utilitas database dari folder root ke direktori baru **`scripts/`**:
    - [scripts/clean_database.php](file:///d:/PROJECTS/app_web/sistem_akademik/scripts/clean_database.php)
    - [scripts/migrate_to_10_tables.php](file:///d:/PROJECTS/app_web/sistem_akademik/scripts/migrate_to_10_tables.php)
    - Menyesuaikan dependensi `require_once` pada skrip tersebut agar mengarah ke `../config/db.php` dan `../schema.sql`.

### 🧭 Standarisasi Routing & Parameter URL ([views/dashboard.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/dashboard.php))
- Standarisasi query parameter menu Kelola Pengguna menjadi `?view=users` (diselaraskan dengan nama file [views/users.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/users.php)) dengan tetap mempertahankan backward compatibility untuk `?view=data-users`.

---

## [v0.3.0] - 2026-09-21

### 🎨 Perombakan Antarmuka (UI/UX Modern & Minimalis - Bootstrap 5.3.3)
- **Desain Baru Shell & Tipografi ([mainpage.php](file:///d:/PROJECTS/app_web/sistem_akademik/mainpage.php))**:
  - Mengintegrasikan tipografi modern **Plus Jakarta Sans** (Google Fonts) dan pustaka ikon vektor **Bootstrap Icons 1.11.3 CDN** menggantikan font kaku dan gambar PNG lama.
  - Menghilangkan background gelap flat pekat dan garis `<hr>` yang padat, digantikan oleh tata letak modern berbasis variabel CSS Bootstrap 5.3 (`bg-body`, `bg-body-tertiary`, `border-color`).
  - Mengeliminasi duplikasi tag `<!DOCTYPE html>`, `<html>`, dan `<head>` di dalam view komponen parsial.

- **Sistem Navigasi Sidebar Expand / Collapse ([views/dashboard.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/dashboard.php))**:
  - Sidebar dinamis dengan dua mode: **Expanded** (lebar 260px) dan **Collapsed / Mini-sidebar** (lebar 76px dengan ikon terpusat).
  - Tombol toggle hamburger di Topbar dan tombol pin chevron di header sidebar dengan transisi halus (`0.25s cubic-bezier`).
  - Status collapse tersimpan otomatis di `localStorage` (`siakad_sidebar_collapsed`) sehingga tidak reset saat berpindah halaman.
  - Dukungan tampilan perangkat mobile/tablet dengan drawer offcanvas dan backdrop overlay.
  - Pengelompokan menu terstruktur rapi berdasarkan seksi: *Utama*, *Akademik*, *Penerimaan (PMB)*, dan *Sistem*.

- **Sticky Topbar Header ([views/dashboard.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/dashboard.php))**:
  - Menambahkan Topbar bersih di atas area konten yang memuat tombol navigasi hamburger, judul & deskripsi halaman dinamis, badge tanggal sistem, tombol toggle tema instan (☀️/🌙), serta dropdown profil pengguna cepat.

- **Beranda Dashboard Interaktif ([views/welcome.html](file:///d:/PROJECTS/app_web/sistem_akademik/views/welcome.html))**:
  - Mengganti kartu gambar besar statis lama dengan dashboard metrik riil:
    - **Welcome Banner**: Sapaan pengguna dan status tahun akademik/semester berjalan.
    - **4 Kartu KPI Metrik Riil**: Total Mahasiswa Aktif (`students_data`), Total Dosen Pengajar (`lecturers_data`), Total Matakuliah Kurikulum (`courses_data`), dan Total Pendaftar PMB Baru (`pmb_data`) dengan badge status verifikasi.
    - **Aksi Cepat (Quick Actions)**: Pintasan 1-klik untuk Verifikasi PMB, Penawaran KRS, Tambah Matakuliah, dan Tambah Dosen.
    - **Tabel Pendaftar PMB Terkini**: Menampilkan 5 calon mahasiswa baru pendaftar terakhir beserta badge status dan tombol langsung periksa berkas.

### 🌓 Ditambahkan (Added)
- **Fitur Switch Light & Dark Mode (Per-User Setting)**:
  - **Konfigurasi JSON ([config/theme_setting.json](file:///d:/PROJECTS/app_web/sistem_akademik/config/theme_setting.json))**:
    - Struktur konfigurasi berbasis per-user (`default: "light"`, `users: { ... }`) sehingga preferensi tema tiap akun tersimpan mandiri tanpa menimpa pengguna lain.
  - **Endpoint API Tema ([src/api.php](file:///d:/PROJECTS/app_web/sistem_akademik/src/api.php))**:
    - Menambahkan `case 'setTheme'` yang menerima request POST AJAX untuk memperbarui preferensi tema pengguna aktif ke `config/theme_setting.json` secara aman (*file lock*).
  - **Pemuatan Bebas Flicker (No-FOUC)**:
    - Preferensi tema langsung disematkan pada tag `<html lang="id" data-bs-theme="...">` di sisi server PHP pada saat halaman dimuat.

---

## [v0.2.0] - 2026-09-21

### 🚀 Direstrukturisasi (Enterprise Database Refactoring - 10 Tabel Terstandarisasi)
- **Arsitektur 10 Tabel Terstandarisasi ([schema.sql](file:///d:/PROJECTS/app_web/sistem_akademik/schema.sql))**:
  - `users_credential`: Master otentikasi terpusat multi-role (`superadmin`, `admin`, `student`, `lecturer`, `applicant`).
  - `personal_profiles`: Dokumen identitas legal warga negara (KTP/KK/Akta), kontak darurat, dan alamat KTP/domisili.
  - `majors_data`: Master data program studi resmi (`A11`, `A12`, `A14`, `A15`, `A22`).
  - `courses_data`: Master matakuliah berelasi ke `majors_data` dengan kontrol kurikulum aktif.
  - `pmb_data`: Berkas pendaftaran PMB, data orang tua, sekolah asal, dan status seleksi.
  - `students_data`: Data akademik mahasiswa resmi (NIM, tahun angkatan, semester, status UKT, status akademik).
  - `lecturers_data`: Data fungsional dosen (NPP, NIDN, gelar akademik, homebase).
  - `students_bills_data`: Manajemen tagihan UKT/SPP mahasiswa per semester.
  - `students_krs_data`: Pengambilan matakuliah per semester dan persetujuan dosen wali.
  - `students_score_data`: Penilaian akademik (Tugas, UTS, UAS, nilai angka, grade huruf, bobot).
- **Skrip Migrasi Mandiri Zero Data Loss ([migrate_to_10_tables.php](file:///d:/PROJECTS/app_web/sistem_akademik/migrate_to_10_tables.php))**:
  - Berhasil mengeksekusi migrasi 100% data riil dari tabel legacy (`users`, `applicants`, `students`, `lecturers`, `courses`) ke 10 tabel terstandarisasi tanpa kehilangan data.
- **Backend API & Query Logic ([src/api.php](file:///d:/PROJECTS/app_web/sistem_akademik/src/api.php))**:
  - Pembaruan endpoint otentikasi (`userLogin`, `userLogout`, `applicantLogin`, `applicantRegister`) terhubung ke `users_credential` dan `personal_profiles`.
  - Refactoring CRUD mahasiswa (`fetchStudents`, `getStudentDetail`, `searchStudent`, `updateStudent`, `deleteStudent`) menggunakan query `JOIN` relasional bersih ke `students_data`, `users_credential`, `personal_profiles`, dan `majors_data`.
  - Siklus pendaftaran PMB & Verifikasi TU (`verifyApplicant` langsung menerbitkan NIM dan insert ke `students_data` secara ringkas dan bersih).
  - CRUD Dosen & Matakuliah diselaraskan ke `lecturers_data` dan `courses_data`.
- **Pelaporan & Views**:
  - Pembaruan query [src/reporting/mpdfMhs.php](file:///d:/PROJECTS/app_web/sistem_akademik/src/reporting/mpdfMhs.php), [views/applicants.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/applicants.php), [views/applicant_portal.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/applicant_portal.php), dan [mainpage.php](file:///d:/PROJECTS/app_web/sistem_akademik/mainpage.php).
- **Pembersihan Data Operasional & Drop Tabel Legacy ([clean_database.php](file:///d:/PROJECTS/app_web/sistem_akademik/clean_database.php))**:
  - Mengosongkan seluruh record operasional (`students_data`, `pmb_data`, `courses_data`, `lecturers_data`, `students_bills_data`, `students_krs_data`, `students_score_data`) dan menyisakan hanya 1 akun Superadmin di `users_credential` & `personal_profiles`.
  - Menghapus (*DROP CASCADE*) seluruh tabel legacy yang sudah tidak digunakan: `applicants`, `courses`, `users`, `students`, `lecturers`, `krs_offers`, `student_krs`.
  - Mengonfigurasi `php.ini` lokal mandiri (`D:\PROJECTS\library\php-8.5.10-nts-Win32-vs17-x64\php.ini`) dan [run.bat](file:///d:/PROJECTS/app_web/sistem_akademik/run.bat) sehingga project dapat dijalankan secara langsung tanpa ketergantungan pada XAMPP.


### 🛠️ Diperbaiki (Fixed)
- **PostgreSQL Prepared Statement Cache (`SQLSTATE[0A000]`)**:
  - Mengubah opsi PDO menjadi `PDO::ATTR_EMULATE_PREPARES => true` dan mengeksekusi `DEALLOCATE ALL;` saat koneksi diinisialisasi pada [config/db.php](file:///d:/PROJECTS/app_web/sistem_akademik/config/db.php). Menghilangkan error `cached plan must not change result type` akibat perubahan kolom pada PostgreSQL Neon Cloud DB.
- **Respons AJAX Non-JSON (`SyntaxError: Unexpected token '<'`)**:
  - Memperbarui penanganan exception outer pada [src/api.php](file:///d:/PROJECTS/app_web/sistem_akademik/src/api.php) agar request AJAX selalu mengembalikan payload JSON (`{"status": "error", "message": "..."}`) alih-alih redirect HTML ke `index.php`.

### ➕ Ditambahkan (Added)
- **Kolom Status Mahasiswa & Prodi Mahasiswa pada Tabel**:
  - Menambahkan kolom **Prodi Mahasiswa** (badge program studi sesuai NIM/prodi) dan **Status Mahasiswa** (badge indikator status: *Aktif*, *Cuti*, *Lulus*, *Non-Aktif*) pada tabel data mahasiswa [views/students.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/students.php) dan API [src/api.php](file:///d:/PROJECTS/app_web/sistem_akademik/src/api.php) (`fetchStudents` dan `searchStudent`).
  - Auto-migration kolom `status` (`VARCHAR(50) DEFAULT 'Aktif'`) pada tabel `students`.
- **Standarisasi Kolom Database `major` & `parent_phone`**:
  - Auto-migration kolom `major` (`VARCHAR(100)`) menggantikan istilah kolom `prodi` untuk jurusan sekolah asal pada tabel `students` & `applicants`.
  - Auto-migration kolom `parent_phone` (`VARCHAR(50)`) menggantikan istilah `father_phone` pada tabel `students` & `applicants`.
- **Modal Popup Detail Mahasiswa (`#viewStudentModal`)**:
  - Menambahkan tombol aksi **`👁️ Lihat`** pada tabel data mahasiswa [views/students.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/students.php) untuk menampilkan rincian data mahasiswa lengkap menggunakan Bootstrap 5 Modal.
  - Menampilkan foto profil jernih (105x105 px), status keaktifan, badge NIM, prodi mahasiswa, rincian data diri (NIK, NISN, No. HP, Gender, Agama, Tempat/Tgl Lahir, Status Kawin & Kerja), data orang tua/wali, data sekolah asal/akademik, serta data alamat KTP & domisili.
  - Menampilkan metadata audit (**Created at**, **Edited at**, dan **Author**) di kartu profil modal popup.
  - Menyediakan tombol pintas langsung: **📄 Buka KRS Mahasiswa** dan **✏️ Edit Data**.
- **Data Diri Tambahan (Status Pernikahan & Pekerjaan)**:
  - Auto-migration kolom `marital_status` (`VARCHAR(50)`) dan `job_status` (`VARCHAR(50)`) pada tabel `applicants` & `students`.
  - Dropdown **Status Pernikahan** (*Belum Menikah*, *Menikah*) dan **Status Pekerjaan** (*Belum Bekerja*, *Bekerja*) di [views/applicant_portal.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/applicant_portal.php) dan modal edit di [views/students.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/students.php).
- **Data Orang Tua Tambahan (Data Ayah)**:
  - Auto-migration kolom `father_name` (`VARCHAR(255)`) dan `father_phone` (`VARCHAR(50)`).
  - Input field **Nama Ayah** dan **No. HP / WhatsApp Ayah** pada Portal PMB, Modal Detail Pendaftar ([views/applicants.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/applicants.php)), dan Modal Edit Student.
- **Alamat Sesuai KTP & Jurusan Sekolah Asal**:
  - Auto-migration `ktp_address` (`TEXT`).
  - Textarea **Alamat Sesuai KTP** dan input **Jurusan Sekolah Asal** (IPA/IPS/TKJ/dll) pada seluruh form pendaftaran dan edit.

### 🔄 Diubah (Changed) & Direstrukturisasi (Refactored)
- **Penyelarasan Layout Grid Data Sekolah Asal Modal**:
  - Memperbaiki ketidaksejajaran input modal pada bagian data sekolah asal di `#viewStudentModal` dan `#editStudentModal`. Mengubah ukuran kolom *Asal Sekolah* dari `col-md-7` menjadi `col-md-6`, dan *Nilai Akhir Rata-rata* dari `col-md-5` menjadi `col-md-6`, sehingga sejajar simetris dengan baris di atasnya (*NISN* `col-md-6` & *Jurusan* `col-md-6`).
- **Pembaruan Label & Relokasi Field Orang Tua**:
  - Mengubah label *"Program Studi"* pada bagian Data Sekolah Asal menjadi **"Jurusan (Sekolah Asal)"**.
  - Mengubah label *"No. HP / WhatsApp Ayah"* menjadi **"No. HP / WhatsApp Orang Tua"** agar lebih fleksibel diisi oleh mahasiswa (bebas diisi data ayah, ibu, maupun wali).
  - Menambahkan pilihan input **Program Studi Mahasiswa** dan **Status Mahasiswa** pada modal edit mahasiswa.
- **Perapihan Tabel Data Mahasiswa**:
  - Menyederhanakan struktur tabel mahasiswa menjadi kolom yang bersih dan modern: `#`, `NIM`, `Nama Mahasiswa`, `Prodi Mahasiswa`, `Status`, `Email`, dan `Aksi` dengan pembungkus `.table-responsive` dan `.align-middle`.
  - **Relokasi Tombol KRS**: Menghapus kolom terpisah *KRS* dan memindahkannya ke dalam kolom aksi sebagai tombol **`KRS`** (hijau) yang bersanding dengan tombol Lihat, Edit, dan Hapus pada [src/api.php](file:///d:/PROJECTS/app_web/sistem_akademik/src/api.php) (`fetchStudents` dan `searchStudent`).
  - **Relokasi Foto Profil**: Menghapus kolom foto dari tabel utama untuk menghemat ruang vertikal layar, memindahkannya secara eksklusif ke Modal Popup Detail Mahasiswa.
  - **Relokasi Kolom Created at, Edited at, dan Author**: Menghapus kolom timestamp dan author dari tabel utama lalu merelokasikannya ke dalam Modal Popup Detail Mahasiswa.
- **Relokasi Field No. HP Mahasiswa**:
  - Memindahkan posisi input **No. HP / WhatsApp Mahasiswa** dari sekelompok Data Orang Tua ke sekelompok **👤 1. Data Diri Mahasiswa** pada [views/students.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/students.php).
- **Redesain Layout Modal Edit Student**:
  - Container foto profil dibuat lebih ringkas (menghapus `h-100` flex stretch, menyesuaikan foto `85x85 px`).
  - Form field dikelompokkan menjadi 4 bagian terstruktur (*Data Diri*, *Data Orang Tua*, *Data Sekolah Asal*, *Data Tempat Tinggal*) dipisahkan garis divider halus.
- **Labeling Alamat**:
  - Mengubah label *Alamat Rumah Lengkap* menjadi **Alamat Domisili (Tempat Tinggal Saat Ini)**.
- **Tampilan Kolom NIM & Prodi Tanpa Badge**:
  - Menghilangkan styling badge warna/kotak pada kolom **NIM** dan **Prodi Mahasiswa** pada tabel data mahasiswa, digantikan format teks bersih biasa (*monospace* untuk NIM).
- **Kolom Email Ditransformasi Menjadi Kolom Kontak**:
  - Mengubah header kolom *Email* menjadi **Kontak**, serta menampilkan email mahasiswa beserta nomor telepon/HP secara vertikal ringkas dan rapi.
- **Pembaruan Pemanggilan Modal Edit**:
  - Menambahkan fungsi JavaScript `openEditStudentModal(id)` sehingga tombol edit di dalam Modal Detail tetap dapat membuka modal form edit secara mandiri tanpa bergantung pada elemen tombol di baris tabel.

### 🗑️ Dihapus (Removed)
- **Fitur Hapus & Edit pada Tampilan Aksi Tabel Mahasiswa**:
  - Menghapus tombol *Hapus* dan *Edit* dari kolom aksi pada tabel data mahasiswa ([views/students.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/students.php) dan [src/api.php](file:///d:/PROJECTS/app_web/sistem_akademik/src/api.php)). Penghapusan data ditutup karena proses hapus data harus melalui mekanisme proses Drop Out (DO) khusus.
- **Emotikon/Ikon pada Tombol Aksi**:
  - Menghapus seluruh ikon/emotikon dari tombol aksi tabel data mahasiswa, menyisakan teks murni yang bersih (**`Lihat`** dan **`KRS`**).
- **Ikon `👁️` pada Modal Popup**:
  - Menghapus emotikon `👁️` dari judul header Modal Popup Detail Data Mahasiswa (`#viewStudentModalLabel`).

---

## [v0.1.1] - 2026-09-19

### ➕ Ditambahkan (Added)
- **Modal Popup Detail Pendaftar (`#detailApplicantModal`)**:
  - Tombol **`🔍 Detail`** pada tabel utama [views/applicants.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/applicants.php) untuk melihat seluruh berkas pendaftar tanpa *page reload*.
- **Pemotongan Teks (Truncated Text)**:
  - Fungsi `truncate18()` dengan tooltip `title="..."` untuk kolom *Nama & Contact* pada tabel pendaftar.

### 🔄 Diubah (Changed)
- **Tampilan Portal PMB ([views/applicant_portal.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/applicant_portal.php))**:
  - Penyesuaian tema warna biru khas UDINUS (`#0f4c92`), header putih ber-shadow, greeting *"Halo, Pendaftar"*, dan footer melayang `sia_v0.1.2026`.
- **Tabel Pendaftar**:
  - Menghapus kolom *Alamat Rumah* dari tabel utama pendaftar agar tampilan layar monitor lebih ringkas dan rapi.

---

## [v0.1.0] - 2025-02-14

### 🚀 Inisialisasi Sistem Core SIA (Initial Release)
- **Sistem Autentikasi & Akun (`index.php`, `views/users.php`)**:
  - Manajemen login multi-role (*Admin*, *Dosen*, *Mahasiswa*), enkripsi password BCRYPT, dan manajemen session user.
- **Manajemen Data Mahasiswa (`views/students.php`)**:
  - Pengelolaan data mahasiswa (NIM, Nama, Email, Foto Profil, Status Akademik).
- **Manajemen Data Dosen (`views/lecturers.php`)**:
  - Pengelolaan data dosen (NPP, Nama Dosen, Homebase, Status Aktif).
- **Manajemen Mata Kuliah & Penawaran KRS (`views/courses.php`, `views/krs_offers.php`)**:
  - Pengelolaan data Mata Kuliah (Kode MK, Nama MK, SKS, Semester) dan Penawaran Kelas KRS per tahun akademik.
- **Sistem Pengisian KRS Online (`views/student_krs.php`)**:
  - Pengambilan mata kuliah oleh mahasiswa dan alur verifikasi/persetujuan oleh Pembimbing Akademik / TU.
- **Reporting Engine mPDF (`src/reporting/`)**:
  - Modul cetak dokumen PDF untuk Data Mahasiswa (`mpdfMhs.php`), Data Dosen (`mpdfLect.php`), Data Mata Kuliah (`mpdfCourses.php`), dan Kartu Rencana Studi (`mpdfKrsOffers.php`).
- **Inisialisasi Database Cloud (`config/db.php`, `schema.sql`)**:
  - Integrasi PDO dengan PostgreSQL Cloud Neon DB dan pembuatan skema tabel utama (`applicants`, `students`, `lecturers`, `courses`, `krs_offers`, `student_krs`, `users`).
