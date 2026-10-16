<?php
declare(strict_types=1);

/*
 * Shared Admin document shell.
 * $viewPath must point to a PHP view inside www/views/admin/.
 * This is an internal controller value, never a request parameter.
 */
$adminViewRoot = realpath(__DIR__ . '/../views/admin');
$requestedView = isset($viewPath) && is_string($viewPath) ? realpath($viewPath) : false;
if (
    $adminViewRoot === false
    || $requestedView === false
    || !str_starts_with($requestedView, $adminViewRoot . DIRECTORY_SEPARATOR)
) {
    http_response_code(500);
    exit('View Admin tidak ditemukan atau tidak valid.');
}
$viewPath = $requestedView;
$documentTitle = isset($documentTitle) && is_string($documentTitle)
    ? $documentTitle
    : 'Bel Madrasah — Admin';

require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../lib/AssetHelpers.php';
try {
    if (isset($db) && $db instanceof Database) {
        $logoDatabase = $db;
    } elseif (isset($appDatabase) && $appDatabase instanceof Database) {
        $logoDatabase = $appDatabase;
    } else {
        $logoDatabase = new Database();
    }
    if (!isset($schoolLogoPath) || !is_string($schoolLogoPath)) {
        $schoolLogoPath = $logoDatabase->getSetting('school_logo_path', '');
    }
    $schoolLogoDataUri = getLocalImageDataUri($schoolLogoPath);
} catch (Throwable $exception) {
    $schoolLogoDataUri = '';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1f6b46">
    <title><?= htmlspecialchars($documentTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>
    <link rel="stylesheet" href="assets/app.css">
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body class="admin-area">
<div class="app-shell admin-shell">
    <aside class="admin-sidebar" aria-label="Area administrasi">
        <a class="sidebar-brand" href="dashboard.php" aria-label="Bel Madrasah — Dashboard">
            <span class="sidebar-brand-mark" aria-hidden="true"><?php if (($schoolLogoDataUri ?? '') !== ''): ?><img src="<?= htmlspecialchars($schoolLogoDataUri, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt=""><?php else: ?>BM<?php endif; ?></span>
            <span class="sidebar-brand-copy"><strong>Bel Madrasah</strong><small>PUSAT KONTROL</small></span>
        </a>
        <?php require __DIR__ . '/../partials/admin-nav.php'; ?>
        <div class="sidebar-bottom">
            <span class="sidebar-bottom-mark" aria-hidden="true"></span>
            <span><strong>Mode lokal</strong><small>Data tersimpan di perangkat ini</small></span>
        </div>
    </aside>
    <div class="admin-workspace">
        <?php require __DIR__ . '/../partials/admin-header.php'; ?>
        <main class="admin-main">
            <?php require $viewPath; ?>
        </main>
        <?php require __DIR__ . '/../partials/admin-footer.php'; ?>
    </div>
</div>
</body>
</html>
