-- ========================================
-- DATABASE: gelanggang_sewa
-- ========================================

CREATE DATABASE IF NOT EXISTS gelanggang_sewa
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE gelanggang_sewa;

-- ========================================
-- 1. TABEL USERS
-- ========================================
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(15) NULL,
    role ENUM('admin', 'member') NOT NULL DEFAULT 'member',
    email_verified_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ========================================
-- 2. TABEL GELANGGANGS
-- ========================================
CREATE TABLE gelanggangS (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    jenis ENUM('badminton', 'basket', 'futsal', 'tenis', 'voli') NOT NULL,
    deskripsi TEXT NULL,
    harga_per_jam DECIMAL(10,2) NOT NULL,
    kapasitas INT NOT NULL DEFAULT 10,
    fasilitas JSON NULL,
    status ENUM('aktif', 'nonaktif', 'maintenance') NOT NULL DEFAULT 'aktif',
    foto_utama VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ========================================
-- 3. TABEL GELANGGANG_IMAGES
-- ========================================
CREATE TABLE gelanggang_images (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    gelanggang_id BIGINT UNSIGNED NOT NULL,
    path VARCHAR(255) NOT NULL,
    urutan INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_images_gelanggang
        FOREIGN KEY (gelanggang_id)
        REFERENCES gelanggangS(id)
        ON DELETE CASCADE
);

-- ========================================
-- 4. TABEL JADWAL_OPERASIONAL
-- ========================================
CREATE TABLE jadwal_operasional (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    gelanggang_id BIGINT UNSIGNED NOT NULL,
    hari ENUM('senin','selasa','rabu','kamis','jumat','sabtu','minggu') NOT NULL,
    jam_buka TIME NOT NULL DEFAULT '07:00:00',
    jam_tutup TIME NOT NULL DEFAULT '22:00:00',
    is_libur TINYINT(1) NOT NULL DEFAULT 0,

    CONSTRAINT fk_jadwal_gelanggang
        FOREIGN KEY (gelanggang_id)
        REFERENCES gelanggangS(id)
        ON DELETE CASCADE,

    UNIQUE KEY unique_jadwal (gelanggang_id, hari)
);

-- ========================================
-- 5. TABEL BOOKINGS
-- ========================================
CREATE TABLE bookings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_booking VARCHAR(20) NOT NULL UNIQUE,
    user_id BIGINT UNSIGNED NOT NULL,
    gelanggang_id BIGINT UNSIGNED NOT NULL,
    tanggal DATE NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    durasi_jam INT NOT NULL,
    total_harga DECIMAL(10,2) NOT NULL,
    status ENUM('pending','confirmed','cancelled','selesai') NOT NULL DEFAULT 'pending',
    catatan TEXT NULL,
    cancelled_at TIMESTAMP NULL,
    alasan_cancel TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_booking_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_booking_gelanggang
        FOREIGN KEY (gelanggang_id)
        REFERENCES gelanggangS(id)
        ON DELETE CASCADE,

    -- Index untuk cek konflik jadwal
    INDEX idx_cek_jadwal (gelanggang_id, tanggal, jam_mulai, jam_selesai),
    INDEX idx_user_booking (user_id, status)
);

-- ========================================
-- 6. TABEL PAYMENTS
-- ========================================
CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id BIGINT UNSIGNED NOT NULL UNIQUE,
    metode ENUM('transfer', 'qris', 'tunai') NOT NULL,
    jumlah DECIMAL(10,2) NOT NULL,
    status ENUM('unpaid', 'paid', 'refunded') NOT NULL DEFAULT 'unpaid',
    bukti_bayar VARCHAR(255) NULL,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_payment_booking
        FOREIGN KEY (booking_id)
        REFERENCES bookings(id)
        ON DELETE CASCADE
);

-- ========================================
-- STORED PROCEDURE: CEK KONFLIK JADWAL
-- ========================================
DELIMITER $$

CREATE PROCEDURE cek_konflik_jadwal(
    IN p_gelanggang_id BIGINT,
    IN p_tanggal DATE,
    IN p_jam_mulai TIME,
    IN p_jam_selesai TIME,
    IN p_exclude_booking_id BIGINT  -- isi 0 jika booking baru
)
BEGIN
    SELECT COUNT(*) AS konflik
    FROM bookings
    WHERE gelanggang_id = p_gelanggang_id
      AND tanggal = p_tanggal
      AND status NOT IN ('cancelled')
      AND id != p_exclude_booking_id
      AND (
          -- Cek tumpang tindih waktu
          (jam_mulai < p_jam_selesai AND jam_selesai > p_jam_mulai)
      );
