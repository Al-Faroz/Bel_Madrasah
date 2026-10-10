<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/Database.php';
function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
$db = null; $error = ''; $message = '';
$schoolName = ''; $operatorName = ''; $volume = '80';
try {
    $db = new Database();
    $schoolName = $db->getSetting('school_name', '');
    $operatorName = $db->getSetting('operator_name', '');
    $volume = $db->getSetting('default_volume', '80');
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $schoolName = trim((string)($_POST['school_name'] ?? ''));
        $operatorName = trim((string)($_POST['operator_name'] ?? ''));
        $volumeInput = filter_var($_POST['default_volume'] ?? '80', FILTER_VALIDATE_INT);
        if (strlen($schoolName) > 120 || strlen($operatorName) > 80) {
            $error = 'Nama madrasah maksimal 120 karakter dan nama operator maksimal 80 karakter.';
        } elseif ($volumeInput === false || $volumeInput < 0 || $volumeInput > 100) {
            $error = 'Volume bawaan harus berupa angka 0 sampai 100.';
        } else {
            $db->saveSettings(['school_name'=>$schoolName, 'operator_name'=>$operatorName, 'default_volume'=>(string)$volumeInput]);
            $volume = (string)$volumeInput;
            $message = 'Konfigurasi berhasil disimpan di database lokal.';
        }
    }
} catch (Throwable $e) { $error = 'Database belum siap: ' . $e->getMessage(); }
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Konfigurasi — Bel Madrasah</title><link rel="stylesheet" href="assets/app.css"></head>
<body><div class="app-shell"><header class="topbar"><div class="brand-mark">BM</div><div><p class="eyebrow">TAHAP 3 · KONFIGURASI</p><h1>Bel Madrasah</h1><p class="subtitle">Pengaturan lokal aplikasi</p></div></header>
<nav class="main-nav"><a href="index.php">Dashboard diagnostik</a><a href="packages.php">Paket jadwal</a><a class="active" href="settings.php">Konfigurasi</a><a href="backup.php">Backup data</a></nav>
<section class="intro panel page-intro"><div><p class="eyebrow">PENGATURAN LOKAL</p><h2>Konfigurasi aplikasi</h2><p>Nilai disimpan di SQLite lokal dan tetap ada setelah aplikasi ditutup. Volume di sini adalah preferensi untuk integrasi pemutar pada tahap berikutnya, belum mengubah volume audio otomatis.</p></div></section>
<?php if ($message !== ''): ?><p class="action-feedback success" role="status"><?=h($message)?></p><?php endif; ?><?php if ($error !== ''): ?><p class="action-feedback error" role="alert"><?=h($error)?></p><?php endif; ?>
<section class="panel form-panel"><form method="post" action="settings.php"><label>Nama madrasah/sekolah<input type="text" name="school_name" maxlength="120" value="<?=h($schoolName)?>" placeholder="Contoh: MTs Negeri ..."></label><label>Nama operator (opsional)<input type="text" name="operator_name" maxlength="80" value="<?=h($operatorName)?>" placeholder="Nama petugas"></label><label>Volume bawaan yang diinginkan (%)<input type="number" name="default_volume" min="0" max="100" step="1" required value="<?=h($volume)?>"><small>Preferensi ini disimpan sekarang; penerapan ke audio otomatis dilakukan pada tahap berikutnya.</small></label><div class="form-actions"><button type="submit">Simpan konfigurasi</button></div></form></section>
<footer><span>Bel Madrasah · Tahap 3</span><span>Konfigurasi tersimpan lokal</span></footer></div></body></html>
