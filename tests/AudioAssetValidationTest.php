<?php
declare(strict_types=1);

require_once __DIR__ . '/../prototype/phpdesktop/www/lib/AudioAsset.php';

function expectAudioAsset(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
}

$temporaryRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'BelMadrasahAudioAssetTest-' . bin2hex(random_bytes(4));
if (!mkdir($temporaryRoot, 0770, true) && !is_dir($temporaryRoot)) {
    throw new RuntimeException('Folder test sementara tidak dapat dibuat.');
}

try {
    $missingReference = AudioAsset::inspect('   ');
    expectAudioAsset(
        !$missingReference['ok'] && $missingReference['code'] === 'missing_reference',
        'Referensi audio kosong harus dikenali terpisah dari file hilang.'
    );

    $missingFile = AudioAsset::inspect($temporaryRoot . DIRECTORY_SEPARATOR . 'missing.mp3');
    expectAudioAsset(
        !$missingFile['ok'] && $missingFile['code'] === 'not_found',
        'File berformat yang didukung tetapi tidak ada harus ditandai not_found.'
    );

    $unsupportedPath = $temporaryRoot . DIRECTORY_SEPARATOR . 'catatan.txt';
    file_put_contents($unsupportedPath, 'bukan audio');
    $unsupported = AudioAsset::inspect($unsupportedPath);
    expectAudioAsset(
        !$unsupported['ok'] && $unsupported['code'] === 'unsupported_format',
        'Ekstensi yang tidak didukung harus ditolak.'
    );

    $emptyPath = $temporaryRoot . DIRECTORY_SEPARATOR . 'kosong.wav';
    file_put_contents($emptyPath, '');
    $empty = AudioAsset::inspect($emptyPath);
    expectAudioAsset(
        !$empty['ok'] && $empty['code'] === 'empty',
        'File audio berukuran nol harus ditandai empty.'
    );

    $directoryPath = $temporaryRoot . DIRECTORY_SEPARATOR . 'folder.wav';
    mkdir($directoryPath);
    $directory = AudioAsset::inspect($directoryPath);
    expectAudioAsset(
        !$directory['ok'] && $directory['code'] === 'not_found',
        'Direktori yang namanya berekstensi audio tidak boleh dianggap sebagai file.'
    );

    $validPath = $temporaryRoot . DIRECTORY_SEPARATOR . 'contoh.mp3';
    file_put_contents($validPath, "ID3\x03\x00\x00\x00\x00\x00\x00");
    $available = AudioAsset::inspect($validPath);
    expectAudioAsset(
        $available['ok']
            && $available['code'] === 'available'
            && $available['path'] === realpath($validPath)
            && $available['extension'] === 'mp3'
            && $available['mime_type'] === 'audio/mpeg'
            && $available['size'] > 0,
        'File yang ada, tidak kosong, dan dapat dibuka harus menghasilkan metadata audio yang konsisten.'
    );

    echo "PASS: audio asset reference, extension, file, size, and open checks\n";
} finally {
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
