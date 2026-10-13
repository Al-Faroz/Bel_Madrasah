<?php
/*
 * Diagnostic view.
 * Uji runtime, persistensi, dan audio tetap dipertahankan; shell disediakan layout Admin.
 */
?>
<section class="intro panel">
            <div>
                <p class="eyebrow">WINDOWS DESKTOP · PHP</p>
                <h2>Memastikan fondasi berjalan sebelum membangun fitur.</h2>
                <p>Halaman ini memeriksa runtime PHP, kesiapan SQLite, persistensi data lokal, dan pemutaran audio uji. Ini bukan versi aplikasi bel produksi.</p>
            </div>
            <div class="clock-card">
                <span>Jam komputer</span>
                <strong id="clock">--:--:--</strong>
                <small id="date">Memuat tanggal…</small>
            </div>
        </section>

        <section class="section-heading">
            <div>
                <p class="eyebrow">01 / ENVIRONMENT</p>
                <h2>Pemeriksaan runtime</h2>
            </div>
            <span class="section-note">Dihasilkan oleh PHP</span>
        </section>

        <section class="checks-grid">
            <?php foreach ($checks as $check): ?>
                <article class="check-card panel">
                    <div class="check-top">
                        <span class="check-label"><?= h($check['label']) ?></span>
                        <span class="check-icon <?= $check['ok'] ? 'is-ok' : 'is-warning' ?>"><?= $check['ok'] ? '✓' : '!' ?></span>
                    </div>
                    <strong class="check-value"><?= h($check['value']) ?></strong>
                    <small><?= $check['ok'] ? 'Terbaca oleh runtime' : 'Perlu diperiksa sebelum lanjut' ?></small>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="section-heading">
            <div>
                <p class="eyebrow">02 / SQLITE PERSISTENCE</p>
                <h2>Uji simpan dan baca database</h2>
            </div>
            <span class="section-note">Database lokal pengguna Windows</span>
        </section>

        <section class="storage-panel panel">
            <div class="storage-header">
                <div>
                    <h3>SQLite · Uji persistensi setelah restart</h3>
                    <p>Data uji disimpan di folder AppData pengguna, terpisah dari folder program agar tidak hilang saat aplikasi diperbarui.</p>
                </div>
                <span class="storage-badge <?= $storageReady ? 'is-ok' : 'is-warning' ?>"><?= $storageReady ? 'DATABASE SIAP' : 'DATABASE BERMASALAH' ?></span>
            </div>

            <div class="storage-path">
                <span>Lokasi database uji</span>
                <code><?= h($storagePath !== '' ? $storagePath : 'Belum tersedia') ?></code>
            </div>

            <?php if ($storageMessage !== ''): ?>
                <p class="storage-message <?= $storageReady && $storageAction !== '' ? 'is-result' : 'is-warning' ?>" role="status"><?= h($storageMessage) ?></p>
            <?php endif; ?>

            <?php if ($storageRecord !== null): ?>
                <div class="record-grid">
                    <div>
                        <span>Marker tersimpan</span>
                        <strong><?= h((string) $storageRecord['marker']) ?></strong>
                    </div>
                    <div>
                        <span>Waktu simpan</span>
                        <strong><?= h((string) $storageRecord['saved_at']) ?></strong>
                    </div>
                </div>
            <?php else: ?>
                <p class="empty-record">Belum ada marker uji yang disimpan.</p>
            <?php endif; ?>

            <?php if ($storageError !== ''): ?>
                <pre class="storage-error"><?= h($storageError) ?></pre>
            <?php endif; ?>

            <form class="storage-actions" method="post">
                <button type="submit" name="storage_action" value="save">Simpan data uji</button>
                <button type="submit" name="storage_action" value="read" class="button-secondary">Baca data tersimpan</button>
            </form>
            <p class="storage-help">Jangan hapus file database ini selama tes. Ini hanya database diagnostik; database aplikasi utama akan dibuat terpisah pada tahap berikutnya.</p>
        </section>

        <section class="section-heading">
            <div>
                <p class="eyebrow">03 / APPLICATION DATABASE</p>
                <h2>Database aplikasi utama</h2>
            </div>
            <span class="section-note">Terpisah dari database diagnostik Tahap 1A</span>
        </section>

        <section class="storage-panel panel">
            <div class="storage-header">
                <div>
                    <h3>SQLite · Skema aplikasi Tahap 2</h3>
                    <p>Database ini menjadi fondasi paket jadwal, aturan hari, kegiatan bel, pengaturan, dan log pemutaran. Belum ada data jadwal produksi yang dimasukkan pada tahap ini.</p>
                </div>
                <span class="storage-badge <?= $appDatabaseReady ? 'is-ok' : 'is-warning' ?>"><?= $appDatabaseReady ? 'SKEMA SIAP' : 'PERLU DIPERIKSA' ?></span>
            </div>
            <div class="storage-path">
                <span>Lokasi database aplikasi</span>
                <code><?= h($appDatabasePath !== '' ? $appDatabasePath : 'Belum tersedia') ?></code>
            </div>
            <?php if ($appDatabaseReady): ?>
                <p class="storage-message is-result">PASS: koneksi database berhasil; skema versi <?= h((string) $appDatabaseVersion) ?> tersedia; uji tulis/baca sementara berhasil. Data uji memakai TEMP table dan tidak disimpan sebagai data permanen.</p>
                <div class="record-grid">
                    <div><span>Versi skema</span><strong><?= h((string) $appDatabaseVersion) ?></strong></div>
                    <div><span>Jumlah tabel inti</span><strong><?= h((string) count($appDatabaseTables)) ?></strong></div>
                </div>
                <div class="schema-table-list">
                    <span>Tabel terdeteksi</span>
                    <p><?= h(implode(' · ', $appDatabaseTables)) ?></p>
                </div>
            <?php else: ?>
                <p class="storage-message is-warning">FAIL: database aplikasi belum siap. Catat pesan error untuk diagnosis.</p>
                <?php if ($appDatabaseError !== ''): ?><pre class="storage-error"><?= h($appDatabaseError) ?></pre><?php endif; ?>
            <?php endif; ?>
            <p class="storage-help">File ini berbeda dari <code>diagnostics/storage-test.sqlite</code>. Jangan menghapus folder data saat memperbarui program.</p>
        </section>

        <section class="section-heading">
            <div>
                <p class="eyebrow">04 / LOCAL AUDIO</p>
                <h2>Uji pemutaran audio</h2>
            </div>
            <span class="section-note">Tidak membuka pemutar eksternal</span>
        </section>

        <section class="audio-panel panel">
            <div class="audio-copy">
                <div class="audio-icon" aria-hidden="true">♫</div>
                <div>
                    <h3>Pilih file audio dari komputer</h3>
                    <p>Uji MP3/WAV dari komputer. Pemutaran memakai komponen audio internal; uji file panjang sampai benar-benar selesai.</p>
                </div>
            </div>
            <label class="file-picker">
                <span>Pilih file MP3 atau WAV</span>
                <input id="audioFile" type="file" accept=".mp3,.wav,audio/mpeg,audio/wav,audio/x-wav">
            </label>
            <p class="file-name" id="fileName">Belum ada file yang dipilih.</p>
            <p class="file-name" id="audioDuration">Durasi: belum diketahui.</p>
            <audio id="audioPlayer" controls preload="metadata"></audio>
            <p class="test-message" id="audioMessage" role="status">Pilih file lalu tekan Play. Pastikan speaker komputer aktif.</p>
        </section>

        <section class="notice panel">
            <span class="notice-symbol" aria-hidden="true">i</span>
            <div>
                <strong>Yang belum diuji pada tahap ini</strong>
                <p>Penjadwalan otomatis, pemutaran tanpa duplikasi, audio panjang, system tray, pemulihan setelah sleep, dan konsumsi RAM/CPU selama aplikasi menunggu. Hasil prototipe harus diuji di komputer Windows Bro.</p>
            </div>
        </section>

