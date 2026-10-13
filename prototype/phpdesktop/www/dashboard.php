<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/ScheduleResolver.php';

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
$resolverConflict = false;
$resolvedMode = 'none';
$pasaranToday = ScheduleResolver::getPasaran($now);
$monthWeekLabel = '';
if ($activePackage !== null) {
    $resolution = ScheduleResolver::resolve($db, (int) $activePackage['id'], $now);
    $resolvedMode = (string) $resolution['mode'];
    $resolverConflict = (bool) $resolution['conflict'];
    $pasaranToday = (string) $resolution['pasaran'];
    $monthWeekLabel = (string) $resolution['month_week_label'];
    $todayRuleCount = count($resolution['rules']);
    $todayEvents = ScheduleResolver::getEnabledEvents($db, $resolution);
}
foreach ($todayEvents as $event) {
    if ((string) $event['event_time'] >= $now->format('H:i')) {
        $nextEvent = $event;
        break;
    }
}
$enabledEventsCount = count($todayEvents);
$offDayLabels = ['5' => 'Jumat', '7' => 'Minggu', '6,7' => 'Sabtu–Minggu'];
$offDayLabel = $activePackage === null ? '—' : ($offDayLabels[(string) ($activePackage['off_days'] ?? '6,7')] ?? 'Belum ditentukan');

$documentTitle = 'Dashboard — Bel Madrasah';
$pageEyebrow = 'PUSAT KONTROL OPERASIONAL';
$pageHeading = 'Bel Madrasah';
$pageSubtitle = 'Ringkasan pengaturan jadwal dan kesiapan operasional';
$pageStatusLabel = 'DASHBOARD ADMIN';
$activePage = 'dashboard';
$footerPageLabel = 'Dashboard Admin';
$footerMeta = 'Data lokal · ' . $now->format('d/m/Y H:i');
$viewPath = __DIR__ . '/views/admin/dashboard.php';

require __DIR__ . '/layouts/admin.php';
