<?php
declare(strict_types=1);

require_once __DIR__ . '/../prototype/phpdesktop/www/lib/Database.php';

function expectDayRuleStatus(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
}

$temporaryRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'BelMadrasahDayRuleStatus-' . bin2hex(random_bytes(4));
if (!mkdir($temporaryRoot, 0770, true) && !is_dir($temporaryRoot)) {
    throw new RuntimeException('Folder test sementara tidak dapat dibuat.');
}
putenv('APPDATA=' . $temporaryRoot);

try {
    $db = new Database();
    $packageId = $db->saveSchedulePackage(null, 'Day Rule Status Test', 'Fixture uji status aturan hari', '6,7');
    $otherPackageId = $db->saveSchedulePackage(null, 'Day Rule Status Other Test', 'Fixture paket pembanding', '6,7');
    $ruleId = $db->saveDayRule(null, $packageId, 2, 'special', 'pasaran', 'Legi', true);
    $eventId = $db->saveBellEvent(null, $ruleId, 'Bel masuk', '07:00', '', true, 1);

    $db->setDayRuleEnabled($ruleId, $packageId, false);
    $disabledRule = $db->getDayRule($ruleId);
    $eventsAfterDisable = $db->getBellEvents($ruleId);
    expectDayRuleStatus(
        $disabledRule !== null
            && (int) $disabledRule['is_enabled'] === 0
            && (int) $disabledRule['weekday'] === 2
            && $disabledRule['rule_mode'] === 'special'
            && $disabledRule['special_type'] === 'pasaran'
            && $disabledRule['special_value'] === 'Legi',
        'Menonaktifkan aturan hanya boleh mengubah status, bukan hari atau atribut pola.'
    );
    expectDayRuleStatus(
        count($eventsAfterDisable) === 1
            && (int) $eventsAfterDisable[0]['id'] === $eventId
            && $eventsAfterDisable[0]['name'] === 'Bel masuk'
            && substr((string) $eventsAfterDisable[0]['event_time'], 0, 5) === '07:00'
            && (int) $eventsAfterDisable[0]['is_enabled'] === 1,
        'Menonaktifkan aturan tidak boleh menghapus atau menonaktifkan kegiatan bel.'
    );

    $crossPackageRejected = false;
    try {
        $db->setDayRuleEnabled($ruleId, $otherPackageId, true);
    } catch (InvalidArgumentException $exception) {
        $crossPackageRejected = true;
    }
    $ruleAfterRejectedToggle = $db->getDayRule($ruleId);
    expectDayRuleStatus(
        $crossPackageRejected
            && $ruleAfterRejectedToggle !== null
            && (int) $ruleAfterRejectedToggle['is_enabled'] === 0,
        'Aksi dari paket lain harus ditolak tanpa mengubah status aturan.'
    );

    $db->setDayRuleEnabled($ruleId, $packageId, true);
    $enabledRule = $db->getDayRule($ruleId);
    expectDayRuleStatus(
        $enabledRule !== null && (int) $enabledRule['is_enabled'] === 1,
        'Aturan yang dinonaktifkan harus dapat diaktifkan kembali.'
    );

    unset($db);
    echo "PASS: day rule status toggles safely without changing pattern or bell events\n";
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