<script>
(() => {
    const clock = document.getElementById('clock');
    const date = document.getElementById('date');
    const refreshClock = () => {
        const now = new Date();
        clock.textContent = now.toLocaleTimeString('id-ID', { hour12: false });
        date.textContent = now.toLocaleDateString('id-ID', {
            weekday: 'long', day: '2-digit', month: 'long', year: 'numeric'
        });
    };
    refreshClock();
    window.setInterval(refreshClock, 1000);

    const picker = document.getElementById('audioFile');
    const player = document.getElementById('audioPlayer');
    const fileName = document.getElementById('fileName');
    const message = document.getElementById('audioMessage');
    let currentObjectUrl = null;

    picker.addEventListener('change', () => {
        const file = picker.files && picker.files[0];
        if (!file) return;

        if (currentObjectUrl) URL.revokeObjectURL(currentObjectUrl);
        currentObjectUrl = URL.createObjectURL(file);
        player.src = currentObjectUrl;
        fileName.textContent = file.name + ' · ' + (file.size / 1024 / 1024).toFixed(2) + ' MB';
        message.textContent = 'File siap diuji. Tekan Play dan pastikan suara benar-benar terdengar dari perangkat output Windows.';
        player.load();
    });

    player.addEventListener('loadedmetadata', () => {
        if (Number.isFinite(player.duration)) {
            const total = Math.floor(player.duration);
            const minutes = Math.floor(total / 60);
            const seconds = String(total % 60).padStart(2, '0');
            document.getElementById('audioDuration').textContent = 'Durasi audio: ' + minutes + ':' + seconds;
        } else {
            document.getElementById('audioDuration').textContent = 'Durasi audio tidak dapat dibaca oleh runtime.';
        }
    });
    player.addEventListener('playing', () => {
        message.textContent = 'Audio sedang diputar. Pastikan suara benar-benar terdengar.';
    });
    player.addEventListener('ended', () => {
        message.textContent = 'Audio selesai diputar.';
    });
    player.addEventListener('error', () => {
        message.textContent = 'Audio gagal dimuat atau format/codec tidak didukung oleh runtime ini. Catat format file dan hasilnya.';
    });
    window.addEventListener('beforeunload', () => {
        if (currentObjectUrl) URL.revokeObjectURL(currentObjectUrl);
    });
})();
</script>
