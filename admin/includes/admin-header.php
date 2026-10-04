<?php
require_once __DIR__ . '/inline-image-helper.php';
// Expects $active (string) to highlight the current sidebar link.
$active = $active ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — <?= e($active ?: 'Overview') ?></title>
<meta name="robots" content="noindex, nofollow">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Fraunces:wght@600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-shell">
  <aside class="admin-sidebar">
    <p class="display" style="font-size:1.1rem;margin-bottom:20px;">Dashboard</p>
    <a href="dashboard.php" class="<?= $active === 'overview' ? 'active' : '' ?>">Overview</a>
    <a href="projects.php" class="<?= $active === 'projects' ? 'active' : '' ?>">Projects</a>
    <a href="taxonomy.php?type=categories" class="<?= $active === 'taxonomy-categories' ? 'active' : '' ?>">Project categories</a>
    <a href="taxonomy.php?type=tags" class="<?= $active === 'taxonomy-tags' ? 'active' : '' ?>">Project tags</a>
    <a href="blog.php" class="<?= $active === 'blog' ? 'active' : '' ?>">Blog</a>
    <a href="manage.php?section=experience" class="<?= $active === 'experience' ? 'active' : '' ?>">Experience</a>
    <a href="manage.php?section=skills" class="<?= $active === 'skills' ? 'active' : '' ?>">Skills</a>
    <a href="manage.php?section=services" class="<?= $active === 'services' ? 'active' : '' ?>">Services</a>
    <a href="manage.php?section=social" class="<?= $active === 'social' ? 'active' : '' ?>">Social links</a>
    <a href="manage.php?section=social_posts" class="<?= $active === 'social_posts' ? 'active' : '' ?>">Social content</a>
    <a href="manage.php?section=testimonials" class="<?= $active === 'testimonials' ? 'active' : '' ?>">Testimonials</a>
    <a href="manage.php?section=clients" class="<?= $active === 'clients' ? 'active' : '' ?>">Clients &amp; Brands</a>
    <a href="messages.php" class="<?= $active === 'messages' ? 'active' : '' ?>">Messages</a>
    <a href="settings.php" class="<?= $active === 'settings' ? 'active' : '' ?>">Site settings</a>
    <a href="logs.php" class="<?= $active === 'logs' ? 'active' : '' ?>" style="display:flex;align-items:center;justify-content:space-between;">
      <span>Error logs</span>
      <?php if (defined('APP_ERROR_LOG') && file_exists(APP_ERROR_LOG) && filesize(APP_ERROR_LOG) > 0): ?>
        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#ff7373;" title="Errors logged"></span>
      <?php endif; ?>
    </a>
    <a href="logout.php" style="margin-top:20px;color:var(--accent);">Log out</a>
  </aside>
  <div class="admin-main">
