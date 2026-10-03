<?php
require_once __DIR__ . '/../config/config.php';
require_admin_login();

$counts = [
    'Projects'      => $pdo->query('SELECT COUNT(*) c FROM projects')->fetch()['c'],
    'Blog posts'    => $pdo->query('SELECT COUNT(*) c FROM blog_posts')->fetch()['c'],
    'Testimonials'  => $pdo->query('SELECT COUNT(*) c FROM testimonials')->fetch()['c'],
    'Unread messages' => $pdo->query('SELECT COUNT(*) c FROM contact_messages WHERE is_read = 0')->fetch()['c'],
];

$active = 'overview';
require __DIR__ . '/includes/admin-header.php';
?>
<h1 class="display" style="font-size:1.8rem;margin-bottom:6px;">Welcome back, <?= e($_SESSION['admin_username']) ?></h1>
<p style="color:var(--text-muted);margin-bottom:var(--space-4);">Everything on the live site is controlled from here.</p>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:var(--space-4);">
  <?php foreach ($counts as $label => $count): ?>
    <div class="admin-card">
      <p style="color:var(--text-muted);font-size:0.85rem;"><?= e($label) ?></p>
      <p class="display" style="font-size:2rem;margin-top:6px;"><?= (int)$count ?></p>
    </div>
  <?php endforeach; ?>
</div>

<div class="admin-card">
  <h3 style="margin-bottom:12px;">Quick actions</h3>
  <p style="margin-bottom:8px;"><a href="add.php" style="color:var(--accent);">+ Add a new project</a></p>
  <p style="margin-bottom:8px;"><a href="blog.php" style="color:var(--accent);">+ Write a blog post</a></p>
  <p><a href="../" target="_blank" style="color:var(--accent);">View live site &rarr;</a></p>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
