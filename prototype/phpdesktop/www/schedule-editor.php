<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/Database.php';

function h(mixed $value): string
{
    // PHP mengubah key array numerik seperti '1' menjadi int 1.
    // Escape harus aman untuk angka, string, dan nilai null dari database/form.
    if ($value === null) {
        $value = '';
    } elseif (!is_string($value) && !is_numeric($value) && !is_bool($value)) {
        $value = '';
    }

    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$weekdays = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
$pasarans = ['Legi', 'Pahing', 'Pon', 'Wage', 'Kliwon'];
$weekOptions = ['1' => 'Minggu ke-1', '2' => 'Minggu ke-2', '3' => 'Minggu ke-3', '4' => 'Minggu ke-4', '5' => 'Minggu ke-5', 'last' => 'Minggu terakhir'];
$db = null;
$error = '';
$message = '';
$selectedPackageId = filter_var($_POST['package_id'] ?? $_GET['package_id'] ?? 0, FILTER_VALIDATE_INT);
$selectedPackageId = $selectedPackageId !== false && $selectedPackageId > 0 ? $selectedPackageId : 0;
$editingRuleId = 0;
$ruleForm = ['weekday' => '1', 'rule_mode' => 'normal', 'special_type' => 'pasaran', 'special_value' => 'Legi', 'is_enabled' => '1'];
$eventFormErrors = [];
$copyPreview = null;

try {
    $db = new Database();
    $db->ensureDefaultSchedulePackage();
    $packages = $db->getSchedulePackages();
    if ($selectedPackageId === 0 && $packages !== []) {
        foreach ($packages as $package) {
            if ((int) $package['is_active'] === 1) {
                $selectedPackageId = (int) $package['id'];
                break;
            }
        }
        if ($selectedPackageId === 0) {
            $selectedPackageId = (int) $packages[0]['id'];
        }
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $action = (string) ($_POST['editor_action'] ?? '');
        if ($action === 'preview_copy_schedule') {
            $sourceRules = array_values(array_filter(
                $db->getDayRules($selectedPackageId, 1),
                static fn (array $item): bool => $item['rule_mode'] === 'normal'
            ));
            $targetRules = array_values(array_filter(
                $db->getDayRules($selectedPackageId, 2),
                static fn (array $item): bool => $item['rule_mode'] === 'normal'
            ));
            if ($sourceRules === [] || $targetRules === []) {
                throw new InvalidArgumentException('Salin jadwal memerlukan aturan Normal untuk Senin dan Selasa pada paket yang dipilih. Buat kedua aturan terlebih dahulu.');
            }
            $sourceRule = $sourceRules[0];
            $targetRule = $targetRules[0];
            $copyPreview = [
                'source_rule_id' => (int) $sourceRule['id'],
                'target_rule_id' => (int) $targetRule['id'],
                'events' => $db->getBellEvents((int) $sourceRule['id']),
                'target_count' => count($db->getBellEvents((int) $targetRule['id'])),
            ];
        } elseif ($action === 'confirm_copy_schedule') {
            $sourceRuleId = (int) ($_POST['source_rule_id'] ?? 0);
            $targetRuleId = (int) ($_POST['target_rule_id'] ?? 0);
            $copiedCount = $db->replaceDayScheduleFromSource($selectedPackageId, $sourceRuleId, $targetRuleId);
            $message = 'Jadwal Selasa berhasil diganti dengan ' . $copiedCount . ' kegiatan dari Senin. Paket dan hari lain tidak diubah.';
        } elseif ($action === 'save_rule') {
            $editingRuleId = (int) ($_POST['rule_id'] ?? 0);
            $ruleForm = [
                'weekday' => (string) ($_POST['weekday'] ?? '1'),
                'rule_mode' => (string) ($_POST['rule_mode'] ?? 'normal'),
                'special_type' => (string) ($_POST['special_type'] ?? 'pasaran'),
                'special_value' => (string) ($_POST['special_value'] ?? 'Legi'),
                'is_enabled' => isset($_POST['is_enabled']) ? '1' : '0',
            ];
            $ruleId = $db->saveDayRule(
                $editingRuleId > 0 ? $editingRuleId : null,
                $selectedPackageId,
                (int) $ruleForm['weekday'],
                $ruleForm['rule_mode'],
                $ruleForm['rule_mode'] === 'special' ? $ruleForm['special_type'] : null,
                $ruleForm['rule_mode'] === 'special' ? $ruleForm['special_value'] : null,
                $ruleForm['is_enabled'] === '1'
            );
            $message = $editingRuleId > 0 ? 'Aturan hari berhasil diperbarui.' : 'Aturan hari berhasil ditambahkan.';
            // Pertahankan hari yang baru disimpan agar kartu aturan tidak tampak menghilang setelah submit.
            $savedWeekday = $ruleForm['weekday'];
            $editingRuleId = 0;
            $ruleForm = ['weekday' => $savedWeekday, 'rule_mode' => 'normal', 'special_type' => 'pasaran', 'special_value' => 'Legi', 'is_enabled' => '1'];
        } elseif ($action === 'edit_rule') {
            $editingRuleId = (int) ($_POST['rule_id'] ?? 0);
            $rule = $db->getDayRule($editingRuleId);
            if ($rule === null || (int) $rule['package_id'] !== $selectedPackageId) {
                throw new InvalidArgumentException('Aturan hari yang dipilih tidak ditemukan.');
            }
            $ruleForm = [
                'weekday' => (string) $rule['weekday'],
                'rule_mode' => (string) $rule['rule_mode'],
                'special_type' => (string) ($rule['special_type'] ?? 'pasaran'),
                'special_value' => (string) ($rule['special_value'] ?? 'Legi'),
                'is_enabled' => (string) $rule['is_enabled'],
            ];
        } elseif ($action === 'save_event') {
            $ruleId = (int) ($_POST['rule_id'] ?? 0);
            $rule = $db->getDayRule($ruleId);
            if ($rule === null || (int) $rule['package_id'] !== $selectedPackageId) {
                throw new InvalidArgumentException('Aturan hari untuk kegiatan tidak ditemukan.');
            }
            $eventId = (int) ($_POST['event_id'] ?? 0);
            $eventName = trim((string) ($_POST['event_name'] ?? ''));
            $eventTime = trim((string) ($_POST['event_time'] ?? ''));
            $sortOrder = (int) ($_POST['sort_order'] ?? 0);

            // Validate the form before storing an uploaded file, so invalid data
            // cannot leave an orphaned audio file in AppData.
            if ($eventName === '' || strlen($eventName) > 100) {
                throw new InvalidArgumentException('Nama kegiatan wajib diisi dan maksimal 100 karakter.');
            }
            if (!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $eventTime)) {
                throw new InvalidArgumentException('Waktu harus valid dengan format 00:00 sampai 23:59.');
            }
            if ($sortOrder < 0 || $sortOrder > 9999) {
                throw new InvalidArgumentException('Urutan harus antara 0 dan 9999.');
            }

            // Never trust a hidden form field for the stored path. Keep the path
            // from the database and verify that the event belongs to this rule.
            $audioPath = '';
            if ($eventId > 0) {
                $existingEvent = $db->getBellEvent($eventId);
                if ($existingEvent === null || (int) $existingEvent['day_rule_id'] !== $ruleId) {
                    throw new InvalidArgumentException('Kegiatan yang akan diubah tidak ditemukan pada aturan hari ini.');
                }
                $audioPath = (string) $existingEvent['audio_path'];
            }

            $uploadedAudioPath = null;
            try {
                if (isset($_FILES['audio_file']) && (int) ($_FILES['audio_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $uploadedAudioPath = $db->storeUploadedAudio($_FILES['audio_file']);
                    $audioPath = $uploadedAudioPath;
                }
                $db->saveBellEvent(
                    $eventId > 0 ? $eventId : null,
                    $ruleId,
                    $eventName,
                    $eventTime,
                    $audioPath,
                    isset($_POST['event_enabled']),
                    $sortOrder
                );
            } catch (Throwable $saveException) {
                // If persistence fails after upload, remove only the new orphan.
                if ($uploadedAudioPath !== null && is_file($uploadedAudioPath)) {
                    @unlink($uploadedAudioPath);
                }
                throw $saveException;
            }
            $message = $eventId > 0 ? 'Kegiatan bel berhasil diperbarui.' : 'Kegiatan bel berhasil ditambahkan.';
        } elseif ($action === 'delete_event') {
            $ruleId = (int) ($_POST['rule_id'] ?? 0);
            $rule = $db->getDayRule($ruleId);
            if ($rule === null || (int) $rule['package_id'] !== $selectedPackageId) {
                throw new InvalidArgumentException('Aturan hari untuk kegiatan tidak ditemukan.');
            }
            $db->deleteBellEvent((int) ($_POST['event_id'] ?? 0), $ruleId);
            $message = 'Kegiatan bel berhasil dihapus.';
        } else {
            throw new InvalidArgumentException('Aksi editor jadwal tidak dikenali.');
        }
    }

    $selectedPackage = $db->getSchedulePackage($selectedPackageId);
    if ($selectedPackage === null) {
        throw new RuntimeException('Paket jadwal yang dipilih tidak tersedia.');
    }
    // Database yang masih benar-benar kosong mendapat pola awal yang bisa diedit.
    // Seed hanya berjalan sekali dan tidak menimpa aturan yang sudah dibuat pengguna.
    $starterScheduleCreated = $db->ensureStarterScheduleRules($selectedPackageId);
    if ($starterScheduleCreated) {
        $message = 'Pola awal Senin–Jumat dibuat. Waktu bel adalah contoh—silakan sesuaikan dengan jadwal madrasah.';
    }
    $rules = $db->getDayRules($selectedPackageId);
    $eventsByRule = [];
    foreach ($rules as $rule) {
        $eventsByRule[(int) $rule['id']] = $db->getBellEvents((int) $rule['id']);
    }
} catch (Throwable $exception) {
    $error = $exception instanceof InvalidArgumentException
        ? $exception->getMessage()
        : 'Editor jadwal gagal memproses data: ' . $exception->getMessage();
    $packages = $packages ?? [];
    $selectedPackage = isset($db, $selectedPackageId) && $db instanceof Database ? $db->getSchedulePackage($selectedPackageId) : null;
    $rules = isset($db, $selectedPackageId) && $db instanceof Database ? $db->getDayRules($selectedPackageId) : [];
    $eventsByRule = [];
    if ($db instanceof Database) {
        foreach ($rules as $rule) {
            $eventsByRule[(int) $rule['id']] = $db->getBellEvents((int) $rule['id']);
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
    <title>Editor Jadwal Harian — Bel Madrasah</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body>
<div class="app-shell">
    <header class="topbar">
        <div class="brand-mark" aria-hidden="true">BM</div>
        <div><p class="eyebrow">TAHAP 5 · EDITOR JADWAL HARIAN</p><h1>Bel Madrasah</h1><p class="subtitle">Atur pola hari dan kegiatan bel dalam setiap paket</p></div>
    </header>
    <nav class="main-nav" aria-label="Navigasi utama">
        <a href="dashboard.php">Dashboard</a>
        <a href="index.php">Pemeriksaan</a>
        <a href="packages.php">Paket jadwal</a>
        <a href="schedule-editor.php" class="active">Editor jadwal</a>
        <a href="settings.php">Konfigurasi</a>
        <a href="backup.php">Backup data</a>
    </nav>

    <?php if ($message !== ''): ?><p class="action-feedback success" role="status"><?= h($message) ?></p><?php endif; ?>
    <?php if ($error !== ''): ?><p class="action-feedback error" role="alert"><?= h($error) ?></p><?php endif; ?>

    <section class="panel editor-package-select">
        <div><p class="eyebrow">PAKET YANG DIEDIT</p><h2><?= $selectedPackage !== null ? h((string) $selectedPackage['name']) : 'Pilih paket' ?></h2>
        <?php if ($selectedPackage !== null && (int) $selectedPackage['is_active'] === 1): ?><span class="package-badge active">PAKET AKTIF GLOBAL</span><?php else: ?><span class="package-badge">PAKET NONAKTIF</span><?php endif; ?></div>
        <form method="get" action="schedule-editor.php" class="package-switch-form">
            <label for="package_id">Pilih paket</label>
            <select id="package_id" name="package_id" onchange="this.form.submit()">
                <?php foreach (($packages ?? []) as $package): ?><option value="<?= (int) $package['id'] ?>" <?= (int) $package['id'] === $selectedPackageId ? 'selected' : '' ?>><?= h((string) $package['name']) ?><?= (int) $package['is_active'] === 1 ? ' — AKTIF' : '' ?></option><?php endforeach; ?>
            </select>
            <noscript><button type="submit" class="button-compact">Pilih paket</button></noscript>
        </form>
    </section>

    <section class="panel copy-schedule-panel">
        <div class="section-heading">
            <div><p class="eyebrow">AKSI CEPAT</p><h2>Salin jadwal antarhari</h2><p class="copy-schedule-description">Salin seluruh kegiatan dari Senin ke Selasa pada paket ini. Data Selasa akan diganti setelah konfirmasi; paket dan hari lain tetap aman.</p></div>
        </div>
        <?php if ($copyPreview === null): ?>
            <form method="post" action="schedule-editor.php" class="copy-schedule-form">
                <input type="hidden" name="package_id" value="<?= $selectedPackageId ?>">
                <input type="hidden" name="editor_action" value="preview_copy_schedule">
                <div class="copy-schedule-route">
                    <div class="copy-schedule-day"><span>Sumber</span><strong>Senin</strong><small>Seluruh kegiatan</small></div>
                    <span class="copy-schedule-arrow" aria-hidden="true">→</span>
                    <div class="copy-schedule-day is-target"><span>Tujuan</span><strong>Selasa</strong><small>Ganti seluruh kegiatan</small></div>
                </div>
                <button type="submit" class="copy-schedule-button">Pratinjau Salin Jadwal</button>
            </form>
        <?php else: ?>
            <div class="copy-preview-panel" aria-live="polite">
                <div class="copy-preview-heading"><strong>Periksa sebelum menyimpan</strong><span><?= count($copyPreview['events']) ?> kegiatan dari Senin</span></div>
                <p>Jadwal Selasa saat ini memiliki <?= (int) $copyPreview['target_count'] ?> kegiatan. Jika dilanjutkan, seluruh kegiatan Selasa akan diganti dengan daftar berikut.</p>
                <?php if ($copyPreview['events'] === []): ?>
                    <p class="copy-preview-empty">Senin belum memiliki kegiatan. Konfirmasi akan mengosongkan kegiatan Selasa.</p>
                <?php else: ?>
                    <div class="copy-preview-table-wrap"><table class="copy-preview-table">
                        <thead><tr><th>Jam</th><th>Nama kegiatan</th><th>Audio</th><th>Status</th></tr></thead>
                        <tbody><?php foreach ($copyPreview['events'] as $copyEvent): ?>
                            <tr><td><?= h(substr((string) $copyEvent['event_time'], 0, 5)) ?></td><td><?= h($copyEvent['name']) ?></td><td><?= trim((string) $copyEvent['audio_path']) !== '' ? h(basename((string) $copyEvent['audio_path'])) : 'Belum diatur' ?></td><td><?= (int) $copyEvent['is_enabled'] === 1 ? 'Aktif' : 'Nonaktif' ?></td></tr>
                        <?php endforeach; ?></tbody>
                    </table></div>
                <?php endif; ?>
                <div class="copy-preview-warning"><strong>Perhatian:</strong> kegiatan Selasa yang ada sekarang akan dihapus dan diganti. Referensi audio disalin tanpa menggandakan file audio. Data Senin, paket lain, dan hari lain tidak berubah.</div>
                <div class="copy-preview-actions">
                    <form method="post" action="schedule-editor.php" onsubmit="return confirm('Yakin mengganti seluruh kegiatan Selasa dengan jadwal Senin? Tindakan ini tidak mengubah paket atau hari lain.');">
                        <input type="hidden" name="package_id" value="<?= $selectedPackageId ?>">
                        <input type="hidden" name="editor_action" value="confirm_copy_schedule">
                        <input type="hidden" name="source_rule_id" value="<?= (int) $copyPreview['source_rule_id'] ?>">
                        <input type="hidden" name="target_rule_id" value="<?= (int) $copyPreview['target_rule_id'] ?>">
                        <button type="submit" class="copy-schedule-button is-confirm">Ya, Ganti Seluruh Jadwal Selasa</button>
                    </form>
                    <a class="button-link-secondary" href="schedule-editor.php?package_id=<?= $selectedPackageId ?>">Batal</a>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <section class="editor-grid">
        <section class="panel editor-rule-form">
            <form id="day-rule-form" method="post" action="schedule-editor.php">
            <div class="section-heading editor-rule-heading"><div><p class="eyebrow"><?= $editingRuleId > 0 ? 'EDIT ATURAN' : 'ATURAN HARI BARU' ?></p><h2><?= $editingRuleId > 0 ? 'Ubah pola hari' : 'Tambah pola hari' ?></h2></div><button type="submit" class="rule-submit-button rule-submit-button-top"><?= $editingRuleId > 0 ? 'Simpan Perubahan' : '+ Tambah Aturan Hari' ?></button></div>
                <input type="hidden" name="package_id" value="<?= $selectedPackageId ?>">
                <input type="hidden" name="editor_action" value="save_rule">
                <input type="hidden" name="rule_id" value="<?= $editingRuleId ?>">
                <label>Hari
                    <select name="weekday" required>
                        <?php foreach ($weekdays as $dayNumber => $dayName): ?><option value="<?= $dayNumber ?>" <?= (int) $ruleForm['weekday'] === $dayNumber ? 'selected' : '' ?>><?= h($dayName) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <fieldset class="pattern-choice"><legend>Pola hari</legend>
                    <label class="choice-row"><input type="radio" name="rule_mode" value="normal" <?= $ruleForm['rule_mode'] === 'normal' ? 'checked' : '' ?> onchange="toggleSpecialFields()" required><span><strong>Normal</strong><small>Jadwal umum untuk hari ini.</small></span></label>
                    <label class="choice-row"><input type="radio" name="rule_mode" value="special" <?= $ruleForm['rule_mode'] === 'special' ? 'checked' : '' ?> onchange="toggleSpecialFields()" required><span><strong>Khusus</strong><small>Jadwal yang berlaku saat atribut cocok.</small></span></label>
                </fieldset>
                <div id="special-fields" <?= $ruleForm['rule_mode'] !== 'special' ? 'hidden' : '' ?>>
                    <label>Jenis kekhususan
                        <select name="special_type" id="special_type" onchange="toggleSpecialValue()">
                            <option value="pasaran" <?= $ruleForm['special_type'] === 'pasaran' ? 'selected' : '' ?>>Pasaran Jawa</option>
                            <option value="month_week" <?= $ruleForm['special_type'] === 'month_week' ? 'selected' : '' ?>>Minggu dalam bulan (Model A)</option>
                        </select>
                    </label>
                    <label>Nilai kekhususan
                        <select name="special_value" id="special_value">
                            <?php foreach ($pasarans as $value): ?><option data-kind="pasaran" value="<?= h($value) ?>" <?= $ruleForm['special_type'] === 'pasaran' && $ruleForm['special_value'] === $value ? 'selected' : '' ?>><?= h($value) ?></option><?php endforeach; ?>
                            <?php foreach ($weekOptions as $value => $label): ?><option data-kind="month_week" value="<?= h($value) ?>" <?= $ruleForm['special_type'] === 'month_week' && $ruleForm['special_value'] === $value ? 'selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <label class="checkbox-line"><input type="checkbox" name="is_enabled" value="1" <?= $ruleForm['is_enabled'] === '1' ? 'checked' : '' ?>> Aturan aktif</label>
                <p class="form-note">Untuk setiap hari, buat satu aturan Normal sebagai jadwal dasar. Aturan Khusus dapat ditambahkan lebih dari satu kali untuk atribut yang berbeda.</p>
                <div class="form-actions rule-submit-actions"><button type="submit" class="rule-submit-button"><?= $editingRuleId > 0 ? 'Simpan Perubahan Aturan' : '+ Tambah Aturan Hari' ?></button><?php if ($editingRuleId > 0): ?><a class="button-link-secondary" href="schedule-editor.php?package_id=<?= $selectedPackageId ?>">Batal Edit</a><?php endif; ?></div>
            </form>
        </section>

        <section class="panel editor-rules-list">
            <div class="section-heading"><div><p class="eyebrow">POLA YANG TERSIMPAN</p><h2>Aturan per hari</h2></div><span class="section-note" id="rules-count"><?= count($rules ?? []) ?> aturan</span></div>
            <label class="schedule-day-filter">Tampilkan data hari
                <select id="schedule-day-filter" aria-label="Tampilkan aturan untuk hari">
                    <?php foreach ($weekdays as $dayNumber => $dayName): ?><option value="<?= $dayNumber ?>" <?= (int) $ruleForm['weekday'] === $dayNumber ? 'selected' : '' ?>><?= h($dayName) ?></option><?php endforeach; ?>
                </select>
            </label>
            <p class="empty-record" id="no-rules-for-day" hidden>Belum ada aturan tersimpan untuk hari ini. Gunakan formulir di sebelah kiri untuk menambahkan aturan bagi hari yang dipilih.</p>
            <?php if (($rules ?? []) === []): ?>
                <div class="schedule-empty-state">
                    <strong>Belum ada aturan hari untuk paket ini</strong>
                    <p>Buat aturan Normal terlebih dahulu. Setelah aturan tersimpan, tombol <b>Edit aturan</b> dan <b>+ Tambah kegiatan bel</b> akan tersedia pada kartu aturan tersebut.</p>
                    <a class="schedule-empty-cta" href="#day-rule-form">+ Tambah Aturan Hari</a>
                </div>
            <?php else: ?>
                <?php foreach ($rules as $rule): ?>
                    <?php
                    $weekdayName = $weekdays[(int) $rule['weekday']] ?? 'Hari tidak dikenal';
                    $specialLabel = '';
                    if ($rule['rule_mode'] === 'special') {
                        $specialLabel = $rule['special_type'] === 'pasaran'
                            ? (string) $rule['special_value']
                            : ($weekOptions[(string) $rule['special_value']] ?? (string) $rule['special_value']);
                    }
                    $ruleEvents = $eventsByRule[(int) $rule['id']] ?? [];
                    ?>
                    <article class="day-rule-card" data-weekday="<?= (int) $rule['weekday'] ?>">
                        <div class="day-rule-header">
                            <div><h3><?= h($weekdayName) ?> · <?= $rule['rule_mode'] === 'normal' ? 'Normal' : 'Khusus' ?></h3>
                                <p><?= $rule['rule_mode'] === 'special' ? h(($rule['special_type'] === 'pasaran' ? 'Pasaran Jawa: ' : 'Minggu kalender: ') . $specialLabel) : 'Pola dasar untuk hari ini' ?></p></div>
                            <span class="package-badge <?= (int) $rule['is_enabled'] === 1 ? 'active' : '' ?>"><?= (int) $rule['is_enabled'] === 1 ? 'AKTIF' : 'NONAKTIF' ?></span>
                        </div>
                        <div class="rule-card-actions">
                            <form method="post" action="schedule-editor.php"><input type="hidden" name="package_id" value="<?= $selectedPackageId ?>"><input type="hidden" name="editor_action" value="edit_rule"><input type="hidden" name="rule_id" value="<?= (int) $rule['id'] ?>"><button type="submit" class="button-compact rule-edit-button">Edit Aturan</button></form>
                            <span class="section-note"><?= count($ruleEvents) ?> kegiatan</span>
                        </div>
                        <?php if ($rule['rule_mode'] === 'special'): ?>
                            <p class="overlap-warning"><strong>Perhatian:</strong> aturan Khusus pada hari yang sama dapat bertumpang tindih dengan atribut lain pada tanggal tertentu. Resolver dan peringatan tanggal konflik akan dilengkapi pada Tahap 6. Pastikan aturan ini memang diperlukan.</p>
                        <?php endif; ?>
                        <div class="event-list">
                            <?php if ($ruleEvents !== []): ?>
                                <div class="bell-events-table-wrap">
                                    <table class="bell-events-table">
                                        <thead><tr><th>Jam</th><th>Keterangan bel</th><th>Audio</th><th>Status</th><th>Aksi</th></tr></thead>
                                        <tbody>
                                        <?php foreach ($ruleEvents as $event): ?>
                                            <tr>
                                                <td class="bell-time-cell">
                                                    <form id="event-form-<?= (int) $event['id'] ?>" class="bell-event-row-form" method="post" action="schedule-editor.php" enctype="multipart/form-data">
                                                        <input type="hidden" name="package_id" value="<?= $selectedPackageId ?>"><input type="hidden" name="editor_action" value="save_event"><input type="hidden" name="rule_id" value="<?= (int) $rule['id'] ?>"><input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                                                    </form>
                                                    <strong><?= h(substr((string) $event['event_time'], 0, 5)) ?></strong>
                                                </td>
                                                <td>
                                                        <label class="sr-only" for="event-name-<?= (int) $event['id'] ?>">Keterangan bel</label>
                                                        <input form="event-form-<?= (int) $event['id'] ?>" id="event-name-<?= (int) $event['id'] ?>" name="event_name" maxlength="100" required value="<?= h((string) $event['name']) ?>">
                                                        <label class="sr-only" for="event-time-<?= (int) $event['id'] ?>">Jam</label>
                                                        <input form="event-form-<?= (int) $event['id'] ?>" id="event-time-<?= (int) $event['id'] ?>" type="time" name="event_time" required value="<?= h((string) $event['event_time']) ?>">
                                                        <label class="sr-only" for="event-order-<?= (int) $event['id'] ?>">Urutan</label>
                                                        <input form="event-form-<?= (int) $event['id'] ?>" id="event-order-<?= (int) $event['id'] ?>" type="number" name="sort_order" min="0" max="9999" required value="<?= (int) $event['sort_order'] ?>" title="Urutan pemutaran">
                                                </td>
                                                <td>
                                                        <label class="bell-audio-input">Pilih/ganti audio<input form="event-form-<?= (int) $event['id'] ?>" type="file" name="audio_file" accept=".mp3,.wav,.ogg,.m4a,.aac,audio/*"></label>
                                                        <small class="bell-audio-status"><?= $event['audio_path'] !== '' ? 'Tersimpan: ' . h(basename((string) $event['audio_path'])) : 'Belum ada audio' ?></small>
                                                </td>
                                                <td>
                                                        <label class="checkbox-line bell-enabled-toggle"><input form="event-form-<?= (int) $event['id'] ?>" type="checkbox" name="event_enabled" value="1" <?= (int) $event['is_enabled'] === 1 ? 'checked' : '' ?>> Aktif</label>
                                                </td>
                                                <td class="bell-actions-cell">
                                                        <button type="submit" form="event-form-<?= (int) $event['id'] ?>" class="button-compact">Simpan</button>
                                                    <form method="post" action="schedule-editor.php" class="delete-event-form" onsubmit="return confirm('Hapus kegiatan bel ini?')"><input type="hidden" name="package_id" value="<?= $selectedPackageId ?>"><input type="hidden" name="editor_action" value="delete_event"><input type="hidden" name="rule_id" value="<?= (int) $rule['id'] ?>"><input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>"><button type="submit" class="text-danger">Hapus</button></form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="empty-event">Belum ada kegiatan bel untuk aturan ini. Tambahkan kegiatan melalui formulir di bawah.</p>
                            <?php endif; ?>
                            <details class="add-event-details"><summary><span aria-hidden="true">＋</span> Tambah Kegiatan Bel</summary>
                                <form class="event-edit-card" method="post" action="schedule-editor.php" enctype="multipart/form-data">
                                    <input type="hidden" name="package_id" value="<?= $selectedPackageId ?>"><input type="hidden" name="editor_action" value="save_event"><input type="hidden" name="rule_id" value="<?= (int) $rule['id'] ?>"><input type="hidden" name="event_id" value="0">
                                    <div class="event-form-grid">
                                        <label>Nama kegiatan<input name="event_name" maxlength="100" required placeholder="Contoh: Bel masuk"></label>
                                        <label>Waktu<input type="time" name="event_time" required value="07:00"></label>
                                        <label>Urutan<input type="number" name="sort_order" min="0" max="9999" required value="<?= count($ruleEvents) + 1 ?>"></label>
                                        <label class="audio-upload-label">File audio (maks. 50 MB)<input type="file" name="audio_file" accept=".mp3,.wav,.ogg,.m4a,.aac,audio/*"><small>MP3, WAV, OGG, M4A, AAC. File disimpan di AppData lokal.</small></label>
                                    </div>
                                    <label class="checkbox-line"><input type="checkbox" name="event_enabled" value="1" checked> Kegiatan aktif</label>
                                    <div class="form-actions"><button type="submit" class="button-compact">Tambah kegiatan</button></div>
                                </form>
                            </details>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </section>

    <section class="panel package-rules editor-guidance">
        <h2>Catatan penting Tahap 5</h2>
        <ul>
            <li>Hari Normal adalah pola dasar. Aturan Khusus dimaksudkan untuk menggantikan pola Normal ketika atribut tanggalnya cocok; bukan memutar keduanya.</li>
            <li>Pasaran Jawa dan minggu kalender merupakan dua jenis atribut yang terpisah. Satu aturan Khusus hanya boleh memakai salah satunya.</li>
            <li>Jika Pasaran dan Minggu kalender berpotensi cocok pada tanggal yang sama, halaman memberi peringatan. Penentuan prioritas tanggal dan fallback Normal dibuat di Tahap 6.</li>
            <li>File audio disalin ke penyimpanan lokal aplikasi, maksimal 50 MB per file. Tahap ini menyimpan konfigurasi; pemutaran otomatis belum aktif.</li>
        </ul>
    </section>
    <footer><span>Bel Madrasah · Tahap 5</span><span>Editor jadwal · mesin otomatis belum aktif</span></footer>
</div>
<script>
function toggleSpecialFields() {
    const selected = document.querySelector('input[name="rule_mode"]:checked');
    const fields = document.getElementById('special-fields');
    if (fields) fields.hidden = !selected || selected.value !== 'special';
    toggleSpecialValue();
}
function toggleSpecialValue() {
    const type = document.getElementById('special_type');
    const value = document.getElementById('special_value');
    if (!type || !value) return;
    const kind = type.value === 'pasaran' ? 'pasaran' : 'month_week';
    let first = null;
    Array.from(value.options).forEach(option => {
        const visible = option.dataset.kind === kind;
        option.hidden = !visible;
        option.disabled = !visible;
        if (visible && first === null) first = option;
    });
    if (!value.selectedOptions.length || value.selectedOptions[0].dataset.kind !== kind) {
        if (first) first.selected = true;
    }
}
toggleSpecialFields();

// Sinkronkan pilihan hari dengan daftar aturan yang terlihat.
(function () {
    const daySelect = document.getElementById('schedule-day-filter');
    const ruleFormDay = document.querySelector('#day-rule-form select[name="weekday"]');
    const cards = Array.from(document.querySelectorAll('.day-rule-card[data-weekday]'));
    const emptyMessage = document.getElementById('no-rules-for-day');
    const countLabel = document.getElementById('rules-count');
    const genericEmpty = document.querySelector('.schedule-empty-state');

    function applyDayFilter(day) {
        let visible = 0;
        cards.forEach(card => {
            const matches = card.dataset.weekday === String(day);
            card.hidden = !matches;
            if (matches) visible++;
        });
        if (emptyMessage) emptyMessage.hidden = visible !== 0;
        if (countLabel) countLabel.textContent = visible + ' aturan untuk hari ini';
    }

    if (daySelect && ruleFormDay) {
        // Empty state dinamis di bawah filter menggantikan pesan umum lama.
        if (genericEmpty) genericEmpty.hidden = true;
        // Filter mengikuti hari pada form; pilihan di daftar juga bisa mengubah form.
        daySelect.value = ruleFormDay.value;
        daySelect.addEventListener('change', function () {
            ruleFormDay.value = daySelect.value;
            applyDayFilter(daySelect.value);
        });
        ruleFormDay.addEventListener('change', function () {
            daySelect.value = ruleFormDay.value;
            applyDayFilter(ruleFormDay.value);
        });
        applyDayFilter(daySelect.value);
    }
})();
</script>
</body>
</html>
