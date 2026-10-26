<?php
declare(strict_types=1);

/**
 * Pemeriksaan referensi audio bersama untuk scheduler dan endpoint stream.
 * Pemeriksaan ini memastikan file tersedia dan dapat dibuka; bukan validasi codec/dekode media.
 */
final class AudioAsset
{
    private const MIME_TYPES = [
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'ogg' => 'audio/ogg',
        'm4a' => 'audio/mp4',
        'aac' => 'audio/aac',
    ];

    /**
     * @return array{ok:bool,code:string,message:string,path?:string,extension?:string,mime_type?:string,size?:int}
     */
    public static function inspect(string $path): array
    {
        $path = trim($path);
        if ($path === '') {
            return self::failure('missing_reference', 'Kegiatan ini belum memiliki referensi audio.');
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!isset(self::MIME_TYPES[$extension])) {
            $format = $extension !== '' ? strtoupper($extension) : 'tanpa ekstensi';
            return self::failure(
                'unsupported_format',
                'Format audio tidak didukung (' . $format . '). Gunakan MP3 atau WAV yang didukung aplikasi.'
            );
        }

        clearstatcache(true, $path);
        $realPath = realpath($path);
        if ($realPath === false || !is_file($realPath)) {
            return self::failure('not_found', 'File audio tidak ditemukan: ' . basename($path));
        }
        if (!is_readable($realPath)) {
            return self::failure('unreadable', 'File audio tidak dapat dibaca: ' . basename($realPath));
        }

        clearstatcache(true, $realPath);
        $size = @filesize($realPath);
        if ($size === false) {
            return self::failure('size_unavailable', 'Ukuran file audio tidak dapat dibaca: ' . basename($realPath));
        }
        if ($size <= 0) {
            return self::failure('empty', 'File audio kosong: ' . basename($realPath));
        }

        // is_readable() alone is not sufficient on Windows; verify that PHP can open the file.
        $handle = @fopen($realPath, 'rb');
        if ($handle === false) {
            return self::failure('cannot_open', 'Windows tidak dapat membuka file audio: ' . basename($realPath));
        }
        fclose($handle);

        return [
            'ok' => true,
            'code' => 'available',
            'message' => 'File audio tersedia.',
            'path' => $realPath,
            'extension' => $extension,
            'mime_type' => self::MIME_TYPES[$extension],
            'size' => (int) $size,
        ];
    }

    /**
     * @return array{ok:false,code:string,message:string}
     */
    private static function failure(string $code, string $message): array
    {
        return ['ok' => false, 'code' => $code, 'message' => $message];
    }
}
