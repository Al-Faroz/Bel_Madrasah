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
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#17633e">
    <title><?= htmlspecialchars($documentTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body class="admin-area">
<div class="app-shell admin-shell">
    <?php require __DIR__ . '/../partials/admin-header.php'; ?>
    <?php require __DIR__ . '/../partials/admin-nav.php'; ?>
    <main class="admin-main">
        <?php require $viewPath; ?>
    </main>
    <?php require __DIR__ . '/../partials/admin-footer.php'; ?>
</div>
</body>
</html>
