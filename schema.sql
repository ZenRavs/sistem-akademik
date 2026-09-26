-- ========================================================
-- Schema Database PostgreSQL Neon Cloud (Sistem AKADEMIK FIK)
-- Arsitektur Enterprise: 10 Tabel Terstandarisasi
-- Sesuai Blueprint di config/ai_context/db_refactoring.md
-- ========================================================

-- 1. users_credential (Master Akun & Otentikasi Terpusat)
CREATE TABLE IF NOT EXISTS users_credential (
    id              BIGSERIAL PRIMARY KEY,
    username        VARCHAR(100) UNIQUE NOT NULL, -- NIK / NIM / NPP / Username PMB
    email           VARCHAR(100) UNIQUE NOT NULL,
    password        VARCHAR(255) NOT NULL,        -- Hash BCRYPT
    role            VARCHAR(30) NOT NULL,         -- 'applicant', 'student', 'lecturer', 'admin', 'superadmin'
    account_status  VARCHAR(30) DEFAULT 'active' CHECK (account_status IN ('active', 'suspended', 'banned', 'archived', 'pending_activation')),
    last_login_at   TIMESTAMP DEFAULT NULL,
    last_active_at  TIMESTAMP DEFAULT NULL,       -- Waktu aktivitas terakhir (deteksi online/offline & auto-logout)
    session_token   VARCHAR(255) DEFAULT NULL,    -- Token sesi tunggal (single-device login enforcement)
    failed_attempts SMALLINT DEFAULT 0,           -- Proteksi brute-force login
    locked_until    TIMESTAMP DEFAULT NULL,       -- Masa kunci akun sementara saat berkali-kali salah password
    pswd_reset      VARCHAR(255) DEFAULT NULL,    -- Token/Status reset password (persiapan fitur reset password)
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. personal_profiles (Dokumen Identitas KTP/KK/Akta & Kontak Darurat)
CREATE TABLE IF NOT EXISTS personal_profiles (
    id               BIGSERIAL PRIMARY KEY,
    user_id          BIGINT UNIQUE NOT NULL REFERENCES users_credential(id) ON DELETE CASCADE,
    nik              VARCHAR(16) UNIQUE,          -- No. KTP 16-Digit
    full_name        VARCHAR(255) NOT NULL,       -- Nama Lengkap Sesuai Dokumen Resmi
    gender           VARCHAR(20),                 -- Laki-laki / Perempuan
    pob              VARCHAR(100),                -- Tempat Lahir
    dob              DATE,                        -- Tanggal Lahir
    religion         VARCHAR(50),                 -- Agama
    marital_status   VARCHAR(50) DEFAULT NULL,
    job_status       VARCHAR(50) DEFAULT NULL,
    phone            VARCHAR(50),                 -- No. HP / WhatsApp Utama
    emerg_phone      VARCHAR(50),                 -- No. Kontak Darurat (Emergency Contact)
    ktp_address      TEXT,                        -- Alamat Sesuai KTP
    domicile_address TEXT,                        -- Alamat Domisili Saat Ini
    photo            VARCHAR(255),                -- Nama File Foto Profil
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. majors_data (Master Data Program Studi / Jurusan)
CREATE TABLE IF NOT EXISTS majors_data (
    id          SERIAL PRIMARY KEY,
    major_code  VARCHAR(10) UNIQUE NOT NULL, -- contoh: 'A11', 'A12', 'A14', 'A15', 'A22'
    major_name  VARCHAR(100) NOT NULL,       -- contoh: 'Teknik Informatika', 'Sistem Informasi'
    degree      VARCHAR(20) NOT NULL,        -- contoh: 'S1', 'D3', 'S2'
    faculty     VARCHAR(100) DEFAULT 'Fakultas Ilmu Komputer',
    is_active   BOOLEAN DEFAULT TRUE,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. courses_data (Master Data Matakuliah)
CREATE TABLE IF NOT EXISTS courses_data (
    id            SERIAL PRIMARY KEY,
    major_id      INT NOT NULL REFERENCES majors_data(id) ON DELETE CASCADE,
    course_code   VARCHAR(20) UNIQUE NOT NULL,                      -- contoh: 'A11.54301'
    course_name   VARCHAR(150) NOT NULL,                            -- contoh: 'Pemrograman Web'
    credits       INT NOT NULL DEFAULT 3,                           -- Jumlah SKS
    semester      INT NOT NULL,                                     -- Semester Rekomendasi (1-8)
    course_type   VARCHAR(30) DEFAULT 'Wajib',                      -- 'Wajib', 'Pilihan', 'Praktikum'
    is_active     BOOLEAN DEFAULT TRUE,                             -- Kontrol Kurikulum Aktif
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. pmb_data (Berkas Pendaftaran PMB, Sekolah Asal, & Orang Tua)
CREATE TABLE IF NOT EXISTS pmb_data (
    id                   BIGSERIAL PRIMARY KEY,
    user_id              BIGINT UNIQUE NOT NULL REFERENCES users_credential(id) ON DELETE CASCADE,
    major_id             INT REFERENCES majors_data(id),   -- Pilihan Jurusan yang Dituju
    nisn                 VARCHAR(10),                      -- NISN Pendaftar
    
    -- Data Orang Tua / Wali Pendaftar
    mother_name          VARCHAR(255),                     -- Nama Ibu Kandung
    father_name          VARCHAR(255),                     -- Nama Ayah
    parent_phone         VARCHAR(50),                      -- No. HP / WA Orang Tua
    
    -- Data Sekolah Asal
    high_school_name     VARCHAR(255),                     -- Nama SMA/SMK Asal
    high_school_major    VARCHAR(100),                     -- Jurusan SMA/SMK (IPA/IPS/TKJ/RPL)
    high_school_address  TEXT,
    high_school_score    NUMERIC(5,2),                     -- Nilai Rata-rata Rapor/Ujian
    
    -- Jalur Seleksi & Berkas
    admission_track      VARCHAR(50) DEFAULT 'Mandiri',    -- 'Mandiri', 'Prestasi', 'Tes CBT'
    certificate_file     VARCHAR(255),                     -- Sertifikat Prestasi
    
    -- Status Seleksi & Pembayaran Pendaftaran
    payment_proof        VARCHAR(255),                     -- Bukti Pembayaran PMB
    payment_status       VARCHAR(30) DEFAULT 'Unpaid',     -- 'Unpaid', 'Pending', 'Verified'
    application_status   VARCHAR(30) DEFAULT 'Draft',      -- 'Draft', 'Pending', 'Approved', 'Rejected'
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. students_data (Data Akademik Resmi Mahasiswa Diterima)
CREATE TABLE IF NOT EXISTS students_data (
    id                BIGSERIAL PRIMARY KEY,
    user_id           BIGINT UNIQUE NOT NULL REFERENCES users_credential(id) ON DELETE CASCADE,
    pmb_id            BIGINT UNIQUE REFERENCES pmb_data(id), -- Asal Riwayat PMB
    major_id          INT NOT NULL REFERENCES majors_data(id),
    nim               VARCHAR(30) UNIQUE NOT NULL,           -- NIM Resmi (contoh: A11.2026.00001)
    batch_year        INT DEFAULT 2026,                      -- Tahun Angkatan
    
    -- Status Operasional Semester
    current_semester  INT DEFAULT 1,                         -- Semester Berjalan (1, 2, dst)
    semester_status   VARCHAR(30) DEFAULT 'Ganjil',          -- 'Ganjil', 'Genap', 'Pendek'
    total_krs_credits INT DEFAULT 0,                         -- Total SKS yang Sedang Diambil
    payment_status    VARCHAR(30) DEFAULT 'Belum Lunas',     -- Status Bayar UKT ('Lunas', 'Belum Lunas', 'Cicilan')
    academic_status   VARCHAR(30) DEFAULT 'Aktif',           -- 'Aktif', 'Cuti', 'Lulus', 'DO'
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. lecturers_data (Data Akademik Dosen / Pegawai Pengajar)
CREATE TABLE IF NOT EXISTS lecturers_data (
    id              BIGSERIAL PRIMARY KEY,
    user_id         BIGINT UNIQUE NOT NULL REFERENCES users_credential(id) ON DELETE CASCADE,
    major_id        INT NOT NULL REFERENCES majors_data(id), -- Homebase Jurusan Dosen
    npp             VARCHAR(30) UNIQUE NOT NULL,             -- Nomor Pokok Pegawai
    nidn            VARCHAR(30),                             -- NIDN Nasional
    academic_title  VARCHAR(100),                            -- Gelar (misal: 'Dr. Eng., M.Kom.')
    status          VARCHAR(30) DEFAULT 'Aktif',             -- 'Aktif', 'Tugas Belajar', 'Pensiun'
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 8. students_bills_data (Data Tagihan UKT & Keuangan Mahasiswa)
CREATE TABLE IF NOT EXISTS students_bills_data (
    id                 BIGSERIAL PRIMARY KEY,
    student_id         BIGINT NOT NULL REFERENCES students_data(id) ON DELETE CASCADE,
    bill_code          VARCHAR(50) UNIQUE,          -- contoh: 'INV-20261-A12202600001'
    academic_year      VARCHAR(30) NOT NULL,        -- contoh: '2026/2027 Ganjil'
    bill_type          VARCHAR(50) NOT NULL,        -- 'UKT Pokok', 'Biaya SKS', 'Daftar Ulang'
    amount             NUMERIC(12,2) NOT NULL,      -- Nominal Tagihan
    due_date           DATE NOT NULL,               -- Batas Pembayaran
    payment_method     VARCHAR(30) DEFAULT NULL,    -- 'Mandiri', 'BRI', 'BCA', 'Tunai'
    va_number          VARCHAR(60) DEFAULT NULL,    -- Nomor Virtual Account: '008.112.2026.00001'
    payment_proof      VARCHAR(255) DEFAULT NULL,   -- Bukti Transfer
    payment_status     VARCHAR(30) DEFAULT 'Unpaid',-- 'Unpaid', 'Pending_Verification', 'Paid', 'Cancelled'
    paid_at            TIMESTAMP DEFAULT NULL,
    verified_by        VARCHAR(100) DEFAULT NULL,   -- Petugas TU atau 'VA-System (Auto)'
    notes              TEXT DEFAULT NULL,           -- Catatan Tagihan/Transaksi
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 9. students_krs_data (Data Pengambilan Matakuliah Mahasiswa)
CREATE TABLE IF NOT EXISTS students_krs_data (
    id                 BIGSERIAL PRIMARY KEY,
    student_id         BIGINT NOT NULL REFERENCES students_data(id) ON DELETE CASCADE,
    course_id          INT NOT NULL REFERENCES courses_data(id),
    academic_year      VARCHAR(30) NOT NULL,        -- '2026/2027 Ganjil'
    semester           INT NOT NULL,                -- Semester saat ambil (1, 2, dst)
    approval_status    VARCHAR(30) DEFAULT 'Draft', -- 'Draft', 'Submitted', 'Approved', 'Rejected'
    approved_by        BIGINT REFERENCES lecturers_data(id), -- Dosen Pembimbing Akademik (Dosen Wali)
    approved_at        TIMESTAMP DEFAULT NULL,
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 10. students_score_data (Data Nilai, KHS, & Transkrip Akademik)
CREATE TABLE IF NOT EXISTS students_score_data (
    id                 BIGSERIAL PRIMARY KEY,
    student_krs_id     BIGINT UNIQUE NOT NULL REFERENCES students_krs_data(id) ON DELETE CASCADE,
    student_id         BIGINT NOT NULL REFERENCES students_data(id) ON DELETE CASCADE,
    course_id          INT NOT NULL REFERENCES courses_data(id), -- Relasi Resmi ke Matakuliah
    assignment_score   NUMERIC(5,2) DEFAULT 0,      -- Nilai Tugas (0-100)
    midterm_score      NUMERIC(5,2) DEFAULT 0,      -- Nilai UTS (0-100)
    final_exam_score   NUMERIC(5,2) DEFAULT 0,      -- Nilai UAS (0-100)
    final_score_num    NUMERIC(5,2) DEFAULT 0,      -- Nilai Angka (0-100)
    grade_letter       VARCHAR(5) DEFAULT NULL,     -- 'A', 'AB', 'B', 'BC', 'C', 'D', 'E'
    grade_point        NUMERIC(3,2) DEFAULT 0,      -- Bobot: 4.00, 3.50, dst
    graded_by          BIGINT REFERENCES lecturers_data(id), -- Dosen yang Menilai
    graded_at          TIMESTAMP DEFAULT NULL,
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ========================================================
-- Master Data Seeds (Program Studi)
-- ========================================================
INSERT INTO majors_data (major_code, major_name, degree, faculty, is_active) VALUES
    ('A11', 'Teknik Informatika', 'S1', 'Fakultas Ilmu Komputer', TRUE),
    ('A12', 'Sistem Informasi', 'S1', 'Fakultas Ilmu Komputer', TRUE),
    ('A14', 'Desain Komunikasi Visual', 'S1', 'Fakultas Ilmu Komputer', TRUE),
    ('A15', 'Ilmu Komunikasi', 'S1', 'Fakultas Ilmu Komputer', TRUE),
    ('A22', 'Teknik Informatika', 'D3', 'Fakultas Ilmu Komputer', TRUE)
ON CONFLICT (major_code) DO UPDATE SET
    major_name = EXCLUDED.major_name,
    degree = EXCLUDED.degree,
    faculty = EXCLUDED.faculty,
    is_active = EXCLUDED.is_active;

-- ========================================================
-- Master Account Seed (Superadmin Default)
-- Username: 0000.0001
-- Password: localadmin001
-- ========================================================
INSERT INTO users_credential (username, email, password, role, account_status)
VALUES (
    '0000.0001',
    'admin@dinus.ac.id',
    '$2y$10$O0.gCGhpRQmZuGDYEB5euudSCUqW1XyrU6AHTIFO7IbrqYA7RdzWS',
    'superadmin',
    'active'
) ON CONFLICT (username) DO NOTHING;

-- Personal Profile untuk Superadmin Default
INSERT INTO personal_profiles (user_id, full_name, phone, ktp_address, domicile_address)
SELECT id, 'Super Admin Local', '08123456789', 'Kampus UDINUS, Semarang', 'Semarang'
FROM users_credential
WHERE username = '0000.0001'
ON CONFLICT (user_id) DO NOTHING;
