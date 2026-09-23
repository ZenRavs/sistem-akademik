# 🎓 Sistem Informasi Akademik (SIA) & PMB Online

Platform sistem informasi akademik terpadu berbasis web yang mengintegrasikan seluruh siklus hidup akademik perguruan tinggi: mulai dari **Penerimaan Mahasiswa Baru (PMB)**, **Manajemen Keuangan & Tagihan (Billing)**, **Perencanaan Studi (KRS & Jadwal)**, hingga **Perkuliahan & Penilaian (KHS & Transkrip)**.

---

## 🏛️ Arsitektur & Teknologi

* **Bahasa Backend**: PHP 8.2+ (Native PDO Architecture)
* **Basis Data**: PostgreSQL (Neon Cloud Database) - Arsitektur 10 Tabel Terstandarisasi
* **Antarmuka (Frontend)**: Bootstrap 5.3.3, Google Fonts (*Plus Jakarta Sans*), Bootstrap Icons 1.11.3
* **Mesin Pelaporan**: mPDF Engine (`src/reporting/`)
* **Keamanan**: Hash BCRYPT, *Single-Device Login Enforcement* (`session_token`), *Auto-Logout 30 Menit*, dan proteksi *Brute-Force*.

---

## 📚 Pusat Dokumentasi Resmi (`docs/`)

Seluruh dokumentasi teknis dan panduan pengembangan sistem dipusatkan di folder [docs/](file:///d:/PROJECTS/app_web/sistem_akademik/docs/):

| Dokumen | Deskripsi |
| :--- | :--- |
| 📄 **[Product Requirements Document (PRD)](file:///d:/PROJECTS/app_web/sistem_akademik/docs/PRD.md)** | Spesifikasi lengkap kebutuhan produk, matriks peran (*RBAC*), aturan akademik (SN-Dikti), dan batasan sistem. |
| 🚀 **[Progression Tracking](file:///d:/PROJECTS/app_web/sistem_akademik/docs/PROGRESSION.md)** | Log progres harian, status pencapaian sistem, catatan teknis, serta agenda kerja harian. |
| 📜 **[Changelogs](file:///d:/PROJECTS/app_web/sistem_akademik/docs/CHANGELOGS.md)** | Riwayat seluruh pembaruan teknis sistem dari versi awal hingga versi terkini (`v0.8.0`). |
| 🧭 **[Branch Guidelines](file:///d:/PROJECTS/app_web/sistem_akademik/docs/BRANCH_GUIDELINES.md)** | Panduan isolasi konteks per modul (*scope boundaries*), kepemilikan file, dan batasan kerja branch. |

---

## 👥 Peran Pengguna (User Roles)

1. **Superadmin**: Akses menyeluruh, manajemen akun kredensial, dan konfigurasi sistem.
2. **Admin (Tata Usaha)**: Verifikasi PMB, master data (mahasiswa, dosen, kurikulum), penerbitan tagihan UKT, dan penawaran jadwal kelas.
3. **Dosen**: Jadwal mengajar, unggah soal UTS/UAS, penilaian (Tugas, UTS, UAS), dan persetujuan (approval) KRS mahasiswa bimbingan.
4. **Mahasiswa**: Pembayaran tagihan UKT, pengisian rencana studi (KRS), jadwal kuliah, dan cetak Kartu Hasil Studi (KHS).
5. **Pendaftar (PMB)**: Registrasi online, kelengkapan berkas KTP/ijazah, unggah bukti bayar formulir, dan pemantauan kelulusan.

---

## 🚀 Memulai (Getting Started)

1. Salin konfigurasi environment:
   ```bash
   cp .env.example .env
   ```
2. Sesuaikan kredensial koneksi PostgreSQL Neon di file `.env`.
3. Jalankan server lokal:
   ```bash
   run.bat
   ```
   Aplikasi akan berjalan di `http://localhost:8000`.
