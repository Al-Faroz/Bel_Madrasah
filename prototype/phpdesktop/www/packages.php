<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/Database.php';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$db = null;
$error = '';
$message = '';
$editId = filter_var($_GET['edit'] ?? 0, FILTER_VALIDATE_INT);
$editId = $editId !== false && $editId > 0 ? $editId : 0;
$formName = '';
$formDescription = '';
$formOffDays = '7';

try {
    $db = new Database();
    $db->ensureDefaultSchedulePackage();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $action = (string) ($_POST['package_action'] ?? '');
        if ($action === 'activate') {
            $targetId = filter_var($_POST['package_id'] ?? null, FILTER_VALIDATE_INT);
            if ($targetId === false || $targetId === null || $targetId < 1) {
                throw new InvalidArgumentException('Paket tujuan tidak valid.');
            }
            $db->activateSchedulePackage($targetId);
            $message = 'Paket jadwal aktif berhasil diganti. Hanya paket ini yang akan menjadi sumber jadwal global.';
            $editId = 0;
        } elseif ($action === 'save') {
            $postedId = filter_var($_POST['package_id'] ?? 0, FILTER_VALIDATE_INT);
            $postedId = $postedId !== false && $postedId > 0 ? $postedId : 0;
            $formName = trim((string) ($_POST['name'] ?? ''));
            $formDescription = trim((string) ($_POST['description'] ?? ''));
            $formOffDays = (string) ($_POST['off_days'] ?? '7');
            if (!in_array($formOffDays, ['5', '7', '6,7'], true)) {
                throw new InvalidArgumentException('Pilihan hari libur mingguan tidak valid.');
            }
            try {
                $savedId = $db->saveSchedulePackage($postedId > 0 ? $postedId : null, $formName, $formDescription, $formOffDays);
                if ($postedId === 0) {
                    $db->initializePackageDays($savedId, $formOffDays);
                }
                $editId = $postedId > 0 ? $savedId : 0;
                $message = $postedId > 0 ? 'Perubahan paket berhasil disimpan.' : 'Paket baru berhasil dibuat dalam keadaan nonaktif.';
                if ($postedId === 0) {
                    $formName = '';
                    $formDescription = '';
                    $formOffDays = '7';
                }
            } catch (PDOException $exception) {
                if (stripos($exception->getMessage(), 'UNIQUE constraint failed') !== false) {
                    throw new InvalidArgumentException('Nama paket sudah digunakan. Gunakan nama yang berbeda.');
                }
                throw $exception;
            }
        } else {
            throw new InvalidArgumentException('Aksi paket tidak dikenali.');
        }
    }

    $packages = $db->getSchedulePackages();
    $activePackage = null;
    foreach ($packages as $package) {
        if ((int) $package['is_active'] === 1) {
            $activePackage = $package;
            break;
        }
    }
    if ($editId > 0 && $formName === '') {
        $editing = $db->getSchedulePackage($editId);
        if ($editing === null) {
            $error = 'Paket yang dipilih untuk diedit tidak ditemukan.';
            $editId = 0;
        } else {
            $formName = (string) $editing['name'];
            $formDescription = (string) $editing['description'];
            $formOffDays = (string) $editing['off_days'];
        }
    }
} catch (Throwable $exception) {
    $error = $exception instanceof InvalidArgumentException
        ? $exception->getMessage()
        : 'Operasi paket gagal: ' . $exception->getMessage();
    if ($db instanceof Database) {
        try {
            $packages = $db->getSchedulePackages();
        } catch (Throwable $ignored) {
            $packages = [];
        }
    } else {
        $packages = [];
    }
    $activePackage = null;
    foreach ($packages as $package) {
        if ((int) $package['is_active'] === 1) {
            $activePackage = $package;
            break;
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#123524">
    <title>Paket Jadwal — Bel Madrasah</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body>
<div class="app-shell">
    <header class="topbar">
        <div class="brand-mark" aria-hidden="true">BM</div>
        <div>
            <p class="eyebrow">TAHAP 4 · PENGELOLAAN JADWAL</p>
            <h1>Bel Madrasah</h1>
            <p class="subtitle">Kelola paket dan pilih satu jadwal yang berlaku</p>
        </div>
    </header>

    <nav class="main-nav" aria-label="Navigasi utama">
        <a href="index.php">Dashboard diagnostik</a>
        <a class="active" href="packages.php">Paket jadwal</a>
        <a href="schedule-editor.php">Editor jadwal</a>
        <a href="settings.php">Konfigurasi</a>
        <a href="backup.php">Backup data</a>
    </nav>

    <section class="intro panel page-intro">
        <div>
            <p class="eyebrow">SUMBER JADWAL GLOBAL</p>
            <h2>Kelola paket tanpa menghapus paket lainnya.</h2>
            <p>Hanya satu paket yang aktif pada satu waktu. Paket yang tidak aktif tetap tersimpan dan tidak akan menjadi sumber jadwal aktif. Saat pertama kali dibuka, aplikasi menyiapkan paket Normal jika belum ada paket apa pun.</p>
        </div>
    </section>

    <?php if ($message !== ''): ?><p class="action-feedback success" role="status"><?= h($message) ?></p><?php endif; ?>
    <?php if ($error !== ''): ?><p class="action-feedback error" role="alert"><?= h($error) ?></p><?php endif; ?>

    <section class="package-active panel">
        <div>
            <p class="eyebrow">PAKET YANG SEDANG BERLAKU</p>
            <?php if ($activePackage !== null): ?>
                <h2><?= h((string) $activePackage['name']) ?></h2>
                <p><?= h((string) $activePackage['description'] !== '' ? (string) $activePackage['description'] : 'Tidak ada keterangan tambahan.') ?></p>
            <?php else: ?>
                <h2>Belum ada paket aktif</h2>
                <p>Buat atau pilih paket untuk menjadikannya sumber jadwal global.</p>
            <?php endif; ?>
        </div>
        <span class="active-indicator"><span></span> <?= $activePackage !== null ? 'AKTIF' : 'BELUM DIPILIH' ?></span>
    </section>

    <section class="package-layout">
        <section class="panel package-list-panel">
            <div class="section-heading package-heading">
                <div><p class="eyebrow">DAFTAR PAKET</p><h2>Paket jadwal tersimpan</h2></div>
                <span class="section-note"><?= count($packages) ?> paket</span>
            </div>
            <?php if ($packages === []): ?>
                <p class="empty-record">Belum ada paket jadwal.</p>
            <?php else: ?>
                <div class="package-list">
                    <?php foreach ($packages as $package): ?>
                        <article class="package-card <?= (int) $package['is_active'] === 1 ? 'is-active' : '' ?>">
                            <div class="package-card-main">
                                <div class="package-title-line">
                                    <h3><?= h((string) $package['name']) ?></h3>
                                    <?php if ((int) $package['is_active'] === 1): ?>
                                        <span class="package-badge active">AKTIF</span>
                                    <?php else: ?>
                                        <span class="package-badge">NONAKTIF</span>
                                    <?php endif; ?>
                                </div>
                                <p><?= h((string) $package['description'] !== '' ? (string) $package['description'] : 'Tidak ada keterangan tambahan.') ?></p>
                                <p class="package-off-day-summary">Hari libur: <strong><?= (string) ($package['off_days'] ?? '6,7') === '5' ? 'Jumat' : ((string) ($package['off_days'] ?? '6,7') === '7' ? 'Minggu' : 'Sabtu–Minggu') ?></strong></p>
                            </div>
                            <div class="package-card-actions">
                                <a class="text-action" href="packages.php?edit=<?= (int) $package['id'] ?>">Edit</a>
                                <?php if ((int) $package['is_active'] !== 1): ?>
                                    <form method="post" action="packages.php">
                                        <input type="hidden" name="package_action" value="activate">
                                        <input type="hidden" name="package_id" value="<?= (int) $package['id'] ?>">
                                        <button type="submit" class="button-compact">Aktifkan</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="panel form-panel package-form-panel">
            <div class="section-heading package-heading">
                <div>
                    <p class="eyebrow"><?= $editId > 0 ? 'UBAH DATA' : 'PAKET BARU' ?></p>
                    <h2><?= $editId > 0 ? 'Edit paket jadwal' : 'Buat paket jadwal' ?></h2>
                </div>
            </div>
            <form method="post" action="packages.php<?= $editId > 0 ? '?edit=' . $editId : '' ?>">
                <input type="hidden" name="package_action" value="save">
                <input type="hidden" name="package_id" value="<?= $editId > 0 ? $editId : 0 ?>">
                <label>Nama paket <span class="required-mark">*</span>
                    <input type="text" name="name" maxlength="80" required value="<?= h($formName) ?>" placeholder="Contoh: Normal, Ujian, Ramadan">
                </label>
                <?php if ($editId === 0): ?>
                <fieldset class="package-off-days">
                    <legend>Hari libur mingguan <span class="required-mark">*</span></legend>
                    <label class="off-day-choice"><input type="radio" name="off_days" value="5" <?= $formOffDays === '5' ? 'checked' : '' ?> required><span><strong>Jumat</strong><small>Sekolah Senin–Kamis dan Sabtu–Minggu</small></span></label>
                    <label class="off-day-choice"><input type="radio" name="off_days" value="7" <?= $formOffDays === '7' ? 'checked' : '' ?> required><span><strong>Minggu</strong><small>Sekolah Senin–Sabtu</small></span></label>
                    <label class="off-day-choice"><input type="radio" name="off_days" value="6,7" <?= $formOffDays === '6,7' ? 'checked' : '' ?> required><span><strong>Sabtu–Minggu</strong><small>Sekolah Senin–Jumat</small></span></label>
                    <p class="form-note">Paket baru otomatis dibuatkan aturan Normal pada setiap hari sekolah, lengkap dengan contoh kegiatan bel yang bisa diedit.</p>
                </fieldset>
                <?php else: ?>
                <input type="hidden" name="off_days" value="<?= h($formOffDays) ?>">
                <p class="form-note package-off-day-readonly">Hari libur paket ini: <strong><?= $formOffDays === '5' ? 'Jumat' : ($formOffDays === '7' ? 'Minggu' : 'Sabtu–Minggu') ?></strong>. Pengaturan hari libur hanya ditentukan saat paket dibuat agar aturan dan kegiatan yang sudah tersimpan tidak berubah diam-diam.</p>
                <?php endif; ?>
                <label>Keterangan (opsional)
                    <textarea name="description" maxlength="300" rows="4" placeholder="Jelaskan kapan paket ini digunakan"><?= h($formDescription) ?></textarea>
                    <small>Maksimal 300 karakter. Nama paket harus unik.</small>
                </label>
                <?php if ($editId === 0): ?><p class="form-note">Paket baru dibuat dalam keadaan nonaktif agar paket yang sedang digunakan tidak berubah tanpa sengaja.</p><?php else: ?><p class="form-note">Mengedit nama atau keterangan tidak mengubah status aktif paket ini.</p><?php endif; ?>
                <div class="form-actions">
                    <button type="submit"><?= $editId > 0 ? 'Simpan perubahan' : 'Buat paket' ?></button>
                    <?php if ($editId > 0): ?><a class="button-link-secondary" href="packages.php">Batal edit</a><?php endif; ?>
                </div>
            </form>
        </section>
    </section>

    <section class="panel package-rules">
        <h2>Aturan pada tahap ini</h2>
        <ul>
            <li>Paket Normal disiapkan otomatis hanya jika database belum memiliki paket.</li>
            <li>Paket baru selalu nonaktif sampai operator menekan <strong>Aktifkan</strong>.</li>
            <li>Penggantian paket aktif dilakukan dalam satu transaksi database untuk mencegah status aktif ganda.</li>
            <li>Paket tidak dihapus pada tahap ini agar data jadwal yang akan ditambahkan di tahap berikutnya tidak terhapus tanpa sengaja.</li>
        </ul>
    </section>

    <footer><span>Bel Madrasah · Tahap 4</span><span>Data paket tersimpan lokal</span></footer>
</div>
</body>
</html>
