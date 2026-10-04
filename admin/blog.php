<?php
require_once __DIR__ . '/../config/config.php';
require_admin_login();

$posts = $pdo->query(
    'SELECT p.*, COUNT(c.id) AS comment_count 
     FROM blog_posts p 
     LEFT JOIN blog_comments c ON c.post_id = p.id 
     GROUP BY p.id 
     ORDER BY p.created_at DESC'
)->fetchAll();
$active = 'blog';
require __DIR__ . '/includes/admin-header.php';
?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-3);flex-wrap:wrap;gap:10px;">
  <h1 class="display" style="font-size:1.6rem;">Blog posts</h1>
  <a href="blog-add.php" class="btn btn-primary">+ Add blog post</a>
</div>

<?php if (isset($_GET['added'])): ?><div class="alert alert-success">Blog post added.</div><?php endif; ?>
<?php if (isset($_GET['updated'])): ?><div class="alert alert-success">Blog post updated.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Blog post deleted.</div><?php endif; ?>

<div class="admin-card" style="padding:0;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Title</th>
        <th style="width:120px;">Status</th>
        <th style="width:120px;">Comments</th>
        <th style="width:140px;">Date</th>
        <th style="width:140px;text-align:right;">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($posts as $post): ?>
      <tr>
        <td>
          <a href="blog-edit.php?id=<?= (int)$post['id'] ?>" style="color:var(--text);font-weight:600;"><?= e($post['title']) ?></a>
        </td>
        <td><span class="badge <?= $post['is_published'] ? 'badge-on' : 'badge-off' ?>"><?= $post['is_published'] ? 'Live' : 'Draft' ?></span></td>
        <td>
          <?php $cc = (int)($post['comment_count'] ?? 0); ?>
          <?php if ($cc > 0): ?>
            <a href="blog-edit.php?id=<?= (int)$post['id'] ?>#comments" style="color:var(--accent);font-size:0.85rem;font-weight:600;"><?= $cc ?> comment<?= $cc === 1 ? '' : 's' ?></a>
          <?php else: ?>
            <span style="color:var(--text-muted);font-size:0.85rem;">0</span>
          <?php endif; ?>
        </td>
        <td style="color:var(--text-muted);font-size:0.85rem;"><?= $post['published_at'] ? e(date('M j, Y', strtotime($post['published_at']))) : '&mdash;' ?></td>
        <td style="text-align:right;">
          <a href="blog-edit.php?id=<?= (int)$post['id'] ?>" style="color:var(--accent);margin-right:12px;">Edit</a>
          <form method="POST" action="blog-delete.php" style="display:inline;" onsubmit="return confirm('Delete this blog post? This cannot be undone.');">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$post['id'] ?>">
            <button type="submit" style="background:none;border:0;padding:0;color:#ff7442;cursor:pointer;font:inherit;">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$posts): ?><tr><td colspan="5" style="color:var(--text-muted);text-align:center;padding:24px 12px;">No blog posts yet. Add your first one.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/includes/admin-footer.php'; ?>
