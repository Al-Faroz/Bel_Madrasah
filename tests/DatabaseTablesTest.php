<?php
declare(strict_types=1);

require_once __DIR__ . '/../prototype/phpdesktop/www/lib/Database.php';

function expectDatabaseTables(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
}

$temporaryRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'BelMadrasahDatabaseTablesTest-' . bin2hex(random_bytes(4));
if (!mkdir($temporaryRoot, 0770, true) && !is_dir($temporaryRoot)) {
    throw new RuntimeException('Folder test sementara tidak dapat dibuat.');
}
putenv('APPDATA=' . $temporaryRoot);

try {
    $db = new Database();
    $tables = $db->getTables();
    $requiredTables = [
        'app_meta',
        'app_settings',
        'schedule_packages',
        'day_rules',
        'bell_events',
        'playback_log',
    ];

    foreach ($requiredTables as $requiredTable) {
        expectDatabaseTables(
            in_array($requiredTable, $tables, true),
            'Tabel inti ' . $requiredTable . ' harus tercantum pada hasil getTables().'
        );
    }

    expectDatabaseTables(count($tables) >= 6, 'Pemeriksaan harus menemukan minimal enam tabel aplikasi.');
    expectDatabaseTables(
        !array_filter($tables, static fn (string $name): bool => str_starts_with($name, 'sqlite_')),
        'Tabel internal SQLite tidak boleh ditampilkan sebagai tabel aplikasi.'
    );

    unset($db);
    echo "PASS: Database::getTables returns the initialized application schema\n";
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
