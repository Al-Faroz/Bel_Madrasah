<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/AudioAsset.php';
require_once __DIR__ . '/lib/Security.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');

function audioPreviewError(string $message, int $status): never
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    audioPreviewError('Pratinjau audio hanya menerima permintaan GET.', 405);
}
if (!isSameOriginRequest($_SERVER)) {
    audioPreviewError('Permintaan pratinjau ditolak karena bukan berasal dari origin aplikasi yang sama.', 403);
}

try {
    $db = new Database();
    $assetName = trim((string) ($_GET['asset'] ?? ''));
    $eventId = filter_var($_GET['event_id'] ?? 0, FILTER_VALIDATE_INT);

    if ($assetName !== '' && $eventId !== false && $eventId > 0) {
        audioPreviewError('Pilih satu sumber pratinjau audio.', 400);
    }

    if ($assetName !== '') {
        // Resolve by an exact managed-asset filename; never accept an arbitrary path.
        $path = $db->resolveAudioAsset($assetName);
    } elseif ($eventId !== false && $eventId > 0) {
        $event = $db->getBellEvent((int) $eventId);
        if ($event === null) {
            audioPreviewError('Kegiatan bel tidak ditemukan.', 404);
        }
        $path = trim((string) ($event['audio_path'] ?? ''));
    } else {
        audioPreviewError('Pilih file audio atau kegiatan yang valid untuk dipratinjau.', 400);
    }

    $audio = AudioAsset::inspect($path);
    if (!$audio['ok']) {
        $code = (string) $audio['code'];
        $statusByCode = [
            'missing_reference' => 404,
            'unsupported_format' => 415,
            'not_found' => 404,
            'unreadable' => 403,
            'size_unavailable' => 500,
            'empty' => 422,
            'cannot_open' => 500,
        ];
        audioPreviewError((string) $audio['message'], $statusByCode[$code] ?? 500);
    }

    $realPath = (string) $audio['path'];
    $mimeType = (string) $audio['mime_type'];
    $size = (int) $audio['size'];
    $start = 0;
    $end = $size - 1;
    $partial = false;
    $rangeHeader = trim((string) ($_SERVER['HTTP_RANGE'] ?? ''));

    if ($rangeHeader !== '') {
        if (!preg_match('/^bytes=(\d*)-(\d*)$/', $rangeHeader, $matches)) {
            header('Content-Range: bytes */' . $size);
            audioPreviewError('Rentang byte pratinjau tidak valid.', 416);
        }

        if ($matches[1] === '') {
            $suffixLength = (int) $matches[2];
            if ($suffixLength <= 0) {
                header('Content-Range: bytes */' . $size);
                audioPreviewError('Rentang byte pratinjau tidak valid.', 416);
            }
            $start = max(0, $size - $suffixLength);
        } else {
            $start = (int) $matches[1];
            if ($matches[2] !== '') {
                $end = min($end, (int) $matches[2]);
            }
        }

        if ($start < 0 || $start >= $size || $end < $start) {
            header('Content-Range: bytes */' . $size);
            audioPreviewError('Rentang byte pratinjau berada di luar file.', 416);
        }
        $partial = true;
    }

    $handle = @fopen($realPath, 'rb');
    if ($handle === false || fseek($handle, $start) !== 0) {
        if (is_resource($handle)) {
            fclose($handle);
        }
        audioPreviewError('File audio tidak dapat dibuka untuk pratinjau.', 500);
    }

    http_response_code($partial ? 206 : 200);
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: inline; filename*=UTF-8\'\'' . rawurlencode(basename($realPath)));
    header('Accept-Ranges: bytes');
    header('Content-Length: ' . ($end - $start + 1));
    if ($partial) {
        header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
    }

    $remaining = $end - $start + 1;
    while ($remaining > 0 && !feof($handle)) {
        $chunk = fread($handle, min(8192, $remaining));
        if ($chunk === false || $chunk === '') {
            break;
        }
        echo $chunk;
        $remaining -= strlen($chunk);
    }
    fclose($handle);
    exit;
} catch (InvalidArgumentException $exception) {
    audioPreviewError($exception->getMessage(), 404);
} catch (Throwable $exception) {
    audioPreviewError('Pratinjau audio gagal diproses. Periksa file audio dan konfigurasi aplikasi.', 500);
}
