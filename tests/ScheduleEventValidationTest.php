<?php
declare(strict_types=1);

require_once __DIR__ . '/../prototype/phpdesktop/www/lib/Database.php';
require_once __DIR__ . '/../prototype/phpdesktop/www/lib/ScheduleResolver.php';

function expectScheduleEventValidation(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
}

$temporaryRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'BelMadrasahScheduleEventValidation-' . bin2hex(random_bytes(4));
if (!mkdir($temporaryRoot, 0770, true) && !is_dir($temporaryRoot)) {
    throw new RuntimeException('Folder test sementara tidak dapat dibuat.');
}
putenv('APPDATA=' . $temporaryRoot);

try {
    $db = new Database();

    // Managed assets are selected by filename in the editor/settings UI.
    $audioAssetPath = $db->getAudioAssetsDirectory() . DIRECTORY_SEPARATOR . 'fixture-audio.mp3';
    $logoAssetPath = $db->getLogoAssetsDirectory() . DIRECTORY_SEPARATOR . 'fixture-logo.png';
    file_put_contents($audioAssetPath, 'audio fixture');
    file_put_contents($logoAssetPath, 'logo fixture');
    expectScheduleEventValidation(
        realpath($db->resolveAudioAsset('fixture-audio.mp3')) === realpath($audioAssetPath),
        'Resolver audio harus mengembalikan path penuh dari nama file aset yang dipilih.'
    );
    expectScheduleEventValidation(
        realpath($db->resolveLogoAsset('fixture-logo.png')) === realpath($logoAssetPath),
        'Resolver logo harus mengembalikan path penuh dari nama file aset yang dipilih.'
    );
    $invalidAssetRejected = false;
    try {
        $db->resolveAudioAsset('../fixture-audio.mp3');
    } catch (InvalidArgumentException $exception) {
        $invalidAssetRejected = true;
    }
    expectScheduleEventValidation(
        $invalidAssetRejected,
        'Resolver aset harus menolak input nama file yang berisi path traversal.'
    );

    $packageId = $db->saveSchedulePackage(null, 'Schedule Time Validation Test', 'Fixture test waktu unik', '6,7');
    $ruleId = $db->saveDayRule(null, $packageId, 1, 'normal', null, null, true);

    $firstEventId = $db->saveBellEvent(null, $ruleId, 'Bel masuk', '07:00', '', true, 1);
    expectScheduleEventValidation(
        $db->saveBellEvent($firstEventId, $ruleId, 'Bel masuk', '07:00', '', true, 1) === $firstEventId,
        'Menyimpan ulang kegiatan pada waktu miliknya sendiri harus diizinkan.'
    );

    $duplicateCreateMessage = '';
    try {
        $db->saveBellEvent(null, $ruleId, 'Bel duplikat', '07:00', '', true, 2);
    } catch (InvalidArgumentException $exception) {
        $duplicateCreateMessage = $exception->getMessage();
    }
    expectScheduleEventValidation(
        str_contains($duplicateCreateMessage, 'Waktu 07:00 sudah dipakai'),
        'Tambah kegiatan pada waktu yang sudah dipakai dalam pola yang sama harus ditolak.'
    );

    $secondEventId = $db->saveBellEvent(null, $ruleId, 'Bel berikutnya', '07:05', '', true, 2);
    $duplicateUpdateMessage = '';
    try {
        $db->saveBellEvent($secondEventId, $ruleId, 'Bel berikutnya', '07:00', '', true, 2);
    } catch (InvalidArgumentException $exception) {
        $duplicateUpdateMessage = $exception->getMessage();
    }
    $secondEvent = $db->getBellEvent($secondEventId);
    expectScheduleEventValidation(
        str_contains($duplicateUpdateMessage, 'Waktu 07:00 sudah dipakai')
            && $secondEvent !== null
            && substr((string) $secondEvent['event_time'], 0, 5) === '07:05',
        'Edit ke waktu duplikat harus ditolak dan waktu lama tetap tersimpan.'
    );

    // A different rule can use the same time because Normal/Khusus are resolved as alternatives.
    $specialRuleId = $db->saveDayRule(null, $packageId, 1, 'special', 'pasaran', 'Legi', true);
    $specialEventId = $db->saveBellEvent(null, $specialRuleId, 'Bel khusus', '07:00', '', true, 1);
    expectScheduleEventValidation(
        $specialEventId > 0,
        'Waktu sama pada aturan hari yang berbeda harus tetap diizinkan.'
    );

    $targetRuleId = $db->saveDayRule(null, $packageId, 2, 'normal', null, null, true);
    $db->saveBellEvent(null, $targetRuleId, 'Bel target', '08:00', '', true, 1);

    // Simulate legacy data written before duplicate-time validation existed.
    $legacyInsert = $db->getPdo()->prepare(
        'INSERT INTO bell_events (day_rule_id, name, event_time, audio_path, is_enabled, sort_order, created_at, updated_at)
         VALUES (:day_rule_id, :name, :event_time, :audio_path, :is_enabled, :sort_order, :created_at, :updated_at)'
    );
    $now = date('Y-m-d H:i:s');
    $legacyInsert->execute([
        ':day_rule_id' => $ruleId,
        ':name' => 'Kegiatan lama duplikat',
        ':event_time' => '07:00',
        ':audio_path' => '',
        ':is_enabled' => 1,
        ':sort_order' => 3,
        ':created_at' => $now,
        ':updated_at' => $now,
    ]);

    $copyError = '';
    try {
        $db->replaceWeekdayScheduleFromSource($packageId, 1, 2);
    } catch (InvalidArgumentException $exception) {
        $copyError = $exception->getMessage();
    }
    $targetRulesAfterCopy = $db->getDayRules($packageId, 2);
    $targetEventsAfterCopy = $db->getBellEvents($targetRuleId);
    expectScheduleEventValidation(
        str_contains($copyError, 'waktu duplikat')
            && count($targetRulesAfterCopy) === 1
            && count($targetEventsAfterCopy) === 1
            && $targetEventsAfterCopy[0]['name'] === 'Bel target',
        'Salin dari sumber legacy yang duplikat harus diblokir sebelum mengubah hari tujuan.'
    );

    $firstRunId = $db->recordPlaybackLogIfMissing(
        $packageId,
        $firstEventId,
        '2026-10-19 07:00',
        'failed',
        'Fixture: audio kegiatan pertama gagal.'
    );
    $nextRunId = $db->recordPlaybackLogIfMissing(
        $packageId,
        $secondEventId,
        '2026-10-19 07:05',
        'started',
        'Fixture: kegiatan pada waktu berikutnya mulai diproses.'
    );
    expectScheduleEventValidation(
        is_int($firstRunId) && is_int($nextRunId),
        'Kegagalan pada satu waktu tidak boleh mencegah pencatatan kegiatan pada waktu berikutnya.'
    );

    unset($db);
    echo "PASS: unique schedule times, safe edits, and later-event logging after failure\n";
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
