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

/*
 * Tahap 1A: diagnostic persistence test.
 * Keep the test database in the current Windows user's writable AppData,
 * never beside the program/runtime files.
 */
$storagePath = '';
$storageReady = false;
$storageError = '';
$storageMessage = '';
$storageRecord = null;
$storageAction = (string) ($_POST['storage_action'] ?? '');

try {
    if (!$pdoSqlite) {
        throw new RuntimeException('Ekstensi PDO SQLite tidak tersedia.');
    }

    $userDataRoot = getenv('APPDATA');
    if ($userDataRoot === false || trim($userDataRoot) === '') {
        $userDataRoot = getenv('LOCALAPPDATA');
    }
    if ($userDataRoot === false || trim($userDataRoot) === '') {
        throw new RuntimeException('Folder AppData Windows tidak ditemukan. Database tidak dibuat di folder program.');
    }

    $dataDirectory = rtrim($userDataRoot, "\\/") . DIRECTORY_SEPARATOR . 'BelMadrasah' . DIRECTORY_SEPARATOR . 'diagnostics';
    if (!is_dir($dataDirectory) && !mkdir($dataDirectory, 0770, true) && !is_dir($dataDirectory)) {
        throw new RuntimeException('Folder data lokal tidak dapat dibuat. Periksa izin tulis Windows.');
    }

    $storagePath = $dataDirectory . DIRECTORY_SEPARATOR . 'storage-test.sqlite';
    $db = new PDO('sqlite:' . $storagePath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec(
        'CREATE TABLE IF NOT EXISTS persistence_probe (
            id INTEGER PRIMARY KEY CHECK (id = 1),
            marker TEXT NOT NULL,
            saved_at TEXT NOT NULL
        )'
    );
    $storageReady = true;

    if ($storageAction === 'save') {
        $marker = 'BM-' . strtoupper(bin2hex(random_bytes(4)));
        $savedAt = date('Y-m-d H:i:s');
        $statement = $db->prepare(
            'INSERT INTO persistence_probe (id, marker, saved_at)
             VALUES (1, :marker, :saved_at)
             ON CONFLICT(id) DO UPDATE SET marker = excluded.marker, saved_at = excluded.saved_at'
        );
        $statement->execute([':marker' => $marker, ':saved_at' => $savedAt]);
        $storageMessage = 'PASS: data uji berhasil ditulis dan dibaca kembali pada sesi ini. Tutup aplikasi sepenuhnya, buka lagi, lalu tekan “Baca data tersimpan” untuk menguji persistensi setelah restart.';
    } elseif ($storageAction === 'read') {
        $storageMessage = 'Pembacaan database selesai. Cocokkan marker dan waktu simpan dengan data sebelum aplikasi ditutup.';
    }

    $storageRecord = $db->query(
        'SELECT marker, saved_at FROM persistence_probe WHERE id = 1'
    )->fetch() ?: null;

    if ($storageAction === 'read' && $storageRecord === null) {
        $storageMessage = 'BELUM ADA DATA: tekan “Simpan data uji” terlebih dahulu, lalu tutup dan buka kembali aplikasi.';
    }
} catch (Throwable $exception) {
    $storageReady = false;
    $storageError = $exception->getMessage();
    $storageMessage = 'FAIL: pengujian penyimpanan belum berhasil. Catat pesan di bawah untuk diagnosis.';
}

/*
 * Tahap 2: initialize the real application database separately from the old
 * diagnostic persistence database. The self-test uses a TEMP table only.
 */
$appDatabase = null;
$appDatabaseReady = false;
$appDatabasePath = '';
$appDatabaseError = '';
$appDatabaseSelfTest = false;
$appDatabaseTables = [];
$appDatabaseVersion = 0;

try {
    require_once __DIR__ . '/lib/Database.php';
    $appDatabase = new Database();
    $appDatabasePath = $appDatabase->getPath();
    $appDatabaseVersion = $appDatabase->getSchemaVersion();
    $appDatabaseTables = $appDatabase->getTables();
    $appDatabaseSelfTest = $appDatabase->runSelfTest();
    $appDatabaseReady = $appDatabaseSelfTest && count($appDatabaseTables) >= 6;
} catch (Throwable $exception) {
    $appDatabaseError = $exception->getMessage();
}

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
            <p class="eyebrow">PROTOTIPE TAHAP 2</p>
            <h1>Bel Madrasah</h1>
            <p class="subtitle">Kerangka aplikasi desktop Windows</p>
        </div>
        <span class="status-pill"><span class="status-dot"></span> MODE DIAGNOSTIK</span>
    </header>

    <nav class="main-nav" aria-label="Navigasi utama">
        <a class="active" href="index.php">Dashboard diagnostik</a>
        <a href="packages.php">Paket jadwal</a>
        <a href="settings.php">Konfigurasi</a>
        <a href="backup.php">Backup data</a>
    </nav>
    <main>
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
    </main>

    <footer>
        <span>Bel Madrasah · PoC Tahap 2</span>
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
</body>
</html>
