<?php
declare(strict_types=1);

function expectCsrfProtection(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
}

$sessionDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'BelMadrasahCsrfTest-' . bin2hex(random_bytes(4));
if (!mkdir($sessionDirectory, 0770, true) && !is_dir($sessionDirectory)) {
    throw new RuntimeException('Folder sesi sementara tidak dapat dibuat.');
}

$sessionId = 'BMCSRF' . bin2hex(random_bytes(8));
session_save_path($sessionDirectory);
session_id($sessionId);

try {
    require_once __DIR__ . '/../prototype/phpdesktop/www/lib/Security.php';

    $token = csrfToken();
    expectCsrfProtection(
        preg_match('/^[a-f0-9]{64}$/', $token) === 1,
        'Token CSRF harus berupa token acak dengan panjang dan format yang benar.'
    );
    expectCsrfProtection(
        isValidCsrfToken($token),
        'Token yang cocok dengan sesi harus diterima.'
    );
    expectCsrfProtection(
        !isValidCsrfToken('token-yang-salah'),
        'Token yang salah harus ditolak.'
    );
    expectCsrfProtection(
        !isValidCsrfToken(null),
        'Token yang hilang harus ditolak.'
    );

    echo "PASS: session-backed CSRF token validation\n";
} finally {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    $sessionFile = $sessionDirectory . DIRECTORY_SEPARATOR . 'sess_' . $sessionId;
    if (is_file($sessionFile)) {
        @unlink($sessionFile);
    }
    @rmdir($sessionDirectory);
}
