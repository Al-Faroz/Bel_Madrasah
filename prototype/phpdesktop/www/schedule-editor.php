<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/Database.php';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
        if ($action === 'save_rule') {
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
            $editingRuleId = 0;
            $ruleForm = ['weekday' => '1', 'rule_mode' => 'normal', 'special_type' => 'pasaran', 'special_value' => 'Legi', 'is_enabled' => '1'];
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
            $audioPath = trim((string) ($_POST['existing_audio_path'] ?? ''));
            if (isset($_FILES['audio_file']) && (int) ($_FILES['audio_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $audioPath = $db->storeUploadedAudio($_FILES['audio_file']);
            }
            $db->saveBellEvent(
                $eventId > 0 ? $eventId : null,
                $ruleId,
                $eventName,
                $eventTime,
                $audioPath,
                isset($_POST['event_enabled']),
                max(0, (int) ($_POST['sort_order'] ?? 0))
            );
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
        <a href="index.php">Dashboard diagnostik</a>
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

    <section class="editor-grid">
        <section class="panel editor-rule-form">
            <div class="section-heading"><div><p class="eyebrow"><?= $editingRuleId > 0 ? 'EDIT ATURAN' : 'ATURAN HARI BARU' ?></p><h2><?= $editingRuleId > 0 ? 'Ubah pola hari' : 'Tambah pola hari' ?></h2></div></div>
            <form method="post" action="schedule-editor.php">
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
                <div class="form-actions"><button type="submit"><?= $editingRuleId > 0 ? 'Simpan aturan' : 'Tambah aturan' ?></button><?php if ($editingRuleId > 0): ?><a class="button-link-secondary" href="schedule-editor.php?package_id=<?= $selectedPackageId ?>">Batal</a><?php endif; ?></div>
            </form>
        </section>

        <section class="panel editor-rules-list">
            <div class="section-heading"><div><p class="eyebrow">POLA YANG TERSIMPAN</p><h2>Aturan per hari</h2></div><span class="section-note"><?= count($rules ?? []) ?> aturan</span></div>
            <?php if (($rules ?? []) === []): ?><p class="empty-record">Belum ada pola hari untuk paket ini. Mulailah dengan menambahkan aturan Normal.</p><?php else: ?>
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
                    <article class="day-rule-card">
                        <div class="day-rule-header">
                            <div><h3><?= h($weekdayName) ?> · <?= $rule['rule_mode'] === 'normal' ? 'Normal' : 'Khusus' ?></h3>
                                <p><?= $rule['rule_mode'] === 'special' ? h(($rule['special_type'] === 'pasaran' ? 'Pasaran Jawa: ' : 'Minggu kalender: ') . $specialLabel) : 'Pola dasar untuk hari ini' ?></p></div>
                            <span class="package-badge <?= (int) $rule['is_enabled'] === 1 ? 'active' : '' ?>"><?= (int) $rule['is_enabled'] === 1 ? 'AKTIF' : 'NONAKTIF' ?></span>
                        </div>
                        <div class="rule-card-actions">
                            <form method="post" action="schedule-editor.php"><input type="hidden" name="package_id" value="<?= $selectedPackageId ?>"><input type="hidden" name="editor_action" value="edit_rule"><input type="hidden" name="rule_id" value="<?= (int) $rule['id'] ?>"><button type="submit" class="button-compact">Edit aturan</button></form>
                            <span class="section-note"><?= count($ruleEvents) ?> kegiatan</span>
                        </div>
                        <?php if ($rule['rule_mode'] === 'special'): ?>
                            <p class="overlap-warning"><strong>Perhatian:</strong> aturan Khusus pada hari yang sama dapat bertumpang tindih dengan atribut lain pada tanggal tertentu. Resolver dan peringatan tanggal konflik akan dilengkapi pada Tahap 6. Pastikan aturan ini memang diperlukan.</p>
                        <?php endif; ?>
                        <div class="event-list">
                            <?php foreach ($ruleEvents as $event): ?>
                                <form class="event-edit-card" method="post" action="schedule-editor.php" enctype="multipart/form-data">
                                    <input type="hidden" name="package_id" value="<?= $selectedPackageId ?>"><input type="hidden" name="editor_action" value="save_event"><input type="hidden" name="rule_id" value="<?= (int) $rule['id'] ?>"><input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>"><input type="hidden" name="existing_audio_path" value="<?= h((string) $event['audio_path']) ?>">
                                    <div class="event-card-title"><strong><?= h((string) $event['name']) ?></strong><span class="event-time"><?= h((string) $event['event_time']) ?></span></div>
                                    <div class="event-form-grid">
                                        <label>Nama kegiatan<input name="event_name" maxlength="100" required value="<?= h((string) $event['name']) ?>"></label>
                                        <label>Waktu<input type="time" name="event_time" required value="<?= h((string) $event['event_time']) ?>"></label>
                                        <label>Urutan<input type="number" name="sort_order" min="0" max="9999" required value="<?= (int) $event['sort_order'] ?>"></label>
                                        <label class="audio-upload-label">Ganti file audio (opsional)<input type="file" name="audio_file" accept=".mp3,.wav,.ogg,.m4a,.aac,audio/*"><small><?= $event['audio_path'] !== '' ? 'Tersimpan: ' . h(basename((string) $event['audio_path'])) : 'Belum ada file audio.' ?></small></label>
                                    </div>
                                    <label class="checkbox-line"><input type="checkbox" name="event_enabled" value="1" <?= (int) $event['is_enabled'] === 1 ? 'checked' : '' ?>> Kegiatan aktif</label>
                                    <div class="form-actions"><button type="submit" class="button-compact">Simpan kegiatan</button></div>
                                </form>
                                <form method="post" action="schedule-editor.php" class="delete-event-form" onsubmit="return confirm('Hapus kegiatan bel ini?')"><input type="hidden" name="package_id" value="<?= $selectedPackageId ?>"><input type="hidden" name="editor_action" value="delete_event"><input type="hidden" name="rule_id" value="<?= (int) $rule['id'] ?>"><input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>"><button type="submit" class="text-danger">Hapus kegiatan</button></form>
                            <?php endforeach; ?>
                            <?php if ($ruleEvents === []): ?><p class="empty-event">Belum ada kegiatan untuk pola ini.</p><?php endif; ?>
                            <details class="add-event-details"><summary>+ Tambah kegiatan bel</summary>
                                <form class="event-edit-card" method="post" action="schedule-editor.php" enctype="multipart/form-data">
                                    <input type="hidden" name="package_id" value="<?= $selectedPackageId ?>"><input type="hidden" name="editor_action" value="save_event"><input type="hidden" name="rule_id" value="<?= (int) $rule['id'] ?>"><input type="hidden" name="event_id" value="0"><input type="hidden" name="existing_audio_path" value="">
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
</script>
</body>
</html>
