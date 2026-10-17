<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/AssetHelpers.php';
require_once __DIR__ . '/lib/ScheduleResolver.php';

function h(mixed $value): string
{
    if ($value === null || (!is_string($value) && !is_numeric($value) && !is_bool($value))) {
        $value = '';
    }
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$days = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
$months = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
$now = new DateTimeImmutable('now');
$weekday = (int) $now->format('N');
$dateLabel = $days[$weekday] . ', ' . $now->format('j') . ' ' . $months[(int) $now->format('n')] . ' ' . $now->format('Y');
$schoolName = 'Bel Madrasah';
$schoolLogoPath = '';
$activePackage = null;
$events = [];
$nextEvent = null;
$resolvedMode = 'none';
$resolverConflict = false;
$pasaranLabel = ScheduleResolver::getPasaran($now);
$monthWeekLabel = '';
$error = '';

try {
    $db = new Database();
    $schoolName = trim((string) $db->getSetting('school_name', '')) ?: 'Bel Madrasah';
    $schoolLogoPath = trim((string) $db->getSetting('school_logo_path', ''));
    foreach ($db->getSchedulePackages() as $package) {
        if ((int) $package['is_active'] === 1) {
            $activePackage = $package;
            break;
        }
    }
    if ($activePackage !== null) {
        $resolution = ScheduleResolver::resolve($db, (int) $activePackage['id'], $now);
        $resolvedMode = (string) $resolution['mode'];
        $resolverConflict = (bool) $resolution['conflict'];
        $pasaranLabel = (string) $resolution['pasaran'];
        $monthWeekLabel = (string) $resolution['month_week_label'];
        $events = ScheduleResolver::getEnabledEvents($db, $resolution);
    }
    foreach ($events as $event) {
        if ((string) $event['event_time'] >= $now->format('H:i')) {
            $nextEvent = $event;
            break;
        }
    }
} catch (Throwable $exception) {
    $error = 'Data jadwal belum dapat dibaca. Hubungi operator untuk pemeriksaan.';
}
$schoolLogoDataUri = getLocalImageDataUri($schoolLogoPath);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#17633e">
    <title>Status Bel Madrasah — <?= h($schoolName) ?></title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body class="public-dashboard-body">
<div class="public-shell">
    <header class="public-header">
        <div class="public-brand-mark<?= $schoolLogoDataUri !== '' ? ' has-logo' : '' ?>" aria-hidden="true"><?php if ($schoolLogoDataUri !== ''): ?><img src="<?= h($schoolLogoDataUri) ?>" alt=""><?php else: ?>BM<?php endif; ?></div>
        <div class="public-brand-copy">
            <p class="public-kicker">INFORMASI OPERASIONAL</p>
            <h1><?= h($schoolName) ?></h1>
            <p>Informasi jadwal bel madrasah</p>
        </div>
        <div class="public-live-indicator" data-scheduler-status data-scheduler-state="waiting" role="status" aria-live="polite"><span aria-hidden="true"></span><span data-scheduler-status-text>Menyiapkan scheduler…</span></div>
        <a class="md-button md-button-tonal" href="dashboard.php" target="_self">Dashboard Admin</a>
    </header>

    <main>
        <section class="public-hero">
            <div class="public-date-block">
                <p class="public-kicker">HARI INI</p>
                <h2><?= h($dateLabel) ?></h2>
                <p>Pasaran Jawa: <?= h($pasaranLabel) ?><?= $monthWeekLabel !== '' ? ' · ' . h($monthWeekLabel) : '' ?></p>
            </div>
            <div class="public-clock-block">
                <span>Waktu komputer</span>
                <strong id="public-clock"><?= h($now->format('H:i:s')) ?></strong>
                <small>Waktu lokal perangkat</small>
            </div>
        </section>

        <?php if ($error !== ''): ?>
            <section class="public-alert is-error" role="alert"><strong>Data belum tersedia</strong><p><?= h($error) ?></p></section>
        <?php else: ?>
            <section class="public-overview-grid" aria-label="Ringkasan jadwal">
                <article class="public-overview-card">
                    <span class="public-card-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></span>
                    <span class="public-card-label">Paket aktif</span>
                    <strong><?= $activePackage !== null ? h((string) $activePackage['name']) : 'Belum ditentukan' ?></strong>
                    <small><?= $activePackage !== null ? 'Sumber konfigurasi jadwal' : 'Operator perlu mengaktifkan paket' ?></small>
                </article>
                <article class="public-overview-card">
                    <span class="public-card-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                    <span class="public-card-label">Kegiatan berikutnya</span>
                    <strong id="public-next-time"><?= $nextEvent !== null ? h(substr((string) $nextEvent['event_time'], 0, 5)) : '—' ?></strong>
                    <small id="public-next-name"><?= $nextEvent !== null ? h((string) $nextEvent['name']) : 'Tidak ada kegiatan aktif tersimpan berikutnya' ?></small>
                </article>
                <article class="public-overview-card">
                    <span class="public-card-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></span>
                    <span class="public-card-label">Kegiatan hari ini</span>
                    <strong><?= count($events) ?></strong>
                    <small>Kegiatan aktif sesuai aturan kalender yang berlaku</small>
                </article>
            </section>

            <?php if ($resolverConflict): ?>
                <section class="public-alert is-warning">
                    <strong>Konflik aturan khusus</strong>
                    <p>Lebih dari satu aturan Khusus cocok untuk tanggal ini. Untuk mencegah jadwal ambigu, aplikasi memakai pola Normal sebagai fallback. Periksa aturan hari ini pada Editor Jadwal.</p>
                </section>
            <?php elseif ($resolvedMode === 'special'): ?>
                <section class="public-alert">
                    <strong>Jadwal khusus berlaku</strong>
                    <p>Aturan Khusus yang cocok dengan kalender menggantikan pola Normal untuk hari ini.</p>
                </section>
            <?php endif; ?>

            <section class="public-schedule-panel">
                <div class="public-section-heading">
                    <div><p class="public-kicker">RINCIAN WAKTU</p><h2>Jadwal bel hari ini</h2></div>
                    <span class="public-count"><?= count($events) ?> kegiatan</span>
                </div>
                <?php if ($activePackage === null): ?>
                    <div class="public-empty-state"><strong>Belum ada paket aktif</strong><p>Silakan hubungi operator madrasah untuk mengaktifkan paket jadwal.</p></div>
                <?php elseif ($events === []): ?>
                    <div class="public-empty-state"><strong>Belum ada kegiatan yang ditampilkan</strong><p>Belum ada kegiatan aktif pada aturan yang berlaku untuk hari ini.</p></div>
                <?php else: ?>
                    <div class="public-timeline">
                        <?php foreach ($events as $event): $isNext = $nextEvent !== null && (int) $nextEvent['id'] === (int) $event['id']; ?>
                            <article class="public-event <?= $isNext ? 'is-next' : '' ?>" data-event-time="<?= h(substr((string) $event['event_time'], 0, 5)) ?>" data-event-name="<?= h((string) $event['name']) ?>">
                                <div class="public-event-time"><?= h(substr((string) $event['event_time'], 0, 5)) ?></div>
                                <div class="public-event-marker" aria-hidden="true"></div>
                                <div class="public-event-copy"><strong><?= h((string) $event['name']) ?></strong><span><?= $isNext ? 'Kegiatan berikutnya berdasarkan waktu jadwal' : 'Kegiatan terkonfigurasi' ?></span></div>
                                <span class="public-next-badge" data-next-badge <?= $isNext ? '' : 'hidden' ?>>BERIKUTNYA</span>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
    <footer class="public-footer"><span><?= h($schoolName) ?> · Panel Informasi</span><span>Informasi jadwal tersimpan · bukan indikator pemutaran audio real-time</span></footer>
</div>
<script>
(function () {
    const clock = document.getElementById('public-clock');
    if (!clock) return;
    function updateClock() {
        const now = new Date();
        clock.textContent = [now.getHours(), now.getMinutes(), now.getSeconds()].map(function (part) {
            return String(part).padStart(2, '0');
        }).join(':');
        const current = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
        const rows = Array.from(document.querySelectorAll('.public-event[data-event-time]'));
        let next = null;
        rows.forEach(function (row) {
            const isUpcoming = next === null && row.dataset.eventTime >= current;
            row.classList.toggle('is-next', isUpcoming);
            const badge = row.querySelector('[data-next-badge]');
            if (badge) badge.hidden = !isUpcoming;
            if (isUpcoming) next = row;
        });
        const nextTime = document.getElementById('public-next-time');
        const nextName = document.getElementById('public-next-name');
        if (nextTime && nextName) {
            nextTime.textContent = next ? next.dataset.eventTime : '—';
            nextName.textContent = next ? next.dataset.eventName : 'Tidak ada kegiatan Normal tersimpan berikutnya';
        }
    }
    updateClock();
    window.setInterval(updateClock, 1000);
})();
</script>
<script src="assets/scheduler.js" defer></script>
</body>
</html>
