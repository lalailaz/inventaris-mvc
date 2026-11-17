<?php
/**
 * MODEL - Barang
 *
 * Tanggung jawab model: HANYA berbicara dengan database.
 * Tidak ada HTML dan tidak ada validasi form di sini.
 * Semua query yang memakai input pengguna menggunakan prepared statement.
 */

class Barang
{
    // Daftar pilihan tetap. Dipakai oleh form (View) dan validasi (Controller).
    public const KATEGORI = ['Furniture', 'Elektronik', 'ATK', 'Perlengkapan', 'Kebersihan', 'Lainnya'];
    public const KONDISI  = ['Baik', 'Rusak'];


    public const URUTAN = [
        'terbaru'      => ['label' => 'Tanggal terbaru',    'sql' => 'tanggal_masuk DESC, id DESC'],
        'nama_asc'     => ['label' => 'Nama A - Z',         'sql' => 'nama_barang ASC'],
        'nama_desc'    => ['label' => 'Nama Z - A',         'sql' => 'nama_barang DESC'],
        'jumlah_desc'  => ['label' => 'Jumlah terbanyak',   'sql' => 'jumlah DESC, nama_barang ASC'],
        'jumlah_asc'   => ['label' => 'Jumlah tersedikit',  'sql' => 'jumlah ASC, nama_barang ASC'],
    ];

    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * READ - Mengambil data dengan filter kombinasi.
     * Setiap filter yang terisi menambah satu kondisi WHERE,
     * sehingga semua filter otomatis bisa digabung (AND).
     */
    public function semua(array $filter): array
    {
        $where  = [];
        $params = [];

        // Pencarian nama dengan LIKE. Tanda % dan _ dari pengguna di-escape
        if ($filter['q'] !== '') {
            $where[]      = 'nama_barang LIKE :q';
            $params[':q'] = '%' . addcslashes($filter['q'], '%_\\') . '%';
        }
        if ($filter['kategori'] !== '') {
            $where[]             = 'kategori = :kategori';
            $params[':kategori'] = $filter['kategori'];
        }
        if ($filter['kondisi'] !== '') {
            $where[]            = 'kondisi = :kondisi';
            $params[':kondisi'] = $filter['kondisi'];
        }
        // Rentang tanggal: salah satu ujung boleh kosong.
        if ($filter['tgl_dari'] !== '') {
            $where[]         = 'tanggal_masuk >= :dari';
            $params[':dari'] = $filter['tgl_dari'];
        }
        if ($filter['tgl_sampai'] !== '') {
            $where[]           = 'tanggal_masuk <= :sampai';
            $params[':sampai'] = $filter['tgl_sampai'];
        }

        // Status stok dihitung langsung oleh MySQL memakai CASE WHEN.
        $sql = "SELECT *,
                       CASE
                           WHEN jumlah = 0            THEN 'Habis'
                           WHEN jumlah < stok_minimum THEN 'Stok Rendah'
                           ELSE 'Aman'
                       END AS status_stok
                FROM barang";

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        // Aman: potongan ORDER BY berasal dari whitelist, bukan dari pengguna.
        $urut = self::URUTAN[$filter['urut']]['sql'] ?? self::URUTAN['terbaru']['sql'];
        $sql .= ' ORDER BY ' . $urut;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // READ - Mengambil satu baris berdasarkan id (untuk mengisi form edit).
    public function cari(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM barang WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Mengecek apakah kode sudah dipakai barang lain.
     * Saat edit, $kecualiId diisi id barang yang sedang diedit
     * supaya kodenya sendiri tidak dianggap duplikat.
     */
    public function kodeSudahAda(string $kode, ?int $kecualiId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM barang WHERE kode_barang = :kode';
        $params = [':kode' => $kode];

        if ($kecualiId !== null) {
            $sql          .= ' AND id <> :id';
            $params[':id'] = $kecualiId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    // CREATE - Menambah barang baru.
    public function tambah(array $d): void
    {
        $sql = 'INSERT INTO barang
                    (kode_barang, nama_barang, kategori, jumlah, stok_minimum, kondisi, tanggal_masuk, keterangan)
                VALUES
                    (:kode, :nama, :kategori, :jumlah, :stok_min, :kondisi, :tanggal, :ket)';
        $this->db->prepare($sql)->execute($this->parameter($d));
    }

    // UPDATE - Mengubah barang yang sudah ada.
    public function ubah(int $id, array $d): void
    {
        $sql = 'UPDATE barang SET
                    kode_barang   = :kode,
                    nama_barang   = :nama,
                    kategori      = :kategori,
                    jumlah        = :jumlah,
                    stok_minimum  = :stok_min,
                    kondisi       = :kondisi,
                    tanggal_masuk = :tanggal,
                    keterangan    = :ket
                WHERE id = :id';
        $params        = $this->parameter($d);
        $params[':id'] = $id;
        $this->db->prepare($sql)->execute($params);
    }

    // DELETE - Menghapus barang berdasarkan id.
    public function hapus(int $id): void
    {
        $this->db->prepare('DELETE FROM barang WHERE id = :id')->execute([':id' => $id]);
    }

    /**
     * STATISTIK - Seluruh angka dashboard dihitung di database
     * dengan satu query (COUNT, SUM, dan SUM dengan CASE WHEN).
     * COALESCE memastikan hasilnya 0, bukan NULL, saat tabel kosong.
     */
    public function statistik(): array
    {
        $sql = "SELECT
                    COUNT(*)                                                      AS jenis,
                    COALESCE(SUM(jumlah), 0)                                      AS total_unit,
                    COALESCE(SUM(CASE WHEN kondisi = 'Baik'  THEN jumlah END), 0) AS unit_baik,
                    COALESCE(SUM(CASE WHEN kondisi = 'Rusak' THEN jumlah END), 0) AS unit_rusak,
                    COALESCE(SUM(jumlah = 0), 0)                                  AS jenis_habis,
                    COALESCE(SUM(jumlah > 0 AND jumlah < stok_minimum), 0)        AS jenis_rendah
                FROM barang";
        return array_map('intval', $this->db->query($sql)->fetch());
    }

    // Menyusun array parameter yang sama untuk INSERT dan UPDATE.
    private function parameter(array $d): array
    {
        return [
            ':kode'     => $d['kode_barang'],
            ':nama'     => $d['nama_barang'],
            ':kategori' => $d['kategori'],
            ':jumlah'   => $d['jumlah'],
            ':stok_min' => $d['stok_minimum'],
            ':kondisi'  => $d['kondisi'],
            ':tanggal'  => $d['tanggal_masuk'],
            // Keterangan opsional: string kosong disimpan sebagai NULL
            ':ket'      => $d['keterangan'] === '' ? null : $d['keterangan'],
        ];
    }
}
