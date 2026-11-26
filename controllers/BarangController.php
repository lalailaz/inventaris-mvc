<?php
/**
 * CONTROLLER - BarangController
 *
 * Tanggung jawab controller:
 *   1. Menerima permintaan (GET / POST) dari pengguna
 *   2. Memvalidasi input
 *   3. Memanggil Model untuk membaca/mengubah data
 *   4. Menyiapkan data lalu menampilkannya lewat View
 */

class BarangController
{
    private Barang $model;

    public function __construct(Barang $model)
    {
        $this->model = $model;
    }

    /**
     * POST  -> proses aksi (simpan / hapus) lalu redirect
     * GET   -> tampilkan halaman
     */
    public function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->prosesPost();
            return;
        }
        $this->tampilkan();
    }

    /* ---------------------------------------------------------------
     * BAGIAN 1: MENAMPILKAN HALAMAN (READ)
     * ------------------------------------------------------------- */

    private function tampilkan(array $old = [], array $errors = [], ?int $editId = null): void
    {
        $filter = $this->ambilFilter();

        // Mode edit aktif jika ada ?edit=ID di URL
        $editId = $editId ?? (isset($_GET['edit']) ? (int) $_GET['edit'] : null);
        $form   = $old;

        if ($editId !== null && !$old) {
            $barang = $this->model->cari($editId);
            if ($barang) {
                $form = $barang;           // isi ulang form dengan data lama
            } else {
                $editId = null;            // id tidak ditemukan, kembali ke mode tambah
            }
        }

        
        $data = [
            'daftar'     => $this->model->semua($filter),
            'statistik'  => $this->model->statistik(),
            'filter'     => $filter,
            'filterQuery' => $this->queryFilter($filter),
            'form'       => $form + $this->formKosong(),
            'editId'     => $editId,
            'errors'     => $errors,
            'flash'      => $this->ambilFlash(),
            'csrf'       => $this->tokenCsrf(),
            'kategori'   => Barang::KATEGORI,
            'kondisi'    => Barang::KONDISI,
            'urutan'     => Barang::URUTAN,
        ];

        extract($data);
        require __DIR__ . '/../views/index.php';
    }

    /* ---------------------------------------------------------------
     * BAGIAN 2: MEMPROSES FORM (CREATE, UPDATE, DELETE)
     * ------------------------------------------------------------- */

    private function prosesPost(): void
    {
        if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
            $this->flash('danger', 'Permintaan tidak valid. Silakan muat ulang halaman.');
            $this->redirect();
        }

        $aksi = $_POST['aksi'] ?? '';

        try {
            if ($aksi === 'hapus') {
                $this->hapus();
            } elseif ($aksi === 'simpan') {
                $this->simpan();
            }
        } catch (PDOException $e) {
    
            error_log($e->getMessage());
            $this->flash('danger', 'Terjadi kesalahan pada database. Silakan coba lagi.');
            $this->redirect();
        }
    }

    // Create dan Update
    private function simpan(): void
    {
        $id     = ($_POST['id'] ?? '') !== '' ? (int) $_POST['id'] : null;
        $input  = $this->bersihkanInput($_POST);
        $errors = $this->validasi($input, $id);

        // Validasi gagal: tampilkan form lagi lengkap dengan pesan error

        if ($errors) {
            $this->tampilkan($input, $errors, $id);
            exit;
        }

        if ($id === null) {
            $this->model->tambah($input);
            $this->flash('success', 'Barang "' . $input['nama_barang'] . '" berhasil ditambahkan.');
        } else {
            $this->model->ubah($id, $input);
            $this->flash('success', 'Data barang "' . $input['nama_barang'] . '" berhasil diperbarui.');
        }

        
        $this->redirect();
    }

    private function hapus(): void
    {
        $id     = (int) ($_POST['id'] ?? 0);
        $barang = $this->model->cari($id);

        if (!$barang) {
            $this->flash('danger', 'Data yang akan dihapus tidak ditemukan.');
        } else {
            $this->model->hapus($id);
            $this->flash('success', 'Barang "' . $barang['nama_barang'] . '" berhasil dihapus.');
        }
        $this->redirect();
    }

    /* ---------------------------------------------------------------
     * BAGIAN 3: VALIDASI
     * ------------------------------------------------------------- */

    
    private function bersihkanInput(array $post): array
    {
        return [
            'kode_barang'   => strtoupper(trim($post['kode_barang'] ?? '')),
            'nama_barang'   => trim($post['nama_barang'] ?? ''),
            'kategori'      => trim($post['kategori'] ?? ''),
            'jumlah'        => trim($post['jumlah'] ?? ''),
            'stok_minimum'  => trim($post['stok_minimum'] ?? ''),
            'kondisi'       => trim($post['kondisi'] ?? ''),
            'tanggal_masuk' => trim($post['tanggal_masuk'] ?? ''),
            'keterangan'    => trim($post['keterangan'] ?? ''),
        ];
    }

    /**
     * Mengembalikan array pesan error per field.
     * Array kosong berarti semua input valid.
     */
    private function validasi(array $d, ?int $idSaatEdit): array
    {
        $e = [];

        // Kode: wajib, maksimal 10 karakter, harus unik.
        if ($d['kode_barang'] === '') {
            $e['kode_barang'] = 'Kode barang wajib diisi.';
        } elseif (mb_strlen($d['kode_barang']) > 10) {
            $e['kode_barang'] = 'Kode barang maksimal 10 karakter.';
        } elseif ($this->model->kodeSudahAda($d['kode_barang'], $idSaatEdit)) {
            // Saat edit, id sendiri dikecualikan supaya tidak dianggap duplikat.
            $e['kode_barang'] = 'Kode barang sudah digunakan barang lain.';
        }

        // Nama: wajib dan maksimal 100 karakter.
        if ($d['nama_barang'] === '') {
            $e['nama_barang'] = 'Nama barang wajib diisi.';
        } elseif (mb_strlen($d['nama_barang']) > 100) {
            $e['nama_barang'] = 'Nama barang maksimal 100 karakter.';
        }

        // Kategori dan kondisi harus salah satu dari daftar yang diizinkan.
        if (!in_array($d['kategori'], Barang::KATEGORI, true)) {
            $e['kategori'] = 'Pilih kategori yang tersedia.';
        }
        if (!in_array($d['kondisi'], Barang::KONDISI, true)) {
            $e['kondisi'] = 'Pilih kondisi barang.';
        }

        // Jumlah: bilangan bulat >= 0. FILTER_VALIDATE_INT menolak desimal dan huruf.
        if (filter_var($d['jumlah'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) {
            $e['jumlah'] = 'Jumlah harus bilangan bulat 0 atau lebih.';
        }

        // Stok minimum bilangan bulat > 0.
        if (filter_var($d['stok_minimum'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            $e['stok_minimum'] = 'Stok minimum harus bilangan bulat lebih dari 0.';
        }

        // format tanggal
        if ($d['tanggal_masuk'] === '') {
            $e['tanggal_masuk'] = 'Tanggal masuk wajib diisi.';
        } elseif (!$this->tanggalValid($d['tanggal_masuk'])) {
            $e['tanggal_masuk'] = 'Format tanggal tidak valid.';
        } elseif ($d['tanggal_masuk'] > date('Y-m-d')) {
            // Format Y-m-d bisa dibandingkan langsung sebagai string.
            $e['tanggal_masuk'] = 'Tanggal masuk tidak boleh di masa depan.';
        }

        return $e;
    }

    // Memastikan string benar-benar tanggal Y-m-d yang ada di kalender.
    private function tanggalValid(string $tgl): bool
    {
        $dt = DateTime::createFromFormat('Y-m-d', $tgl);
        return $dt && $dt->format('Y-m-d') === $tgl;
    }

    /* ---------------------------------------------------------------
     * BAGIAN 4: FILTER
     * ------------------------------------------------------------- */

    /**
     * Membaca parameter filter dari URL dan membersihkannya.
     * Nilai di luar daftar yang diizinkan diganti string kosong / default.
     */
    private function ambilFilter(): array
    {
        $f = [
            'q'          => trim($_GET['q'] ?? ''),
            'kategori'   => $_GET['kategori'] ?? '',
            'kondisi'    => $_GET['kondisi'] ?? '',
            'tgl_dari'   => $_GET['tgl_dari'] ?? '',
            'tgl_sampai' => $_GET['tgl_sampai'] ?? '',
            'urut'       => $_GET['urut'] ?? 'terbaru',
        ];

        if (!in_array($f['kategori'], Barang::KATEGORI, true)) $f['kategori'] = '';
        if (!in_array($f['kondisi'], Barang::KONDISI, true))   $f['kondisi']  = '';
        if (!isset(Barang::URUTAN[$f['urut']]))                $f['urut']     = 'terbaru';
        if (!$this->tanggalValid($f['tgl_dari']))              $f['tgl_dari']   = '';
        if (!$this->tanggalValid($f['tgl_sampai']))            $f['tgl_sampai'] = '';

        // Jika rentang terbalik, tukar agar tetap menghasilkan data.
        if ($f['tgl_dari'] !== '' && $f['tgl_sampai'] !== '' && $f['tgl_dari'] > $f['tgl_sampai']) {
            [$f['tgl_dari'], $f['tgl_sampai']] = [$f['tgl_sampai'], $f['tgl_dari']];
        }

        return $f;
    }

    // Mengubah filter menjadi query string agar filter tidak hilang setelah edit/hapus.
    private function queryFilter(array $filter): string
    {
        $aktif = array_filter($filter, fn($v) => $v !== '');
        unset($aktif['urut']);
        if (($filter['urut'] ?? 'terbaru') !== 'terbaru') {
            $aktif['urut'] = $filter['urut'];
        }
        return http_build_query($aktif);
    }

    /* ---------------------------------------------------------------
     * BAGIAN 5: FUNGSI BANTU
     * ------------------------------------------------------------- */

    // Nilai awal form saat mode tambah.
    private function formKosong(): array
    {
        return [
            'kode_barang'   => '',
            'nama_barang'   => '',
            'kategori'      => '',
            'jumlah'        => '',
            'stok_minimum'  => 5,
            'kondisi'       => 'Baik',
            'tanggal_masuk' => date('Y-m-d'),
            'keterangan'    => '',
        ];
    }


    private function tokenCsrf(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    // Flash message: disimpan di session, tampil satu kali, lalu dihapus.
    private function flash(string $tipe, string $pesan): void
    {
        $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
    }

    private function ambilFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }

    // Kembali ke halaman utama dengan mempertahankan filter yang sedang aktif.
    private function redirect(): void
    {
        $query = $this->queryFilter($this->ambilFilter());
        header('Location: index.php' . ($query ? '?' . $query : ''));
        exit;
    }
}
