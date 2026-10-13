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
