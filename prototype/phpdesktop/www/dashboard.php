<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/Database.php';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$dayNames = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
$monthNames = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
$now = new DateTimeImmutable('now');
$weekday = (int) $now->format('N');
$todayLabel = $dayNames[$weekday] . ', ' . $now->format('j') . ' ' . $monthNames[(int) $now->format('n')] . ' ' . $now->format('Y');

$db = new Database();
$packages = $db->getSchedulePackages();
$activePackage = null;
foreach ($packages as $package) {
    if ((int) $package['is_active'] === 1) {
        $activePackage = $package;
        break;
    }
}

$todayEvents = [];
$nextEvent = null;
$todayRuleCount = 0;
$specialRuleCount = 0;
if ($activePackage !== null) {
    $rules = $db->getDayRules((int) $activePackage['id'], $weekday);
    foreach ($rules as $rule) {
        if ((int) $rule['is_enabled'] !== 1) {
            continue;
        }
        if ((string) $rule['rule_mode'] === 'special') {
            $specialRuleCount++;
            continue;
        }
        $todayRuleCount++;
        foreach ($db->getBellEvents((int) $rule['id']) as $event) {
            if ((int) $event['is_enabled'] !== 1) {
                continue;
            }
            $event['rule_mode'] = $rule['rule_mode'];
            $todayEvents[] = $event;
        }
    }
}
usort($todayEvents, static fn(array $a, array $b): int => strcmp((string) $a['event_time'], (string) $b['event_time']));
foreach ($todayEvents as $event) {
    if ((string) $event['event_time'] >= $now->format('H:i')) {
        $nextEvent = $event;
        break;
    }
}
$enabledEventsCount = count($todayEvents);
$offDayLabels = ['5' => 'Jumat', '7' => 'Minggu', '6,7' => 'Sabtu–Minggu'];
$offDayLabel = $activePackage === null ? '—' : ($offDayLabels[(string) ($activePackage['off_days'] ?? '6,7')] ?? 'Belum ditentukan');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#17633e">
    <title>Dashboard — Bel Madrasah</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body>
<div class="app-shell">
    <header class="topbar">
        <div class="brand-mark" aria-hidden="true">BM</div>
        <div>
            <p class="eyebrow">PUSAT KONTROL OPERASIONAL</p>
            <h1>Bel Madrasah</h1>
            <p class="subtitle">Ringkasan pengaturan jadwal dan kesiapan operasional</p>
        </div>
        <span class="status-pill"><span class="status-dot"></span> DASHBOARD ADMIN</span>
    </header>

    <nav class="main-nav" aria-label="Navigasi utama">
        <a class="active" href="dashboard.php" aria-current="page">Dashboard</a>
        <a href="packages.php">Paket jadwal</a>
        <a href="schedule-editor.php">Editor jadwal</a>
        <a href="settings.php">Konfigurasi</a>
        <a href="backup.php">Backup data</a>
        <a href="index.php">Pemeriksaan</a>
    </nav>

    <main>
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
                <a class="md-button md-button-outlined" href="schedule-editor.php">Buka editor jadwal</a>
            </article>
        </section>

        <section class="dashboard-note">
            <span class="dashboard-note-icon" aria-hidden="true">i</span>
            <div>
                <strong>Status pengembangan</strong>
                <p>Dashboard ini merangkum data yang tersimpan. Mesin penjadwalan otomatis, resolver kalender, dan status pemutaran real-time belum tersedia; jangan gunakan ringkasan ini sebagai bukti bel akan berbunyi otomatis.</p>
            </div>
        </section>
    </main>

    <footer><span>Bel Madrasah · Dashboard Admin</span><span>Data lokal · <?= h($now->format('d/m/Y H:i')) ?></span></footer>
</div>
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
</body>
</html>
