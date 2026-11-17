
CREATE DATABASE IF NOT EXISTS db_inventaris
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE db_inventaris;

-- Hapus tabel lama agar impor bisa diulang tanpa error
DROP TABLE IF EXISTS barang;

CREATE TABLE barang (
  id            INT          NOT NULL AUTO_INCREMENT,
  kode_barang   VARCHAR(10)  NOT NULL,
  nama_barang   VARCHAR(100) NOT NULL,
  kategori      VARCHAR(30)  NOT NULL,
  jumlah        INT          NOT NULL DEFAULT 0,
  stok_minimum  INT          NOT NULL DEFAULT 5,
  kondisi       VARCHAR(20)  NOT NULL DEFAULT 'Baik',
  tanggal_masuk DATE         NOT NULL,
  keterangan    TEXT         NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_kode_barang (kode_barang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


INSERT INTO barang
  (kode_barang, nama_barang, kategori, jumlah, stok_minimum, kondisi, tanggal_masuk, keterangan)
VALUES
  ('BRG-001', 'Meja Siswa',          'Furniture',   30, 20, 'Baik',  '2026-07-20', 'Meja single ukuran standar'),
  ('BRG-002', 'Kursi Siswa',         'Furniture',   28, 30, 'Baik',  '2026-07-20', 'Dua kursi perlu perbaikan kaki'),
  ('BRG-003', 'Papan Tulis Putih',   'Perlengkapan', 1,  1, 'Baik',  '2026-07-22', NULL),
  ('BRG-004', 'Spidol Papan Tulis',  'ATK',          4, 10, 'Baik',  '2026-09-01', 'Stok hampir habis'),
  ('BRG-005', 'Penghapus Papan',     'ATK',          0,  3, 'Rusak', '2026-08-10', 'Perlu pengadaan baru'),
  ('BRG-006', 'Proyektor LCD',       'Elektronik',   1,  1, 'Rusak', '2026-08-15', 'Lampu proyektor mati'),
  ('BRG-007', 'Kipas Angin Dinding', 'Elektronik',   2,  2, 'Baik',  '2026-08-18', NULL),
  ('BRG-008', 'Sapu dan Pengki',     'Kebersihan',   6,  4, 'Baik',  '2026-09-12', 'Untuk piket harian');
