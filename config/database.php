<?php
/**
 * CONFIG - Koneksi database
 *
 * File ini hanya berisi pengaturan dan cara membuat koneksi.
 * Jika nama database / user / password berbeda, ubah di sini saja,
 * bagian lain aplikasi tidak perlu diubah.
 */

class Database
{
    // Pengaturan default XAMPP / Laragon. Sesuaikan jika berbeda.
    private const HOST    = 'localhost';
    private const DB_NAME = 'db_inventaris';
    private const USER    = 'root';
    private const PASS    = '';

    /**
     * Membuat objek PDO (jembatan PHP ke MySQL).
     * PDO dipilih karena mendukung prepared statement yang
     * melindungi aplikasi dari SQL Injection.
     */
    public static function connect(): PDO
    {
        $dsn = 'mysql:host=' . self::HOST . ';dbname=' . self::DB_NAME . ';charset=utf8mb4';

        $options = [
            // Error database dilempar sebagai exception supaya bisa di-catch
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Hasil query berupa array asosiatif: $row['nama_barang']
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Prepared statement asli dari MySQL, bukan simulasi PHP
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        return new PDO($dsn, self::USER, self::PASS, $options);
    }
}
