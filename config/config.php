<?php
/**
 * config.php — App-wide constants + secure session bootstrap.
 * Include this FIRST on every page (public and admin).
 */

// Set to false on production. Turning this on reveals error details.
define('APP_DEBUG', false);

define('SITE_ROOT_URL', 'http://localhost/portfolio-cms'); // no trailing slash; change for production
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_ROOT_URL . '/uploads/');
define('MAX_UPLOAD_BYTES', 3 * 1024 * 1024); // 3 MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'image/svg']);
define('ALLOWED_IMAGE_EXTS', ['jpg', 'jpeg', 'png', 'webp', 'svg']);

// Session timeout (seconds) for the admin dashboard.
define('ADMIN_SESSION_TIMEOUT', 30 * 60); // 30 minutes idle timeout

// Login throttling
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_MINUTES', 15);

if (session_status() === PHP_SESSION_NONE) {
    // Harden session cookie before starting the session.
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,   // true once you're on HTTPS (do this immediately on cPanel)
        'httponly' => true,      // JS can never read the session cookie
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

define('APP_LOG_DIR', __DIR__ . '/../logs/');
define('APP_ERROR_LOG', APP_LOG_DIR . 'error.log');

// Error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', APP_ERROR_LOG);

date_default_timezone_set('Asia/Kathmandu');

/**
 * Record a structured error entry to APP_ERROR_LOG.
 */
function app_log_error(string $level, string $message, string $file = '', int $line = 0, ?string $trace = null): void
{
    if (!is_dir(APP_LOG_DIR)) {
        @mkdir(APP_LOG_DIR, 0755, true);
    }
    $time = date('Y-m-d H:i:s');
    $method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
    $uri = $_SERVER['REQUEST_URI'] ?? '-';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '-';

    $entry = "=== [{$time}] [{$level}] [{$ip}] [{$method} {$uri}] ===\n";
    $entry .= "Message: {$message}\n";
    if ($file) {
        $entry .= "Location: {$file}:{$line}\n";
    }
    if ($trace) {
        $entry .= "Trace:\n{$trace}\n";
    }
    $entry .= "\n";

    @file_put_contents(APP_ERROR_LOG, $entry, FILE_APPEND | LOCK_EX);
}

set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    // Respect error suppression (@ operator)
    if (!(error_reporting() & $severity)) {
        return false;
    }
    $levels = [
        E_ERROR             => 'ERROR',
        E_WARNING           => 'WARNING',
        E_PARSE             => 'PARSE',
        E_NOTICE            => 'NOTICE',
        E_CORE_ERROR        => 'CORE_ERROR',
        E_CORE_WARNING      => 'CORE_WARNING',
        E_COMPILE_ERROR     => 'COMPILE_ERROR',
        E_COMPILE_WARNING   => 'COMPILE_WARNING',
        E_USER_ERROR        => 'USER_ERROR',
        E_USER_WARNING      => 'USER_WARNING',
        E_USER_NOTICE       => 'USER_NOTICE',
        E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
        E_DEPRECATED        => 'DEPRECATED',
        E_USER_DEPRECATED   => 'USER_DEPRECATED',
    ];
    $level = $levels[$severity] ?? ('ERROR_' . $severity);
    app_log_error($level, $message, $file, $line);

    // Return true when APP_DEBUG is false so PHP internal handler does not write a duplicate raw line
    return !APP_DEBUG;
});

set_exception_handler(function (Throwable $e): void {
    app_log_error('EXCEPTION', get_class($e) . ': ' . $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
    if (APP_DEBUG) {
        echo '<div style="font-family:sans-serif;padding:24px;background:#141d36;color:#f3f4f6;line-height:1.5;">';
        echo '<h2 style="color:#ff6b6b;margin-top:0;">Uncaught Exception: ' . htmlspecialchars(get_class($e), ENT_QUOTES, 'UTF-8') . '</h2>';
        echo '<p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
        echo '<p><strong>File:</strong> ' . htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8') . ':' . (int)$e->getLine() . '</p>';
        echo '<pre style="background:#0b1021;padding:12px;border-radius:6px;overflow-x:auto;">' . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>';
        echo '</div>';
    } else {
        http_response_code(500);
        echo '<!DOCTYPE html><html lang="en"><head><title>Something went wrong</title><style>body{background:#0b1021;color:#f3f4f6;font-family:sans-serif;display:grid;place-items:center;height:100vh;margin:0;}</style></head><body><div style="text-align:center;"><h1>Something went wrong</h1><p style="color:#94a3b8;">An unexpected error occurred. The site administrator has been notified.</p><a href="' . htmlspecialchars(SITE_ROOT_URL, ENT_QUOTES, 'UTF-8') . '" style="color:#5ce1ff;">Return home</a></div></body></html>';
    }
    exit(1);
});

register_shutdown_function(function (): void {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        app_log_error('FATAL', $error['message'], $error['file'], $error['line']);
    }
});

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/functions.php';
