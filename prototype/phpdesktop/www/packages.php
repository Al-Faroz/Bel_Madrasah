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
$editId = filter_var($_GET['edit'] ?? 0, FILTER_VALIDATE_INT);
$editId = $editId !== false && $editId > 0 ? $editId : 0;
$formName = '';
$formDescription = '';
$formOffDays = '7';
$packages = [];
$activePackage = null;

try {
    $db = new Database();
    $db->ensureDefaultSchedulePackage();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $action = (string) ($_POST['package_action'] ?? '');
        if ($action === 'activate') {
            $targetId = filter_var($_POST['package_id'] ?? null, FILTER_VALIDATE_INT);
            if ($targetId === false || $targetId === null || $targetId < 1) {
                throw new InvalidArgumentException('Paket tujuan tidak valid.');
            }
            $db->activateSchedulePackage($targetId);
            $verifiedPackage = $db->getSchedulePackage($targetId);
            if ($verifiedPackage === null || (int) $verifiedPackage['is_active'] !== 1) {
                throw new RuntimeException('Status aktif paket tidak berhasil diverifikasi setelah penyimpanan.');
            }
            $message = 'Paket jadwal "' . (string) $verifiedPackage['name'] . '" berhasil diaktifkan dan diverifikasi.';
            $editId = 0;
        } elseif ($action === 'delete') {
            $targetId = filter_var($_POST['package_id'] ?? null, FILTER_VALIDATE_INT);
            if ($targetId === false || $targetId === null || $targetId < 1) {
                throw new InvalidArgumentException('Paket yang akan dihapus tidak valid.');
            }
            $targetPackage = $db->getSchedulePackage($targetId);
            if ($targetPackage === null) {
                throw new InvalidArgumentException('Paket yang akan dihapus tidak ditemukan.');
            }
            $deleted = $db->hardDeleteSchedulePackage($targetId);
            $message = 'Hard delete berhasil: paket "' . (string) $targetPackage['name'] . '", '
                . $deleted['rules'] . ' aturan, dan ' . $deleted['events']
                . ' kegiatan dihapus permanen. Riwayat pemutaran tetap dipertahankan.';
            if ($editId === $targetId) {
                $editId = 0;
                $formName = '';
                $formDescription = '';
                $formOffDays = '7';
            }
        } elseif ($action === 'save') {
            $postedId = filter_var($_POST['package_id'] ?? 0, FILTER_VALIDATE_INT);
            $postedId = $postedId !== false && $postedId > 0 ? $postedId : 0;
            $formName = trim((string) ($_POST['name'] ?? ''));
            $formDescription = trim((string) ($_POST['description'] ?? ''));
            $formOffDays = (string) ($_POST['off_days'] ?? '7');
            if (!in_array($formOffDays, ['5', '7', '6,7'], true)) {
                throw new InvalidArgumentException('Pilihan hari libur mingguan tidak valid.');
            }
            try {
                $savedId = $db->saveSchedulePackage($postedId > 0 ? $postedId : null, $formName, $formDescription, $formOffDays);
                if ($postedId === 0) {
                    $db->initializePackageDays($savedId, $formOffDays);
                }
                $editId = $postedId > 0 ? $savedId : 0;
                $message = $postedId > 0 ? 'Perubahan paket berhasil disimpan.' : 'Paket baru berhasil dibuat dalam keadaan nonaktif.';
                if ($postedId === 0) {
                    $formName = '';
                    $formDescription = '';
                    $formOffDays = '7';
                }
            } catch (PDOException $exception) {
                if (stripos($exception->getMessage(), 'UNIQUE constraint failed') !== false) {
                    throw new InvalidArgumentException('Nama paket sudah digunakan. Gunakan nama yang berbeda.');
                }
                throw $exception;
            }
        } else {
            throw new InvalidArgumentException('Aksi paket tidak dikenali.');
        }
    }

    $packages = $db->getSchedulePackages();
    foreach ($packages as $package) {
        if ((int) $package['is_active'] === 1) {
            $activePackage = $package;
            break;
        }
    }
    if ($editId > 0 && $formName === '') {
        $editing = $db->getSchedulePackage($editId);
        if ($editing === null) {
            $error = 'Paket yang dipilih untuk diedit tidak ditemukan.';
            $editId = 0;
        } else {
            $formName = (string) $editing['name'];
            $formDescription = (string) $editing['description'];
            $formOffDays = (string) $editing['off_days'];
        }
    }
} catch (Throwable $exception) {
    $error = $exception instanceof InvalidArgumentException
        ? $exception->getMessage()
        : 'Operasi paket gagal: ' . $exception->getMessage();
    if ($db instanceof Database) {
        try {
            $packages = $db->getSchedulePackages();
        } catch (Throwable $ignored) {
            $packages = [];
        }
    }
    $activePackage = null;
    foreach ($packages as $package) {
        if ((int) $package['is_active'] === 1) {
            $activePackage = $package;
            break;
        }
    }
}

$now = new DateTimeImmutable('now');
$documentTitle = 'Paket Jadwal — Bel Madrasah';
$pageEyebrow = 'PENGELOLAAN JADWAL';
$pageHeading = 'Paket Jadwal';
$pageSubtitle = 'Kelola paket dan pilih satu jadwal yang berlaku';
$pageStatusLabel = 'PAKET JADWAL';
$activePage = 'packages';
$footerPageLabel = 'Paket Jadwal';
$footerMeta = count($packages) . ' paket tersimpan · ' . $now->format('d/m/Y H:i');
$viewPath = __DIR__ . '/views/admin/packages.php';

require __DIR__ . '/layouts/admin.php';
