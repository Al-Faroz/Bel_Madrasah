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
    if (!is_string($submittedToken) || $submittedToken === '') {
        return false;
    }

    return hash_equals(csrfToken(), $submittedToken);
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
        if (!is_string($submittedToken) || !hash_equals($token, $submittedToken)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            exit('Permintaan ditolak karena token keamanan tidak valid. Muat ulang halaman lalu coba lagi.');
        }
    }

    return $token;
}
