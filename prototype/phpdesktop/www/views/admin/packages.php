<?php
/*
 * Paket Jadwal view.
 * Data, validasi, dan operasi database disiapkan oleh packages.php.
 * Layout bersama menangani dokumen HTML, navigasi, dan footer.
 */
?>
<section class="intro page-intro">
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
                                <form method="post" action="packages.php" class="package-activate-form" data-package-name="<?= h((string) $package['name']) ?>">
                                    <input type="hidden" name="package_action" value="activate">
                                    <input type="hidden" name="package_id" value="<?= (int) $package['id'] ?>">
                                    <button type="submit" class="button-compact">Aktifkan paket</button>
                                </form>
                                <?php if (count($packages) > 1): ?>
                                    <form method="post" action="packages.php" class="package-delete-form" data-package-name="<?= h((string) $package['name']) ?>">
                                        <input type="hidden" name="package_action" value="delete">
                                        <input type="hidden" name="package_id" value="<?= (int) $package['id'] ?>">
                                        <button type="submit" class="text-danger">Hapus permanen</button>
                                    </form>
                                <?php endif; ?>
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
            <label for="package-name">Nama paket <span class="required-mark">*</span>
                <input type="text" id="package-name" name="name" maxlength="80" required value="<?= h($formName) ?>" placeholder="Contoh: Normal, Ujian, Ramadan">
            </label>
            <fieldset class="package-off-days">
                <legend>Hari libur mingguan <span class="required-mark">*</span></legend>
                <label class="off-day-choice"><input type="radio" name="off_days" value="5" <?= $formOffDays === '5' ? 'checked' : '' ?> required><span><strong>Jumat</strong><small>Sekolah Senin–Kamis dan Sabtu–Minggu</small></span></label>
                <label class="off-day-choice"><input type="radio" name="off_days" value="7" <?= $formOffDays === '7' ? 'checked' : '' ?> required><span><strong>Minggu</strong><small>Sekolah Senin–Sabtu</small></span></label>
                <label class="off-day-choice"><input type="radio" name="off_days" value="6,7" <?= $formOffDays === '6,7' ? 'checked' : '' ?> required><span><strong>Sabtu–Minggu</strong><small>Sekolah Senin–Jumat</small></span></label>
                <?php if ($editId === 0): ?>
                    <p class="form-note">Paket baru otomatis dibuatkan aturan Normal pada setiap hari sekolah, lengkap dengan contoh kegiatan bel yang bisa diedit.</p>
                <?php else: ?>
                    <p class="form-note package-off-day-warning">Perubahan hari libur menyimpan konfigurasi mingguan paket. Aturan dan kegiatan yang sudah ada tidak dihapus atau dipindahkan otomatis; periksa Editor Jadwal setelah mengubah pilihan ini.</p>
                <?php endif; ?>
            </fieldset>
            <label for="package-description">Keterangan (opsional)
                <textarea id="package-description" name="description" maxlength="300" rows="4" placeholder="Jelaskan kapan paket ini digunakan"><?= h($formDescription) ?></textarea>
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
        <li>Hard delete hanya tersedia untuk paket nonaktif. Semua aturan dan kegiatan paket yang dipilih ikut dihapus permanen; riwayat pemutaran dipertahankan.</li>
    </ul>
</section>

<script>
(function () {
    document.querySelectorAll(".package-delete-form").forEach(function (form) {
        form.addEventListener("submit", function (event) {
            const name = form.dataset.packageName || "paket ini";
            const confirmed = window.confirm(
                "HARD DELETE PERMANEN\n\nPaket: " + name +
                "\n\nSemua aturan dan kegiatan bel pada paket ini akan dihapus permanen dan tidak dapat dibatalkan. Riwayat pemutaran tetap disimpan.\n\nLanjutkan?"
            );
            if (!confirmed) event.preventDefault();
        });
    });
})();
</script>
