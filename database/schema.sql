CREATE DATABASE IF NOT EXISTS db_logistikG
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE db_logistikG;


CREATE TABLE tabel_role (
    id_role   INT AUTO_INCREMENT PRIMARY KEY,
    nama_role VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO tabel_role (nama_role) VALUES ('Koor Perkap'), ('Koor Divisi');


CREATE TABLE tabel_event (
    id_event   INT AUTO_INCREMENT PRIMARY KEY,
    nama_event VARCHAR(150) NOT NULL,
    tanggal    DATE NOT NULL,
    lokasi     VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO tabel_event (nama_event, tanggal, lokasi) VALUES
    ('Seminar Nasional Teknologi 2026', '2026-07-15', 'Auditorium Utama');


CREATE TABLE tabel_divisi (
    id_divisi   INT AUTO_INCREMENT PRIMARY KEY,
    nama_divisi VARCHAR(80) NOT NULL,
    id_event    INT NOT NULL,
    UNIQUE KEY unique_divisi_event (nama_divisi, id_event), -- Mencegah nama divisi kembar dalam 1 event yang sama
    FOREIGN KEY (id_event) REFERENCES tabel_event(id_event) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO tabel_divisi (nama_divisi, id_event) VALUES
    ('Acara', 1), ('Humas', 1), ('Konsumsi', 1), ('Perlengkapan', 1), ('Dokumentasi', 1);


CREATE TABLE tabel_master_barang (
    id_barang   INT AUTO_INCREMENT PRIMARY KEY,
    nama_barang VARCHAR(100) NOT NULL UNIQUE,
    satuan      VARCHAR(20) DEFAULT 'unit',
    deskripsi   TEXT
) ENGINE=InnoDB;

INSERT INTO tabel_master_barang (nama_barang, satuan, deskripsi) VALUES
    ('Proyektor', 'unit', 'Proyektor LCD/LED'), ('Sound System', 'set', 'Speaker + mixer'),
    ('Kabel HDMI', 'buah', 'Kabel HDMI 5m'), ('Tripod Kamera', 'buah', 'Tripod DSLR'),
    ('Snack Box', 'pcs', 'Kotak snack'), ('ID Card', 'pcs', 'Kartu identitas'),
    ('Spanduk 3x1m', 'buah', 'Spanduk kain');


CREATE TABLE tabel_pengajuan (
    id_pengajuan     INT AUTO_INCREMENT PRIMARY KEY,
    id_divisi        INT NOT NULL,
    id_barang        INT NOT NULL,
    kuantitas        INT NOT NULL,
    gambar_referensi VARCHAR(255) NOT NULL,
    catatan          TEXT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_divisi) REFERENCES tabel_divisi(id_divisi) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (id_barang) REFERENCES tabel_master_barang(id_barang) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


CREATE TABLE tabel_pengadaan (
    id_pengadaan    INT AUTO_INCREMENT PRIMARY KEY,
    id_pengajuan    INT NOT NULL UNIQUE,
    status          ENUM('Diajukan','Diproses','Selesai') DEFAULT 'Diajukan',
    metode          ENUM('BELI','PINJAM') DEFAULT NULL,
    catatan_kondisi TEXT,
    foto_bukti      VARCHAR(255) DEFAULT NULL,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pengajuan) REFERENCES tabel_pengajuan(id_pengajuan) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;


DELIMITER //
CREATE TRIGGER tr_auto_pengadaan
AFTER INSERT ON tabel_pengajuan
FOR EACH ROW
BEGIN
    INSERT INTO tabel_pengadaan (id_pengajuan, status) VALUES (NEW.id_pengajuan, 'Diajukan');
END //
DELIMITER ;


CREATE TABLE tabel_user (
    id_user      INT AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(50) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,   
    nama_lengkap VARCHAR(100) NOT NULL,
    id_role      INT NOT NULL,
    id_divisi    INT DEFAULT NULL,
    FOREIGN KEY (id_role)   REFERENCES tabel_role(id_role) ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (id_divisi) REFERENCES tabel_divisi(id_divisi) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;


INSERT INTO tabel_user (username, password, nama_lengkap, id_role, id_divisi) VALUES
    ('perkap',          'admin123',  'Koordinator Perkap',   1, NULL),
    ('acara_seminar',   'divisi123', 'Koor Divisi Acara',    2, 1),
    ('humas_seminar',   'divisi123', 'Koor Divisi Humas',    2, 2),
    ('konsumsi_seminar','divisi123', 'Koor Divisi Konsumsi', 2, 3);