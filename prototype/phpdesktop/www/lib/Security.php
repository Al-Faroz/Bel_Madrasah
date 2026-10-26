<?php
declare(strict_types=1);

/**
 * Create or retrieve the session-backed CSRF token used by state-changing forms.
 */
function csrfToken(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE && !session_start()) {
        throw new RuntimeException('Sesi keamanan tidak dapat dimulai.');
    }

    $token = $_SESSION['bel_madrasah_csrf_token'] ?? null;
    if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
        $token = bin2hex(random_bytes(32));
        $_SESSION['bel_madrasah_csrf_token'] = $token;
    }

    return $token;
}

/**
 * Validate a submitted token against the token stored in the current session.
 */
function isValidCsrfToken(mixed $submittedToken): bool
{
    if (!is_string($submittedToken) || $submittedToken === '' || session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }

    $expectedToken = $_SESSION['bel_madrasah_csrf_token'] ?? null;
    if (!is_string($expectedToken) || !preg_match('/^[a-f0-9]{64}$/', $expectedToken)) {
        return false;
    }

    return hash_equals($expectedToken, $submittedToken);
}

/**
 * Enforce CSRF protection on POST requests and return the token for the view.
 */
function enforceCsrfForPost(): string
{
    $token = csrfToken();
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

    if ($method === 'POST') {
        $submittedToken = $_POST['_csrf_token'] ?? null;
        if (!isValidCsrfToken($submittedToken)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            exit('Permintaan ditolak karena token keamanan tidak valid. Muat ulang halaman lalu coba lagi.');
        }
    }

    return $token;
}


/**
 * Validate browser same-origin metadata for internal scheduler requests.
 *
 * Unlike a hostname-only comparison, this treats scheme and port as part of
 * the origin. Requests without Origin are accepted only when Fetch Metadata
 * explicitly identifies them as same-origin.
 *
 * @param array<string, mixed> $server Server variables (injectable for tests).
 */
function isSameOriginRequest(array $server): bool
{
    $fetchSite = strtolower(trim((string) ($server['HTTP_SEC_FETCH_SITE'] ?? '')));
    if ($fetchSite !== '' && $fetchSite !== 'same-origin') {
        return false;
    }

    $origin = trim((string) ($server['HTTP_ORIGIN'] ?? ''));
    if ($origin === '') {
        return $fetchSite === 'same-origin';
    }

    $requestHost = trim((string) ($server['HTTP_HOST'] ?? ''));
    if ($requestHost === '') {
        return false;
    }

    $requestScheme = (!empty($server['HTTPS']) && strtolower((string) $server['HTTPS']) !== 'off')
        ? 'https'
        : 'http';
    $originParts = parse_url($origin);
    $requestParts = parse_url($requestScheme . '://' . $requestHost);

    if (!is_array($originParts) || !is_array($requestParts)
        || !isset($originParts['scheme'], $originParts['host'], $requestParts['scheme'], $requestParts['host'])
        || isset($originParts['user']) || isset($originParts['pass'])
        || isset($originParts['query']) || isset($originParts['fragment'])
        || (isset($originParts['path']) && $originParts['path'] !== '' && $originParts['path'] !== '/')) {
        return false;
    }

    $originScheme = strtolower((string) $originParts['scheme']);
    $originHost = strtolower((string) $originParts['host']);
    $requestHostName = strtolower((string) $requestParts['host']);
    if (!in_array($originScheme, ['http', 'https'], true)
        || $originScheme !== strtolower((string) $requestParts['scheme'])
        || $originHost !== $requestHostName) {
        return false;
    }

    $defaultPort = static fn (string $scheme): int => strtolower($scheme) === 'https' ? 443 : 80;
    $originPort = isset($originParts['port'])
        ? (int) $originParts['port']
        : $defaultPort($originScheme);
    $requestPort = isset($requestParts['port'])
        ? (int) $requestParts['port']
        : $defaultPort((string) $requestParts['scheme']);

    return $originPort === $requestPort;
}
