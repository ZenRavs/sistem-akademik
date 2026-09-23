# 📄 PRODUCT REQUIREMENTS DOCUMENT (PRD)
# Sistem Informasi Akademik (SIA) & PMB Online

> 📂 Dokumen resmi lengkap tersimpan di [docs/PRD.md](file:///d:/PROJECTS/app_web/sistem_akademik/docs/PRD.md).

| Metadata | Keterangan |
| :--- | :--- |
| **Nama Produk** | Sistem Informasi Akademik (SIA) & PMB Online Terpadu |
| **Versi Dokumen** | `v1.0.0` (Resmi / Baseline) |
| **Status Dokumen** | **Disetujui (Approved)** |
| **Tanggal Terbit** | 24 September 2026 |
| **Basis Data** | PostgreSQL (Neon Cloud DB) - Arsitektur 10 Tabel |
| **Teknologi Utama** | PHP 8.2+, Bootstrap 5.3.3, Plus Jakarta Sans, Bootstrap Icons, mPDF |

---

## 1. 🎯 Ringkasan Eksekutif & Visi Produk
Sistem Informasi Akademik (SIA) & PMB Online adalah platform web terpadu satu pintu (*single-window platform*) yang mengintegrasikan seluruh siklus hidup akademik perguruan tinggi: mulai dari **Penerimaan Mahasiswa Baru (PMB)**, **Registrasi & Keuangan (Billing)**, **Perencanaan Studi (KRS & Jadwal)**, hingga **Perkuliahan & Evaluasi Akademik (Penilaian & KHS)**.

### Tujuan Utama:
1. **Otentikasi Terpusat**: Satu master kredensial (`users_credential`) yang melayani multi-peran (*Superadmin, Admin, Dosen, Mahasiswa, Pendaftar*).
2. **Integritas Data Pokok**: Pemisahan identitas warga negara legal (`personal_profiles`) dari data akademik dan fungsional.
3. **Otomasi Siklus Mahasiswa**: Mengubah pendaftar yang lulus seleksi dan lunas biaya pendaftaran langsung menjadi mahasiswa resmi ber-NIM tanpa input ganda.
4. **Alur Finansial Terhubung**: Pembayaran tagihan semester berfungsi sebagai prasyarat administratif (*gatekeeper*) pembukaan akses pengisian KRS.

---

## 2. 👥 Matriks Peran Pengguna (User Roles & RBAC Matrix)

Sistem mendukung 5 peran pengguna (*roles*) dengan pembagian hak akses:

| Fitur / Modul | Superadmin | Admin (TU) | Dosen | Mahasiswa | Pendaftar (PMB) |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Kelola Akun & Hak Akses (`users`)** | Full CRUD | Read-Only | - | - | - |
| **Verifikasi & Kelulusan PMB** | Full CRUD | Full CRUD | - | - | Sendiri |
| **Master Mahasiswa, Dosen, Matakuliah** | Full CRUD | Full CRUD | - | - | - |
| **Manajemen Tagihan (UKT/Billing)** | Full CRUD | Full CRUD | - | Read & Bayar | - |
| **Penawaran Jadwal Matakuliah** | Full CRUD | Full CRUD | Read Jadwal | - | - |
| **Pengisian KRS Mahasiswa** | Bypass | Bypass | - | Ambil/Hapus | - |
| **Persetujuan (Approval) KRS** | Bypass | Bypass | Approve/Reject | - | - |
| **Unggah Soal UTS / UAS** | - | Read | Upload/Edit | - | - |
| **Entri Nilai (Tugas, UTS, UAS)** | Bypass | Read | Input/Update | - | - |
| **Kartu Hasil Studi (KHS) & Transkrip** | Read/Cetak | Read/Cetak | Read | Read & Cetak | - |
| **Edit Profil Pribadi** | Mandiri | Mandiri | Mandiri | Mandiri | Mandiri |

---

## 3. 🔄 Alur Bisnis Inti (Core User Journeys)

```
[1. PMB Online] ──(Lulus & Bayar)──> [2. Terbit Akun & NIM]
                                             │
                                             ▼
[4. Penilaian & KHS] <── [3. Bimbingan KRS] <── [2. Tagihan UKT Lunas]
```

### 3.1. Alur Penerimaan Mahasiswa Baru (PMB)
1. **Pendaftaran Akun**: Calon mahasiswa mendaftar mandiri via form PMB di [index.php](file:///d:/PROJECTS/app_web/sistem_akademik/index.php).
2. **Kelengkapan Dokumen**: Pendaftar mengisi biodata identitas (`personal_profiles`), memilih prodi tujuan (`majors_data`), mengunggah ijazah/sertifikat (`certificate_file`), dan mengunggah bukti bayar pendaftaran (`payment_proof`).
3. **Verifikasi Admin**: Admin memeriksa berkas di [views/admin/applicants.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/admin/applicants.php).
4. **Penerbitan NIM & Konversi Akun**: Jika disetujui (*Approved*), sistem secara atomik (*DB Transaction*):
   - Menerbitkan NIM urut otomatis (`{KodeProdi}.{Tahun}.{Urutan5Digit}`).
   - Menambahkan record ke `students_data`.
   - Mengubah peran akun pendaftar dari `applicant` menjadi `student` di `users_credential`.

### 3.2. Alur Keuangan & Pembukaan KRS
1. **Penerbitan Tagihan**: Admin menerbitkan tagihan UKT semester berjalan di `students_bills_data`.
2. **Pembayaran Mahasiswa**: Mahasiswa melihat tagihan di [views/student/bills.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/student/bills.php) dan mengunggah bukti transfer.
3. **Verifikasi Admin**: Admin memvalidasi bukti pembayaran dan mengubah status menjadi `Lunas`.
4. **Buka Akses KRS**: Status lunas otomatis membuka kunci menu pengisian KRS mahasiswa.

### 3.3. Alur Perencanaan Studi (KRS) & Jadwal
1. **Penawaran Kelas**: Admin membuka jadwal kelas matakuliah di [views/admin/course_schedule.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/admin/course_schedule.php).
2. **Pengisian KRS**: Mahasiswa memilih paket matakuliah sesuai batas maksimal SKS di [views/student/krs_items.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/student/krs_items.php).
3. **Verifikasi Dosen Wali**: Dosen Wali memeriksa dan menyetujui (*Approve*) KRS di [views/lecturer/krs_approval.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/lecturer/krs_approval.php).
4. **Jadwal Perkuliahan**: Mahasiswa dan dosen dapat melihat jadwal kelas mingguan masing-masing.

### 3.4. Alur Evaluasi & Penilaian Akademik
1. **Unggah Soal**: Dosen mengunggah dokumen/berkas soal UTS dan UAS untuk arsip prodi.
2. **Input Nilai**: Dosen menginput nilai Tugas, UTS, dan UAS mahasiswa per kelas di [views/lecturer/grading.php](file:///d:/PROJECTS/app_web/sistem_akademik/views/lecturer/grading.php).
3. **Kalkulasi Otomatis**: Sistem menghitung Nilai Akhir, Grade Huruf (A–E), Bobot SKS, dan Indeks Prestasi (IPS/IPK).
4. **KHS & Transkrip**: Mahasiswa mencetak Kartu Hasil Studi (KHS) semesteran atau Transkrip Nilai resmi format PDF.

---

## 4. 📋 Spesifikasi Kebutuhan Fungsional (Functional Requirements)

### 4.1. Modul Calon Mahasiswa (PMB Online)
* **FR-PMB-01**: Pendaftar dapat membuat akun baru dengan email unik dan kata sandi aman.
* **FR-PMB-02**: Pendaftar dapat mengisi biodata lengkap sesuai KTP/KK (NIK, TTL, Alamat KTP, Alamat Domisili, Kontak Darurat).
* **FR-PMB-03**: Pendaftar dapat memilih Program Studi pilihan utama.
* **FR-PMB-04**: Pendaftar dapat mengunggah berkas sertifikat/ijazah dan bukti transfer pembayaran biaya pendaftaran.
* **FR-PMB-05**: Pendaftar dapat memantau status seleksi (*Pending / Verified / Rejected*) secara realtime di dashboard.

### 4.2. Modul Admin / Tata Usaha (TU)
* **FR-ADM-01 (PMB)**: Admin dapat melihat daftar pendaftar, memeriksa berkas fisik, menolak dengan catatan, atau menyetujui pendaftar.
* **FR-ADM-02 (Data Mahasiswa)**: Admin dapat mengelola master mahasiswa, memfilter berdasarkan prodi/angkatan, dan melihat modal biodata lengkap.
* **FR-ADM-03 (Data Dosen)**: Admin dapat menambah, mengubah, dan menonaktifkan data dosen (NPP, NIDN, Gelar, Homebase).
* **FR-ADM-04 (Matakuliah & Kurikulum)**: Admin dapat mengelola katalog mata kuliah (Kode MK, Nama MK, SKS, Semester).
* **FR-ADM-05 (Jadwal & Kuota)**: Admin dapat membuka penawaran kelas matakuliah, menentukan ruangan, hari/jam, dosen pengampu, dan kuota mahasiswa.
* **FR-ADM-06 (Keuangan & Billing)**:
  * Admin dapat menerbitkan tagihan UKT massal per angkatan/prodi atau tagihan individual.
  * Admin dapat memverifikasi pembayaran transfer manual dan mencetak kuitansi pembayaran PDF.
* **FR-ADM-07 (Data User & Akun)**: Khusus Superadmin: dapat mereset kata sandi, mengunci akun (*suspend/ban*), dan mengubah role pengguna.
* **FR-ADM-08 (Pelaporan PDF)**: Admin dapat mengekspor rekap cetak PDF untuk Data Mahasiswa, Dosen, Matakuliah, dan Jadwal Kelas.

### 4.3. Modul Mahasiswa
* **FR-MHS-01**: Mahasiswa dapat melihat ringkasan status akademik (NIM, Semester Aktif, IPK Sementara, Dosen Wali).
* **FR-MHS-02 (Billing)**: Mahasiswa dapat melihat tagihan aktif, mengunggah bukti pembayaran, dan mengunduh kuitansi lunas.
* **FR-MHS-03 (KRS)**: Mahasiswa dapat memilih kelas matakuliah yang ditawarkan hingga batas maksimal SKS, dan mengajukan persetujuan ke Dosen Wali.
* **FR-MHS-04 (Jadwal)**: Mahasiswa dapat melihat jadwal kuliah mingguan terstruktur berdasarkan KRS yang telah disetujui.
* **FR-MHS-05 (Nilai & KHS)**: Mahasiswa dapat melihat nilai akhir matakuliah, nilai IPS, IPK, serta mencetak dokumen KHS PDF.
* **FR-MHS-06 (Profil)**: Mahasiswa dapat memperbarui kontak pribadi, alamat domisili, dan mengganti foto profil.

### 4.4. Modul Dosen Pengajar & Dosen Pembimbing Akademik
* **FR-DSN-01**: Dosen dapat melihat jadwal mengajar mingguan dan daftar mahasiswa peserta kelas.
* **FR-DSN-02 (Upload Soal)**: Dosen dapat mengunggah file soal UTS dan UAS untuk setiap kelas yang diampu.
* **FR-DSN-03 (Penilaian)**: Dosen dapat menginput dan mengubah nilai komponen (Tugas, UTS, UAS) mahasiswa per kelas.
* **FR-DSN-04 (Persetujuan KRS)**: Khusus Dosen Wali: dapat melihat daftar mahasiswa bimbingan, meninjau rancangan KRS, serta menyetujui (*Approve*) atau menolak (*Reject*) dengan catatan revisi.
* **FR-DSN-05 (Profil)**: Dosen dapat memperbarui kontak pribadi dan foto profil.

---

## 5. 🏛️ Standar Kebijakan Akademik (Academic Rules & Defaults)

Mengacu pada standar umum pendidikan tinggi (SN-Dikti):

1. **Format Penomoran NIM**:
   * Format baku: `{KodeProdi}.{TahunAngkatan}.{Urutan5Digit}`
   * *Contoh*: `A11.2026.00001` (Program Studi Teknik Informatika, Angkatan 2026, Urutan ke-1).
2. **Batas Beban Belajar (SKS)**:
   * **Semester 1 & 2**: Sistem paket baku (20 – 22 SKS).
   * **Semester 3 ke atas**: Berdasarkan IPS semester sebelumnya:
     * $IPS \ge 3.00$ : Maksimal **24 SKS**
     * $2.50 \le IPS < 3.00$ : Maksimal **21 SKS**
     * $2.00 \le IPS < 2.50$ : Maksimal **18 SKS**
     * $IPS < 2.00$ : Maksimal **15 SKS**
3. **Skala Nilai & Huruf Mutu (7 Tingkat Standar)**:
   | Rentang Angka | Nilai Huruf | Bobot Angka | Kategori |
   | :---: | :---: | :---: | :--- |
   | $85 - 100$ | **A** | 4.00 | Sangat Istimewa |
   | $75 - 84$ | **AB** | 3.50 | Sangat Baik |
   | $70 - 74$ | **B** | 3.00 | Baik |
   | $65 - 69$ | **BC** | 2.50 | Cukup Baik |
   | $60 - 64$ | **C** | 2.00 | Cukup |
   | $50 - 59$ | **D** | 1.00 | Kurang |
   | $< 50$ | **E** | 0.00 | Gagal / Tidak Lulus |
4. **Bobot Standar Penilaian**:
   * Tugas / Kuis: 20%
   * Ujian Tengah Semester (UTS): 35%
   * Ujian Akhir Semester (UAS): 45%

---

## 6. 🛡️ Kebutuhan Non-Fungsional (Non-Functional Requirements)

1. **Keamanan (Security)**:
   * Seluruh kata sandi dienkripsi menggunakan `PASSWORD_BCRYPT`.
   * Enforce *Single-Device Login*: Sesi lama otomatis terputus saat login dari perangkat lain (`session_token`).
   * *Auto-Logout*: Sesi otomatis dihancurkan jika pengguna idle selama 30 menit.
   * Proteksi Brute-Force: Kunci akun otomatis 15 menit jika kata sandi salah 5 kali berturut-turut.
   * Whitelist upload berkas: Hanya file gambar (`jpg, png, webp`) dan dokumen (`pdf`) dengan pemeriksaan MIME asli (`mime_content_type`).
2. **Integritas Basis Data (Database Integrity)**:
   * Menggunakan PostgreSQL transaksi ACID (`beginTransaction`, `commit`, `rollBack`) pada operasi mutasi kritis (PMB ➔ Mahasiswa, Pembayaran, KRS).
   * Menjaga compatibility driver PostgreSQL Neon DB: `PDO::ATTR_EMULATE_PREPARES => true` dan `SET TIME ZONE 'Asia/Jakarta'`.
3. **Arsitektur Kode & Path Terpusat**:
   * Seluruh routing dan import menggunakan konstanta global di [config/paths.php](file:///d:/PROJECTS/app_web/sistem_akademik/config/paths.php) tanpa *relative path* bertingkat (`../../`).

---

## 7. 🚫 Hal di Luar Cakupan Versi 1.0 (Out of Scope / Non-Goals)
Fitur-fitur berikut **tidak diimplementasikan pada Versi 1.0** untuk menjaga ketepatan waktu rilis:
- ❌ Integrasi Payment Gateway otomatis (Midtrans/Xendit) — *Pembayaran murni upload transfer manual*.
- ❌ Ujian Seleksi Online Realtime (CBT interaktif).
- ❌ Integrasi sinkronisasi otomatis Feeder PDDIKTI.
- ❌ Presensi kuliah menggunakan QR Code / GPS Geolocation.
- ❌ Forum diskusi perkuliahan dan LMS interaktif materi belajar.

---

## 8. 🗺️ Rencana Rilis Bertahap (Phased Roadmap)

* **Fase 1: Pondasi & Keamanan Core (v0.1 – v0.8) — Selesai ✅**
  * Skema 10 tabel, keamanan sesi, autologout, modular views, dan alur PMB Online terproteksi.
* **Fase 2: Keuangan & Tagihan Mahasiswa (v0.9) — Sedang Berjalan / Target Besok ⏳**
  * Antarmuka tagihan UKT Admin, penerbitan tagihan massal, verifikasi bukti transfer, kuitansi PDF, dan gembok prasyarat KRS.
* **Fase 3: Penjadwalan, Pengisian KRS, & Dosen Wali (v1.0)**
  * Penawaran kelas matakuliah, pemilihan paket SKS mahasiswa, dan verifikasi KRS oleh Dosen Wali.
* **Fase 4: Penilaian Perkuliahan & Cetak KHS (v1.1)**
  * Upload soal UTS/UAS oleh dosen, form entri nilai per kelas, dan penerbitan KHS/Transkrip PDF.
