<?php
/*
 * Dashboard Admin view.
 * Data and calculations are prepared by dashboard.php; the shared layout owns
 * the document, navigation, and footer.
 */
?>
        <section class="dashboard-welcome panel">
            <div>
                <p class="eyebrow">RINGKASAN HARI INI</p>
                <h2><?= h($todayLabel) ?></h2>
                <p>Periksa paket yang dipakai dan kegiatan yang telah dikonfigurasi untuk hari ini.</p>
            </div>
            <div class="dashboard-clock">
                <span>Jam komputer</span>
                <strong id="dashboard-clock"><?= h($now->format('H:i:s')) ?></strong>
                <small>Waktu lokal Windows</small>
            </div>
        </section>

        <section class="dashboard-stats" aria-label="Ringkasan operasional">
            <article class="dashboard-stat panel">
                <span class="stat-icon" aria-hidden="true">▦</span>
                <span class="stat-label">Paket jadwal</span>
                <strong><?= count($packages) ?></strong>
                <small><?= $activePackage === null ? 'Belum ada paket aktif' : 'Satu paket digunakan secara global' ?></small>
            </article>
            <article class="dashboard-stat panel">
                <span class="stat-icon" aria-hidden="true">◷</span>
                <span class="stat-label">Kegiatan aktif hari ini</span>
                <strong><?= $enabledEventsCount ?></strong>
                <small><?= $todayRuleCount > 0 ? 'Berdasarkan aturan Normal yang aktif' : 'Belum ada aturan Normal aktif untuk hari ini' ?></small>
            </article>
            <article class="dashboard-stat panel">
                <span class="stat-icon" aria-hidden="true">♫</span>
                <span class="stat-label">Mesin bel otomatis</span>
                <strong class="stat-text">Tahap berikutnya</strong>
                <small>Scheduler otomatis belum diaktifkan pada build ini</small>
            </article>
        </section>

        <section class="dashboard-columns">
            <article class="panel dashboard-main-card">
                <div class="dashboard-section-heading">
                    <div>
                        <p class="eyebrow">PAKET YANG BERLAKU</p>
                        <h2><?= $activePackage !== null ? h((string) $activePackage['name']) : 'Belum ada paket aktif' ?></h2>
                    </div>
                    <a class="md-button md-button-tonal" href="packages.php">Kelola paket</a>
                </div>
                <?php if ($activePackage !== null): ?>
                    <p class="dashboard-description"><?= trim((string) $activePackage['description']) !== '' ? h((string) $activePackage['description']) : 'Tidak ada keterangan paket.' ?></p>
                    <div class="dashboard-meta-grid">
                        <div><span>Hari libur mingguan</span><strong><?= h($offDayLabel) ?></strong></div>
                        <div><span>Hari ini</span><strong><?= h($dayNames[$weekday]) ?></strong></div>
                    </div>
                <?php else: ?>
                    <p class="dashboard-description">Buat atau aktifkan satu paket jadwal terlebih dahulu. Paket yang aktif akan menjadi sumber konfigurasi jadwal.</p>
                    <a class="md-button md-button-filled" href="packages.php">Buat paket jadwal</a>
                <?php endif; ?>
            </article>

            <article class="panel dashboard-main-card">
                <div class="dashboard-section-heading">
                    <div>
                        <p class="eyebrow">KEGIATAN BERIKUTNYA</p>
                        <h2><?= $nextEvent !== null ? h((string) $nextEvent['event_time']) : 'Belum tersedia' ?></h2>
                    </div>
                    <span class="dashboard-status-chip <?= $nextEvent !== null ? 'is-ready' : 'is-muted' ?>"><?= $nextEvent !== null ? 'Terjadwal' : 'Tidak ada' ?></span>
                </div>
                <?php if ($nextEvent !== null): ?>
                    <p class="next-event-name"><?= h((string) $nextEvent['name']) ?></p>
                    <p class="dashboard-description">Waktu berikutnya dari jadwal Normal yang aktif. Ini adalah informasi konfigurasi, bukan bukti bahwa audio otomatis sedang berjalan.</p>
                <?php elseif ($specialRuleCount > 0): ?>
                    <p class="dashboard-description">Hari ini memiliki aturan khusus. Pemilihan aturan berdasarkan kalender akan ditangani oleh resolver pada tahap berikutnya, jadi dashboard belum menyimpulkan kegiatan berikutnya.</p>
                <?php else: ?>
                    <p class="dashboard-description">Tidak ada kegiatan Normal aktif setelah waktu sekarang. Periksa Editor Jadwal jika ini tidak sesuai dengan jadwal sekolah.</p>
                <?php endif; ?>
                <div class="dashboard-action-row"><a class="md-button md-button-outlined" href="schedule-editor.php">Buka editor jadwal</a><a class="md-button md-button-tonal" href="public.php" target="_blank" rel="noopener">Lihat tampilan publik</a></div>
            </article>
        </section>

        <section class="dashboard-note">
            <span class="dashboard-note-icon" aria-hidden="true">i</span>
            <div>
                <strong>Status pengembangan</strong>
                <p>Dashboard ini merangkum data yang tersimpan. Mesin penjadwalan otomatis, resolver kalender, dan status pemutaran real-time belum tersedia; jangan gunakan ringkasan ini sebagai bukti bel akan berbunyi otomatis.</p>
            </div>
        </section>

<script>
(function () {
    const clock = document.getElementById('dashboard-clock');
    if (!clock) return;
    const update = () => {
        const now = new Date();
        clock.textContent = [now.getHours(), now.getMinutes(), now.getSeconds()]
            .map(value => String(value).padStart(2, '0')).join(':');
    };
    update();
    window.setInterval(update, 1000);
})();
</script>
