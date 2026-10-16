<?php
/*
 * Backup/Restore view.
 * Controller melakukan validasi dan operasi SQLite; layout bersama menyediakan shell Admin.
 */
?>
<section class="intro page-intro">
    <div>
        <p class="eyebrow">PERLINDUNGAN DATA</p>
        <h2>Backup dan pemulihan database</h2>
        <p>Backup membuat salinan konsisten database aktif. Pemulihan hanya dapat dilakukan dari file SQLite yang lolos pemeriksaan integritas dan struktur dasar aplikasi.</p>
    </div>
</section>

<?php if ($message !== ''): ?><p class="action-feedback success" role="status"><?= h($message) ?></p><?php endif; ?>
<?php if ($error !== ''): ?><p class="action-feedback error" role="alert"><?= h($error) ?></p><?php endif; ?>

<section class="panel form-panel backup-panel">
    <div class="section-heading">
        <div><p class="eyebrow">DATABASE AKTIF</p><h2>Cadangkan data sekarang</h2></div>
    </div>
    <div class="storage-path"><span>Database aktif</span><code><?= h($databasePath !== '' ? $databasePath : 'Belum tersedia') ?></code></div>
    <div class="storage-path"><span>Folder backup</span><code><?= h($backupDirectory !== '' ? $backupDirectory : 'Belum tersedia') ?></code></div>
    <p>Backup tidak menghapus atau memindahkan database aktif. Untuk perlindungan tambahan, salin file backup ke USB atau penyimpanan lain.</p>
    <form method="post" action="backup.php">
        <input type="hidden" name="backup_action" value="create_backup">
        <button type="submit" <?= $db instanceof Database ? '' : 'disabled' ?>>Buat backup sekarang</button>
    </form>
    <?php if ($backupPath !== ''): ?><div class="storage-path backup-result"><span>File backup dibuat</span><code><?= h($backupPath) ?></code></div><?php endif; ?>
</section>

<section class="panel backup-list-panel">
    <div class="section-heading">
        <div><p class="eyebrow">FILE TERSIMPAN</p><h2>Daftar backup lokal</h2></div>
        <span class="section-note"><?= count($backups) ?> file</span>
    </div>
    <?php if ($backups === []): ?>
        <p class="empty-record">Belum ada file backup. Buat backup terlebih dahulu.</p>
    <?php else: ?>
        <div class="copy-preview-table-wrap">
            <table class="copy-preview-table">
                <thead><tr><th>Nama file</th><th>Waktu</th><th>Ukuran</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php foreach ($backups as $backup): ?>
                        <tr>
                            <td><strong><?= h((string) $backup['name']) ?></strong></td>
                            <td><?= date('d/m/Y H:i:s', (int) $backup['modified']) ?></td>
                            <td><?= number_format(((int) $backup['size']) / 1024, 1, ',', '.') ?> KB</td>
                            <td>
                                <form method="post" action="backup.php">
                                    <input type="hidden" name="backup_action" value="preview_restore">
                                    <input type="hidden" name="backup_name" value="<?= h((string) $backup['name']) ?>">
                                    <button type="submit" class="button-compact">Periksa &amp; pratinjau</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php if ($restorePreview !== null): ?>
<section class="panel restore-preview-panel">
    <div class="section-heading">
        <div><p class="eyebrow">PRATINJAU PEMULIHAN</p><h2><?= h((string) $restorePreview['name']) ?></h2></div>
        <span class="package-badge active">INTEGRITAS OK</span>
    </div>
    <div class="dashboard-meta-grid">
        <div><span>Ukuran file</span><strong><?= number_format(((int) $restorePreview['size']) / 1024, 1, ',', '.') ?> KB</strong></div>
        <div><span>Waktu file</span><strong><?= date('d/m/Y H:i:s', (int) $restorePreview['modified']) ?></strong></div>
        <div><span>Versi skema</span><strong><?= (int) $restorePreview['schema_version'] ?></strong></div>
        <div><span>Tabel database</span><strong><?= (int) $restorePreview['table_count'] ?></strong></div>
    </div>
    <div class="copy-preview-warning">
        <strong>Perhatian:</strong> pemulihan akan mengganti data database aktif dengan isi backup ini. Sebelum mengganti, aplikasi membuat backup pra-pemulihan dari database aktif saat ini. Jangan menutup aplikasi selama proses berlangsung.
    </div>
    <form id="restore-confirm-form" method="post" action="backup.php" class="restore-confirm-form">
        <input type="hidden" name="backup_action" value="confirm_restore">
        <input type="hidden" name="backup_name" value="<?= h((string) $restorePreview['name']) ?>">
        <label class="checkbox-line"><input type="checkbox" name="restore_confirm" value="1" required> Saya memahami bahwa data aktif akan diganti dengan data backup yang dipilih.</label>
        <div class="form-actions">
            <button type="button" class="copy-schedule-button is-confirm" onclick="document.getElementById('restore-confirm-form').requestSubmit();">Pulihkan database dari backup</button>
            <a class="button-link-secondary" href="backup.php">Batalkan</a>
        </div>
    </form>
</section>
<?php endif; ?>
