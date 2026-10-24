<?php
/*
 * Editor Jadwal view.
 * Controller menangani operasi database dan validasi; layout bersama
 * menyediakan dokumen, navigasi Admin, dan footer.
 */
?>
<?php if ($message !== ''): ?><p class="action-feedback success" role="status"><?= h($message) ?></p><?php endif; ?>
    <?php if ($error !== ''): ?><p class="action-feedback error" role="alert"><?= h($error) ?></p><?php endif; ?>

    <section class="panel editor-package-select" aria-label="Paket jadwal yang dipilih">
        <div><p class="eyebrow">PAKET YANG DIEDIT</p><h2><?= $selectedPackage !== null ? h((string) $selectedPackage['name']) : 'Pilih paket' ?></h2>
        <?php if ($selectedPackage !== null && (int) $selectedPackage['is_active'] === 1): ?><span class="package-badge active">PAKET AKTIF GLOBAL</span><?php else: ?><span class="package-badge">PAKET NONAKTIF</span><?php endif; ?></div>
        <form method="get" action="schedule-editor.php" class="package-switch-form">
            <label for="package_id">Pilih paket</label>
            <select id="package_id" name="package_id" onchange="this.form.submit()">
                <?php foreach (($packages ?? []) as $package): ?><option value="<?= (int) $package['id'] ?>" <?= (int) $package['id'] === $selectedPackageId ? 'selected' : '' ?>><?= h((string) $package['name']) ?><?= (int) $package['is_active'] === 1 ? ' — AKTIF' : '' ?></option><?php endforeach; ?>
            </select>
            <noscript><button type="submit" class="button-compact">Pilih paket</button></noscript>
        </form>
        <?php if ($selectedPackage !== null): ?>
            <p class="form-note">Hari libur mingguan paket ini: <strong><?= h((string) ($selectedPackage['off_days'] ?? '6,7') === '5' ? 'Jumat' : ((string) ($selectedPackage['off_days'] ?? '6,7') === '7' ? 'Minggu' : 'Sabtu–Minggu')) ?></strong>. Pada hari tersebut scheduler tidak menjalankan bel. Aturan lama tetap tersimpan dan dapat diedit, tetapi tidak dijalankan selama hari itu ditetapkan sebagai libur.</p>
        <?php endif; ?>
    </section>

    <section class="panel editor-workflow-guide" aria-label="Panduan status jadwal">
        <div class="editor-workflow-title">
            <p class="eyebrow">SEBELUM JADWAL TAMPIL DI PUBLIK</p>
            <h2>Periksa 3 tingkat status</h2>
            <p>Jadwal hanya tampil jika paket, aturan hari, dan kegiatan bel sama-sama sesuai dan aktif.</p>
        </div>
        <ol class="editor-workflow-steps">
            <li><span class="workflow-number">1</span><div><strong>Paket jadwal</strong><small>Paket yang dipakai Publik harus berstatus <b>AKTIF</b>.</small></div></li>
            <li><span class="workflow-number">2</span><div><strong>Aturan hari</strong><small>Pola Normal/Khusus untuk hari yang cocok harus berstatus <b>AKTIF</b>.</small></div></li>
            <li><span class="workflow-number">3</span><div><strong>Kegiatan bel</strong><small>Setiap kegiatan yang ingin ditampilkan harus dicentang <b>Aktif</b>.</small></div></li>
        </ol>
    </section>

    <section class="panel copy-schedule-panel">
        <div class="section-heading">
            <div><p class="eyebrow">AKSI CEPAT</p><h2>Salin jadwal antarhari</h2><p class="copy-schedule-description">Pilih hari sumber dan tujuan. Seluruh pola Normal/Khusus beserta kegiatan pada hari tujuan akan diganti setelah Anda memeriksa pratinjau.</p></div>
        </div>
        <?php if ($copyPreview === null): ?>
            <form method="post" action="schedule-editor.php?package_id=<?= $selectedPackageId ?>" class="copy-schedule-form">
                <input type="hidden" name="package_id" value="<?= $selectedPackageId ?>">
                <input type="hidden" name="editor_action" value="preview_copy_schedule">
                <div class="copy-schedule-route">
                    <label class="copy-schedule-day" for="copy-source-weekday"><span>Sumber</span>
                        <select id="copy-source-weekday" name="source_weekday" required>
                            <?php foreach ($weekdays as $dayNumber => $dayName): ?><option value="<?= $dayNumber ?>" <?= $copySourceWeekday === $dayNumber ? 'selected' : '' ?>><?= h($dayName) ?></option><?php endforeach; ?>
                        </select>
                        <small>Jadwal yang disalin</small>
                    </label>
                    <span class="copy-schedule-arrow" aria-hidden="true">→</span>
                    <label class="copy-schedule-day is-target" for="copy-target-weekday"><span>Tujuan</span>
                        <select id="copy-target-weekday" name="target_weekday" required>
                            <?php foreach ($weekdays as $dayNumber => $dayName): ?><option value="<?= $dayNumber ?>" <?= $copyTargetWeekday === $dayNumber ? 'selected' : '' ?>><?= h($dayName) ?></option><?php endforeach; ?>
                        </select>
                        <small>Jadwal yang diganti</small>
                    </label>
                </div>
                <button type="submit" class="copy-schedule-button">Tampilkan Pratinjau</button>
            </form>
        <?php else: ?>
            <div class="copy-preview-panel" aria-live="polite">
                <?php $sourceDayName = $weekdays[(int) $copyPreview['source_weekday']] ?? 'Sumber'; $targetDayName = $weekdays[(int) $copyPreview['target_weekday']] ?? 'Tujuan'; ?>
                <div class="copy-preview-heading"><strong>Periksa sebelum menyimpan</strong><span><?= (int) $copyPreview['source_rule_count'] ?> aturan · <?= count($copyPreview['events']) ?> kegiatan dari <?= h($sourceDayName) ?></span></div>
                <p><?= h($targetDayName) ?> saat ini memiliki <?= (int) $copyPreview['target_rule_count'] ?> aturan dan <?= (int) $copyPreview['target_count'] ?> kegiatan. Jika dilanjutkan, seluruh aturan Normal/Khusus dan kegiatan <?= h($targetDayName) ?> akan diganti dengan salinan dari <?= h($sourceDayName) ?>.</p>
                <div class="copy-preview-source-rules">
                    <strong>Aturan yang akan disalin</strong>
                    <ul><?php foreach ($copyPreview['source_rules'] as $sourceRule): ?>
                        <li><?= h($sourceRule['label']) ?> · <?= (int) $sourceRule['event_count'] ?> kegiatan · <?= (int) $sourceRule['is_enabled'] === 1 ? 'Aktif' : 'Nonaktif' ?></li>
                    <?php endforeach; ?></ul>
                </div>
                <?php if ($copyPreview['events'] === []): ?>
                    <p class="copy-preview-empty"><?= h($sourceDayName) ?> memiliki aturan, tetapi belum memiliki kegiatan. Jika dilanjutkan, aturan <?= h($targetDayName) ?> akan diganti dan kegiatan yang ada di sana akan dihapus.</p>
                <?php else: ?>
                    <div class="copy-preview-table-wrap"><table class="copy-preview-table">
                        <thead><tr><th>Pola hari</th><th>Jam</th><th>Nama kegiatan</th><th>File audio</th><th>Status kegiatan</th></tr></thead>
                        <tbody><?php foreach ($copyPreview['events'] as $copyEvent): ?>
                            <tr><td><?= h($copyEvent['rule_label']) ?></td><td><?= h(substr((string) $copyEvent['event_time'], 0, 5)) ?></td><td><?= h($copyEvent['name']) ?></td><td><?= trim((string) $copyEvent['audio_path']) !== '' ? h(basename((string) $copyEvent['audio_path'])) : 'Belum diatur' ?></td><td><?= (int) $copyEvent['is_enabled'] === 1 ? 'Aktif' : 'Nonaktif' ?></td></tr>
                        <?php endforeach; ?></tbody>
                    </table></div>
                <?php endif; ?>
                <div class="copy-preview-warning"><strong>Perhatian:</strong> seluruh aturan dan kegiatan pada hari tujuan akan diganti, termasuk aturan Khusus. Referensi audio disalin tanpa menggandakan file. Hari sumber, paket lain, dan hari lain tidak berubah.</div>
                <div class="copy-preview-actions">
                    <form id="copy-confirm-form" method="post" action="schedule-editor.php?package_id=<?= $selectedPackageId ?>">
                        <input type="hidden" name="package_id" value="<?= $selectedPackageId ?>">
                        <input type="hidden" name="editor_action" value="confirm_copy_schedule">
                        <input type="hidden" name="source_weekday" value="<?= (int) $copyPreview['source_weekday'] ?>">
                        <input type="hidden" name="target_weekday" value="<?= (int) $copyPreview['target_weekday'] ?>">
                        <button type="button" class="copy-schedule-button is-confirm" onclick="document.getElementById('copy-confirm-form').submit();">Ya, Ganti Seluruh Jadwal <?= h($targetDayName) ?></button>
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
                <label for="rule-weekday">Hari
                    <select id="rule-weekday" name="weekday" required>
                        <?php foreach ($weekdays as $dayNumber => $dayName): ?><option value="<?= $dayNumber ?>" <?= (int) $ruleForm['weekday'] === $dayNumber ? 'selected' : '' ?>><?= h($dayName) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <fieldset class="pattern-choice"><legend>Pola hari</legend>
                    <label class="choice-row"><input type="radio" name="rule_mode" value="normal" <?= $ruleForm['rule_mode'] === 'normal' ? 'checked' : '' ?> onchange="toggleSpecialFields()" required><span><strong>Normal</strong><small>Jadwal umum untuk hari ini.</small></span></label>
                    <label class="choice-row"><input type="radio" name="rule_mode" value="special" <?= $ruleForm['rule_mode'] === 'special' ? 'checked' : '' ?> onchange="toggleSpecialFields()" required><span><strong>Khusus</strong><small>Jadwal yang berlaku saat atribut cocok.</small></span></label>
                </fieldset>
                <div id="special-fields" <?= $ruleForm['rule_mode'] !== 'special' ? 'hidden' : '' ?>>
                    <label for="special_type">Jenis kekhususan
                        <select name="special_type" id="special_type" onchange="toggleSpecialValue()">
                            <option value="pasaran" <?= $ruleForm['special_type'] === 'pasaran' ? 'selected' : '' ?>>Pasaran Jawa</option>
                            <option value="month_week" <?= $ruleForm['special_type'] === 'month_week' ? 'selected' : '' ?>>Minggu dalam bulan (Model A)</option>
                        </select>
                    </label>
                    <label for="special_value">Nilai kekhususan
                        <select name="special_value" id="special_value">
                            <?php foreach ($pasarans as $value): ?><option data-kind="pasaran" value="<?= h($value) ?>" <?= $ruleForm['special_type'] === 'pasaran' && $ruleForm['special_value'] === $value ? 'selected' : '' ?>><?= h($value) ?></option><?php endforeach; ?>
                            <?php foreach ($weekOptions as $value => $label): ?><option data-kind="month_week" value="<?= h($value) ?>" <?= $ruleForm['special_type'] === 'month_week' && $ruleForm['special_value'] === $value ? 'selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <label class="checkbox-line"><input type="checkbox" name="is_enabled" value="1" <?= $ruleForm['is_enabled'] === '1' ? 'checked' : '' ?>> Aturan aktif</label>
                <p class="form-note rule-form-help"><strong>Normal</strong> adalah jadwal dasar hari tersebut. <strong>Khusus</strong> menggantikan Normal saat pasaran atau minggu kalender cocok. Pastikan centang <strong>Aturan aktif</strong> menyala agar pola bisa dipakai.</p>
                <div class="form-actions rule-submit-actions"><button type="submit" class="rule-submit-button"><?= $editingRuleId > 0 ? 'Simpan Perubahan Aturan' : '+ Tambah Aturan Hari' ?></button><?php if ($editingRuleId > 0): ?><a class="button-link-secondary" href="schedule-editor.php?package_id=<?= $selectedPackageId ?>">Batal Edit</a><?php endif; ?></div>
            </form>
        </section>

        <section class="panel editor-rules-list">
            <div class="section-heading"><div><p class="eyebrow">POLA YANG TERSIMPAN</p><h2>Aturan per hari</h2></div><span class="section-note" id="rules-count"><?= count($rules ?? []) ?> aturan</span></div>
            <p class="form-note rules-list-help">Pilih hari untuk memfilter kartu. Status <strong>aturan</strong> berlaku untuk seluruh polanya; status <strong>kegiatan</strong> di tabel berlaku per bel. Keduanya perlu aktif agar kegiatan bisa muncul di Publik. Jam, nama, urutan, audio, dan status kegiatan dapat diubah langsung di tabel lalu tekan <strong>Simpan</strong>. Setiap pola hanya boleh memiliki satu kegiatan pada satu jam-menit; waktu duplikat ditolak.</p>
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
                    $enabledEventCount = count(array_filter($ruleEvents, static fn (array $event): bool => (int) $event['is_enabled'] === 1));
                    $seenEventTimes = [];
                    $duplicateTimes = [];
                    foreach ($ruleEvents as $ruleEvent) {
                        $ruleEventTime = substr((string) ($ruleEvent['event_time'] ?? ''), 0, 5);
                        if (isset($seenEventTimes[$ruleEventTime])) {
                            $duplicateTimes[$ruleEventTime] = true;
                        } else {
                            $seenEventTimes[$ruleEventTime] = true;
                        }
                    }
                    $duplicateTimeLabels = array_keys($duplicateTimes);
                    ?>
                    <article class="day-rule-card" data-weekday="<?= (int) $rule['weekday'] ?>">
                        <div class="day-rule-header">
                            <div><h3><?= h($weekdayName) ?> · <?= $rule['rule_mode'] === 'normal' ? 'Normal' : 'Khusus' ?></h3>
                                <p><?= $rule['rule_mode'] === 'special' ? h(($rule['special_type'] === 'pasaran' ? 'Pasaran Jawa: ' : 'Minggu kalender: ') . $specialLabel) : 'Pola dasar untuk hari ini' ?></p></div>
                            <span class="package-badge <?= (int) $rule['is_enabled'] === 1 ? 'active' : '' ?>"><?= (int) $rule['is_enabled'] === 1 ? 'AKTIF' : 'NONAKTIF' ?></span>
                        </div>
                        <?php if ($duplicateTimeLabels !== []): ?>
                            <p class="event-time-duplicate-warning" role="alert">
                                <strong>Waktu duplikat ditemukan.</strong>
                                Jam <?= h(implode(', ', $duplicateTimeLabels)) ?> dipakai oleh lebih dari satu kegiatan pada pola ini.
                                Pilih waktu berbeda dan simpan baris yang perlu diperbaiki. Data lama tidak dihapus otomatis.
                            </p>
                        <?php endif; ?>
                        <div class="rule-card-actions">
                            <form method="post" action="schedule-editor.php?package_id=<?= $selectedPackageId ?>"><input type="hidden" name="package_id" value="<?= $selectedPackageId ?>"><input type="hidden" name="editor_action" value="edit_rule"><input type="hidden" name="rule_id" value="<?= (int) $rule['id'] ?>"><button type="submit" class="button-compact rule-edit-button">Ubah Pola Hari</button></form>
                            <span class="rule-event-summary"><strong><?= $enabledEventCount ?> aktif</strong><span>dari <?= count($ruleEvents) ?> kegiatan</span></span>
                        </div>
                        <?php if ($rule['rule_mode'] === 'special'): ?>
                            <p class="overlap-warning"><strong>Perhatian:</strong> aturan Khusus pada hari yang sama dapat bertumpang tindih pada tanggal tertentu. Resolver kalender sudah menandai konflik jika beberapa aturan Khusus cocok; pada konflik, pola Normal menjadi fallback. Pastikan setiap aturan Khusus memang diperlukan.</p>
                        <?php endif; ?>
                        <div class="event-list">
                            <?php if ($ruleEvents !== []): ?>
                                <div class="bell-events-table-wrap">
                                    <table class="bell-events-table">
                                        <caption class="sr-only">Daftar kegiatan bel untuk pola <?= h($weekdayName . ' · ' . ($rule['rule_mode'] === 'normal' ? 'Normal' : 'Khusus')) ?></caption>
                                        <thead><tr><th>Jam</th><th>Nama kegiatan bel</th><th>Urutan</th><th>File audio</th><th>Status kegiatan</th><th>Aksi</th></tr></thead>
                                        <tbody>
                                        <?php foreach ($ruleEvents as $event): ?>
                                            <?php
                                            $currentEventTime = substr((string) ($event['event_time'] ?? ''), 0, 5);
                                            $isDuplicateTime = isset($duplicateTimes[$currentEventTime]);
                                            $currentAudioPath = trim((string) $event['audio_path']);
                                            $currentAudioRealPath = $currentAudioPath !== '' ? realpath($currentAudioPath) : false;
                                            $currentAudioIsManaged = false;
                                            $currentAudioStatus = 'none';
                                            $currentAudioStatusText = 'Belum ada audio';
                                            if ($currentAudioPath !== '') {
                                                $currentAudioExtension = strtolower(pathinfo($currentAudioPath, PATHINFO_EXTENSION));
                                                if (!is_file($currentAudioPath) || !is_readable($currentAudioPath)) {
                                                    $currentAudioStatus = 'missing';
                                                    $currentAudioStatusText = 'File tidak ditemukan atau tidak dapat dibaca';
                                                } elseif (!in_array($currentAudioExtension, ['mp3', 'wav', 'ogg', 'm4a', 'aac'], true)) {
                                                    $currentAudioStatus = 'unsupported';
                                                    $currentAudioStatusText = 'Format tidak didukung; gunakan MP3/WAV';
                                                } else {
                                                    clearstatcache(true, $currentAudioPath);
                                                    $currentAudioSize = @filesize($currentAudioPath);
                                                    if ($currentAudioSize === false || $currentAudioSize <= 0) {
                                                        $currentAudioStatus = 'empty';
                                                        $currentAudioStatusText = 'File kosong atau ukuran tidak dapat dibaca';
                                                    } else {
                                                        $currentAudioStatus = 'available';
                                                        $currentAudioStatusText = 'File tersedia · ' . number_format($currentAudioSize / 1048576, 2, ',', '.') . ' MB; codec perlu diuji putar';
                                                    }
                                                }
                                            }
                                            foreach ($audioAssets as $audioAsset) {
                                                if ($currentAudioRealPath !== false && $audioAsset['path'] === $currentAudioRealPath) {
                                                    $currentAudioIsManaged = true;
                                                    break;
                                                }
                                            }
                                            ?>
                                            <tr>
                                                <td class="bell-time-cell">
                                                    <form id="event-form-<?= (int) $event['id'] ?>" class="bell-event-row-form" method="post" action="schedule-editor.php?package_id=<?= $selectedPackageId ?>">
                                                        <input type="hidden" name="package_id" value="<?= $selectedPackageId ?>"><input type="hidden" name="editor_action" value="save_event"><input type="hidden" name="rule_id" value="<?= (int) $rule['id'] ?>"><input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                                                    </form>
                                                    <label class="sr-only" for="event-time-<?= (int) $event['id'] ?>">Jam</label>
                                                    <input form="event-form-<?= (int) $event['id'] ?>" id="event-time-<?= (int) $event['id'] ?>" type="text" name="event_time" inputmode="numeric" autocomplete="off" spellcheck="false" pattern="([01][0-9]|2[0-3]):[0-5][0-9]" maxlength="5" placeholder="HH:MM" title="<?= $isDuplicateTime ? 'Waktu ini duplikat pada pola hari; pilih waktu berbeda.' : 'Gunakan format 24 jam, misalnya 07:05' ?>"<?= $isDuplicateTime ? ' class="has-duplicate-time" aria-invalid="true"' : '' ?> required value="<?= h($currentEventTime) ?>">
                                                </td>
                                                <td>
                                                    <label class="sr-only" for="event-name-<?= (int) $event['id'] ?>">Keterangan bel</label>
                                                    <input form="event-form-<?= (int) $event['id'] ?>" id="event-name-<?= (int) $event['id'] ?>" name="event_name" maxlength="100" required value="<?= h((string) $event['name']) ?>">
                                                </td>
                                                <td class="bell-order-cell">
                                                    <label class="sr-only" for="event-order-<?= (int) $event['id'] ?>">Urutan</label>
                                                    <input form="event-form-<?= (int) $event['id'] ?>" id="event-order-<?= (int) $event['id'] ?>" type="number" name="sort_order" min="0" max="9999" required value="<?= (int) $event['sort_order'] ?>" title="Urutan pemutaran">
                                                </td>
                                                <td>
                                                        <label class="bell-audio-input" for="event-audio-<?= (int) $event['id'] ?>">Audio dari folder aset</label>
                                                        <select form="event-form-<?= (int) $event['id'] ?>" id="event-audio-<?= (int) $event['id'] ?>" name="audio_asset">
                                                            <option value="" <?= !$currentAudioIsManaged ? 'selected' : '' ?>><?= $currentAudioPath !== '' ? 'Pertahankan audio saat ini: ' . h(basename($currentAudioPath)) : 'Tanpa audio' ?></option>
                                                            <option value="__clear__">Hapus referensi audio</option>
                                                            <?php foreach ($audioAssets as $audioAsset): ?>
                                                                <option value="<?= h((string) $audioAsset['name']) ?>" <?= $currentAudioRealPath !== false && $audioAsset['path'] === $currentAudioRealPath ? 'selected' : '' ?>><?= h((string) $audioAsset['name']) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <small class="bell-audio-status" data-audio-health="<?= h($currentAudioStatus) ?>"><?= h($currentAudioStatusText) ?><?php if ($currentAudioPath !== ''): ?> · <?= h(basename($currentAudioPath)) ?><?php endif; ?></small>
                                                </td>
                                                <td>
                                                        <label class="checkbox-line bell-enabled-toggle"><input form="event-form-<?= (int) $event['id'] ?>" type="checkbox" name="event_enabled" value="1" <?= (int) $event['is_enabled'] === 1 ? 'checked' : '' ?>> Aktif</label>
                                                </td>
                                                <td class="bell-actions-cell">
                                                        <button type="submit" form="event-form-<?= (int) $event['id'] ?>" class="button-compact">Simpan</button>
                                                    <div class="delete-event-action">
                                                        <button type="button" class="text-danger" onclick="this.hidden=true; this.nextElementSibling.hidden=false;">Hapus</button>
                                                        <form method="post" action="schedule-editor.php?package_id=<?= $selectedPackageId ?>" class="delete-event-form" hidden>
                                                            <input type="hidden" name="package_id" value="<?= $selectedPackageId ?>">
                                                            <input type="hidden" name="editor_action" value="delete_event">
                                                            <input type="hidden" name="rule_id" value="<?= (int) $rule['id'] ?>">
                                                            <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                                                            <div class="delete-event-confirm-panel">
                                                                <strong>Hapus satu kegiatan ini?</strong>
                                                                <span>“<?= h((string) $event['name']) ?>” akan dihapus. Pola hari dan bel lain tetap ada.</span>
                                                                <div class="delete-event-confirm-buttons">
                                                                    <button type="submit" class="text-danger">Ya, hapus</button>
                                                                    <button type="button" class="button-compact" onclick="this.closest('.delete-event-action').querySelector(':scope > .text-danger').hidden=false; this.closest('form').hidden=true;">Batal</button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
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
                                <form class="event-edit-card" method="post" action="schedule-editor.php">
                                    <input type="hidden" name="package_id" value="<?= $selectedPackageId ?>"><input type="hidden" name="editor_action" value="save_event"><input type="hidden" name="rule_id" value="<?= (int) $rule['id'] ?>"><input type="hidden" name="event_id" value="0">
                                    <div class="event-form-grid">
                                        <label>Nama kegiatan<input name="event_name" maxlength="100" required placeholder="Contoh: Bel masuk"></label>
                                        <label>Waktu<input type="text" name="event_time" inputmode="numeric" autocomplete="off" spellcheck="false" pattern="([01][0-9]|2[0-3]):[0-5][0-9]" maxlength="5" placeholder="HH:MM" title="Gunakan format 24 jam, misalnya 07:05" required value="07:00"><small>Format 24 jam: 00:00–23:59. Waktu harus unik pada pola hari ini.</small></label>
                                        <label>Urutan<input type="number" name="sort_order" min="0" max="9999" required value="<?= count($ruleEvents) + 1 ?>"></label>
                                        <label class="audio-upload-label">Audio dari folder aset
                                            <select name="audio_asset">
                                                <option value="">Tanpa audio</option>
                                                <?php foreach ($audioAssets as $audioAsset): ?>
                                                    <option value="<?= h((string) $audioAsset['name']) ?>"><?= h((string) $audioAsset['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small><?= $audioAssets === [] ? 'Belum ada file audio. Salin file ke folder Audio melalui menu Konfigurasi, lalu muat ulang halaman.' : 'Pilih file yang sudah disalin ke folder Audio. Nama file tidak perlu diubah.' ?></small>
                                        </label>
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
        <h2>Panduan aturan jadwal</h2>
        <ul>
            <li>Hari Normal adalah pola dasar. Aturan Khusus dimaksudkan untuk menggantikan pola Normal ketika atribut tanggalnya cocok; bukan memutar keduanya.</li>
            <li>Pasaran Jawa dan minggu kalender merupakan dua jenis atribut yang terpisah. Satu aturan Khusus hanya boleh memakai salah satunya.</li>
            <li>Jika beberapa aturan Khusus cocok pada tanggal yang sama, resolver menandai konflik dan menggunakan pola Normal sebagai fallback; jadwal Khusus tidak digabung secara diam-diam.</li>
            <li>Audio dipilih dari daftar file pada folder aset lokal; aplikasi menyimpan referensi path tanpa mengunggah atau menggandakan file. Referensi audio lama tetap dipertahankan jika file belum dipindahkan. Scheduler memeriksa jadwal aktif secara berkala dan menggunakan pemutar audio internal. Status pemutaran aktual ditampilkan pada panel Publik; jika audio gagal, periksa status scheduler, referensi file, dan log pemutaran.</li>
        </ul>
    </section>

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
