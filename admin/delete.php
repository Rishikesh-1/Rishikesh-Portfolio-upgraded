<?php
require_once __DIR__ . '/../config/config.php';
require_admin_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

verify_csrf();
$id = (int)($_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT cover_image FROM projects WHERE id = :id');
$stmt->execute([':id' => $id]);
$project = $stmt->fetch();

if ($project) {
    delete_uploaded_file($project['cover_image']);
    $del = $pdo->prepare('DELETE FROM projects WHERE id = :id');
    $del->execute([':id' => $id]);
}

header('Location: projects.php?deleted=1');
exit;
