# 🧭 PANDUAN STRUKTUR, BATASAN, & HAK AKSES PATH BRANCH (BRANCH GUIDELINES)
# Sistem Informasi Akademik (SIA) & PMB Online

Dokumen ini adalah **pedoman resmi pembagian kerja berbasis modul (*Vertical Slice Architecture*)**. Tujuannya adalah mencegah kebingungan pengembang, mengisolasi konteks AI agar tidak tumpang tindih (*context window pollution*), dan menjamin tidak ada tabrakan kode (*code collision*) antar-fitur.

---

## 📑 Daftar Isi
1. [Prinsip Dasar & Aturan Emas](#1-prinsip-dasar--aturan-emas-golden-rules)
2. [Peta Arsitektur 7 Branch Sistem](#2-peta-arsitektur-7-branch-sistem)
3. [Matriks Hak Akses Path & Direktori (Path Access Matrix)](#3-matriks-hak-akses-path--direktori-path-access-matrix)
4. [Rincian Spesifikasi & Kebijakan Path per Branch](#4-rincian-spesifikasi--kebijakan-path-per-branch)
5. [Template Prompt Pembuka (Dipisahkan ke PROMPT_TEMPLATES.md)](#5--template-prompt-pembuka-dipisahkan-ke-prompt_templatesmd)
6. [Alur Kerja Sinkronisasi & Git Workflow](#6-alur-kerja-sinkronisasi--git-workflow)

---

## 1. 🌟 Prinsip Dasar & Aturan Emas (Golden Rules)

### Mengapa Berbasis "Per Modul" (Vertical Slice)?
Sebelumnya, branch dibagi berdasarkan lapisan teknis (*Database, UI, Logic, Path*). Hal ini membingungkan karena satu fitur (misal: *Tagihan*) membutuhkan perubahan di database, logic, sekaligus UI.
Dengan pendekatan **Vertical Slice (Per Modul)**:
* Satu branch menyelesaikan satu fitur secara tuntas dari UI, backend API, hingga tabel database.
* Konteks chat AI menjadi sangat fokus (*laser-focused*), tidak cepat penuh, dan tidak terdistraksi modul lain.

### 4 Aturan Emas Setiap Branch:
1. **Kepatuhan Hak Akses Path & Protokol Override (Y/N)**: Setiap branch hanya boleh mengedit path yang masuk dalam daftar 🟢 **Write Access**. Jika suatu saat branch MUTLAK memerlukan perubahan pada path yang berstatus 🔴 **Forbidden / Blocked**, AI/Developer **DILARANG LANGSUNG MENGEDITNYA**. AI WAJIB berhenti, menjelaskan alasan urgensinya, potensi risiko dampaknya, dan **HARUS meminta konfirmasi eksplisit (Y/N) kepada pengguna** sebelum menyentuh file tersebut.
2. **Baca Acuan Resmi**: Setiap kali memulai sesi branch, selalu rujuk [docs/PRD.md](file:///d:/PROJECTS/app_web/sistem_akademik/docs/PRD.md) dan [docs/PROGRESSION.md](file:///d:/PROJECTS/app_web/sistem_akademik/docs/PROGRESSION.md).
3. **Keamanan Transaksi Database**: Operasi mutasi data kritis wajib menggunakan transaksi PDO (`beginTransaction`, `commit`, `rollBack`).
4. **Pelaporan Terpusat**: Setelah pekerjaan di branch modul selesai, laporkan ringkasan perubahan ke **Main Chat** untuk dicatat di [docs/PROGRESSION.md](file:///d:/PROJECTS/app_web/sistem_akademik/docs/PROGRESSION.md) dan [docs/CHANGELOGS.md](file:///d:/PROJECTS/app_web/sistem_akademik/docs/CHANGELOGS.md).

---

## 2. 🗺️ Peta Arsitektur 7 Branch Sistem

```
📦 REPOSITORI UTAMA (Branch: main)
├── 🏠 [Branch 0: Main Chat / Orchestrator] ── Roadmap, PRD, Progression, Arsitektur Global
│
├── 🎓 [Branch 1: Modul PMB & Penerimaan] ──── Pendaftaran, berkas, verifikasi, terbit NIM
├── 👥 [Branch 2: Modul Master Akademik] ──── Mahasiswa, Dosen, Matakuliah, Program Studi
├── 💳 [Branch 3: Modul Keuangan & Tagihan] ── UKT/Billing Admin, bayar mhs, syarat buka KRS
├── 📝 [Branch 4: Modul Penjadwalan & KRS] ── Penawaran kelas, pemilihan SKS, approval wali
├── 📊 [Branch 5: Modul Nilai & Transkrip] ── Upload soal UTS/UAS, entri nilai, cetak KHS
└── 🔐 [Branch 6: Modul Auth & Keamanan] ──── Login portal, session security, manajemen user
```

---

## 3. 🛡️ Matriks Hak Akses Path & Direktori (Path Access Matrix)

Tabel berikut menentukan izin akses direktori untuk setiap branch:
* 🟢 **Write**: Boleh membuat, mengedit, atau menghapus file pada direktori tersebut.
* 🟡 **Read-Only**: Boleh membaca isi file sebagai referensi/dependensi, **dilarang mengubah isinya**.
* 🔴 **Forbidden**: Dilarang keras membuka, memanggil, atau mengedit file tersebut di branch bersangkutan.

| Path Direktori / File | B-0: Main Chat | B-1: PMB | B-2: Master | B-3: Billing | B-4: KRS | B-5: Nilai | B-6: Auth |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: |
| `docs/*` & `README.md` | 🟢 RW | 🟡 Read | 🟡 Read | 🟡 Read | 🟡 Read | 🟡 Read | 🟡 Read |
| `config/db.php` & `paths.php` | 🟢 RW | 🟡 Read | 🟡 Read | 🟡 Read | 🟡 Read | 🟡 Read | 🟡 Read |
| `schema.sql` (Global DDL) | 🟢 RW | 🟡 Read | 🟡 Read | 🟡 Read | 🟡 Read | 🟡 Read | 🟡 Read |
| `index.php` & `portal.php` | 🟢 RW | 🟡 Read | 🟡 Read | 🟡 Read | 🟡 Read | 🟡 Read | 🟢 Write |
| `views/layouts/*` (Shells) | 🟢 RW | 🟡 Read | 🟡 Read | 🟡 Read | 🟡 Read | 🟡 Read | 🟡 Read |
| `views/applicant/*` | 🟢 RW | 🟢 Write | 🔴 Block | 🔴 Block | 🔴 Block | 🔴 Block | 🔴 Block |
| `views/admin/applicants.php` | 🟢 RW | 🟢 Write | 🔴 Block | 🔴 Block | 🔴 Block | 🔴 Block | 🔴 Block |
| `views/admin/students.php` & form | 🟢 RW | 🔴 Block | 🟢 Write | 🔴 Block | 🔴 Block | 🔴 Block | 🔴 Block |
| `views/admin/lecturers.php` & form | 🟢 RW | 🔴 Block | 🟢 Write | 🔴 Block | 🔴 Block | 🔴 Block | 🔴 Block |
| `views/admin/courses.php` & form | 🟢 RW | 🔴 Block | 🟢 Write | 🔴 Block | 🔴 Block | 🔴 Block | 🔴 Block |
| `views/admin/students_bills.php` | 🟢 RW | 🔴 Block | 🔴 Block | 🟢 Write | 🔴 Block | 🔴 Block | 🔴 Block |
| `views/student/bills.php` | 🟢 RW | 🔴 Block | 🔴 Block | 🟢 Write | 🔴 Block | 🔴 Block | 🔴 Block |
| `views/admin/course_schedule.php` | 🟢 RW | 🔴 Block | 🔴 Block | 🔴 Block | 🟢 Write | 🔴 Block | 🔴 Block |
| `views/admin/students_krs_items.php` | 🟢 RW | 🔴 Block | 🔴 Block | 🔴 Block | 🟢 Write | 🔴 Block | 🔴 Block |
| `views/student/krs_items.php` | 🟢 RW | 🔴 Block | 🔴 Block | 🔴 Block | 🟢 Write | 🔴 Block | 🔴 Block |
| `views/lecturer/krs_approval.php` | 🟢 RW | 🔴 Block | 🔴 Block | 🔴 Block | 🟢 Write | 🔴 Block | 🔴 Block |
| `views/lecturer/schedule.php` | 🟢 RW | 🔴 Block | 🔴 Block | 🔴 Block | 🟢 Write | 🔴 Block | 🔴 Block |
| `views/lecturer/grading.php` | 🟢 RW | 🔴 Block | 🔴 Block | 🔴 Block | 🔴 Block | 🟢 Write | 🔴 Block |
| `views/student/grades.php` | 🟢 RW | 🔴 Block | 🔴 Block | 🔴 Block | 🔴 Block | 🟢 Write | 🔴 Block |
| `views/admin/users.php` & form | 🟢 RW | 🔴 Block | 🔴 Block | 🔴 Block | 🔴 Block | 🔴 Block | 🟢 Write |
| `public/uploads/pmb_docs/*` | 🟢 RW | 🟢 Write | 🔴 Block | 🔴 Block | 🔴 Block | 🔴 Block | 🔴 Block |
| `public/uploads/user_photos/*` | 🟢 RW | 🟢 Write | 🟢 Write | 🔴 Block | 🔴 Block | 🔴 Block | 🔴 Block |
| `src/reporting/*` | 🟢 RW | 🔴 Block | 🟢 Master | 🟢 Billing | 🟢 KRS | 🟢 KHS | 🔴 Block |
| `src/api.php` | 🟢 RW | 🟢 (PMB) | 🟢 (Master) | 🟢 (Bill) | 🟢 (KRS) | 🟢 (Grade) | 🟢 (Auth) |
| `scripts/*` (Skrip Utilitas/Migrasi) | 🟢 RW | 🟡 Read | 🟡 Read | 🟡 Read | 🟡 Read | 🟡 Read | 🟡 Read |

> ⚠️ **Catatan untuk `src/api.php`**: Karena `src/api.php` adalah controller terpusat, setiap branch **HANYA diizinkan mengedit `case` switch-statement yang sesuai dengan modulnya**. Dilarang mengubah case milik modul lain.

### 🛑 Protokol Wajib Konfirmasi (Y/N) untuk Akses Jalur Terlarang (Blocked Path Override)
Jika saat mengerjakan suatu modul terjadi situasi di mana branch **MUTLAK memerlukan perubahan pada path/file yang berstatus 🔴 Forbidden**:
1. **Dilarang Langsung Mengedit**: AI / Developer tidak boleh melakukan perubahan apa pun ke file tersebut secara sepihak.
2. **Wajib Ajukan Konfirmasi Y/N**: AI harus menghentikan proses pengerjaan sementara dan mengirimkan pesan konfirmasi terstruktur dengan format baku berikut:

```markdown
🛑 [PERINGATAN: AKSES JALUR TERLARANG (BLOCKED PATH)]
Branch ini mendeteksi kebutuhan mendesak untuk mengubah file di luar kepemilikan modulnya:
- File Target: `path/to/blocked_file.php` (Status: 🔴 Forbidden)
- Alasan Urgensi: [Jelaskan secara teknis mengapa file ini harus diubah]
- Potensi Risiko: [Jelaskan apakah ini bisa berdampak pada modul lain]
- Rencana Perubahan: [Rangkuman baris/fungsi yang ingin diubah]

Apakah Anda mengizinkan branch ini untuk mengedit file tersebut? [Y / N]:
```
3. **Aturan Eksekusi**:
   * Jika Pengguna menjawab **`Y` (Yes)**: AI diizinkan melanjutkan perubahan khusus pada baris yang dimintakan izin.
   * Jika Pengguna menjawab **`N` (No)**: AI wajib membatalkan rencana tersebut dan mencari solusi alternatif yang hanya menggunakan file dalam daftar 🟢 Write Access modulnya.

---

## 4. 🔍 Rincian Spesifikasi & Kebijakan Path per Branch

---

### 🏠 Branch 0: Main Chat (Hub & Project Orchestrator)
* **Peran**: Bertindak sebagai *Product Owner*, *System Architect*, *Project Manager*, dan *Lead Technical Orchestrator*.
* **Tanggung Jawab**:
  * Menjaga dokumen arsitektur, dokumentasi proyek, dan koordinasi progres global.
  * Mengatur skema DDL basis data global (`schema.sql`) serta skrip migrasi/utilitas.
  * Membagi sprint kerja dan delegasi tugas ke branch modul (B-1 s/d B-6).
  * Melakukan orkestrasi, refaktorisasi lintas modul, serta integrasi global codebase.
* **Kebijakan Hak Akses Path**:
  * 🟢 **Full Read & Write (RW) Global**: Memiliki hak akses penuh membaca dan menulis (**RW**) ke seluruh file, folder, dan subdirektori proyek tanpa batasan (`docs/`, `config/`, `src/`, `views/`, `scripts/`, `public/`, `schema.sql`, `index.php`, `portal.php`, dll.).
  * 🔴 **Forbidden**: **Nihil** (Bebas / Tanpa batasan hak akses).
  * 💡 **Prinsip Operasional**: Meskipun memiliki hak RW global, Branch 0 tetap mengutamakan pendelegasian pengerjaan fitur/tampilan spesifik ke sub-branch modul bersangkutan (B-1 s/d B-6) demi menjaga isolasi konteks kerja.

---

### 🎓 Branch 1: Modul PMB & Penerimaan
* **Peran**: Menangani alur pendaftaran calon mahasiswa baru hingga terbit akun mahasiswa dan NIM resmi.
* **Tabel Database**: `pmb_data`, `personal_profiles`, `majors_data`.
* **Kebijakan Hak Akses Path**:
  * 🟢 **Write**:
    - `views/applicant/` (termasuk `portal.php`)
    - `views/admin/applicants.php`
    - `public/uploads/pmb_docs/` (`certificates/` dan `payments/`)
    - `src/api.php` *(Hanya case: `applicantRegister`, `applicantLogin`, `applicantUpdateProfile`, `verifyApplicant`)*
  * 🟡 **Read-Only**: `config/paths.php`, `config/db.php`, `views/layouts/applicant_layout.php`, `views/layouts/admin_layout.php`, `schema.sql`.
  * 🔴 **Forbidden**: `views/student/*`, `views/lecturer/*`, `views/admin/students.php`, `views/admin/students_bills.php`, `views/admin/course_schedule.php`, `src/reporting/*`.

---

### 👥 Branch 2: Modul Master Data Akademik
* **Peran**: Mengelola data induk institusi (Mahasiswa, Dosen, Matakuliah, Program Studi).
* **Tabel Database**: `students_data`, `lecturers_data`, `courses_data`, `majors_data`, `personal_profiles`.
* **Kebijakan Hak Akses Path**:
  * 🟢 **Write**:
    - `views/admin/students.php`, `views/admin/lecturers.php`, `views/admin/courses.php`
    - `views/admin/forms/student_form.php`, `lecturer_form.php`, `course_form.php`
    - `src/reporting/pdf_students.php`, `pdf_lecturers.php`, `pdf_courses.php`
    - `public/uploads/user_photos/`
    - `src/api.php` *(Hanya case: `fetchStudents`, `getStudentDetail`, `updateStudent`, `deleteStudent`, `fetchLecturers`, `fetchCourses`, dan CRUD Master)*
  * 🟡 **Read-Only**: `config/paths.php`, `config/db.php`, `views/layouts/admin_layout.php`.
  * 🔴 **Forbidden**: `views/applicant/*`, `views/student/bills.php`, `views/student/krs_items.php`, `views/lecturer/grading.php`, `views/admin/students_bills.php`.

---

### 💳 Branch 3: Modul Keuangan & Tagihan Mahasiswa (Billing)
* **Peran**: Mengelola tagihan UKT/SPP mahasiswa, verifikasi pembayaran manual, dan prasyarat administrasi KRS.
* **Tabel Database**: `students_bills_data`, `students_data` (kolom `payment_status`).
* **Kebijakan Hak Akses Path**:
  * 🟢 **Write**:
    - `views/admin/students_bills.php`
    - `views/student/bills.php`
    - `src/reporting/pdf_bill_receipt.php`
    - `config/tuition_fees.json`, `config/payment_methods.json`
    - `src/api.php` *(Hanya case: `fetchBills`, `generateBills`, `verifyBillPayment`, `studentUploadPaymentProof`)*
  * 🟡 **Read-Only**: `config/paths.php`, `config/db.php`, `views/layouts/admin_layout.php`, `views/layouts/student_layout.php`.
  * 🔴 **Forbidden**: `views/admin/courses.php`, `views/admin/course_schedule.php`, `views/student/krs_items.php`, `views/lecturer/grading.php`, `views/applicant/*`.

---

### 📝 Branch 4: Modul Penjadwalan & Rencana Studi (KRS)
* **Peran**: Mengelola operasional awal semester (penawaran kelas, pengambilan SKS mahasiswa, dan bimbingan dosen wali).
* **Tabel Database**: `students_krs_data`, `courses_data`, `lecturers_data`, `students_data`.
* **Kebijakan Hak Akses Path**:
  * 🟢 **Write**:
    - `views/admin/course_schedule.php` & `views/admin/forms/course_schedule_form.php`
    - `views/admin/students_krs_items.php`
    - `views/student/krs_items.php`
    - `views/lecturer/krs_approval.php` & `views/lecturer/schedule.php`
    - `src/reporting/pdf_course_schedule.php`
    - `src/api.php` *(Hanya case: `fetchCourseSchedule`, `saveCourseSchedule`, `studentSubmitKRS`, `lecturerApproveKRS`)*
  * 🟡 **Read-Only**: `config/paths.php`, `config/db.php`, `views/layouts/*`.
  * 🔴 **Forbidden**: `views/admin/students_bills.php`, `views/student/bills.php`, `views/applicant/*`, `views/lecturer/grading.php`.

---

### 📊 Branch 5: Modul Penilaian, KHS, & Transkrip
* **Peran**: Mengelola evaluasi perkuliahan akhir semester, unggah berkas ujian, dan publikasi hasil studi.
* **Tabel Database**: `students_score_data`, `students_krs_data`, `students_data`.
* **Kebijakan Hak Akses Path**:
  * 🟢 **Write**:
    - `views/lecturer/grading.php` (termasuk form & modal unggah soal UTS/UAS)
    - `views/student/grades.php`
    - `public/uploads/exam_papers/` (penyimpanan dokumen soal ujian)
    - `src/reporting/pdf_khs.php`, `src/reporting/pdf_transcript.php`
    - `src/api.php` *(Hanya case: `fetchClassGrades`, `saveClassGrades`, `uploadExamPaper`, `fetchStudentTranscript`)*
  * 🟡 **Read-Only**: `config/paths.php`, `config/db.php`, `views/layouts/*`.
  * 🔴 **Forbidden**: `views/admin/course_schedule.php`, `views/student/krs_items.php`, `views/admin/students_bills.php`, `views/applicant/*`.

---

### 🔐 Branch 6: Modul Autentikasi, Keamanan Sesi, & User
* **Peran**: Mengelola pintu gerbang sistem, sesi login, enkripsi, dan manajemen akun superadmin.
* **Tabel Database**: `users_credential`, `personal_profiles`.
* **Kebijakan Hak Akses Path**:
  * 🟢 **Write**:
    - `index.php` (tampilan portal login 3-tab & fitur toggle password)
    - `portal.php` (dispatcher, single-device session check, idle timeout 30 menit)
    - `views/admin/users.php` & form user
    - `src/api.php` *(Hanya case: `userLogin`, `userLogout`, `forceLogout`, `setTheme`, `resetUserPassword`, `manageUsers`)*
  * 🟡 **Read-Only**: `config/db.php`, `config/paths.php`, `config/theme_setting.json`.
  * 🔴 **Forbidden**: Seluruh view akademik: `views/student/*`, `views/lecturer/*`, `views/admin/students_bills.php`, `views/admin/course_schedule.php`.

---

## 5. 📋 Template Prompt Pembuka (Dipisahkan ke PROMPT_TEMPLATES.md)

Untuk menjaga dokumen ini tetap fokus sebagai pedoman arsitektur dan batasan kerja, seluruh template prompt pembuka siap pakai telah dipisahkan ke dalam dokumen khusus:

👉 **[docs/PROMPT_TEMPLATES.md](file:///d:/PROJECTS/app_web/sistem_akademik/docs/PROMPT_TEMPLATES.md)**

Silakan buka dokumen di atas untuk menyalin (*copy-paste*) prompt pembuka sesi saat membuat chat baru:
* 🎓 **Branch 1**: Modul PMB & Penerimaan Mahasiswa Baru
* 👥 **Branch 2**: Modul Master Data Akademik (Mahasiswa, Dosen, Matakuliah, Prodi)
* 💳 **Branch 3**: Modul Keuangan & Tagihan Mahasiswa (Billing)
* 📝 **Branch 4**: Modul Penjadwalan & Rencana Studi (KRS)
* 📊 **Branch 5**: Modul Penilaian, KHS, & Transkrip
* 🔐 **Branch 6**: Modul Autentikasi, Keamanan Sesi, & User

---

## 6. 🔄 Alur Kerja Sinkronisasi & Git Workflow

1. **Memulai Pekerjaan**:
   * Buka chat branch modul terkait dengan menyalin template prompt di atas.
2. **Eksekusi & Uji Coba**:
   * Kerjakan kode hanya pada direktori yang masuk dalam daftar 🟢 **Write Access**.
   * Langsung uji coba fungsionalitasnya di browser lokal / Docker.
3. **Commit Fitur**:
   * Gunakan format pesan commit standar (*Conventional Commits*):
     * `feat(billing): implement mass bill generation in admin panel`
     * `fix(pmb): prevent duplicate sequence on auto-generate nim`
     * `style(krs): improve responsive table layout for mobile view`
4. **Pelaporan Balik ke Main Chat**:
   * Setelah fitur selesai dan stabil, kembali ke **Main Chat** lalu tulis:
     > *"Pekerjaan di Modul [Nama Modul] selesai. Tolong perbarui `docs/PROGRESSION.md` dan `docs/CHANGELOGS.md`."*
   * Main Chat akan mengaudit perubahan dan mencatat milestone baru sistem.
