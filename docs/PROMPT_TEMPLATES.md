# Are u ai model? Do not read this file!

# 📋 TEMPLATE PROMPT PEMBUKA SESI BRANCH (PROMPT TEMPLATES)
# Sistem Informasi Akademik (SIA) & PMB Online

Dokumen ini berisi kumpulan **template prompt pembuka siap pakai (*ready-to-use opening prompts*)** untuk setiap branch chat. Salin (*copy-paste*) prompt yang sesuai sebagai pesan pertama saat Anda membuka chat branch baru.

> 📖 **Panduan Acuan**: Sebelum memulai, pastikan selalu merujuk [docs/BRANCH_GUIDELINES.md](file:///d:/PROJECTS/app_web/sistem_akademik/docs/BRANCH_GUIDELINES.md), [docs/PRD.md](file:///d:/PROJECTS/app_web/sistem_akademik/docs/PRD.md), dan [docs/PROGRESSION.md](file:///d:/PROJECTS/app_web/sistem_akademik/docs/PROGRESSION.md).

---

## 📑 Daftar Template Branch

1. [Branch 1: Modul PMB & Penerimaan](#1-🎓-template-branch-modul-pmb--penerimaan)
2. [Branch 2: Modul Master Data Akademik](#2-👥-template-branch-modul-master-data-akademik)
3. [Branch 3: Modul Keuangan & Tagihan (Billing)](#3-💳-template-branch-modul-keuangan--tagihan-billing)
4. [Branch 4: Modul Penjadwalan & Rencana Studi (KRS)](#4-📝-template-branch-modul-penjadwalan--krs)
5. [Branch 5: Modul Penilaian, KHS, & Transkrip](#5-📊-template-branch-modul-nilai--transkrip)
6. [Branch 6: Modul Autentikasi, Keamanan Sesi, & User](#6-🔐-template-branch-modul-auth--keamanan)

---

### 1. 🎓 Template: Branch Modul PMB & Penerimaan
```markdown
Halo! Di chat branch ini kita HANYA FOKUS pada: MODUL PMB & PENERIMAAN MAHASISWA BARU.
Acuan resmi kita:
1. Baca docs/BRANCH_GUIDELINES.md bagian "Branch 1: Modul PMB & Penerimaan".
2. Patuhi Kebijakan Hak Akses Path:
   - WRITE ACCESS: views/applicant/*, views/admin/applicants.php, public/uploads/pmb_docs/*, API PMB di src/api.php.
   - FORBIDDEN: Jangan mengubah tagihan mahasiswa lama, jadwal kelas, KRS, atau nilai.
3. Baca docs/PRD.md seksi 3.1 dan FR-PMB.
4. PROTOKOL BLOCKED PATH: Jika Anda mutlak memerlukan akses edit pada file berstatus FORBIDDEN, Anda DILARANG langsung mengeditnya. Anda HARUS berhenti dan meminta konfirmasi eksplisit (Y/N) kepada saya terlebih dahulu!
Mari kita mulai!
```

---

### 2. 👥 Template: Branch Modul Master Data Akademik
```markdown
Halo! Di chat branch ini kita HANYA FOKUS pada: MODUL MASTER DATA AKADEMIK (Mahasiswa, Dosen, Matakuliah, Prodi).
Acuan resmi kita:
1. Baca docs/BRANCH_GUIDELINES.md bagian "Branch 2: Modul Master Data Akademik".
2. Patuhi Kebijakan Hak Akses Path:
   - WRITE ACCESS: views/admin/students.php, lecturers.php, courses.php, forms terkait, PDF reporting di src/reporting/, API master di src/api.php.
   - FORBIDDEN: Jangan menyentuh transaksi pembayaran UKT atau alur PMB.
3. Baca docs/PRD.md seksi FR-ADM-02, FR-ADM-03, FR-ADM-04.
4. PROTOKOL BLOCKED PATH: Jika Anda mutlak memerlukan akses edit pada file berstatus FORBIDDEN, Anda DILARANG langsung mengeditnya. Anda HARUS berhenti dan meminta konfirmasi eksplisit (Y/N) kepada saya terlebih dahulu!
Mari kita mulai!
```

---

### 3. 💳 Template: Branch Modul Keuangan & Tagihan (Billing)
```markdown
Halo! Di chat branch ini kita HANYA FOKUS pada: MODUL KEUANGAN & TAGIHAN MAHASISWA.
Acuan resmi kita:
1. Baca docs/BRANCH_GUIDELINES.md bagian "Branch 3: Modul Keuangan & Tagihan".
2. Patuhi Kebijakan Hak Akses Path:
   - WRITE ACCESS: views/admin/students_bills.php, views/student/bills.php, src/reporting/pdf_bill_receipt.php, config/tuition_fees.json, API billing di src/api.php.
   - FORBIDDEN: Jangan mengedit jadwal kelas, KRS, nilai, atau profil PMB.
3. Baca docs/PRD.md seksi 3.2 dan FR-ADM-06, FR-MHS-02.
4. Baca docs/PROGRESSION.md untuk target aktif.
5. PROTOKOL BLOCKED PATH: Jika Anda mutlak memerlukan akses edit pada file berstatus FORBIDDEN, Anda DILARANG langsung mengeditnya. Anda HARUS berhenti dan meminta konfirmasi eksplisit (Y/N) kepada saya terlebih dahulu!
Mari kita mulai eksekusi modul tagihan!
```

---

### 4. 📝 Template: Branch Modul Penjadwalan & KRS
```markdown
Halo! Di chat branch ini kita HANYA FOKUS pada: MODUL PENJADWALAN KULIAH & KRS (Rencana Studi).
Acuan resmi kita:
1. Baca docs/BRANCH_GUIDELINES.md bagian "Branch 4: Modul Penjadwalan & KRS".
2. Patuhi Kebijakan Hak Akses Path:
   - WRITE ACCESS: views/admin/course_schedule.php, views/student/krs_items.php, views/lecturer/krs_approval.php, views/lecturer/schedule.php, PDF schedule, API KRS di src/api.php.
   - FORBIDDEN: Jangan mengubah nominal biaya tagihan atau seleksi PMB.
3. Baca docs/PRD.md seksi 3.3, FR-ADM-05, FR-MHS-03, FR-DSN-04.
4. PROTOKOL BLOCKED PATH: Jika Anda mutlak memerlukan akses edit pada file berstatus FORBIDDEN, Anda DILARANG langsung mengeditnya. Anda HARUS berhenti dan meminta konfirmasi eksplisit (Y/N) kepada saya terlebih dahulu!
Mari kita mulai!
```

---

### 5. 📊 Template: Branch Modul Nilai & Transkrip
```markdown
Halo! Di chat branch ini kita HANYA FOKUS pada: MODUL PENILAIAN, KHS, & TRANSKRIP AKADEMIK.
Acuan resmi kita:
1. Baca docs/BRANCH_GUIDELINES.md bagian "Branch 5: Modul Nilai & Transkrip".
2. Patuhi Kebijakan Hak Akses Path:
   - WRITE ACCESS: views/lecturer/grading.php (input nilai & upload soal), views/student/grades.php, PDF KHS/Transkrip, API penilaian di src/api.php.
   - FORBIDDEN: Jangan mengubah jadwal kelas atau membuka/menutup periode KRS.
3. Baca docs/PRD.md seksi 3.4, FR-DSN-02, FR-DSN-03, FR-MHS-05.
4. PROTOKOL BLOCKED PATH: Jika Anda mutlak memerlukan akses edit pada file berstatus FORBIDDEN, Anda DILARANG langsung mengeditnya. Anda HARUS berhenti dan meminta konfirmasi eksplisit (Y/N) kepada saya terlebih dahulu!
Mari kita mulai!
```

---

### 6. 🔐 Template: Branch Modul Auth & Keamanan
```markdown
Halo! Di chat branch ini kita HANYA FOKUS pada: MODUL AUTENTIKASI, KEAMANAN SESI, & USER.
Acuan resmi kita:
1. Baca docs/BRANCH_GUIDELINES.md bagian "Branch 6: Modul Auth & Keamanan".
2. Patuhi Kebijakan Hak Akses Path:
   - WRITE ACCESS: index.php, portal.php, views/admin/users.php & form akun, API auth/session di src/api.php.
   - FORBIDDEN: Jangan mengubah alur bisnis akademik (KRS, jadwal, tagihan, penilaian).
3. Baca docs/PRD.md seksi 2 (RBAC) dan seksi 6 (NFR Security).
4. PROTOKOL BLOCKED PATH: Jika Anda mutlak memerlukan akses edit pada file berstatus FORBIDDEN, Anda DILARANG langsung mengeditnya. Anda HARUS berhenti dan meminta konfirmasi eksplisit (Y/N) kepada saya terlebih dahulu!
Mari kita mulai!
```
