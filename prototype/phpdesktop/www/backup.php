<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/Database.php';
function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
$db = null; $error = ''; $message = ''; $backupPath = '';
try {
    $db = new Database();
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $root = getenv('APPDATA');
        if ($root === false || trim($root) === '') $root = getenv('LOCALAPPDATA');
        if ($root === false || trim($root) === '') throw new RuntimeException('Folder data Windows tidak ditemukan.');
        $dir = rtrim($root, "\\/") . DIRECTORY_SEPARATOR . 'BelMadrasah' . DIRECTORY_SEPARATOR . 'backups';
        $backupPath = $dir . DIRECTORY_SEPARATOR . 'bel-madrasah-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2))) . '.sqlite';
        $db->createBackup($backupPath);
        $message = 'Backup berhasil dibuat. Salin file ke USB atau penyimpanan lain untuk perlindungan tambahan.';
    }
} catch (Throwable $e) { $error = 'Backup gagal atau database belum siap: ' . $e->getMessage(); }
$databasePath = $db instanceof Database ? $db->getPath() : '';
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Backup Data — Bel Madrasah</title><link rel="stylesheet" href="assets/app.css"></head>
<body><div class="app-shell"><header class="topbar"><div class="brand-mark">BM</div><div><p class="eyebrow">TAHAP 3 · PERLINDUNGAN DATA</p><h1>Bel Madrasah</h1><p class="subtitle">Backup database aplikasi</p></div></header>
<nav class="main-nav" aria-label="Navigasi utama">
        <a href="dashboard.php">Dashboard</a>
        <a href="index.php">Pemeriksaan</a>
        <a href="packages.php">Paket jadwal</a>
        <a href="schedule-editor.php">Editor jadwal</a>
        <a href="settings.php">Konfigurasi</a>
        <a class="active" href="backup.php">Backup data</a>
    </nav>
<section class="intro panel page-intro"><div><p class="eyebrow">BACKUP SQLITE</p><h2>Cadangkan data lokal</h2><p>Backup membuat salinan konsisten database aktif tanpa menghapus atau memindahkan database sumber.</p></div></section>
<?php if ($message !== ''): ?><p class="action-feedback success" role="status"><?=h($message)?></p><?php endif; ?><?php if ($error !== ''): ?><p class="action-feedback error" role="alert"><?=h($error)?></p><?php endif; ?>
<section class="panel form-panel backup-panel"><div class="storage-path"><span>Database aktif</span><code><?=h($databasePath !== '' ? $databasePath : 'Belum tersedia')?></code></div><p>Folder tujuan: <code>%APPDATA%\\BelMadrasah\\backups</code> (fallback ke <code>%LOCALAPPDATA%</code>). Backup pada komputer yang sama tidak melindungi dari kerusakan disk; salin hasilnya ke media lain.</p><form method="post" action="backup.php"><button type="submit" <?= $db instanceof Database ? '' : 'disabled' ?>>Buat backup sekarang</button></form><?php if ($backupPath !== ''): ?><div class="storage-path backup-result"><span>File backup dibuat</span><code><?=h($backupPath)?></code></div><?php endif; ?></section>
<footer><span>Bel Madrasah · Tahap 3</span><span>Backup tidak menghapus database aktif</span></footer></div></body></html>
