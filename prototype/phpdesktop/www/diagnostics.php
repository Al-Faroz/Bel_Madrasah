<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/Security.php';
$csrfToken = enforceCsrfForPost();

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$phpVersion = PHP_VERSION;
$sapi = PHP_SAPI;
$pdoSqlite = extension_loaded('pdo_sqlite');
$sqlite3 = extension_loaded('sqlite3');
$memoryLimit = (string) ini_get('memory_limit');
$serverSoftware = (string) ($_SERVER['SERVER_SOFTWARE'] ?? 'Tidak tersedia');
$documentRoot = (string) ($_SERVER['DOCUMENT_ROOT'] ?? 'Tidak tersedia');

$checks = [
    ['label' => 'PHP runtime', 'value' => $phpVersion, 'ok' => true],
    ['label' => 'PHP SAPI', 'value' => $sapi, 'ok' => true],
    ['label' => 'PDO SQLite', 'value' => $pdoSqlite ? 'Tersedia' : 'Belum tersedia', 'ok' => $pdoSqlite],
    ['label' => 'SQLite3 extension', 'value' => $sqlite3 ? 'Tersedia' : 'Belum tersedia', 'ok' => $sqlite3],
    ['label' => 'Memory limit PHP', 'value' => $memoryLimit, 'ok' => true],
    ['label' => 'Server internal', 'value' => $serverSoftware, 'ok' => true],
];

/*
 * Tahap 1A: diagnostic persistence test.
 * Keep the test database in the current Windows user's writable AppData,
 * never beside the program/runtime files.
 */
$storagePath = '';
$storageReady = false;
$storageError = '';
$storageMessage = '';
$storageRecord = null;
$storageAction = (string) ($_POST['storage_action'] ?? '');

try {
    if (!$pdoSqlite) {
        throw new RuntimeException('Ekstensi PDO SQLite tidak tersedia.');
    }

    $userDataRoot = getenv('APPDATA');
    if ($userDataRoot === false || trim($userDataRoot) === '') {
        $userDataRoot = getenv('LOCALAPPDATA');
    }
    if ($userDataRoot === false || trim($userDataRoot) === '') {
        throw new RuntimeException('Folder AppData Windows tidak ditemukan. Database tidak dibuat di folder program.');
    }

    $dataDirectory = rtrim($userDataRoot, "\\/") . DIRECTORY_SEPARATOR . 'BelMadrasah' . DIRECTORY_SEPARATOR . 'diagnostics';
    if (!is_dir($dataDirectory) && !mkdir($dataDirectory, 0770, true) && !is_dir($dataDirectory)) {
        throw new RuntimeException('Folder data lokal tidak dapat dibuat. Periksa izin tulis Windows.');
    }

    $storagePath = $dataDirectory . DIRECTORY_SEPARATOR . 'storage-test.sqlite';
    $db = new PDO('sqlite:' . $storagePath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec(
        'CREATE TABLE IF NOT EXISTS persistence_probe (
            id INTEGER PRIMARY KEY CHECK (id = 1),
            marker TEXT NOT NULL,
            saved_at TEXT NOT NULL
        )'
    );
    $storageReady = true;

    if ($storageAction === 'save') {
        $marker = 'BM-' . strtoupper(bin2hex(random_bytes(4)));
        $savedAt = date('Y-m-d H:i:s');
        $statement = $db->prepare(
            'INSERT INTO persistence_probe (id, marker, saved_at)
             VALUES (1, :marker, :saved_at)
             ON CONFLICT(id) DO UPDATE SET marker = excluded.marker, saved_at = excluded.saved_at'
        );
        $statement->execute([':marker' => $marker, ':saved_at' => $savedAt]);
        $storageMessage = 'PASS: data uji berhasil ditulis dan dibaca kembali pada sesi ini. Tutup aplikasi sepenuhnya, buka lagi, lalu tekan “Baca data tersimpan” untuk menguji persistensi setelah restart.';
    } elseif ($storageAction === 'read') {
        $storageMessage = 'Pembacaan database selesai. Cocokkan marker dan waktu simpan dengan data sebelum aplikasi ditutup.';
    }

    $storageRecord = $db->query(
        'SELECT marker, saved_at FROM persistence_probe WHERE id = 1'
    )->fetch() ?: null;

    if ($storageAction === 'read' && $storageRecord === null) {
        $storageMessage = 'BELUM ADA DATA: tekan “Simpan data uji” terlebih dahulu, lalu tutup dan buka kembali aplikasi.';
    }
} catch (Throwable $exception) {
    $storageReady = false;
    $storageError = $exception->getMessage();
    $storageMessage = 'FAIL: pengujian penyimpanan belum berhasil. Catat pesan di bawah untuk diagnosis.';
}

/*
 * Tahap 2: initialize the real application database separately from the old
 * diagnostic persistence database. The self-test uses a TEMP table only.
 */
$appDatabase = null;
$appDatabaseReady = false;
$appDatabasePath = '';
$appDatabaseError = '';
$appDatabaseSelfTest = false;
$appDatabaseTables = [];
$appDatabaseVersion = 0;

try {
    require_once __DIR__ . '/lib/Database.php';
    $appDatabase = new Database();
    $appDatabasePath = $appDatabase->getPath();
    $appDatabaseVersion = $appDatabase->getSchemaVersion();
    $appDatabaseTables = $appDatabase->getTables();
    $appDatabaseSelfTest = $appDatabase->runSelfTest();
    $appDatabaseReady = $appDatabaseSelfTest && count($appDatabaseTables) >= 6;
} catch (Throwable $exception) {
    $appDatabaseError = $exception->getMessage();
}


$now = new DateTimeImmutable('now');
$documentTitle = 'Pemeriksaan Teknis — Bel Madrasah';
$pageEyebrow = 'PEMERIKSAAN TEKNIS';
$pageHeading = 'Pemeriksaan';
$pageSubtitle = 'Runtime, database, persistensi, dan uji audio';
$pageStatusLabel = 'MODE DIAGNOSTIK';
$activePage = 'diagnostics';
$footerPageLabel = 'Pemeriksaan';
$footerMeta = 'PHP ' . PHP_VERSION . ' · ' . $now->format('d/m/Y H:i');
$viewPath = __DIR__ . '/views/admin/diagnostics.php';

require __DIR__ . '/layouts/admin.php';
