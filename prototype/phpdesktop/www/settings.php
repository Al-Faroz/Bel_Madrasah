<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/Database.php';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$db = null;
$error = '';
$message = '';
$schoolName = '';
$operatorName = '';
$volume = '80';

try {
    $db = new Database();
    $schoolName = $db->getSetting('school_name', '');
    $operatorName = $db->getSetting('operator_name', '');
    $volume = $db->getSetting('default_volume', '80');

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $schoolName = trim((string) ($_POST['school_name'] ?? ''));
        $operatorName = trim((string) ($_POST['operator_name'] ?? ''));
        $volumeInput = filter_var($_POST['default_volume'] ?? '80', FILTER_VALIDATE_INT);

        if (strlen($schoolName) > 120 || strlen($operatorName) > 80) {
            $error = 'Nama madrasah maksimal 120 karakter dan nama operator maksimal 80 karakter.';
        } elseif ($volumeInput === false || $volumeInput < 0 || $volumeInput > 100) {
            $error = 'Volume bawaan harus berupa angka 0 sampai 100.';
        } else {
            $db->saveSettings([
                'school_name' => $schoolName,
                'operator_name' => $operatorName,
                'default_volume' => (string) $volumeInput,
            ]);
            $volume = (string) $volumeInput;
            $message = 'Konfigurasi berhasil disimpan di database lokal.';
        }
    }
} catch (Throwable $exception) {
    $error = 'Database belum siap: ' . $exception->getMessage();
}

$assetError = '';
$audioAssetsPath = '';
$logoAssetsPath = '';
$audioAssets = [];
$logoAssets = [];

if ($db instanceof Database) {
    try {
        $audioAssetsPath = $db->getAudioAssetsDirectory();
        $logoAssetsPath = $db->getLogoAssetsDirectory();
        $audioAssets = $db->listAudioAssets();
        $logoAssets = $db->listLogoAssets();
    } catch (Throwable $exception) {
        $assetError = 'Folder aset belum dapat disiapkan: ' . $exception->getMessage();
    }
}

$now = new DateTimeImmutable('now');
$documentTitle = 'Konfigurasi — Bel Madrasah';
$pageEyebrow = 'PENGATURAN APLIKASI';
$pageHeading = 'Bel Madrasah';
$pageSubtitle = 'Konfigurasi lokal dan pengelolaan lokasi aset';
$pageStatusLabel = 'KONFIGURASI';
$activePage = 'settings';
$footerPageLabel = 'Konfigurasi';
$footerMeta = 'Data lokal · ' . $now->format('d/m/Y H:i');
$viewPath = __DIR__ . '/views/admin/settings.php';

require __DIR__ . '/layouts/admin.php';
