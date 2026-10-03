<?php
require_once __DIR__ . '/../config/config.php';
require_admin_login();

$type = $_GET['type'] ?? 'categories';
$definitions = [
    'categories' => [
        'title' => 'Project categories',
        'table' => 'project_categories',
        'name_label' => 'Category name',
        'slug_label' => 'Category slug',
    ],
    'tags' => [
        'title' => 'Project tags',
        'table' => 'tags',
        'name_label' => 'Tag name',
        'slug_label' => null,
    ],
];
if (!isset($definitions[$type])) {
    http_response_code(404);
    exit('Taxonomy not found.');
}

$definition = $definitions[$type];
$table = $definition['table'];
$error = '';
$editId = (int)($_GET['edit'] ?? 0);
$editRecord = null;

if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE id = :id");
    $stmt->execute([':id' => $editId]);
    $editRecord = $stmt->fetch();
    if (!$editRecord) $editId = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM {$table} WHERE id = :id");
        $stmt->execute([':id' => $id]);
        header('Location: taxonomy.php?type=' . urlencode($type) . '&deleted=1');
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $slug = make_slug(trim($_POST['slug'] ?? $name));
    if ($name === '') {
        $error = 'Name is required.';
    } elseif ($type === 'categories' && $slug === '') {
        $error = 'A valid slug is required.';
    } else {
        try {
            if ($type === 'categories') {
                $slugCheck = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE slug = :slug AND id <> :id");
                $slugCheck->execute([':slug' => $slug, ':id' => $id]);
                if ((int)$slugCheck->fetchColumn() > 0) throw new RuntimeException('That category slug is already in use.');
            }
            if ($id) {
                if ($type === 'categories') {
                    $stmt = $pdo->prepare("UPDATE {$table} SET name = :name, slug = :slug WHERE id = :id");
                    $stmt->execute([':name' => $name, ':slug' => $slug, ':id' => $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE {$table} SET name = :name WHERE id = :id");
                    $stmt->execute([':name' => $name, ':id' => $id]);
                }
            } elseif ($type === 'categories') {
                $stmt = $pdo->prepare("INSERT INTO {$table} (name, slug) VALUES (:name, :slug)");
                $stmt->execute([':name' => $name, ':slug' => $slug]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO {$table} (name) VALUES (:name)");
                $stmt->execute([':name' => $name]);
            }
            header('Location: taxonomy.php?type=' . urlencode($type) . '&saved=1');
            exit;
        } catch (PDOException | RuntimeException $exception) {
            $error = $exception instanceof PDOException && $exception->getCode() === '23000'
                ? 'That name is already in use.'
                : $exception->getMessage();
        }
    }
}

$records = $pdo->query("SELECT * FROM {$table} ORDER BY name ASC")->fetchAll();
$active = 'taxonomy-' . $type;
require __DIR__ . '/includes/admin-header.php';
?>
<h1 class="display" style="font-size:1.6rem;margin-bottom:var(--space-3);"> <?= e($definition['title']) ?></h1>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Saved.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Deleted.</div><?php endif; ?>

<form method="POST" class="admin-card" style="margin-bottom:var(--space-3);">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save">
  <input type="hidden" name="id" value="<?= (int)($editRecord['id'] ?? 0) ?>">
  <div class="form-row">
    <div class="form-group"><label><?= e($definition['name_label']) ?></label><input type="text" name="name" required maxlength="100" value="<?= e($_POST['name'] ?? ($editRecord['name'] ?? '')) ?>"></div>
    <?php if ($definition['slug_label']): ?><div class="form-group"><label><?= e($definition['slug_label']) ?></label><input type="text" name="slug" maxlength="100" value="<?= e($_POST['slug'] ?? ($editRecord['slug'] ?? '')) ?>"></div><?php endif; ?>
  </div>
  <button type="submit" class="btn btn-primary"><?= $editRecord ? 'Update' : 'Add' ?></button>
  <?php if ($editRecord): ?><a href="taxonomy.php?type=<?= e($type) ?>" class="btn btn-ghost">Cancel</a><?php endif; ?>
</form>

<div class="admin-card" style="padding:0;">
  <table class="admin-table"><thead><tr><th>Name</th><?php if ($definition['slug_label']): ?><th>Slug</th><?php endif; ?><th>Actions</th></tr></thead><tbody>
  <?php foreach ($records as $record): ?><tr><td><?= e($record['name']) ?></td><?php if ($definition['slug_label']): ?><td><?= e($record['slug']) ?></td><?php endif; ?><td><a href="taxonomy.php?type=<?= e($type) ?>&edit=<?= (int)$record['id'] ?>" style="color:var(--accent);margin-right:12px;">Edit</a><form method="POST" style="display:inline;" onsubmit="return confirm('Delete this item?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$record['id'] ?>"><button type="submit" style="background:none;border:0;padding:0;color:#ff7442;cursor:pointer;font:inherit;">Delete</button></form></td></tr><?php endforeach; ?>
  <?php if (!$records): ?><tr><td colspan="<?= $definition['slug_label'] ? 3 : 2 ?>" style="color:var(--text-muted);">No items yet.</td></tr><?php endif; ?>
  </tbody></table>
</div>
<?php require __DIR__ . '/includes/admin-footer.php'; ?>
