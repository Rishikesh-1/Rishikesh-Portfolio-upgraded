<?php
require_once __DIR__ . '/../config/config.php';
require_admin_login();

$projects = $pdo->query(
  'SELECT p.*, c.name AS category_name, GROUP_CONCAT(t.name ORDER BY t.name SEPARATOR ", ") AS tag_names FROM projects p
     LEFT JOIN project_categories c ON c.id = p.category_id
   LEFT JOIN project_tags pt ON pt.project_id = p.id
   LEFT JOIN tags t ON t.id = pt.tag_id
   GROUP BY p.id
     ORDER BY p.sort_order ASC, p.created_at DESC'
)->fetchAll();

$catCount = (int)$pdo->query('SELECT COUNT(*) FROM project_categories')->fetchColumn();
$tagCount = (int)$pdo->query('SELECT COUNT(*) FROM tags')->fetchColumn();

$active = 'projects';
require __DIR__ . '/includes/admin-header.php';
?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-2);flex-wrap:wrap;gap:12px;">
  <h1 class="display" style="font-size:1.6rem;margin:0;">Projects</h1>
  <a href="add.php" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add project
  </a>
</div>

<!-- Project sub-nav tabs -->
<div style="display:flex;gap:8px;margin-bottom:var(--space-3);border-bottom:1px solid var(--hairline);padding-bottom:14px;flex-wrap:wrap;">
  <a href="projects.php" class="btn btn-primary btn-sm">All Projects (<?= count($projects) ?>)</a>
  <a href="taxonomy.php?type=categories" class="btn btn-secondary btn-sm">Project Categories (<?= $catCount ?>)</a>
  <a href="taxonomy.php?type=tags" class="btn btn-secondary btn-sm">Project Tags (<?= $tagCount ?>)</a>
</div>

<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Project deleted.</div><?php endif; ?>
<?php if (isset($_GET['added'])): ?><div class="alert alert-success">Project added successfully.</div><?php endif; ?>
<?php if (isset($_GET['updated'])): ?><div class="alert alert-success">Project updated successfully.</div><?php endif; ?>

<div class="admin-card" style="padding:0;">
  <table class="admin-table">
    <thead><tr><th>Title</th><th>Category</th><th>Tags</th><th>Featured</th><th>Visible</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($projects as $p): ?>
      <tr>
        <td><?= e($p['title']) ?></td>
        <td><?= e($p['category_name'] ?? '—') ?></td>
        <td><?= e($p['tag_names'] ?? '—') ?></td>
        <td><span class="badge <?= $p['is_featured'] ? 'badge-on' : 'badge-off' ?>"><?= $p['is_featured'] ? 'Yes' : 'No' ?></span></td>
        <td><span class="badge <?= $p['is_visible'] ? 'badge-on' : 'badge-off' ?>"><?= $p['is_visible'] ? 'Live' : 'Hidden' ?></span></td>
        <td>
          <a href="edit.php?id=<?= (int)$p['id'] ?>" style="color:var(--accent);margin-right:12px;">Edit</a>
          <form method="POST" action="delete.php" style="display:inline;" onsubmit="return confirm('Delete this project? This cannot be undone.');">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <button type="submit" style="background:none;border:0;padding:0;color:#ff7442;cursor:pointer;font:inherit;">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$projects): ?><tr><td colspan="6" style="color:var(--text-muted);">No projects yet. Add your first one.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/includes/admin-footer.php'; ?>
