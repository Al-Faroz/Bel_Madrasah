<?php
declare(strict_types=1);

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$phpVersion = PHP_VERSION;
$sapi = PHP_SAPI;
$pdoSqlite = extension_loaded('pdo_sqlite');
$sqlite3 = extension_loaded('sqlite3');
$memoryLimit = (string) ini_get('memory_limit');
$serverSoftware = (string) ($_SERVER['SERVER_SOFTWARE'] ?? 'Tidak tersedia');
$documentRoot = (string) ($_SERVER['DOCUMENT_ROOT'] ?? 'Tidak tersedia');

$checks = [
    ['label' => 'PHP runtime', 'value' => $phpVersion, 'ok' => true],
    ['label' => 'PHP SAPI', 'value' => $sapi, 'ok' => true],
    ['label' => 'PDO SQLite', 'value' => $pdoSqlite ? 'Tersedia' : 'Belum tersedia', 'ok' => $pdoSqlite],
    ['label' => 'SQLite3 extension', 'value' => $sqlite3 ? 'Tersedia' : 'Belum tersedia', 'ok' => $sqlite3],
    ['label' => 'Memory limit PHP', 'value' => $memoryLimit, 'ok' => true],
    ['label' => 'Server internal', 'value' => $serverSoftware, 'ok' => true],
];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#123524">
    <title>Bel Madrasah — Pemeriksaan Fondasi</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body>
<div class="app-shell">
    <header class="topbar">
        <div class="brand-mark" aria-hidden="true">BM</div>
        <div>
            <p class="eyebrow">PROTOTIPE TAHAP 1</p>
            <h1>Bel Madrasah</h1>
            <p class="subtitle">Pemeriksaan fondasi aplikasi desktop Windows</p>
        </div>
        <span class="status-pill"><span class="status-dot"></span> MODE DIAGNOSTIK</span>
    </header>

    <main>
        <section class="intro panel">
            <div>
                <p class="eyebrow">WINDOWS DESKTOP · PHP</p>
                <h2>Memastikan fondasi berjalan sebelum membangun fitur.</h2>
                <p>Halaman ini memeriksa runtime PHP dan kesiapan SQLite, sekaligus menyediakan tes audio lokal di dalam jendela aplikasi. Ini bukan versi aplikasi bel produksi.</p>
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
                <p class="eyebrow">02 / LOCAL AUDIO</p>
                <h2>Uji pemutaran audio</h2>
            </div>
            <span class="section-note">Tidak membuka pemutar eksternal</span>
        </section>

        <section class="audio-panel panel">
            <div class="audio-copy">
                <div class="audio-icon" aria-hidden="true">♫</div>
                <div>
                    <h3>Pilih file audio dari komputer</h3>
                    <p>Gunakan file pendek untuk tes awal. Pemutaran dilakukan oleh elemen audio di dalam aplikasi desktop.</p>
                </div>
            </div>
            <label class="file-picker">
                <span>Pilih file MP3 atau WAV</span>
                <input id="audioFile" type="file" accept=".mp3,.wav,audio/mpeg,audio/wav,audio/x-wav">
            </label>
            <p class="file-name" id="fileName">Belum ada file yang dipilih.</p>
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
    </main>

    <footer>
        <span>Bel Madrasah · PoC Tahap 1</span>
        <span>Data diagnostik lokal · Tidak dikirim ke internet</span>
    </footer>
</div>
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
        message.textContent = 'File siap diuji. Tekan Play dan pastikan suara terdengar dari perangkat output Windows.';
        player.load();
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
</body>
</html>
