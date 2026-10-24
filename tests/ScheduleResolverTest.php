<?php
declare(strict_types=1);

require_once __DIR__ . '/../prototype/phpdesktop/www/lib/Database.php';
require_once __DIR__ . '/../prototype/phpdesktop/www/lib/ScheduleResolver.php';

function expectResolver(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
}

$temporaryRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'BelMadrasahResolverTest-' . bin2hex(random_bytes(4));
if (!mkdir($temporaryRoot, 0770, true) && !is_dir($temporaryRoot)) {
    throw new RuntimeException('Folder test sementara tidak dapat dibuat.');
}
putenv('APPDATA=' . $temporaryRoot);

try {
    $db = new Database();
    $db->ensureDefaultSchedulePackage();

    $anchor = new DateTimeImmutable('1945-08-17');
    $nextDay = new DateTimeImmutable('1945-08-18');
    $modernAnchor = new DateTimeImmutable('2000-01-01');
    expectResolver(ScheduleResolver::getPasaran($anchor) === 'Legi', '17 Agustus 1945 harus Legi.');
    expectResolver(ScheduleResolver::getPasaran($nextDay) === 'Pahing', 'Pasaran harus maju satu langkah setiap hari.');
    expectResolver(ScheduleResolver::getPasaran($modernAnchor) === 'Legi', '1 Januari 2000 harus Legi.');

    $conflictPackageId = $db->saveSchedulePackage(null, 'Resolver Conflict Test', 'Fixture test', '6,7');
    $db->saveDayRule(null, $conflictPackageId, 1, 'normal', null, null, true);
    $db->saveDayRule(null, $conflictPackageId, 1, 'special', 'pasaran', 'Legi', true);
    $db->saveDayRule(null, $conflictPackageId, 1, 'special', 'month_week', 'last', true);

    $conflictDate = new DateTimeImmutable('2026-10-26');
    $conflict = ScheduleResolver::resolve($db, $conflictPackageId, $conflictDate);
    expectResolver($conflict['pasaran'] === 'Legi', '26 Oktober 2026 harus Legi untuk fixture konflik.');
    expectResolver($conflict['conflict'] === true, 'Dua aturan Khusus yang cocok harus ditandai konflik.');
    expectResolver($conflict['mode'] === 'normal_fallback', 'Konflik harus kembali ke Normal.');
    expectResolver(count($conflict['rules']) === 1 && $conflict['rules'][0]['rule_mode'] === 'normal', 'Fallback hanya memilih aturan Normal.');

    $singlePackageId = $db->saveSchedulePackage(null, 'Resolver Single Test', 'Fixture test', '6,7');
    $db->saveDayRule(null, $singlePackageId, 1, 'normal', null, null, true);
    $db->saveDayRule(null, $singlePackageId, 1, 'special', 'pasaran', 'Legi', true);
    $single = ScheduleResolver::resolve($db, $singlePackageId, $conflictDate);
    expectResolver($single['mode'] === 'special' && !$single['conflict'], 'Satu aturan Khusus yang cocok harus menggantikan Normal.');

    $fifthPackageId = $db->saveSchedulePackage(null, 'Resolver Fifth Week Test', 'Fixture test', '6,7');
    $db->saveDayRule(null, $fifthPackageId, 1, 'normal', null, null, true);
    $db->saveDayRule(null, $fifthPackageId, 1, 'special', 'month_week', '5', true);
    $fifthDate = new DateTimeImmutable('2026-06-29');
    $fifth = ScheduleResolver::resolve($db, $fifthPackageId, $fifthDate);
    expectResolver($fifth['mode'] === 'special', 'Minggu ke-5 harus cocok meskipun juga merupakan minggu terakhir.');

    $orderPackageId = $db->saveSchedulePackage(null, 'Resolver Event Order Test', 'Fixture test', '6,7');
    $orderRuleId = $db->saveDayRule(null, $orderPackageId, 1, 'normal', null, null, true);
    // Insert out of chronological order: the resolver must display unique times chronologically.
    $db->saveBellEvent(null, $orderRuleId, 'Bel jam 07:05', '07:05', '', true, 1);
    $db->saveBellEvent(null, $orderRuleId, 'Bel jam 07:00', '07:00', '', true, 2);
    $orderResolution = ScheduleResolver::resolve($db, $orderPackageId, new DateTimeImmutable('2026-10-19'));
    $orderedEvents = ScheduleResolver::getEnabledEvents($db, $orderResolution);
    expectResolver(
        count($orderedEvents) === 2
            && substr((string) $orderedEvents[0]['event_time'], 0, 5) === '07:00'
            && substr((string) $orderedEvents[1]['event_time'], 0, 5) === '07:05',
        'Kegiatan dengan waktu unik harus disusun menurut jam secara kronologis.'
    );

    unset($db);
    echo "PASS: ScheduleResolver calendar fixtures\n";
} finally {
    if (isset($db)) {
        unset($db);
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($temporaryRoot, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $entry) {
        if ($entry->isDir()) {
            @rmdir($entry->getPathname());
        } else {
            @unlink($entry->getPathname());
        }
    }
    @rmdir($temporaryRoot);
}
