<?php
require_once __DIR__ . '/../config/config.php';
require_admin_login();

$posts = $pdo->query('SELECT * FROM blog_posts ORDER BY created_at DESC')->fetchAll();
$active = 'blog';
require __DIR__ . '/includes/admin-header.php';
?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-3);">
  <h1 class="display" style="font-size:1.6rem;">Blog posts</h1>
  <a href="blog-add.php" class="btn btn-primary">+ Add blog post</a>
</div>

<?php if (isset($_GET['added'])): ?><div class="alert alert-success">Blog post added.</div><?php endif; ?>
<?php if (isset($_GET['updated'])): ?><div class="alert alert-success">Blog post updated.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Blog post deleted.</div><?php endif; ?>

<div class="admin-card" style="padding:0;">
  <table class="admin-table">
    <thead><tr><th>Title</th><th>Published</th><th>Date</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($posts as $post): ?>
      <tr>
        <td><?= e($post['title']) ?></td>
        <td><span class="badge <?= $post['is_published'] ? 'badge-on' : 'badge-off' ?>"><?= $post['is_published'] ? 'Live' : 'Draft' ?></span></td>
        <td style="color:var(--text-muted);font-size:0.85rem;"><?= $post['published_at'] ? e(date('M j, Y', strtotime($post['published_at']))) : '&mdash;' ?></td>
        <td>
          <a href="blog-edit.php?id=<?= (int)$post['id'] ?>" style="color:var(--accent);margin-right:12px;">Edit</a>
          <form method="POST" action="blog-delete.php" style="display:inline;" onsubmit="return confirm('Delete this blog post? This cannot be undone.');">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$post['id'] ?>">
            <button type="submit" style="background:none;border:0;padding:0;color:#ff7442;cursor:pointer;font:inherit;">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$posts): ?><tr><td colspan="4" style="color:var(--text-muted);">No blog posts yet. Add your first one.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/includes/admin-footer.php'; ?>
