<?php
require_once __DIR__ . '/../config/config.php';
require_admin_login();

$section = $_GET['section'] ?? '';
$definitions = [
    'experience' => [
        'title' => 'Experience',
        'table' => 'experience',
        'primary' => 'id',
        'order' => 'sort_order ASC, id DESC',
        'fields' => [
            'role_title' => ['label' => 'Role title', 'required' => true, 'maxlength' => 150],
            'organization' => ['label' => 'Organization', 'required' => true, 'maxlength' => 150],
            'location' => ['label' => 'Location', 'maxlength' => 120],
            'start_date' => ['label' => 'Start date', 'required' => true, 'maxlength' => 30],
            'end_date' => ['label' => 'End date', 'default' => 'Present', 'maxlength' => 30],
            'description' => ['label' => 'Description', 'type' => 'textarea'],
            'sort_order' => ['label' => 'Display order', 'type' => 'number', 'default' => 0],
            'is_visible' => ['label' => 'Visible on live site', 'type' => 'checkbox', 'default' => 1],
        ],
        'columns' => ['role_title', 'organization', 'start_date', 'is_visible'],
    ],
    'skills' => [
        'title' => 'Skills',
        'table' => 'skills',
        'primary' => 'id',
        'order' => 'sort_order ASC, id DESC',
        'fields' => [
            'skill_name' => ['label' => 'Skill name', 'required' => true, 'maxlength' => 100],
            'category' => ['label' => 'Category', 'default' => 'General', 'maxlength' => 80],
            'proficiency' => ['label' => 'Proficiency (%)', 'type' => 'number', 'default' => 80, 'min' => 0, 'max' => 100],
            'icon_class' => ['label' => 'Icon class', 'maxlength' => 80],
            'sort_order' => ['label' => 'Display order', 'type' => 'number', 'default' => 0],
        ],
        'columns' => ['skill_name', 'category', 'proficiency'],
    ],
    'services' => [
        'title' => 'Services',
        'table' => 'services',
        'primary' => 'id',
        'order' => 'sort_order ASC, id DESC',
        'fields' => [
            'title' => ['label' => 'Title', 'required' => true, 'maxlength' => 150],
            'description' => ['label' => 'Description', 'type' => 'textarea'],
            'price_label' => ['label' => 'Price label', 'maxlength' => 80],
            'icon_class' => ['label' => 'Icon class', 'maxlength' => 80],
            'sort_order' => ['label' => 'Display order', 'type' => 'number', 'default' => 0],
            'is_visible' => ['label' => 'Visible on live site', 'type' => 'checkbox', 'default' => 1],
        ],
        'columns' => ['title', 'price_label', 'is_visible'],
    ],
    'social' => [
        'title' => 'Social links',
        'table' => 'social_links',
        'primary' => 'id',
        'order' => 'sort_order ASC, id DESC',
        'fields' => [
            'platform' => ['label' => 'Platform', 'type' => 'select', 'options' => ['YouTube', 'Instagram', 'TikTok', 'LinkedIn', 'Facebook', 'Vimeo'], 'required' => true],
            'handle' => ['label' => 'Handle', 'maxlength' => 100],
            'url' => ['label' => 'URL', 'type' => 'url', 'required' => true, 'maxlength' => 255],
            'icon_class' => ['label' => 'Icon class', 'maxlength' => 80],
            'embed_code' => ['label' => 'Embed code', 'type' => 'textarea'],
            'followers_label' => ['label' => 'Followers label', 'maxlength' => 50],
            'sort_order' => ['label' => 'Display order', 'type' => 'number', 'default' => 0],
            'is_visible' => ['label' => 'Visible on live site', 'type' => 'checkbox', 'default' => 1],
        ],
        'columns' => ['platform', 'handle', 'url', 'is_visible'],
    ],
    'social_posts' => [
        'title' => 'Social content',
        'table' => 'social_posts',
        'primary' => 'id',
        'order' => 'is_featured DESC, sort_order ASC, id DESC',
        'fields' => [
            'platform' => ['label' => 'Platform', 'type' => 'select', 'options' => ['YouTube', 'Instagram', 'TikTok', 'LinkedIn', 'Vimeo'], 'required' => true],
            'title' => ['label' => 'Post or video title', 'required' => true, 'maxlength' => 180],
            'url' => ['label' => 'Post URL', 'type' => 'url', 'required' => true, 'maxlength' => 500],
            'embed_url' => ['label' => 'Video URL for inline player (YouTube or Vimeo)', 'type' => 'url', 'maxlength' => 500],
            'thumbnail_image' => ['label' => 'Upload thumbnail (JPG, PNG, or WEBP)', 'type' => 'image'],
            'thumbnail_url' => ['label' => 'Thumbnail URL', 'type' => 'url', 'maxlength' => 500],
            'description' => ['label' => 'Short description', 'type' => 'textarea'],
            'is_featured' => ['label' => 'Feature this post', 'type' => 'checkbox', 'default' => 0],
            'sort_order' => ['label' => 'Display order', 'type' => 'number', 'default' => 0],
            'is_visible' => ['label' => 'Visible on live site', 'type' => 'checkbox', 'default' => 1],
        ],
        'columns' => ['platform', 'title', 'is_featured', 'is_visible'],
    ],
    'testimonials' => [
        'title' => 'Testimonials',
        'table' => 'testimonials',
        'primary' => 'id',
        'order' => 'sort_order ASC, id DESC',
        'fields' => [
            'client_name' => ['label' => 'Client name', 'required' => true, 'maxlength' => 120],
            'client_role' => ['label' => 'Client role', 'maxlength' => 150],
            'client_photo' => ['label' => 'Client photo', 'type' => 'image'],
            'quote' => ['label' => 'Quote', 'type' => 'textarea', 'required' => true],
            'rating' => ['label' => 'Rating (1-5)', 'type' => 'number', 'default' => 5, 'min' => 1, 'max' => 5],
            'sort_order' => ['label' => 'Display order', 'type' => 'number', 'default' => 0],
            'is_visible' => ['label' => 'Visible on live site', 'type' => 'checkbox', 'default' => 1],
        ],
        'columns' => ['client_name', 'client_role', 'rating', 'is_visible'],
    ],
];

