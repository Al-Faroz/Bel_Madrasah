<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/Database.php';

function audioStreamError(string $message, int $status): never
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    echo $message;
    exit;
}

$fetchSite = strtolower((string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? ''));
if ($fetchSite === 'cross-site') {
    audioStreamError('Permintaan audio lintas situs ditolak.', 403);
}
$origin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
if ($origin !== '') {
    $originHost = parse_url($origin, PHP_URL_HOST);
    $requestHost = parse_url('http://' . (string) ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
    if (!is_string($originHost) || !is_string($requestHost) || strcasecmp($originHost, $requestHost) !== 0) {
        audioStreamError('Origin permintaan tidak sesuai dengan aplikasi.', 403);
    }
}

try {
    $runId = filter_var($_GET['run_id'] ?? 0, FILTER_VALIDATE_INT);
    if ($runId === false || $runId < 1) {
        audioStreamError('ID pemutaran tidak valid.', 400);
    }

    $db = new Database();
    $run = $db->getPlaybackLog((int) $runId);
    if ($run === null || (string) $run['status'] !== 'started') {
        audioStreamError('Kejadian audio tidak aktif atau sudah ditutup.', 409);
    }
    if (empty($run['event_id'])) {
        $db->updatePlaybackLogStatus((int) $runId, 'failed', 'Referensi kegiatan sudah tidak tersedia.');
        audioStreamError('Referensi kegiatan sudah tidak tersedia.', 404);
    }

    $event = $db->getBellEvent((int) $run['event_id']);
    if ($event === null) {
        $db->updatePlaybackLogStatus((int) $runId, 'failed', 'Data kegiatan tidak tersedia saat audio diminta.');
        audioStreamError('Kegiatan tidak tersedia.', 404);
    }

    $audioPath = trim((string) ($event['audio_path'] ?? ''));
    $extension = strtolower(pathinfo($audioPath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'ogg' => 'audio/ogg',
        'm4a' => 'audio/mp4',
        'aac' => 'audio/aac',
    ];
    if (!isset($mimeTypes[$extension])) {
        $db->updatePlaybackLogStatus((int) $runId, 'failed', 'Format file audio tidak didukung.');
        audioStreamError('Format file audio tidak didukung.', 415);
    }

    $realPath = realpath($audioPath);
    if ($realPath === false || !is_file($realPath) || !is_readable($realPath)) {
        $db->updatePlaybackLogStatus((int) $runId, 'failed', 'File audio tidak ditemukan atau tidak dapat dibaca.');
        audioStreamError('File audio tidak ditemukan atau tidak dapat dibaca.', 404);
    }

    $size = filesize($realPath);
    if ($size === false || $size <= 0) {
        $db->updatePlaybackLogStatus((int) $runId, 'failed', 'File audio kosong atau ukurannya tidak dapat dibaca.');
        audioStreamError('File audio kosong atau tidak dapat dibaca.', 422);
    }

    $start = 0;
    $end = $size - 1;
    $partial = false;
    $rangeHeader = trim((string) ($_SERVER['HTTP_RANGE'] ?? ''));
    if ($rangeHeader !== '') {
        if (!preg_match('/^bytes=(\d*)-(\d*)$/', $rangeHeader, $matches)) {
            header('Content-Range: bytes */' . $size);
            audioStreamError('Rentang byte tidak valid.', 416);
        }

        if ($matches[1] === '') {
            $suffixLength = (int) $matches[2];
            if ($suffixLength <= 0) {
                header('Content-Range: bytes */' . $size);
                audioStreamError('Rentang byte tidak valid.', 416);
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
            audioStreamError('Rentang byte berada di luar file.', 416);
        }
        $partial = true;
    }

    $handle = @fopen($realPath, 'rb');
    if ($handle === false) {
        $db->updatePlaybackLogStatus((int) $runId, 'failed', 'Windows gagal membuka file audio.');
        audioStreamError('File audio tidak dapat dibuka.', 500);
    }
    if (fseek($handle, $start) !== 0) {
        fclose($handle);
        $db->updatePlaybackLogStatus((int) $runId, 'failed', 'Posisi pembacaan file audio tidak dapat diatur.');
        audioStreamError('File audio tidak dapat dibaca.', 500);
    }

    http_response_code($partial ? 206 : 200);
    header('Content-Type: ' . $mimeTypes[$extension]);
    header('Accept-Ranges: bytes');
    header('Content-Length: ' . ($end - $start + 1));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('X-Content-Type-Options: nosniff');
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
        flush();
        if (connection_aborted()) {
            break;
        }
    }
    fclose($handle);
} catch (Throwable $exception) {
    audioStreamError('Audio tidak dapat disalurkan: ' . $exception->getMessage(), 500);
}
