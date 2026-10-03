<?php
require_once __DIR__ . '/../config/config.php';
require_admin_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

verify_csrf();
$id = (int)($_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT cover_image FROM blog_posts WHERE id = :id');
$stmt->execute([':id' => $id]);
$post = $stmt->fetch();

if ($post) {
    delete_uploaded_file($post['cover_image']);
    $del = $pdo->prepare('DELETE FROM blog_posts WHERE id = :id');
    $del->execute([':id' => $id]);
}

header('Location: blog.php?deleted=1');
exit;
