<?php
/**
 * INDEX - Pintu masuk (front controller) aplikasi
 *
 */

session_start();   

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Barang.php';
require_once __DIR__ . '/controllers/BarangController.php';

/* ---------------------------------------------------------------
 * FUNGSI BANTU UNTUK VIEW
 * ------------------------------------------------------------- */


function e($teks): string
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

/**
 * Mencetak ikon SVG inline (tanpa library dan tanpa internet).
 * Pemakaian di View:  <?= ikon('search') ?>  atau  <?= ikon('box', 22) ?>
 */
function ikon(string $nama, int $ukuran = 18): string
{
    static $bentuk = [
        'box'        => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.27 6.96L12 12.01l8.73-5.05"/><path d="M12 22.08V12"/>',
        'tag'        => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><path d="M7 7h.01"/>',
        'layers'     => '<path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>',
        'check'      => '<path d="M20 6L9 17l-5-5"/>',
        'check-circle' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/>',
        'wrench'     => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
        'alert'      => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        'alert-octagon' => '<path d="M7.86 2h8.28L22 7.86v8.28L16.14 22H7.86L2 16.14V7.86L7.86 2z"/><path d="M12 8v4"/><path d="M12 16h.01"/>',
        'plus-circle' => '<circle cx="12" cy="12" r="10"/><path d="M12 8v8"/><path d="M8 12h8"/>',
        'edit'       => '<path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>',
        'trash'      => '<path d="M3 6h18"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M10 11v6"/><path d="M14 11v6"/>',
        'search'     => '<circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>',
        'filter'     => '<path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/>',
        'reset'      => '<path d="M1 4v6h6"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>',
        'save'       => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/>',
        'x'          => '<path d="M18 6L6 18"/><path d="M6 6l12 12"/>',
        'calendar'   => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/>',
        'table'      => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M3 15h18"/><path d="M9 3v18"/>',
        'inbox'      => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
        'chevron'    => '<path d="M6 9l6 6 6-6"/>',
        'sliders'    => '<path d="M4 21v-7"/><path d="M4 10V3"/><path d="M12 21v-9"/><path d="M12 8V3"/><path d="M20 21v-5"/><path d="M20 12V3"/><path d="M1 14h6"/><path d="M9 8h6"/><path d="M17 16h6"/>',
        'pie'        => '<path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/>',
    ];

    return '<svg class="ikon" width="' . $ukuran . '" height="' . $ukuran . '" viewBox="0 0 24 24" '
         . 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" '
         . 'stroke-linejoin="round" aria-hidden="true">' . ($bentuk[$nama] ?? '') . '</svg>';
}

/* ---------------------*/
try {
    // Database -> Model -> Controller
    $model      = new Barang(Database::connect());
    $controller = new BarangController($model);
    $controller->handle();
} catch (PDOException $ex) {
    // Jika koneksi gagal, tampilkan pesan
    error_log($ex->getMessage());
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Koneksi Gagal</title>'
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<link href="assets/css/style.css" rel="stylesheet"></head>'
       . '<body style="min-height:100vh;display:flex;align-items:center;justify-content:center">'
       . '<div class="panel panel-body" style="max-width:480px"><h3>Database tidak dapat diakses</h3>'
       . '<p style="color:#64748b;margin-top:8px">Pastikan MySQL aktif, database <code>db_inventaris</code> '
       . 'sudah diimpor, dan pengaturan di <code>config/database.php</code> sudah benar.</p></div></body></html>';
}