if (!isset($definitions[$section])) {
    http_response_code(404);
    exit('Section not found.');
}

$definition = $definitions[$section];
$table = $definition['table'];
$fields = $definition['fields'];
$primary = $definition['primary'];
$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);
$error = '';
$record = [];

if ($action === 'edit') {
    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE {$primary} = :id");
    $stmt->execute([':id' => $id]);
    $record = $stmt->fetch();
    if (!$record) {
        header('Location: manage.php?section=' . urlencode($section));
        exit;
    }
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE {$primary} = :id");
    $stmt->execute([':id' => $id]);
    $record = $stmt->fetch();
    if ($record) {
        foreach ($fields as $fieldName => $field) {
            if (($field['type'] ?? '') === 'image' && !empty($record[$fieldName])) {
                delete_uploaded_file($record[$fieldName]);
            }
        }
        $stmt = $pdo->prepare("DELETE FROM {$table} WHERE {$primary} = :id");
        $stmt->execute([':id' => $id]);
    }
    header('Location: manage.php?section=' . urlencode($section) . '&deleted=1');
    exit;
}

if (($action === 'add' || $action === 'edit') && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach ($fields as $name => $field) {
        if (($field['type'] ?? '') === 'checkbox') {
            $_POST[$name] = isset($_POST[$name]) ? 1 : 0;
        } elseif (($field['type'] ?? '') !== 'image') {
            $_POST[$name] = trim($_POST[$name] ?? '');
        }
    }

    foreach ($fields as $name => $field) {
        if (!empty($field['required']) && ($_POST[$name] ?? '') === '') {
            $error = $field['label'] . ' is required.';
            break;
        }
    }
    if (!$error && isset($_POST['proficiency']) && ((int)$_POST['proficiency'] < 0 || (int)$_POST['proficiency'] > 100)) {
        $error = 'Proficiency must be between 0 and 100.';
    }
    if (!$error && isset($_POST['rating']) && ((int)$_POST['rating'] < 1 || (int)$_POST['rating'] > 5)) {
        $error = 'Rating must be between 1 and 5.';
    }
    if (!$error && in_array($section, ['social', 'social_posts'], true) && !filter_var($_POST['url'], FILTER_VALIDATE_URL)) {
        $error = 'Please enter a valid URL.';
    }
    if (!$error && $section === 'social_posts' && !empty($_POST['thumbnail_url']) && !filter_var($_POST['thumbnail_url'], FILTER_VALIDATE_URL)) {
        $error = 'Please enter a valid thumbnail URL.';
    }
    if (!$error && $section === 'social_posts' && !empty($_POST['embed_url'])) {
        $safeEmbedUrl = social_embed_url($_POST['embed_url'], $_POST['platform']);
        if (!$safeEmbedUrl) {
            $error = 'Inline video supports YouTube URLs for YouTube posts and Vimeo URLs for Vimeo posts.';
        } else {
            $_POST['embed_url'] = $safeEmbedUrl;
        }
    }

    $newImage = null;
    if (!$error) {
        try {
            foreach ($fields as $fieldName => $field) {
                if (($field['type'] ?? '') === 'image') {
                    $newImage = handle_image_upload($_FILES[$fieldName] ?? [], $field['label']);
                    if ($newImage) break;
                }
            }

            $values = [];
            foreach ($fields as $name => $field) {
                if (($field['type'] ?? '') === 'image') {
                    $values[$name] = $newImage ?: ($record[$name] ?? null);
                } else {
                    $values[$name] = $_POST[$name] ?? null;
                }
            }

            if ($action === 'add') {
                $columns = implode(', ', array_keys($values));
                $placeholders = ':' . implode(', :', array_keys($values));
                $stmt = $pdo->prepare("INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})");
                foreach ($values as $name => $value) $stmt->bindValue(':' . $name, $value);
                $stmt->execute();
            } else {
                $assignments = implode(', ', array_map(static fn($name) => "{$name} = :{$name}", array_keys($values)));
                $stmt = $pdo->prepare("UPDATE {$table} SET {$assignments} WHERE {$primary} = :record_id");
                foreach ($values as $name => $value) $stmt->bindValue(':' . $name, $value);
                $stmt->bindValue(':record_id', $id, PDO::PARAM_INT);
                $stmt->execute();
                if ($newImage) {
                    foreach ($fields as $fieldName => $field) {
                        if (($field['type'] ?? '') === 'image' && !empty($record[$fieldName])) {
                            delete_uploaded_file($record[$fieldName]);
                        }
                    }
                }
            }

            header('Location: manage.php?section=' . urlencode($section) . '&saved=1');
            exit;
        } catch (RuntimeException $exception) {
            if ($newImage) delete_uploaded_file($newImage);
            $error = $exception->getMessage();
        }
    }
}

