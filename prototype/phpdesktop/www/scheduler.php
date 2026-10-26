<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/ScheduleResolver.php';
require_once __DIR__ . '/lib/Security.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');

function schedulerRespond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function schedulerEnforceSameOrigin(): void
{
    if (!isSameOriginRequest($_SERVER)) {
        schedulerRespond(['ok' => false, 'message' => 'Permintaan ditolak karena bukan berasal dari origin aplikasi yang sama.'], 403);
    }
}

schedulerEnforceSameOrigin();

try {
    $db = new Database();
    $action = (string) ($_GET['action'] ?? $_POST['action'] ?? 'tick');

    if ($action === 'page_start') {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            schedulerRespond(['ok' => false, 'message' => 'Inisialisasi scheduler harus menggunakan POST.'], 405);
        }
        $closedRuns = $db->markStartedPlaybackAsFailed();
        schedulerRespond(['ok' => true, 'state' => 'ready', 'reconciled_runs' => $closedRuns]);
    }

    if ($action === 'finish') {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            schedulerRespond(['ok' => false, 'message' => 'Pembaruan status pemutaran harus menggunakan POST.'], 405);
        }

        $runId = filter_var($_POST['run_id'] ?? 0, FILTER_VALIDATE_INT);
        $status = (string) ($_POST['status'] ?? '');
        $message = trim((string) ($_POST['message'] ?? ''));
        if ($runId === false || $runId < 1 || !in_array($status, ['completed', 'failed'], true)) {
            schedulerRespond(['ok' => false, 'message' => 'Data status pemutaran tidak valid.'], 400);
        }

        if (!$db->updatePlaybackLogStatus((int) $runId, $status, $message)) {
            $existingLog = $db->getPlaybackLog((int) $runId);
            if ($existingLog !== null && (string) $existingLog['status'] !== 'started') {
                $existingStatus = (string) $existingLog['status'];
                $existingMessage = trim((string) ($existingLog['message'] ?? ''));
                $closedMessage = 'Log pemutaran sudah ditutup dengan status ' . $existingStatus . '.';
                if ($existingMessage !== '') {
                    $closedMessage .= ' Detail: ' . $existingMessage;
                }
                schedulerRespond(['ok' => false, 'message' => $closedMessage], 409);
            }

            schedulerRespond(['ok' => false, 'message' => 'Log pemutaran tidak ditemukan atau sudah ditutup.'], 409);
        }

        schedulerRespond(['ok' => true, 'state' => $status]);
    }

    if ($action !== 'tick') {
        schedulerRespond(['ok' => false, 'message' => 'Aksi scheduler tidak dikenal.'], 400);
    }

    $now = new DateTimeImmutable('now');
    $package = $db->getActiveSchedulePackage();
    if ($package === null) {
        schedulerRespond([
            'ok' => true,
            'state' => 'idle',
            'message' => 'Belum ada paket jadwal aktif.',
            'volume' => 80,
        ]);
    }

    $packageId = (int) $package['id'];
    $resolution = ScheduleResolver::resolve($db, $packageId, $now);
    $events = ScheduleResolver::getEnabledEvents($db, $resolution);
    $duplicateEventTimes = ScheduleResolver::getDuplicateEventTimes($events);
    $volumeInput = filter_var($db->getSetting('default_volume', '80'), FILTER_VALIDATE_INT);
    $volume = $volumeInput === false ? 80 : max(0, min(100, $volumeInput));
    $graceSeconds = 30;
    $dueEvents = [];
    $duplicateTimeFailures = [];

    foreach ($events as $event) {
        $eventTime = substr(trim((string) ($event['event_time'] ?? '')), 0, 5);
        if (!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $eventTime)) {
            continue;
        }

        $scheduledFor = $now->format('Y-m-d') . ' ' . $eventTime;
        $scheduledAt = DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i',
            $scheduledFor,
            $now->getTimezone()
        );
        if (!($scheduledAt instanceof DateTimeImmutable)) {
            continue;
        }

        $delaySeconds = $now->getTimestamp() - $scheduledAt->getTimestamp();
        if ($delaySeconds < 0 || $db->getPlaybackLogForEventAt((int) $event['id'], $scheduledFor) !== null) {
            continue;
        }

        if (in_array($eventTime, $duplicateEventTimes, true)) {
            $message = 'Waktu ' . $eventTime
                . ' digunakan oleh lebih dari satu kegiatan aktif dalam pola hari. Bel tidak diputar; perbaiki waktu duplikat di Editor Jadwal.';
            $db->recordPlaybackLogIfMissing($packageId, (int) $event['id'], $scheduledFor, 'failed', $message);
            $duplicateTimeFailures[] = $eventTime;
            continue;
        }

        if ($delaySeconds > $graceSeconds) {
            $db->recordPlaybackLogIfMissing(
                $packageId,
                (int) $event['id'],
                $scheduledFor,
                'missed',
                'Aplikasi tidak memproses jadwal dalam toleransi keterlambatan ' . $graceSeconds . ' detik.'
            );
            continue;
        }

        $dueEvents[] = ['event' => $event, 'scheduled_for' => $scheduledFor];
    }

    foreach ($dueEvents as $due) {
        $event = $due['event'];
        $eventId = (int) $event['id'];
        $scheduledFor = (string) $due['scheduled_for'];
        $audioPath = trim((string) ($event['audio_path'] ?? ''));

        if ($audioPath === '') {
            $db->recordPlaybackLogIfMissing(
                $packageId,
                $eventId,
                $scheduledFor,
                'skipped',
                'Kegiatan ini belum memiliki referensi audio.'
            );
            schedulerRespond([
                'ok' => true,
                'state' => 'skipped',
                'message' => 'Kegiatan ' . (string) $event['name'] . ' dilewati karena audio belum dipilih.',
                'conflict' => (bool) $resolution['conflict'],
                'volume' => $volume,
            ]);
        }

        if (!is_file($audioPath) || !is_readable($audioPath)) {
            $message = 'File audio tidak ditemukan atau tidak dapat dibaca: ' . basename($audioPath);
            $db->recordPlaybackLogIfMissing($packageId, $eventId, $scheduledFor, 'failed', $message);
            schedulerRespond([
                'ok' => true,
                'state' => 'failed',
                'message' => 'Audio untuk ' . (string) $event['name'] . ' gagal: file tidak ditemukan atau tidak dapat dibaca.',
                'conflict' => (bool) $resolution['conflict'],
                'volume' => $volume,
            ]);
        }

        $extension = strtolower(pathinfo($audioPath, PATHINFO_EXTENSION));
        $supportedExtensions = ['mp3', 'wav', 'ogg', 'm4a', 'aac'];
        if (!in_array($extension, $supportedExtensions, true)) {
            $message = 'Format audio tidak didukung (' . ($extension !== '' ? strtoupper($extension) : 'tanpa ekstensi') . '). Gunakan MP3 atau WAV yang didukung aplikasi.';
            $db->recordPlaybackLogIfMissing($packageId, $eventId, $scheduledFor, 'failed', $message);
            schedulerRespond([
                'ok' => true,
                'state' => 'failed',
                'message' => 'Audio untuk ' . (string) $event['name'] . ' gagal: ' . $message,
                'conflict' => (bool) $resolution['conflict'],
                'volume' => $volume,
            ]);
        }

        clearstatcache(true, $audioPath);
        $audioSize = @filesize($audioPath);
        if ($audioSize === false || $audioSize <= 0) {
            $message = 'File audio kosong atau ukurannya tidak dapat dibaca: ' . basename($audioPath);
            $db->recordPlaybackLogIfMissing($packageId, $eventId, $scheduledFor, 'failed', $message);
            schedulerRespond([
                'ok' => true,
                'state' => 'failed',
                'message' => 'Audio untuk ' . (string) $event['name'] . ' gagal: file kosong atau tidak dapat dibaca.',
                'conflict' => (bool) $resolution['conflict'],
                'volume' => $volume,
            ]);
        }

        // is_readable() is not sufficient on Windows if the file is locked or cannot be opened.
        $audioHandle = @fopen($audioPath, 'rb');
        if ($audioHandle === false) {
            $message = 'Windows tidak dapat membuka file audio: ' . basename($audioPath);
            $db->recordPlaybackLogIfMissing($packageId, $eventId, $scheduledFor, 'failed', $message);
            schedulerRespond([
                'ok' => true,
                'state' => 'failed',
                'message' => 'Audio untuk ' . (string) $event['name'] . ' gagal: file tidak dapat dibuka oleh Windows.',
                'conflict' => (bool) $resolution['conflict'],
                'volume' => $volume,
            ]);
        }
        fclose($audioHandle);

        $runId = $db->recordPlaybackLogIfMissing(
            $packageId,
            $eventId,
            $scheduledFor,
            'started',
            'Kegiatan dikirim ke pemutar audio internal.'
        );
        if ($runId === null) {
            continue;
        }

        schedulerRespond([
            'ok' => true,
            'state' => 'playing',
            'run_id' => $runId,
            'event_id' => $eventId,
            'event_name' => (string) $event['name'],
            'scheduled_for' => $scheduledFor,
            'audio_url' => 'audio-stream.php?run_id=' . $runId,
            'conflict' => (bool) $resolution['conflict'],
            'volume' => $volume,
        ]);
    }

    $latestLog = $db->getLatestPlaybackLog($now->format('Y-m-d'));
    $duplicateTimeFailures = array_values(array_unique($duplicateTimeFailures));
    $hasDuplicateTimeFailures = $duplicateTimeFailures !== [];
    $duplicateTimeFailureMessage = $hasDuplicateTimeFailures
        ? 'Waktu duplikat terdeteksi (' . implode(', ', $duplicateTimeFailures)
            . '). Tidak ada bel yang diputar pada waktu tersebut. Perbaiki pola di Editor Jadwal.'
        : '';
    schedulerRespond([
        'ok' => true,
        'state' => $hasDuplicateTimeFailures
            ? 'failed'
            : ((bool) $resolution['conflict']
                ? 'conflict'
                : ((string) $resolution['mode'] === 'off_day' ? 'off_day' : 'waiting')),
        'message' => $hasDuplicateTimeFailures
            ? $duplicateTimeFailureMessage
            : ((bool) $resolution['conflict']
                ? 'Konflik aturan Khusus terdeteksi. Resolver memakai pola Normal sebagai fallback.'
                : ((string) $resolution['mode'] === 'off_day'
                    ? 'Hari libur mingguan. Scheduler tidak menjalankan kegiatan bel hari ini.'
                    : 'Menunggu jadwal berikutnya.')),
        'conflict' => (bool) $resolution['conflict'],
        'volume' => $volume,
        'last_log' => $latestLog === null ? null : [
            'status' => (string) $latestLog['status'],
            'event_name' => (string) ($latestLog['event_name'] ?? 'Kegiatan tidak tersedia'),
            'scheduled_for' => (string) $latestLog['scheduled_for'],
            'message' => (string) $latestLog['message'],
        ],
    ]);
} catch (Throwable $exception) {
    schedulerRespond([
        'ok' => false,
        'state' => 'error',
        'message' => 'Scheduler gagal memeriksa jadwal: ' . $exception->getMessage(),
    ], 500);
}
