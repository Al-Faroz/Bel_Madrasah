<?php
/*
 * Konfigurasi view.
 * Controller menangani penyimpanan SQLite dan layout bersama menangani shell Admin.
 */
?>
<section class="intro panel page-intro">
    <div>
        <p class="eyebrow">PENGATURAN LOKAL</p>
        <h2>Konfigurasi aplikasi</h2>
        <p>Nilai disimpan di SQLite lokal dan tetap ada setelah aplikasi ditutup. Volume di sini adalah preferensi untuk integrasi pemutar pada tahap berikutnya, belum mengubah volume audio otomatis.</p>
    </div>
</section>

<?php if ($message !== ''): ?><p class="action-feedback success" role="status"><?= h($message) ?></p><?php endif; ?>
<?php if ($error !== ''): ?><p class="action-feedback error" role="alert"><?= h($error) ?></p><?php endif; ?>

<section class="panel form-panel">
    <div class="section-heading">
        <div><p class="eyebrow">PREFERENSI APLIKASI</p><h2>Identitas dan audio</h2></div>
    </div>
    <form method="post" action="settings.php">
        <label for="school-name">Nama madrasah/sekolah
            <input id="school-name" type="text" name="school_name" maxlength="120" value="<?= h($schoolName) ?>" placeholder="Contoh: MTs Negeri ...">
        </label>
        <label for="operator-name">Nama operator (opsional)
            <input id="operator-name" type="text" name="operator_name" maxlength="80" value="<?= h($operatorName) ?>" placeholder="Nama petugas">
        </label>
        <label for="default-volume">Volume bawaan yang diinginkan (%)
            <input id="default-volume" type="number" name="default_volume" min="0" max="100" step="1" required value="<?= h($volume) ?>">
            <small>Preferensi ini disimpan sekarang; penerapan ke audio otomatis dilakukan pada tahap berikutnya.</small>
        </label>
        <div class="form-actions"><button type="submit">Simpan konfigurasi</button></div>
    </form>
</section>

<section class="panel asset-path-panel">
    <div class="section-heading">
        <div><p class="eyebrow">ASET PERSISTEN</p><h2>Folder audio dan logo</h2></div>
        <span class="section-note">Penyimpanan lokal</span>
    </div>
    <p>Salin lokasi folder di bawah, lalu buka File Explorer dan tempel lokasi itu ke bilah alamat. Operator dapat menyalin-tempel file ke folder yang sesuai. Nama file bebas; aplikasi tidak mewajibkan pola penamaan.</p>

    <?php if ($assetError !== ''): ?><p class="action-feedback error" role="alert"><?= h($assetError) ?></p><?php endif; ?>

    <div class="asset-path-list">
        <div class="asset-path-item">
            <label for="audio-assets-path">Folder audio</label>
            <code id="audio-assets-path"><?= h($audioAssetsPath !== '' ? $audioAssetsPath : 'Folder belum tersedia') ?></code>
            <div class="form-actions"><button type="button" class="button-compact" data-copy-target="audio-assets-path">Salin lokasi audio</button></div>
            <small><?= count($audioAssets) ?> file audio ditemukan (MP3, WAV, OGG, M4A, AAC).</small>
        </div>
        <div class="asset-path-item">
            <label for="logo-assets-path">Folder logo</label>
            <code id="logo-assets-path"><?= h($logoAssetsPath !== '' ? $logoAssetsPath : 'Folder belum tersedia') ?></code>
            <div class="form-actions"><button type="button" class="button-compact" data-copy-target="logo-assets-path">Salin lokasi logo</button></div>
            <small><?= count($logoAssets) ?> file gambar ditemukan (PNG, JPG, WEBP, GIF, SVG).</small>
        </div>
    </div>

    <div class="package-rules">
        <h3>Petunjuk singkat</h3>
        <ol>
            <li>Tekan tombol salin lokasi folder.</li>
            <li>Buka File Explorer, tekan <strong>Ctrl+L</strong>, tempel lokasi folder, lalu tekan Enter.</li>
            <li>Copy-paste file audio atau logo ke folder yang sesuai. Tidak perlu mengganti nama file.</li>
            <li>Jangan memindahkan atau menghapus file yang sedang dipakai jadwal. Referensi audio lama tetap dipertahankan.</li>
        </ol>
    </div>
</section>

<script>
(function () {
    document.querySelectorAll('[data-copy-target]').forEach(function (button) {
        button.addEventListener('click', async function () {
            const target = document.getElementById(button.dataset.copyTarget);
            const value = target ? target.textContent.trim() : '';
            if (!value || value === 'Folder belum tersedia') {
                window.alert('Lokasi folder belum tersedia.');
                return;
            }
            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(value);
                } else {
                    const temporary = document.createElement('textarea');
                    temporary.value = value;
                    temporary.setAttribute('readonly', '');
                    temporary.style.position = 'fixed';
                    temporary.style.opacity = '0';
                    document.body.appendChild(temporary);
                    temporary.select();
                    const copied = document.execCommand('copy');
                    temporary.remove();
                    if (!copied) throw new Error('Clipboard tidak tersedia');
                }
                button.textContent = 'Lokasi tersalin';
            } catch (error) {
                window.prompt('Salin lokasi folder ini:', value);
            }
        });
    });
})();
</script>