if ($action === 'add' || $action === 'edit') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        foreach ($fields as $name => $field) $record[$name] = $record[$name] ?? ($field['default'] ?? '');
    }
    $active = $section;
    require __DIR__ . '/includes/admin-header.php';
    ?>
    <h1 class="display" style="font-size:1.6rem;margin-bottom:var(--space-3);">
      <?= $action === 'add' ? 'Add ' : 'Edit ' ?><?= e($definition['title']) ?>
    </h1>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="POST" enctype="multipart/form-data" class="admin-card">
      <?= csrf_field() ?>
      <?php foreach ($fields as $name => $field):
          $type = $field['type'] ?? 'text';
          $value = $_POST[$name] ?? ($record[$name] ?? ($field['default'] ?? ''));
          $label = $field['label'];
          $maxlength = !empty($field['maxlength']) ? ' maxlength="' . (int)$field['maxlength'] . '"' : '';
          $min = isset($field['min']) ? ' min="' . (int)$field['min'] . '"' : '';
          $max = isset($field['max']) ? ' max="' . (int)$field['max'] . '"' : '';
      ?>
        <?php if ($type === 'checkbox'): ?>
          <div class="form-group"><label><input type="checkbox" name="<?= e($name) ?>" <?= $value ? 'checked' : '' ?> style="width:auto;display:inline;margin-right:8px;"> <?= e($label) ?></label></div>
                <?php elseif ($type === 'textarea'): ?>
          <div class="form-group"><label><?= e($label) ?></label><textarea name="<?= e($name) ?>" rows="6"<?= $maxlength ?>><?= e((string)$value) ?></textarea></div>
                <?php elseif ($type === 'select'): ?>
                    <div class="form-group"><label><?= e($label) ?></label><select name="<?= e($name) ?>"<?= !empty($field['required']) ? ' required' : '' ?>><?php foreach ($field['options'] as $option): ?><option value="<?= e($option) ?>" <?= (string)$value === (string)$option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select></div>
        <?php elseif ($type === 'image'): ?>
          <div class="form-group"><label><?= e($label) ?></label>
            <?php if ($value): ?><img src="<?= e(UPLOAD_URL . $value) ?>" alt="" style="width:100px;border-radius:4px;margin-bottom:8px;display:block;"><?php endif; ?>
            <input type="file" name="<?= e($name) ?>" accept=".jpg,.jpeg,.png,.webp">
          </div>
        <?php else: ?>
          <div class="form-group"><label><?= e($label) ?></label><input type="<?= e($type) ?>" name="<?= e($name) ?>" value="<?= e((string)$value) ?>"<?= $maxlength . $min . $max ?><?= !empty($field['required']) ? ' required' : '' ?>></div>
        <?php endif; ?>
      <?php endforeach; ?>
      <button type="submit" class="btn btn-primary"><?= $action === 'add' ? 'Save' : 'Update' ?></button>
      <a href="manage.php?section=<?= e($section) ?>" class="btn btn-ghost">Cancel</a>
    </form>
    <?php
    require __DIR__ . '/includes/admin-footer.php';
    exit;
}

