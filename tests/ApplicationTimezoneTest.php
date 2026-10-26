<?php
declare(strict_types=1);

function expectApplicationTimezone(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
}

// Simulate a PHP runtime whose initial timezone is not the application timezone.
date_default_timezone_set('UTC');
expectApplicationTimezone(
    date_default_timezone_get() === 'UTC',
    'Fixture harus memulai dari zona waktu UTC.'
);

require_once __DIR__ . '/../prototype/phpdesktop/www/lib/Database.php';

expectApplicationTimezone(
    date_default_timezone_get() === 'Asia/Jakarta',
    'Memuat Database.php harus menetapkan zona waktu aplikasi ke Asia/Jakarta.'
);

$localDateTime = new DateTimeImmutable('2026-10-26 00:30:00');
expectApplicationTimezone(
    $localDateTime->getTimezone()->getName() === 'Asia/Jakarta',
    'DateTimeImmutable tanpa zona eksplisit harus menggunakan Asia/Jakarta.'
);
expectApplicationTimezone(
    $localDateTime->format('Y-m-d H:i') === '2026-10-26 00:30',
    'Tanggal dan jam lokal yang dibentuk setelah bootstrap harus dipertahankan.'
);

echo "PASS: application timezone is consistently Asia/Jakarta\n";
