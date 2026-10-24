<?php
declare(strict_types=1);

require_once __DIR__ . '/../prototype/phpdesktop/www/lib/Database.php';

function expectSchedulerLog(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
}

$temporaryRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'BelMadrasahSchedulerTest-' . bin2hex(random_bytes(4));
if (!mkdir($temporaryRoot, 0770, true) && !is_dir($temporaryRoot)) {
    throw new RuntimeException('Folder test sementara tidak dapat dibuat.');
}
putenv('APPDATA=' . $temporaryRoot);

try {
    $db = new Database();
    $packageId = $db->saveSchedulePackage(null, 'Scheduler Log Test', 'Fixture test', '6,7');
    $ruleId = $db->saveDayRule(null, $packageId, 1, 'normal', null, null, true);
    $eventId = $db->saveBellEvent(null, $ruleId, 'Tes log scheduler', '07:00', '', true, 1);
    $scheduledFor = '2026-10-19 07:00';

    $firstRunId = $db->recordPlaybackLogIfMissing(
        $packageId,
        $eventId,
        $scheduledFor,
        'started',
        'Fixture mulai.'
    );
    $duplicateRunId = $db->recordPlaybackLogIfMissing(
        $packageId,
        $eventId,
        $scheduledFor,
        'started',
        'Fixture duplikat.'
    );
    expectSchedulerLog(is_int($firstRunId) && $firstRunId > 0, 'Klaim pertama harus membuat log.');
    expectSchedulerLog($duplicateRunId === null, 'Klaim kedua untuk event/waktu yang sama tidak boleh membuat log baru.');

    $startedLog = $db->getPlaybackLog($firstRunId);
    expectSchedulerLog($startedLog !== null && $startedLog['status'] === 'started', 'Log baru harus berstatus started.');

    expectSchedulerLog(
        $db->updatePlaybackLogStatus($firstRunId, 'completed', 'Fixture selesai.'),
        'Status started harus dapat diubah menjadi completed.'
    );
    expectSchedulerLog(
        !$db->updatePlaybackLogStatus($firstRunId, 'failed', 'Tidak boleh menimpa status akhir.'),
        'Log yang sudah ditutup tidak boleh diubah lagi.'
    );
    $completedLog = $db->getPlaybackLog($firstRunId);
    expectSchedulerLog($completedLog !== null && $completedLog['status'] === 'completed', 'Status completed harus persisten.');
    expectSchedulerLog(
        $db->hasPlaybackStartedForScheduledTime($packageId, $scheduledFor),
        'Kejadian yang sudah diproses harus dikenali pada waktu jadwalnya agar tidak diklaim ulang.'
    );

    $missedEventId = $db->saveBellEvent(null, $ruleId, 'Tes terlewat', '07:10', '', true, 2);
    $db->recordPlaybackLogIfMissing($packageId, $missedEventId, '2026-10-19 07:10', 'missed', 'Fixture terlewat.');
    expectSchedulerLog(
        !$db->hasPlaybackStartedForScheduledTime($packageId, '2026-10-19 07:10'),
        'Log missed saja tidak boleh membuka kembali antrean terlambat.'
    );

    $secondEventId = $db->saveBellEvent(null, $ruleId, 'Tes interupsi', '07:05', '', true, 2);
    $secondRunId = $db->recordPlaybackLogIfMissing(
        $packageId,
        $secondEventId,
        '2026-10-19 07:05',
        'started',
        'Fixture mulai kedua.'
    );
    expectSchedulerLog(is_int($secondRunId) && $secondRunId > 0, 'Kejadian kedua harus dapat diklaim.');
    $reconciled = $db->markStartedPlaybackAsFailed('Fixture halaman berpindah.');
    expectSchedulerLog($reconciled === 1, 'Satu log started yang tersisa harus direkonsiliasi.');
    $failedLog = $db->getPlaybackLog($secondRunId);
    expectSchedulerLog($failedLog !== null && $failedLog['status'] === 'failed', 'Log yang terputus harus menjadi failed.');

    unset($db);
    echo "PASS: scheduler playback log idempotency and status transitions\n";
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