$records = $pdo->query("SELECT * FROM {$table} ORDER BY {$definition['order']}")->fetchAll();
$active = $section;
require __DIR__ . '/includes/admin-header.php';
?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-3);">
  <h1 class="display" style="font-size:1.6rem;"><?= e($definition['title']) ?></h1>
  <a href="manage.php?section=<?= e($section) ?>&action=add" class="btn btn-primary">+ Add <?= e(rtrim($definition['title'], 's')) ?></a>
</div>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Saved.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Deleted.</div><?php endif; ?>
<div class="admin-card" style="padding:0;overflow:auto;">
  <table class="admin-table">
    <thead><tr><?php foreach ($definition['columns'] as $column): ?><th><?= e($fields[$column]['label']) ?></th><?php endforeach; ?><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($records as $row): ?>
      <tr>
        <?php foreach ($definition['columns'] as $column): ?>
          <td>
            <?php if (($fields[$column]['type'] ?? '') === 'checkbox'): ?><span class="badge <?= $row[$column] ? 'badge-on' : 'badge-off' ?>"><?= $row[$column] ? 'Yes' : 'No' ?></span>
            <?php elseif (($fields[$column]['type'] ?? '') === 'textarea'): ?><?= e(mb_strimwidth((string)$row[$column], 0, 100, '...')) ?>
            <?php else: ?><?= e((string)$row[$column]) ?><?php endif; ?>
          </td>
        <?php endforeach; ?>
        <td><a href="manage.php?section=<?= e($section) ?>&action=edit&id=<?= (int)$row[$primary] ?>" style="color:var(--accent);margin-right:12px;">Edit</a>
          <form method="POST" action="manage.php?section=<?= e($section) ?>&action=delete&id=<?= (int)$row[$primary] ?>" style="display:inline;" onsubmit="return confirm('Delete this item? This cannot be undone.');"><?= csrf_field() ?><button type="submit" style="background:none;border:0;padding:0;color:#ff7442;cursor:pointer;font:inherit;">Delete</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$records): ?><tr><td colspan="<?= count($definition['columns']) + 1 ?>" style="color:var(--text-muted);">No items yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/includes/admin-footer.php'; ?>
