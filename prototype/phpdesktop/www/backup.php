<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/Security.php';
$csrfToken = enforceCsrfForPost();

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$db = null;
$error = '';
$message = '';
$backupPath = '';
$restorePreview = null;
$backups = [];
$backupDirectory = '';
$databasePath = '';

try {
    $db = new Database();
    $backupDirectory = $db->getBackupDirectory();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $action = (string) ($_POST['backup_action'] ?? '');

        if ($action === 'create_backup') {
            $backupPath = $backupDirectory . DIRECTORY_SEPARATOR
                . 'bel-madrasah-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2))) . '.sqlite';
            $db->createBackup($backupPath);
            $message = 'Backup berhasil dibuat. Salin file ke USB atau penyimpanan lain untuk perlindungan tambahan.';
        } elseif ($action === 'preview_restore' || $action === 'confirm_restore') {
            $backupName = trim((string) ($_POST['backup_name'] ?? ''));
            if ($backupName === '' || basename($backupName) !== $backupName) {
                throw new InvalidArgumentException('Nama file backup tidak valid.');
            }

            $selectedBackup = null;
            foreach ($db->listBackups() as $candidate) {
                if ($candidate['name'] === $backupName) {
                    $selectedBackup = $candidate;
                    break;
                }
            }
            if ($selectedBackup === null) {
                throw new InvalidArgumentException('File backup tidak ditemukan dalam folder backup aplikasi.');
            }

            if ($action === 'preview_restore') {
                $inspection = $db->inspectBackup((string) $selectedBackup['path']);
                $restorePreview = array_merge($selectedBackup, $inspection);
            } else {
                if ((string) ($_POST['restore_confirm'] ?? '') !== '1') {
                    throw new InvalidArgumentException('Centang konfirmasi sebelum memulihkan database.');
                }
                $preRestorePath = $backupDirectory . DIRECTORY_SEPARATOR
                    . 'pre-restore-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2))) . '.sqlite';
                $createdPreRestore = $db->restoreFromBackup((string) $selectedBackup['path'], $preRestorePath);
                $message = 'Pemulihan database berhasil. Backup pra-pemulihan tersimpan di: ' . $createdPreRestore;
            }
        } else {
            throw new InvalidArgumentException('Aksi backup/restore tidak dikenali.');
        }
    }

    $backups = $db->listBackups();
    $databasePath = $db->getPath();
} catch (Throwable $exception) {
    $error = 'Operasi backup/restore gagal: ' . $exception->getMessage();
    if ($db instanceof Database) {
        try {
            $backupDirectory = $db->getBackupDirectory();
            $backups = $db->listBackups();
            $databasePath = $db->getPath();
        } catch (Throwable $ignored) {
            $backups = [];
        }
    }
}

$now = new DateTimeImmutable('now');
$documentTitle = 'Backup dan Restore — Bel Madrasah';
$pageEyebrow = 'PERLINDUNGAN DATA';
$pageHeading = 'Backup & Restore';
$pageSubtitle = 'Cadangkan atau pulihkan database lokal dengan pemeriksaan';
$pageStatusLabel = 'BACKUP / RESTORE';
$activePage = 'backup';
$footerPageLabel = 'Backup dan Restore';
$footerMeta = count($backups) . ' backup lokal · ' . $now->format('d/m/Y H:i');
$viewPath = __DIR__ . '/views/admin/backup.php';

require __DIR__ . '/layouts/admin.php';
