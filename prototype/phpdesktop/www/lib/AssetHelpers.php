<?php
declare(strict_types=1);

/**
 * Embed a selected local logo in the desktop webview without exposing a file:// URL.
 * Only common image formats are accepted, and oversized files are not embedded.
 */
function getLocalImageDataUri(string $path): string
{
    if ($path === '' || !is_file($path) || !is_readable($path)) {
        return '';
    }

    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mime = match ($extension) {
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        default => '',
    };
    if ($mime === '') {
        return '';
    }

    $size = filesize($path);
    if ($size === false || $size <= 0 || $size > 5 * 1024 * 1024) {
        return '';
    }

    $contents = file_get_contents($path);
    if ($contents === false || $contents === '') {
        return '';
    }

    return 'data:' . $mime . ';base64,' . base64_encode($contents);
}

/**
 * Open a known application-owned asset directory in Windows File Explorer.
 * The directory comes from Database, never from a submitted filesystem path.
 * Returns false when the desktop runtime cannot invoke Explorer; the UI can
 * then fall back to copying the directory path for manual navigation.
 */
function openAssetFolderInExplorer(string $path): bool
{
    if (PHP_OS_FAMILY !== 'Windows') {
        return false;
    }

    $directory = realpath($path);
    if ($directory === false || !is_dir($directory)) {
        return false;
    }

    $disabledFunctions = array_filter(array_map(
        static fn (string $name): string => strtolower(trim($name)),
        explode(',', (string) ini_get('disable_functions'))
    ));
    if (!function_exists('exec') || in_array('exec', $disabledFunctions, true)) {
        return false;
    }

    $output = [];
    $exitCode = 1;
    @exec('start "" explorer.exe ' . escapeshellarg($directory), $output, $exitCode);

    return $exitCode === 0;
}
