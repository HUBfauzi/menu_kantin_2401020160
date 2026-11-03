CREATE DATABASE IF NOT EXISTS menu_kantin_2401020160
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE menu_kantin_2401020160;

CREATE TABLE kategori_menu (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE menu (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_menu CHAR(10) NOT NULL UNIQUE,
    nama_menu VARCHAR(100) NOT NULL,
    barcode VARCHAR(30) NOT NULL UNIQUE,
    harga INT UNSIGNED NOT NULL,
    kategori_menu_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_menu_kategori_menu
        FOREIGN KEY (kategori_menu_id)
        REFERENCES kategori_menu(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT chk_harga
        CHECK (harga BETWEEN 1000 AND 100000)
) ENGINE=InnoDB;

INSERT INTO kategori_menu (nama_kategori) VALUES
('Makanan'),
('Minuman');

INSERT INTO menu (kode_menu, nama_menu, barcode, harga, kategori_menu_id) VALUES
('MN00000001', 'Nasi Goreng', 'BR-MKN-0001', 18000, 1),
('MN00000002', 'Es Teh', 'BR-MNM-0001', 5000, 2),
('MN00000003', 'Mie Rebus', 'BR-MKN-0002', 15000, 1);