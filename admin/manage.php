<?php
require_once __DIR__ . '/../config/config.php';
require_admin_login();

$section = $_GET['section'] ?? '';
if ($section === 'clients') {
    ensure_clients_schema($pdo);
} elseif ($section === 'services') {
    ensure_services_schema($pdo);
}
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
            'sort_order' => ['label' => 'Display order (1 = first, 2 = second...)', 'type' => 'number', 'default' => 0],
            'is_visible' => ['label' => 'Visible on live site', 'type' => 'checkbox', 'default' => 1],
        ],
        'columns' => ['sort_order', 'role_title', 'organization', 'start_date', 'is_visible'],
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
            'title' => ['label' => 'Service Title', 'required' => true, 'maxlength' => 150],
            'slug' => ['label' => 'URL Slug (e.g. social-media-management, leave empty to auto-generate)', 'maxlength' => 180],
            'tagline' => ['label' => 'Tagline / Value Proposition', 'maxlength' => 255],
            'description' => ['label' => 'Short Summary (Shown on homepage cards)', 'type' => 'textarea'],
            'cover_image' => ['label' => 'Cover Image / Showcase Graphic', 'type' => 'image'],
            'icon_class' => ['label' => 'Service Category Icon', 'type' => 'select', 'options' => array_keys(service_icon_options())],
            'price_label' => ['label' => 'Starting Price (e.g. NPR 10,000 / mo, From $499)', 'maxlength' => 80],
            'turnaround' => ['label' => 'Delivery Timeline (e.g. 2–3 weeks, Monthly retainer)', 'maxlength' => 80],
            'deliverables' => ['label' => 'Key Deliverables (One per line with "-" or "•")', 'type' => 'textarea'],
            'tools' => ['label' => 'Tools & Platforms (Comma-separated: Premiere Pro, Canva, Meta Suite)', 'maxlength' => 255],
            'overview' => ['label' => 'In-Depth Overview & Scope (Shown on service detail page)', 'type' => 'textarea'],
            'is_featured' => ['label' => 'Featured service (Highlighted card with badge)', 'type' => 'checkbox', 'default' => 0],
            'is_visible' => ['label' => 'Visible on live site', 'type' => 'checkbox', 'default' => 1],
            'sort_order' => ['label' => 'Display order', 'type' => 'number', 'default' => 0],
        ],
        'columns' => ['cover_image', 'title', 'price_label', 'is_featured', 'is_visible'],
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
    'clients' => [
        'title' => 'Clients & Brands',
        'table' => 'clients',
        'primary' => 'id',
        'order' => 'sort_order ASC, id DESC',
        'fields' => [
            'name' => ['label' => 'Company / Brand name', 'required' => true, 'maxlength' => 150],
            'logo_image' => ['label' => 'Logo image (PNG, SVG, WEBP, JPEG, JPG)', 'type' => 'image', 'required' => true],
            'website_url' => ['label' => 'Website URL (optional)', 'type' => 'url', 'maxlength' => 255],
            'sort_order' => ['label' => 'Display order (1 = first, 2 = second...)', 'type' => 'number', 'default' => 0],
            'is_visible' => ['label' => 'Visible on live site', 'type' => 'checkbox', 'default' => 1],
        ],
        'columns' => ['logo_image', 'name', 'website_url', 'sort_order', 'is_visible'],
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
        if (!empty($field['required'])) {
            if (($field['type'] ?? '') === 'image') {
                $hasUploadedFile = !empty($_FILES[$name]['name']) && ($_FILES[$name]['error'] === UPLOAD_ERR_OK);
                $hasExistingFile = !empty($record[$name]);
                $hasLibraryFile = !empty($_POST[$name . '_existing']) && is_file(UPLOAD_DIR . basename($_POST[$name . '_existing']));
                if (!$hasUploadedFile && !$hasExistingFile && !$hasLibraryFile) {
                    $error = $field['label'] . ' is required.';
                    break;
                }
            } else {
                if (($_POST[$name] ?? '') === '') {
                    $error = $field['label'] . ' is required.';
                    break;
                }
            }
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

    if (!$error && $section === 'services') {
        $rawSlug = trim($_POST['slug'] ?? '');
        if ($rawSlug === '' && !empty($_POST['title'])) {
            $rawSlug = make_slug($_POST['title']);
        } elseif ($rawSlug !== '') {
            $rawSlug = make_slug($rawSlug);
        }
        if ($rawSlug !== '') {
            $slugCheck = $pdo->prepare("SELECT id FROM services WHERE slug = :s AND id != :id LIMIT 1");
            $slugCheck->execute([':s' => $rawSlug, ':id' => $id]);
            if ($slugCheck->fetch()) {
                $rawSlug .= '-' . time();
            }
            $_POST['slug'] = $rawSlug;
        }
    }

    $newImages = [];
    if (!$error) {
        try {
            foreach ($fields as $fieldName => $field) {
                if (($field['type'] ?? '') === 'image') {
                    $uploaded = handle_image_upload($_FILES[$fieldName] ?? [], $field['label']);
                    if ($uploaded) {
                        $newImages[$fieldName] = $uploaded;
                    } elseif (!empty($_POST[$fieldName . '_existing'])) {
                        $chosen = basename(trim($_POST[$fieldName . '_existing']));
                        if (is_file(UPLOAD_DIR . $chosen)) {
                            $newImages[$fieldName] = $chosen;
                        }
                    }
                }
            }

            $values = [];
            foreach ($fields as $name => $field) {
                if (($field['type'] ?? '') === 'image') {
                    $values[$name] = $newImages[$name] ?? ($record[$name] ?? null);
                } elseif (($field['type'] ?? '') === 'number') {
                    $values[$name] = isset($_POST[$name]) && $_POST[$name] !== '' ? (int)$_POST[$name] : ($field['default'] ?? 0);
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
                $savedId = (int)$pdo->lastInsertId();
            } else {
                $assignments = implode(', ', array_map(static fn($name) => "{$name} = :{$name}", array_keys($values)));
                $stmt = $pdo->prepare("UPDATE {$table} SET {$assignments} WHERE {$primary} = :record_id");
                foreach ($values as $name => $value) $stmt->bindValue(':' . $name, $value);
                $stmt->bindValue(':record_id', $id, PDO::PARAM_INT);
                $stmt->execute();
                $savedId = $id;
            }

            // Sync related projects for services
            if ($section === 'services' && $savedId) {
                $selectedProjects = isset($_POST['linked_projects']) && is_array($_POST['linked_projects'])
                    ? array_map('intval', $_POST['linked_projects'])
                    : [];
                $delStmt = $pdo->prepare("DELETE FROM service_projects WHERE service_id = :sid");
                $delStmt->execute([':sid' => $savedId]);
                if (!empty($selectedProjects)) {
                    $insProj = $pdo->prepare("INSERT IGNORE INTO service_projects (service_id, project_id) VALUES (:sid, :pid)");
                    foreach ($selectedProjects as $pid) {
                        $insProj->execute([':sid' => $savedId, ':pid' => $pid]);
                    }
                }
            }

            header('Location: manage.php?section=' . urlencode($section) . '&saved=1');
            exit;
        } catch (RuntimeException $exception) {
            foreach ($newImages as $img) {
                if ($img) delete_uploaded_file($img);
            }
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
            <?php if ($value): ?>
              <div style="margin-bottom:8px;">
                <span style="font-size:0.78rem;color:var(--text-muted);display:block;margin-bottom:4px;">Current image:</span>
                <img src="<?= e(UPLOAD_URL . $value) ?>" alt="" style="max-height:80px;max-width:160px;object-fit:contain;border-radius:4px;display:block;background:rgba(255,255,255,0.05);padding:6px;border:1px solid var(--hairline);">
              </div>
            <?php endif; ?>

            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:8px;">
              <button type="button" class="btn btn-secondary btn-sm" onclick="chooseFromMediaLibrary('<?= e($name) ?>', '<?= e(addslashes($label)) ?>')" style="display:inline-flex;align-items:center;gap:6px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                📁 Choose from Media Library
              </button>
              <span class="badge" id="<?= e($name) ?>_selected_badge" style="display:none;background:rgba(92,225,255,0.15);color:var(--accent);border:1px solid rgba(92,225,255,0.3);padding:4px 8px;border-radius:4px;font-size:0.8rem;"></span>
              <button type="button" class="btn btn-ghost btn-sm" id="<?= e($name) ?>_clear_btn" style="display:none;color:#ff6b6b;font-size:0.8rem;padding:3px 8px;" onclick="clearMediaSelection('<?= e($name) ?>')">✕ Clear</button>
            </div>

            <div id="<?= e($name) ?>_preview_wrap" style="display:none;margin-bottom:8px;align-items:center;gap:10px;">
              <img id="<?= e($name) ?>_preview_img" src="" alt="" style="max-height:80px;max-width:160px;object-fit:contain;border-radius:4px;display:block;background:rgba(255,255,255,0.05);padding:6px;border:1px solid var(--accent);">
            </div>

            <input type="hidden" name="<?= e($name) ?>_existing" id="<?= e($name) ?>_existing" value="">
            <input type="file" id="<?= e($name) ?>_file_input" name="<?= e($name) ?>" accept=".jpg,.jpeg,.png,.webp,.svg,.gif" onchange="clearMediaSelection('<?= e($name) ?>')">
            <small style="color:var(--text-muted);display:block;margin-top:4px;">Upload from your computer or pick an existing image from the Media Library. Formats: JPEG, PNG, SVG, WebP, GIF.</small>
          </div>
        <?php else: ?>
          <div class="form-group"><label><?= e($label) ?></label><input type="<?= e($type) ?>" name="<?= e($name) ?>" value="<?= e((string)$value) ?>"<?= $maxlength . $min . $max ?><?= !empty($field['required']) ? ' required' : '' ?>></div>
        <?php endif; ?>
      <?php endforeach; ?>

      <?php if ($section === 'services'): 
          $allProjects = $pdo->query("SELECT id, title, category FROM projects ORDER BY sort_order ASC, id DESC")->fetchAll();
          $linkedProjectIds = [];
          if ($action === 'edit' && $id) {
              $pStmt = $pdo->prepare("SELECT project_id FROM service_projects WHERE service_id = :sid");
              $pStmt->execute([':sid' => $id]);
              $linkedProjectIds = $pStmt->fetchAll(PDO::FETCH_COLUMN);
          }
      ?>
        <div class="form-group" style="margin-top:28px;padding-top:20px;border-top:1px solid var(--hairline);">
          <label style="font-size:1.05rem;font-weight:700;margin-bottom:6px;display:block;">Link Related Projects / Case Studies</label>
          <p style="color:var(--text-muted);font-size:0.86rem;margin:0 0 14px;line-height:1.5;">Check the projects you've completed under this service. They will be highlighted in the "Featured Work & Proven Results" section on the service page.</p>
          <?php if (empty($allProjects)): ?>
            <p style="color:var(--text-muted);font-style:italic;">No projects found yet. You can add projects in the <a href="projects.php" style="color:var(--accent);">Projects</a> section.</p>
          <?php else: ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:12px;">
              <?php foreach ($allProjects as $proj): 
                  $isLinked = in_array((int)$proj['id'], array_map('intval', $linkedProjectIds), true);
              ?>
                <label style="display:flex;align-items:center;gap:12px;padding:12px 14px;border:1px solid <?= $isLinked ? 'var(--accent)' : 'var(--hairline)' ?>;border-radius:10px;background:rgba(255,255,255,0.03);cursor:pointer;transition:border-color .2s ease;">
                  <input type="checkbox" name="linked_projects[]" value="<?= (int)$proj['id'] ?>" <?= $isLinked ? 'checked' : '' ?> style="width:auto;margin:0;">
                  <div style="min-width:0;">
                    <div style="font-weight:600;font-size:0.92rem;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($proj['title']) ?></div>
                    <?php if (!empty($proj['category'])): ?><div style="font-size:0.75rem;color:var(--accent);"><?= e($proj['category']) ?></div><?php endif; ?>
                  </div>
                </label>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

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
            <?php elseif (($fields[$column]['type'] ?? '') === 'image'): ?>
              <?php if (!empty($row[$column])): ?>
                <img src="<?= e(UPLOAD_URL . $row[$column]) ?>" alt="" style="max-height:36px;max-width:90px;object-fit:contain;vertical-align:middle;background:rgba(255,255,255,0.06);padding:3px;border-radius:4px;">
              <?php else: ?>
                <span style="color:var(--text-muted);">&mdash;</span>
              <?php endif; ?>
            <?php elseif (($fields[$column]['type'] ?? '') === 'textarea'): ?><?= e(mb_strimwidth((string)$row[$column], 0, 100, '...')) ?>
            <?php else: ?><?= e((string)$row[$column]) ?><?php endif; ?>
          </td>
        <?php endforeach; ?>
        <td>
          <?php if ($section === 'services' && !empty($row['slug'])): ?>
            <a href="../service.php?slug=<?= urlencode($row['slug']) ?>" target="_blank" style="color:var(--accent);margin-right:12px;font-weight:600;">View ↗</a>
          <?php endif; ?>
          <a href="manage.php?section=<?= e($section) ?>&action=edit&id=<?= (int)$row[$primary] ?>" style="color:var(--accent);margin-right:12px;">Edit</a>
          <form method="POST" action="manage.php?section=<?= e($section) ?>&action=delete&id=<?= (int)$row[$primary] ?>" style="display:inline;" onsubmit="return confirm('Delete this item? This cannot be undone.');"><?= csrf_field() ?><button type="submit" style="background:none;border:0;padding:0;color:#ff7442;cursor:pointer;font:inherit;">Delete</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$records): ?><tr><td colspan="<?= count($definition['columns']) + 1 ?>" style="color:var(--text-muted);">No items yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/includes/admin-footer.php'; ?>