END$$

DELIMITER ;

-- ========================================
-- TRIGGER: AUTO GENERATE KODE BOOKING
-- ========================================
DELIMITER $$

CREATE TRIGGER before_insert_booking
BEFORE INSERT ON bookings
FOR EACH ROW
BEGIN
    DECLARE urutan INT;
    DECLARE kode VARCHAR(20);

    SELECT COUNT(*) + 1 INTO urutan
    FROM bookings
    WHERE DATE(created_at) = CURDATE();

    SET kode = CONCAT(
        'BK-',
        DATE_FORMAT(NOW(), '%Y%m%d'),
        '-',
        LPAD(urutan, 3, '0')
    );

    SET NEW.kode_booking = kode;
END$$

DELIMITER ;

-- ========================================
-- TRIGGER: AUTO BUAT PAYMENT SETELAH BOOKING
-- ========================================
DELIMITER $$

CREATE TRIGGER after_insert_booking
AFTER INSERT ON bookings
FOR EACH ROW
BEGIN
    INSERT INTO payments (booking_id, metode, jumlah, status)
    VALUES (NEW.id, 'transfer', NEW.total_harga, 'unpaid');
END$$

DELIMITER ;

-- ========================================
-- SEED DATA: ADMIN
-- ========================================
INSERT INTO users (name, email, password, phone, role) VALUES
(
    'Administrator',
    'admin@gelanggang.com',
    '$2y$12$examplehashedpasswordforseeding',  -- ganti dengan hash bcrypt asli
    '081234567890',
    'admin'
);

-- ========================================
-- SEED DATA: GELANGGANGS
-- ========================================
INSERT INTO gelanggangS (nama, jenis, deskripsi, harga_per_jam, kapasitas, fasilitas, status) VALUES
('Lapangan Badminton A', 'badminton', 'Lapangan badminton indoor dengan lantai kayu premium', 75000, 4,  '["AC", "Toilet", "Parkir", "Loker"]', 'aktif'),
('Lapangan Badminton B', 'badminton', 'Lapangan badminton standar internasional', 65000, 4,  '["Toilet", "Parkir"]', 'aktif'),
('Lapangan Futsal 1',   'futsal',    'Lapangan futsal rumput sintetis premium', 150000, 10, '["AC", "Toilet", "Parkir", "Kantin"]', 'aktif'),
('Lapangan Basket',     'basket',    'Lapangan basket outdoor dengan lampu malam', 100000, 10, '["Toilet", "Parkir", "Lampu Malam"]', 'aktif'),
('Lapangan Tenis',      'tenis',     'Lapangan tenis dengan permukaan hard court', 120000, 4,  '["Toilet", "Parkir"]', 'aktif');

-- ========================================
-- SEED DATA: JADWAL OPERASIONAL
-- ========================================
INSERT INTO jadwal_operasional (gelanggang_id, hari, jam_buka, jam_tutup, is_libur)
SELECT
    g.id,
    h.hari,
    '07:00:00',
    '22:00:00',
    0
FROM gelanggangS g
CROSS JOIN (
    SELECT 'senin'   AS hari UNION ALL
    SELECT 'selasa'  UNION ALL
    SELECT 'rabu'    UNION ALL
    SELECT 'kamis'   UNION ALL
    SELECT 'jumat'   UNION ALL
    SELECT 'sabtu'   UNION ALL
    SELECT 'minggu'
) h;

-- ========================================
-- VIEW: BOOKING LENGKAP
-- ========================================
CREATE VIEW v_booking_lengkap AS
SELECT
    b.id,
    b.kode_booking,
    b.tanggal,
    b.jam_mulai,
    b.jam_selesai,
    b.durasi_jam,
    b.total_harga,
    b.status AS status_booking,
    b.catatan,
    b.created_at,
    u.name AS nama_user,
    u.email AS email_user,
    u.phone AS phone_user,
    g.nama AS nama_gelanggang,
    g.jenis AS jenis_gelanggang,
    g.harga_per_jam,
    p.metode AS metode_bayar,
    p.status AS status_bayar,
    p.paid_at
FROM bookings b
JOIN users u ON b.user_id = u.id
JOIN gelanggangS g ON b.gelanggang_id = g.id
LEFT JOIN payments p ON b.id = p.booking_id;
