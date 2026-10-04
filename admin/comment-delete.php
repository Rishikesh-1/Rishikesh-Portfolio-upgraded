<?php
require_once __DIR__ . '/../config/config.php';
require_admin_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

verify_csrf();
$id = (int)($_POST['id'] ?? 0);
$returnUrl = trim((string)($_POST['return_url'] ?? 'blog.php'));

// Prevent open redirect
if (!preg_match('/^[a-zA-Z0-9_\-\.\?=&]+(#[a-zA-Z0-9_\-]+)?$/', $returnUrl) || str_starts_with($returnUrl, '//') || str_contains($returnUrl, '://')) {
    $returnUrl = 'blog.php';
}

if ($id > 0) {
    $stmt = $pdo->prepare('DELETE FROM blog_comments WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

if (str_contains($returnUrl, '#')) {
    [$base, $hash] = explode('#', $returnUrl, 2);
    $separator = str_contains($base, '?') ? '&' : '?';
    $target = $base . $separator . 'deleted=1#' . $hash;
} else {
    $separator = str_contains($returnUrl, '?') ? '&' : '?';
    $target = $returnUrl . $separator . 'deleted=1';
}

header('Location: ' . $target);
exit;
