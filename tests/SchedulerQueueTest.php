<?php
declare(strict_types=1);

require_once __DIR__ . '/../prototype/phpdesktop/www/lib/Database.php';
require_once __DIR__ . '/../prototype/phpdesktop/www/lib/ScheduleResolver.php';

function expectSchedulerQueue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
}

$temporaryRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'BelMadrasahSchedulerQueueTest-' . bin2hex(random_bytes(4));
if (!mkdir($temporaryRoot, 0770, true) && !is_dir($temporaryRoot)) {
    throw new RuntimeException('Folder test sementara tidak dapat dibuat.');
}
putenv('APPDATA=' . $temporaryRoot);

try {
    $db = new Database();
    $packageId = $db->saveSchedulePackage(null, 'Scheduler Queue Test', 'Fixture test antrean', '6,7');
    $ruleId = $db->saveDayRule(null, $packageId, 1, 'normal', null, null, true);

    // Insert in reverse order intentionally: the resolver must sort by sort_order,
    // not by insertion ID.
    $secondEventId = $db->saveBellEvent(null, $ruleId, 'Audio kedua', '07:00', '', true, 2);
    $firstEventId = $db->saveBellEvent(null, $ruleId, 'Audio pertama', '07:00', '', true, 1);

    $scheduledFor = '2026-10-19 07:00';
    $resolution = ScheduleResolver::resolve($db, $packageId, new DateTimeImmutable('2026-10-19 07:00:45'));
    $events = ScheduleResolver::getEnabledEvents($db, $resolution);

    expectSchedulerQueue(
        count($events) === 2
            && (int) $events[0]['id'] === $firstEventId
            && (int) $events[1]['id'] === $secondEventId,
        'Dua kegiatan pada waktu yang sama harus mengikuti sort_order.'
    );

    $firstRunId = $db->recordPlaybackLogIfMissing(
        $packageId,
        $firstEventId,
        $scheduledFor,
        'started',
        'Audio pertama mulai.'
    );
    expectSchedulerQueue(is_int($firstRunId), 'Kegiatan pertama harus berhasil diklaim.');

    expectSchedulerQueue(
        $db->updatePlaybackLogStatus($firstRunId, 'completed', 'Audio pertama selesai.'),
        'Kegiatan pertama harus dapat ditutup sebagai completed.'
    );

    $delaySeconds = 45;
    $graceSeconds = 30;
    $batchAlreadyStarted = $db->hasPlaybackStartedForScheduledTime($packageId, $scheduledFor);
    expectSchedulerQueue(
        $delaySeconds > $graceSeconds && $batchAlreadyStarted,
        'Batch yang sudah dimulai harus tetap terbuka untuk kegiatan berikutnya walau melewati toleransi awal.'
    );

    $secondRunId = $db->recordPlaybackLogIfMissing(
        $packageId,
        $secondEventId,
        $scheduledFor,
        'started',
        'Audio kedua mulai setelah audio pertama selesai.'
    );
    expectSchedulerQueue(is_int($secondRunId), 'Kegiatan kedua pada menit yang sama harus dapat diklaim setelah kegiatan pertama selesai.');

    $duplicateSecondRunId = $db->recordPlaybackLogIfMissing(
        $packageId,
        $secondEventId,
        $scheduledFor,
        'started',
        'Percobaan duplikat tidak boleh membuat log baru.'
    );
    expectSchedulerQueue($duplicateSecondRunId === null, 'Kegiatan kedua tidak boleh diklaim dua kali.');

    expectSchedulerQueue(
        $db->updatePlaybackLogStatus($secondRunId, 'completed', 'Audio kedua selesai.'),
        'Kegiatan kedua harus dapat ditutup sebagai completed.'
    );

    // A failed first item must not block later items in the same scheduled batch.
    $failedPackageId = $db->saveSchedulePackage(null, 'Scheduler Queue Failure Test', 'Fixture test audio gagal', '6,7');
    $failedRuleId = $db->saveDayRule(null, $failedPackageId, 1, 'normal', null, null, true);
    $failedEventId = $db->saveBellEvent(null, $failedRuleId, 'Audio gagal', '08:00', '', true, 1);
    $afterFailureEventId = $db->saveBellEvent(null, $failedRuleId, 'Audio setelah gagal', '08:00', '', true, 2);
    $failedScheduledFor = '2026-10-19 08:00';

    $db->recordPlaybackLogIfMissing(
        $failedPackageId,
        $failedEventId,
        $failedScheduledFor,
        'failed',
        'Fixture audio pertama gagal.'
    );
    expectSchedulerQueue(
        $db->hasPlaybackStartedForScheduledTime($failedPackageId, $failedScheduledFor),
        'Kegagalan satu item tidak boleh menutup antrean item lain pada menit yang sama.'
    );
    $afterFailureRunId = $db->recordPlaybackLogIfMissing(
        $failedPackageId,
        $afterFailureEventId,
        $failedScheduledFor,
        'started',
        'Item berikutnya tetap diproses.'
    );
    expectSchedulerQueue(
        is_int($afterFailureRunId),
        'Kegiatan berikutnya harus dapat diklaim setelah item sebelumnya gagal.'
    );

    unset($db);
    echo "PASS: scheduler same-time queue ordering, continuation, and deduplication\n";
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
