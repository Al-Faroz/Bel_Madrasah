<?php
declare(strict_types=1);

require_once __DIR__ . '/../prototype/phpdesktop/www/lib/Security.php';

function expectSchedulerOrigin(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
}

$cases = [
    [
        'same origin with matching host and port',
        ['HTTP_HOST' => '127.0.0.1:54000', 'HTTP_ORIGIN' => 'http://127.0.0.1:54000', 'HTTP_SEC_FETCH_SITE' => 'same-origin'],
        true,
    ],
    [
        'default HTTP port is normalized',
        ['HTTP_HOST' => 'localhost:80', 'HTTP_ORIGIN' => 'http://localhost', 'HTTP_SEC_FETCH_SITE' => 'same-origin'],
        true,
    ],
    [
        'different port is rejected',
        ['HTTP_HOST' => 'localhost:54000', 'HTTP_ORIGIN' => 'http://localhost:54001', 'HTTP_SEC_FETCH_SITE' => 'same-origin'],
        false,
    ],
    [
        'different host is rejected',
        ['HTTP_HOST' => '127.0.0.1:54000', 'HTTP_ORIGIN' => 'http://localhost:54000', 'HTTP_SEC_FETCH_SITE' => 'same-origin'],
        false,
    ],
    [
        'different scheme is rejected',
        ['HTTP_HOST' => 'localhost:54000', 'HTTP_ORIGIN' => 'https://localhost:54000', 'HTTP_SEC_FETCH_SITE' => 'same-origin'],
        false,
    ],
    [
        'cross-site metadata is rejected even if host matches',
        ['HTTP_HOST' => 'localhost:54000', 'HTTP_ORIGIN' => 'http://localhost:54000', 'HTTP_SEC_FETCH_SITE' => 'cross-site'],
        false,
    ],
    [
        'same-site metadata is not enough',
        ['HTTP_HOST' => 'localhost:54000', 'HTTP_ORIGIN' => 'http://localhost:54000', 'HTTP_SEC_FETCH_SITE' => 'same-site'],
        false,
    ],
    [
        'missing Origin requires same-origin metadata',
        ['HTTP_HOST' => 'localhost:54000', 'HTTP_SEC_FETCH_SITE' => 'same-origin'],
        true,
    ],
    [
        'missing Origin and Fetch Metadata are rejected',
        ['HTTP_HOST' => 'localhost:54000'],
        false,
    ],
    [
        'malformed Origin is rejected',
        ['HTTP_HOST' => 'localhost:54000', 'HTTP_ORIGIN' => 'null', 'HTTP_SEC_FETCH_SITE' => 'same-origin'],
        false,
    ],
];

foreach ($cases as [$label, $server, $expected]) {
    expectSchedulerOrigin(
        isSameOriginRequest($server) === $expected,
        $label . ' (expected ' . ($expected ? 'allow' : 'reject') . ')'
    );
}

echo "PASS: scheduler same-origin validation checks scheme, host, port, and Fetch Metadata\n";
