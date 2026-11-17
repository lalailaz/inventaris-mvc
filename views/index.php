<?php
/**
 * VIEW - Halaman utama
 *
 * View hanya bertugas MENAMPILKAN data yang sudah disiapkan Controller.
 * Tidak ada query database di sini. Fungsi e() dipakai di setiap output
 * untuk mencegah XSS, dan ikon() mencetak ikon SVG.
 *
 * Variabel yang tersedia dari Controller:
 *   $daftar, $statistik, $filter, $filterQuery, $form, $editId,
 *   $errors, $flash, $csrf, $kategori, $kondisi, $urutan
 */

$modeEdit = $editId !== null;

// Persentase komposisi kondisi untuk bar di dashboard.
$persenBaik  = $statistik['total_unit'] > 0 ? round($statistik['unit_baik'] / $statistik['total_unit'] * 100) : 0;
$persenRusak = $statistik['total_unit'] > 0 ? 100 - $persenBaik : 0;

// Awalan URL yang mempertahankan filter aktif pada tautan Edit / Batal / form.
$qs     = $filterQuery ? $filterQuery . '&' : '';
$qsAksi = $filterQuery ? '?' . $filterQuery : '';

// Ada filter aktif? (untuk label hasil dan pesan data kosong)
$adaFilter = $filter['q'] !== '' || $filter['kategori'] !== '' || $filter['kondisi'] !== ''
          || $filter['tgl_dari'] !== '' || $filter['tgl_sampai'] !== '';

// Panel "filter lanjutan" otomatis terbuka jika salah satu isinya sedang dipakai.
$lanjutanTerbuka = $filter['tgl_dari'] !== '' || $filter['tgl_sampai'] !== '' || $filter['urut'] !== 'terbaru';

// Pemetaan status stok ke kelas warna badge.
$kelasStatus = ['Aman' => 'badge-ok', 'Stok Rendah' => 'badge-warn', 'Habis' => 'badge-habis'];

