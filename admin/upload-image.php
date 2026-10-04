<?php
/**
 * upload-image.php — Asynchronous inline image uploader for admin content editor.
 * Securely authenticates admin session, checks CSRF, and stores image via handle_image_upload.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_admin_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Your session has expired. Please refresh the page and log in again.']);
    exit;
}

$token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Security token check failed. Please refresh the page.']);
    exit;
}

if (empty($_FILES['image']) || !is_array($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No image file was received.']);
    exit;
}

try {
    $filename = handle_image_upload($_FILES['image'], 'Content image');
    if (!$filename) {
        throw new RuntimeException('Failed to process uploaded image.');
    }

    $relativeUrl = 'uploads/' . $filename;
    $fullUrl = UPLOAD_URL . $filename;

    echo json_encode([
        'success' => true,
        'filename' => $filename,
        'url' => $relativeUrl,
        'full_url' => $fullUrl,
        'admin_preview_url' => '../' . $relativeUrl,
    ]);
} catch (RuntimeException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    app_log_error('ERROR', 'Inline image upload error: ' . $e->getMessage(), $e->getFile(), $e->getLine());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An unexpected server error occurred while uploading.']);
}
