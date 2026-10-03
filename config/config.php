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
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_IMAGE_EXTS', ['jpg', 'jpeg', 'png', 'webp']);

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

error_reporting(APP_DEBUG ? E_ALL : 0);
ini_set('display_errors', APP_DEBUG ? '1' : '0');

date_default_timezone_set('Asia/Kathmandu');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/functions.php';