// Ikon untuk tiap pilihan kondisi pada radio.
$ikonKondisi = ['Baik' => 'check-circle', 'Rusak' => 'wrench'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventaris Kelas</title>
    <!-- Font: Google Sans (isi halaman) dan EB Garamond Bold (judul).
         Dimuat dari Google Fonts, jadi perlu internet saat pertama kali dibuka. -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;600;700&family=EB+Garamond:wght@700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- ===================== PESAN BERHASIL / GAGAL =====================
     Tampil sebagai notifikasi melayang (toast) dan menghilang sendiri lewat CSS. -->
<?php if ($flash): ?>
    <div class="alert toast <?= $flash['tipe'] === 'success' ? 'alert-sukses' : 'alert-bahaya' ?>" role="alert">
        <?= ikon($flash['tipe'] === 'success' ? 'check-circle' : 'alert-octagon') ?>
        <div><?= e($flash['pesan']) ?></div>
    </div>
<?php endif; ?>

<!-- ===================== HERO ===================== -->
<header class="hero">
    <div class="wadah">
        <div class="hero-isi">
            <div>
                <span class="hero-chip"><?= ikon('box', 14) ?> Sistem Inventaris</span>
                <h1>Inventaris Kelas</h1>
                <p>Catat, pantau, dan kelola barang kelas dalam satu halaman.</p>
            </div>
            <div class="hero-aksi">
                <span class="hero-tanggal"><?= ikon('calendar', 16) ?> <?= e(date('d/m/Y')) ?></span>
                <!-- Tautan anchor: menggulir halaman ke form tambah/edit -->
                <a href="#form-barang" class="btn btn-hero"><?= ikon('plus-circle', 16) ?> Tambah Barang</a>
            </div>
        </div>
    </div>
</header>

<main class="wadah">

    <!-- ===================== DASHBOARD STATISTIK =====================-->
    <section class="grid-stat">

        <!-- Kartu utama: total unit + bar komposisi Baik/Rusak -->
        <div class="panel stat-utama">
            <div class="stat-utama-atas">
                <div>
                    <div class="stat-label">Total Unit</div>
                    <div class="stat-nilai stat-besar"><?= e($statistik['total_unit']) ?></div>
                    <div class="stat-ket">tersebar di <?= e($statistik['jenis']) ?> jenis barang</div>
                </div>
                <span class="ikon-stat nada-biru"><?= ikon('layers', 22) ?></span>
            </div>
            <div>
                <div class="bar">
                    <div class="bar-baik"  style="width: <?= e($persenBaik) ?>%"></div>
                    <div class="bar-rusak" style="width: <?= e($persenRusak) ?>%"></div>
                </div>
                <div class="legenda">
                    <span class="teks-baik"><i class="titik titik-baik"></i>Baik <?= e($persenBaik) ?>%</span>
                    <span class="teks-rusak"><i class="titik titik-rusak"></i>Rusak <?= e($persenRusak) ?>%</span>
                </div>
            </div>
        </div>

        <!-- Empat kartu kecil -->
        <div class="panel kartu-kecil">
            <div class="kartu-kecil-atas">
                <span class="stat-label">Jenis Barang</span>
                <span class="ikon-stat nada-teal"><?= ikon('tag', 20) ?></span>
            </div>
            <div class="stat-nilai"><?= e($statistik['jenis']) ?></div>
        </div>
        <div class="panel kartu-kecil">
            <div class="kartu-kecil-atas">
                <span class="stat-label">Unit Kondisi Baik</span>
                <span class="ikon-stat nada-hijau"><?= ikon('check-circle', 20) ?></span>
            </div>
            <div class="stat-nilai"><?= e($statistik['unit_baik']) ?></div>
        </div>
        <div class="panel kartu-kecil">
            <div class="kartu-kecil-atas">
                <span class="stat-label">Unit Kondisi Rusak</span>
                <span class="ikon-stat nada-merah"><?= ikon('wrench', 20) ?></span>
            </div>
            <div class="stat-nilai"><?= e($statistik['unit_rusak']) ?></div>
        </div>
        <div class="panel kartu-kecil">
            <div class="kartu-kecil-atas">
                <span class="stat-label">Perlu Restok</span>
                <span class="ikon-stat nada-kuning"><?= ikon('alert', 20) ?></span>
            </div>
            <div>
                <div class="stat-nilai"><?= e($statistik['jenis_habis'] + $statistik['jenis_rendah']) ?></div>
                <div class="stat-ket"><?= e($statistik['jenis_habis']) ?> habis, <?= e($statistik['jenis_rendah']) ?> rendah</div>
            </div>
        </div>
    </section>

    <div class="kolom-utama">

        <!-- ===================== KOLOM KIRI: FILTER + TABEL ===================== -->
        <div class="kolom-kiri">

            <!-- Toolbar pencarian dan filter. Memakai method GET agar hasilnya
                 tercermin di URL dan semua filter dikirim bersamaan (kombinasi). -->
            <section class="panel">
                <form method="get" action="index.php">
                    <!-- Baris utama: pencarian, kategori, kondisi -->
                    <div class="toolbar">
                        <div class="input-ikon grow">
                            <?= ikon('search', 16) ?>
                            <label for="q" class="sr-only">Cari nama barang</label>
                            <input type="text" id="q" name="q" class="input"
                                   value="<?= e($filter['q']) ?>" placeholder="Cari nama barang">
                        </div>

                        <label for="f_kategori" class="sr-only">Kategori</label>
                        <select id="f_kategori" name="kategori" class="select">
                            <option value="">Semua kategori</option>
                            <?php foreach ($kategori as $k): ?>
                                <option value="<?= e($k) ?>" <?= $filter['kategori'] === $k ? 'selected' : '' ?>><?= e($k) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label for="f_kondisi" class="sr-only">Kondisi</label>
                        <select id="f_kondisi" name="kondisi" class="select">
                            <option value="">Semua kondisi</option>
                            <?php foreach ($kondisi as $k): ?>
                                <option value="<?= e($k) ?>" <?= $filter['kondisi'] === $k ? 'selected' : '' ?>><?= e($k) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit" class="btn btn-utama"><?= ikon('filter', 16) ?> Terapkan</button>
                    </div>

                    <!-- Baris bawah: filter lanjutan (bisa dibuka/tutup) dan Reset Filter -->
                    <div class="toolbar-bawah">
                        <details class="lanjutan" <?= $lanjutanTerbuka ? 'open' : '' ?>>
                            <summary>
                                <?= ikon('sliders', 16) ?> Rentang tanggal dan urutan
                                <span class="chev"><?= ikon('chevron', 16) ?></span>
                            </summary>
                            <div class="lanjutan-isi">
                                <div class="field">
                                    <label for="tgl_dari">Tanggal dari</label>
                                    <input type="date" id="tgl_dari" name="tgl_dari" class="input" value="<?= e($filter['tgl_dari']) ?>">
                                </div>
                                <div class="field">
                                    <label for="tgl_sampai">Tanggal sampai</label>
                                    <input type="date" id="tgl_sampai" name="tgl_sampai" class="input" value="<?= e($filter['tgl_sampai']) ?>">
                                </div>
                                
                                <div class="field">
                                    <label for="urut">Urutkan</label>
                                    <select id="urut" name="urut" class="select">
                                        <?php foreach ($urutan as $kunci => $opsi): ?>
                                            <option value="<?= e($kunci) ?>" <?= $filter['urut'] === $kunci ? 'selected' : '' ?>><?= e($opsi['label']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </details>

                        <!-- Reset Filter: kembali ke index.php -->
                        <a href="index.php" class="btn btn-teks"><?= ikon('reset', 14) ?> Reset Filter</a>
                    </div>
                </form>
            </section>

            <!-- Tabel data inventaris -->
            <section class="panel">
                <div class="panel-head">
                    <span class="judul"><span class="chip-ikon"><?= ikon('table', 18) ?></span> Daftar Inventaris</span>
                    <span class="badge badge-netral"><?= count($daftar) ?> data<?= $adaFilter ? ' (terfilter)' : '' ?></span>
                </div>

                <div class="tabel-wrap">
                    <table class="tabel">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Barang</th>
                                <th>Kategori</th>
                                <th class="kanan">Jumlah</th>
                                <th>Kondisi</th>
                                <th>Status Stok</th>
                                <th>Masuk</th>
                                <th class="kanan">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$daftar): ?>
                            <!-- Tampilan saat data kosong atau filter tidak menemukan hasil -->
                            <tr>
                                <td colspan="9" class="sel-kosong">
                                    <div class="kosong">
                                        <?= ikon('inbox', 40) ?>
                                        <?= $adaFilter ? 'Tidak ada barang yang cocok dengan filter.' : 'Belum ada data barang.' ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <!-- Satu iterasi = satu baris tabel -->
                        <?php foreach ($daftar as $no => $b): ?>
                            <tr class="<?= $modeEdit && (int) $b['id'] === $editId ? 'baris-edit' : '' ?>">
                                <td class="nomor"><?= $no + 1 ?></td>
                                <td><span class="kode-tag"><?= e($b['kode_barang']) ?></span></td>
                                <td>
                                    <div class="nama-barang"><?= e($b['nama_barang']) ?></div>
                                    <?php if ($b['keterangan']): ?>
                                        <div class="ket-barang"><?= e($b['keterangan']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($b['kategori']) ?></td>
                                <td class="angka-jumlah"><?= e($b['jumlah']) ?></td>
                                <td>
                                    <span class="badge <?= $b['kondisi'] === 'Baik' ? 'badge-ok' : 'badge-bad' ?>"><?= e($b['kondisi']) ?></span>
                                </td>
                                <td>
                                    <!-- status_stok dihitung oleh MySQL (CASE WHEN) -->
                                    <span class="badge <?= $kelasStatus[$b['status_stok']] ?? 'badge-ok' ?>"><?= e($b['status_stok']) ?></span>
                                </td>
                                <td class="nowrap"><?= e(date('d/m/Y', strtotime($b['tanggal_masuk']))) ?></td>
                                <td>
                                    <div class="aksi">
                                        <!-- Edit: memuat ulang halaman dengan ?edit=ID, form terisi otomatis -->
                                        <a href="index.php?<?= e($qs) ?>edit=<?= (int) $b['id'] ?>#form-barang"
                                           class="btn btn-ikon btn-edit" title="Edit"><?= ikon('edit', 16) ?></a>

                                        <!-- Hapus: memakai POST (bukan link) agar tidak terpicu tanpa sengaja,
                                             dan confirm() meminta persetujuan sebelum form dikirim. -->
                                        <form method="post" action="index.php<?= e($qsAksi) ?>"
                                              onsubmit="return confirm('Hapus barang <?= e(addslashes($b['nama_barang'])) ?>? Tindakan ini tidak dapat dibatalkan.');">
                                            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                                            <input type="hidden" name="aksi" value="hapus">
                                            <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                                            <button type="submit" class="btn btn-ikon btn-hapus" title="Hapus"><?= ikon('trash', 16) ?></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <!-- ===================== KOLOM KANAN: FORM TAMBAH / EDIT ===================== -->
        <section class="panel panel-form" id="form-barang">
            <div class="panel-head">
                <span class="judul">
                    <span class="chip-ikon"><?= ikon($modeEdit ? 'edit' : 'plus-circle', 18) ?></span>
                    <?= $modeEdit ? 'Edit Barang' : 'Tambah Barang' ?>
                </span>
            </div>
            <div class="panel-body">

                <!-- Ringkasan error validasi, tampil di dalam form -->
                <?php if ($errors): ?>
                    <div class="alert alert-bahaya form-peringatan" role="alert">
                        <?= ikon('alert-octagon', 16) ?>
                        <div>Data belum dapat disimpan. Periksa isian yang ditandai merah.</div>
                    </div>
                <?php endif; ?>

                <!-- novalidate: validasi utama dilakukan server (Controller),
                     agar pesan error seragam dan tidak bisa dilewati. -->
                <form method="post" action="index.php<?= e($qsAksi) ?>" novalidate>
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="aksi" value="simpan">
                    <!-- id terisi saat edit; kosong berarti tambah data baru -->
                    <input type="hidden" name="id" value="<?= $modeEdit ? e($editId) : '' ?>">

                    <div class="form-grid">

                        <!-- Input text: kode dan nama -->
                        <div class="field">
                            <label for="kode_barang">Kode</label>
                            <input type="text" id="kode_barang" name="kode_barang" maxlength="10"
                                   class="input <?= isset($errors['kode_barang']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($form['kode_barang']) ?>" placeholder="BRG-009">
                            <?php if (isset($errors['kode_barang'])): ?>
                                <div class="pesan-error"><?= e($errors['kode_barang']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="field">
                            <label for="nama_barang">Nama Barang</label>
                            <input type="text" id="nama_barang" name="nama_barang" maxlength="100"
                                   class="input <?= isset($errors['nama_barang']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($form['nama_barang']) ?>" placeholder="Nama barang">
                            <?php if (isset($errors['nama_barang'])): ?>
                                <div class="pesan-error"><?= e($errors['nama_barang']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Select: kategori -->
                        <div class="field penuh">
                            <label for="kategori">Kategori</label>
                            <select id="kategori" name="kategori"
                                    class="select <?= isset($errors['kategori']) ? 'is-invalid' : '' ?>">
                                <option value="">Pilih kategori</option>
                                <?php foreach ($kategori as $k): ?>
                                    <option value="<?= e($k) ?>" <?= $form['kategori'] === $k ? 'selected' : '' ?>><?= e($k) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['kategori'])): ?>
                                <div class="pesan-error"><?= e($errors['kategori']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Input number: jumlah dan stok minimum -->
                        <div class="field">
                            <label for="jumlah">Jumlah</label>
                            <input type="number" id="jumlah" name="jumlah" min="0" step="1"
                                   class="input <?= isset($errors['jumlah']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($form['jumlah']) ?>" placeholder="0">
                            <?php if (isset($errors['jumlah'])): ?>
                                <div class="pesan-error"><?= e($errors['jumlah']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="field">
                            <label for="stok_minimum">Stok Minimum</label>
                            <input type="number" id="stok_minimum" name="stok_minimum" min="1" step="1"
                                   class="input <?= isset($errors['stok_minimum']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($form['stok_minimum']) ?>">
                            <?php if (isset($errors['stok_minimum'])): ?>
                                <div class="pesan-error"><?= e($errors['stok_minimum']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Radio: kondisi. Tampil sebagai tombol pilihan berwarna (lihat .pilih di CSS) -->
                        <div class="field penuh">
                            <span class="label">Kondisi</span>
                            <div class="radio-group">
                                <?php foreach ($kondisi as $k): ?>
                                    <label class="pilih pilih-<?= e(strtolower($k)) ?>">
                                        <input type="radio" name="kondisi" value="<?= e($k) ?>"
                                               <?= $form['kondisi'] === $k ? 'checked' : '' ?>>
                                        <span><?= ikon($ikonKondisi[$k] ?? 'check', 16) ?> <?= e($k) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <?php if (isset($errors['kondisi'])): ?>
                                <div class="pesan-error"><?= e($errors['kondisi']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Date: tanggal masuk (atribut max mencegah pilih tanggal masa depan di browser) -->
                        <div class="field penuh">
                            <label for="tanggal_masuk">Tanggal Masuk</label>
                            <input type="date" id="tanggal_masuk" name="tanggal_masuk" max="<?= e(date('Y-m-d')) ?>"
                                   class="input <?= isset($errors['tanggal_masuk']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($form['tanggal_masuk']) ?>">
                            <?php if (isset($errors['tanggal_masuk'])): ?>
                                <div class="pesan-error"><?= e($errors['tanggal_masuk']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Textarea: keterangan (opsional) -->
                        <div class="field penuh">
                            <label for="keterangan">Keterangan <span class="opsional">(opsional)</span></label>
                            <textarea id="keterangan" name="keterangan" rows="3" class="textarea"
                                      placeholder="Catatan tambahan"><?= e($form['keterangan']) ?></textarea>
                        </div>
                    </div>

                    <!-- Tombol Submit dan Reset. Saat edit, Reset diganti Batal
                         karena reset bawaan HTML hanya mengembalikan ke isian awal halaman. -->
                    <div class="form-aksi">
                        <button type="submit" class="btn btn-utama">
                            <?= ikon('save', 16) ?> <?= $modeEdit ? 'Simpan Perubahan' : 'Simpan' ?>
                        </button>
                        <?php if ($modeEdit): ?>
                            <a href="index.php<?= e($qsAksi) ?>" class="btn btn-garis"><?= ikon('x', 16) ?> Batal</a>
                        <?php else: ?>
                            <button type="reset" class="btn btn-garis"><?= ikon('reset', 16) ?> Reset</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </section>
    </div>
</main>

<footer class="footer">Project PHP MVC &bull; Inventaris Kelas</footer>

</body>
</html>