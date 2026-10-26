<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/AudioAsset.php';

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

$runId = 0;
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
    $audioCheck = AudioAsset::inspect($audioPath);
    if (!$audioCheck['ok']) {
        $failureMessage = (string) $audioCheck['message'];
        $db->updatePlaybackLogStatus((int) $runId, 'failed', $failureMessage);
        $httpStatuses = [
            'missing_reference' => 404,
            'unsupported_format' => 415,
            'not_found' => 404,
            'unreadable' => 403,
            'size_unavailable' => 500,
            'empty' => 422,
            'cannot_open' => 500,
        ];
        $failureCode = (string) $audioCheck['code'];
        audioStreamError($failureMessage, $httpStatuses[$failureCode] ?? 500);
    }

    $realPath = (string) $audioCheck['path'];
    $mimeType = (string) $audioCheck['mime_type'];
    $size = (int) $audioCheck['size'];

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
    header('Content-Type: ' . $mimeType);
    header('Accept-Ranges: bytes');
    header('Content-Length: ' . ($end - $start + 1));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('X-Content-Type-Options: nosniff');
    if ($partial) {
        header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
    }

    $remaining = $end - $start + 1;
    $streamFailure = '';
    while ($remaining > 0 && !feof($handle)) {
        $chunk = fread($handle, min(8192, $remaining));
        if ($chunk === false || $chunk === '') {
            if ($remaining > 0 && !connection_aborted()) {
                $streamFailure = 'Pembacaan audio berhenti sebelum seluruh data terkirim.';
            }
            break;
        }
        echo $chunk;
        $remaining -= strlen($chunk);
        flush();
        if (connection_aborted()) {
            // The client closed the stream; the player will reconcile its run on pagehide/error.
            break;
        }
    }
    fclose($handle);

    // fread() may return a final non-empty chunk and set EOF at the same time.
    // In that case the loop can end with bytes still expected without entering
    // the empty-read branch above. Treat that as a truncated stream, unless the
    // client intentionally disconnected.
    if ($remaining > 0 && $streamFailure === '' && !connection_aborted()) {
        $streamFailure = 'Penyaluran audio berhenti sebelum seluruh byte terkirim (tersisa '
            . $remaining . ' byte).';
    }

    if ($streamFailure !== '') {
        $db->updatePlaybackLogStatus((int) $runId, 'failed', $streamFailure);
    }
} catch (Throwable $exception) {
    if (is_int($runId) && $runId > 0) {
        try {
            $dbForFailure = $db ?? new Database();
            $dbForFailure->updatePlaybackLogStatus(
                $runId,
                'failed',
                'Audio tidak dapat disalurkan: ' . $exception->getMessage()
            );
        } catch (Throwable $ignored) {
            // Preserve the original streaming error response if logging also fails.
        }
    }
    audioStreamError('Audio tidak dapat disalurkan: ' . $exception->getMessage(), 500);
}
