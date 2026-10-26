<?php
declare(strict_types=1);

require_once __DIR__ . '/../prototype/phpdesktop/www/lib/Database.php';

function expectBackupSafety(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
}

function expectBackupSafetyThrows(callable $operation, string $message): void
{
    try {
        $operation();
    } catch (Throwable $exception) {
        return;
    }
    throw new RuntimeException('FAIL: expected rejection for ' . $message);
}

$originalAppData = getenv('APPDATA');
$originalLocalAppData = getenv('LOCALAPPDATA');
$temporaryRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'BelMadrasahBackupSafetyTest-' . bin2hex(random_bytes(4));
if (!mkdir($temporaryRoot, 0770, true) && !is_dir($temporaryRoot)) {
    throw new RuntimeException('Folder test sementara tidak dapat dibuat.');
}

putenv('APPDATA=' . $temporaryRoot);
putenv('LOCALAPPDATA');

$db = null;
try {
    $db = new Database();
    $db->saveSettings(['school_name' => 'Nama sebelum backup']);

    $backupDirectory = $db->getBackupDirectory();
    $validBackup = $backupDirectory . DIRECTORY_SEPARATOR . 'safety-valid.sqlite';
    $db->createBackup($validBackup);

    $inspection = $db->inspectBackup($validBackup);
    expectBackupSafety(
        strtolower((string) ($inspection['integrity'] ?? '')) === 'ok',
        'Backup yang dibuat aplikasi harus lolos integrity_check.'
    );
    expectBackupSafety(
        (int) ($inspection['schema_version'] ?? 0) === $db->getSchemaVersion(),
        'Versi skema backup harus sama dengan versi skema database aktif.'
    );

    $db->saveSettings(['school_name' => 'Nama setelah backup']);
    $preRestoreBackup = $backupDirectory . DIRECTORY_SEPARATOR . 'safety-pre-restore.sqlite';
    $createdPreRestore = $db->restoreFromBackup($validBackup, $preRestoreBackup);

    expectBackupSafety(
        $createdPreRestore === $preRestoreBackup && is_file($preRestoreBackup),
        'Restore harus menghasilkan backup pra-pemulihan.'
    );
    expectBackupSafety(
        $db->getSetting('school_name') === 'Nama sebelum backup',
        'Restore harus mengembalikan data dari backup yang dipilih.'
    );

    $preRestorePdo = new PDO('sqlite:' . $preRestoreBackup);
    $preRestorePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $preRestoreName = $preRestorePdo->query(
        "SELECT setting_value FROM app_settings WHERE setting_key = 'school_name'"
    )->fetchColumn();
    expectBackupSafety(
        $preRestoreName === 'Nama setelah backup',
        'Backup pra-pemulihan harus mempertahankan data yang ada tepat sebelum restore.'
    );
    unset($preRestorePdo);

    $unsupportedVersionBackup = $backupDirectory . DIRECTORY_SEPARATOR . 'safety-version-zero.sqlite';
    if (!copy($validBackup, $unsupportedVersionBackup)) {
        throw new RuntimeException('Fixture versi skema tidak dapat disalin.');
    }
    $versionPdo = new PDO('sqlite:' . $unsupportedVersionBackup);
    $versionPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $versionPdo->exec("UPDATE app_meta SET meta_value = '0' WHERE meta_key = 'schema_version'");
    unset($versionPdo);
    expectBackupSafetyThrows(
        static fn () => $db->inspectBackup($unsupportedVersionBackup),
        'versi skema nol/tidak didukung'
    );

    $foreignKeyBackup = $backupDirectory . DIRECTORY_SEPARATOR . 'safety-foreign-key.sqlite';
    if (!copy($validBackup, $foreignKeyBackup)) {
        throw new RuntimeException('Fixture foreign key tidak dapat disalin.');
    }
    $foreignKeyPdo = new PDO('sqlite:' . $foreignKeyBackup);
    $foreignKeyPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $foreignKeyPdo->exec('PRAGMA foreign_keys = OFF');
    $foreignKeyPdo->exec(
        "INSERT INTO bell_events
            (day_rule_id, name, event_time, audio_path, is_enabled, sort_order, created_at, updated_at)
         VALUES (999999, 'Orphan fixture', '13:00', '', 1, 1, '2026-10-26 00:00:00', '2026-10-26 00:00:00')"
    );
    unset($foreignKeyPdo);
    expectBackupSafetyThrows(
        static fn () => $db->inspectBackup($foreignKeyBackup),
        'backup dengan foreign-key violation'
    );

    $brokenBackup = $backupDirectory . DIRECTORY_SEPARATOR . 'safety-broken.sqlite';
    if (file_put_contents($brokenBackup, 'not a SQLite database') === false) {
        throw new RuntimeException('File backup rusak untuk fixture tidak dapat dibuat.');
    }

    expectBackupSafetyThrows(
        static fn () => $db->inspectBackup($brokenBackup),
        'file SQLite rusak'
    );
    expectBackupSafetyThrows(
        static fn () => $db->restoreFromBackup(
            $brokenBackup,
            $backupDirectory . DIRECTORY_SEPARATOR . 'must-not-be-created.sqlite'
        ),
        'restore dari file rusak'
    );
    expectBackupSafety(
        $db->getSetting('school_name') === 'Nama sebelum backup',
        'File backup rusak tidak boleh mengubah database aktif.'
    );
    expectBackupSafety(
        !file_exists($backupDirectory . DIRECTORY_SEPARATOR . 'must-not-be-created.sqlite'),
        'Validasi file sumber harus terjadi sebelum backup pra-pemulihan dibuat.'
    );

    $outsideBackup = $temporaryRoot . DIRECTORY_SEPARATOR . 'outside.sqlite';
    if (file_put_contents($outsideBackup, 'outside backup directory') === false) {
        throw new RuntimeException('Fixture path luar tidak dapat dibuat.');
    }
    expectBackupSafetyThrows(
        static fn () => $db->inspectBackup($outsideBackup),
        'file backup di luar folder backup terkelola'
    );

    expectBackupSafetyThrows(
        static fn () => $db->createBackup($validBackup),
        'penimpaan file backup yang sudah ada'
    );

    unset($db);
    echo "PASS: backup integrity, restore data, pre-restore safety, invalid files, and managed paths\n";
} finally {
    if ($db instanceof Database) {
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

    if ($originalAppData === false) {
        putenv('APPDATA');
    } else {
        putenv('APPDATA=' . $originalAppData);
    }
    if ($originalLocalAppData === false) {
        putenv('LOCALAPPDATA');
    } else {
        putenv('LOCALAPPDATA=' . $originalLocalAppData);
    }
}
