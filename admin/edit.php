<?php
require_once __DIR__ . '/../config/config.php';
require_admin_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM projects WHERE id = :id');
$stmt->execute([':id' => $id]);
$project = $stmt->fetch();

if (!$project) {
    header('Location: projects.php');
    exit;
}

$categories = $pdo->query('SELECT * FROM project_categories ORDER BY name ASC')->fetchAll();
$tags = $pdo->query('SELECT * FROM tags ORDER BY name ASC')->fetchAll();
$selectedTags = $pdo->prepare('SELECT tag_id FROM project_tags WHERE project_id = :project_id');
$selectedTags->execute([':project_id' => $id]);
$selectedTagIds = array_map('intval', $selectedTags->fetchAll(PDO::FETCH_COLUMN));
$error = '';
$shortDescriptionError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title             = trim($_POST['title'] ?? '');
    $categoryId        = $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
    $shortDescription  = trim($_POST['short_description'] ?? '');
    $description       = trim($_POST['description'] ?? '');
    $externalUrl       = trim($_POST['external_url'] ?? '');
    $githubUrl         = trim($_POST['github_url'] ?? '');
    $isFeatured        = isset($_POST['is_featured']) ? 1 : 0;
    $isVisible         = isset($_POST['is_visible']) ? 1 : 0;
    $metaTitle         = trim($_POST['meta_title'] ?? '');
    $metaDescription   = trim($_POST['meta_description'] ?? '');
    $tagIds             = array_values(array_unique(array_map('intval', $_POST['tag_ids'] ?? [])));
    $removeImage       = isset($_POST['remove_image']);
    $shortDescriptionError = rich_text_word_count($shortDescription) > 20 ? 'Use 20 words or fewer for this preview.' : '';

    if ($title === '') {
        $error = 'Title is required.';
    } elseif ($shortDescriptionError !== '') {
      $error = $shortDescriptionError;
    } else {
        try {
            $coverImage = $project['cover_image'];
            $newUpload = handle_image_upload($_FILES['cover_image'] ?? [], 'cover image');

            if ($newUpload) {
                delete_uploaded_file($coverImage); // remove the old file
                $coverImage = $newUpload;
            } elseif ($removeImage) {
                delete_uploaded_file($coverImage);
                $coverImage = null;
            }

            $stmt = $pdo->prepare(
                'UPDATE projects SET
                    title = :title, category_id = :category_id, short_description = :short_description,
                    description = :description, cover_image = :cover_image, external_url = :external_url,
                    github_url = :github_url, is_featured = :is_featured, is_visible = :is_visible,
                    meta_title = :meta_title, meta_description = :meta_description
                 WHERE id = :id'
            );
            $stmt->execute([
                ':title'             => $title,
                ':category_id'       => $categoryId,
                ':short_description' => $shortDescription,
                ':description'       => $description,
                ':cover_image'       => $coverImage,
                ':external_url'      => $externalUrl,
                ':github_url'        => $githubUrl,
                ':is_featured'       => $isFeatured,
                ':is_visible'        => $isVisible,
                ':meta_title'        => $metaTitle,
                ':meta_description'  => $metaDescription,
                ':id'                => $id,
            ]);

              $pdo->prepare('DELETE FROM project_tags WHERE project_id = :project_id')->execute([':project_id' => $id]);
              $tagStmt = $pdo->prepare('INSERT INTO project_tags (project_id, tag_id) VALUES (:project_id, :tag_id)');
              foreach ($tagIds as $tagId) {
                $tagStmt->execute([':project_id' => $id, ':tag_id' => $tagId]);
              }

            header('Location: projects.php?updated=1');
            exit;
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
} else {
    // Pre-fill form fields from DB on first load.
    $_POST = $project;
    $_POST['is_featured'] = $project['is_featured'];
    $_POST['is_visible'] = $project['is_visible'];
    $_POST['tag_ids'] = $selectedTagIds;
}

$active = 'projects';
require __DIR__ . '/includes/admin-header.php';
?>
<h1 class="display" style="font-size:1.6rem;margin-bottom:var(--space-3);">Edit project</h1>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="admin-card">
  <?= csrf_field() ?>
  <div class="form-group">
    <label>Title</label>
    <input type="text" name="title" required maxlength="180" value="<?= e($_POST['title'] ?? '') ?>">
  </div>

  <div class="form-row">
    <div class="form-group">
      <label>Category</label>
      <select name="category_id">
        <option value="">— None —</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= (int)($_POST['category_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Cover image</label>
      <?php if ($project['cover_image']): ?>
        <img src="<?= e(UPLOAD_URL . $project['cover_image']) ?>" alt="" style="width:120px;border-radius:4px;margin-bottom:8px;">
        <label style="font-weight:400;"><input type="checkbox" name="remove_image" style="width:auto;display:inline;margin-right:6px;">Remove current image</label>
      <?php endif; ?>
      <input type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp">
    </div>
  </div>

  <div class="form-group">
    <label>Short description (20-word preview)</label>
    <input type="text" name="short_description" maxlength="300" value="<?= e($_POST['short_description'] ?? '') ?>" class="<?= $shortDescriptionError !== '' ? 'field-invalid' : '' ?>" <?= $shortDescriptionError !== '' ? 'aria-invalid="true" aria-describedby="short-description-error"' : '' ?>>
    <?php if ($shortDescriptionError !== ''): ?><p class="field-error" id="short-description-error" role="alert"><?= e($shortDescriptionError) ?></p><?php endif; ?>
  </div>

  <div class="form-group">
    <label>Project tags</label>
    <div class="form-row">
      <?php foreach ($tags as $tag): ?><label style="font-weight:400;"><input type="checkbox" name="tag_ids[]" value="<?= (int)$tag['id'] ?>" style="width:auto;display:inline;margin-right:6px;" <?= in_array((int)$tag['id'], array_map('intval', $_POST['tag_ids'] ?? []), true) ? 'checked' : '' ?>><?= e($tag['name']) ?></label><?php endforeach; ?>
    </div>
    <?php if (!$tags): ?><p style="color:var(--text-muted);">Create tags from Project tags in the sidebar first.</p><?php endif; ?>
  </div>

  <div class="form-group">
    <label>Full description</label>
    <textarea name="description" rows="6"><?= e($_POST['description'] ?? $project['description']) ?></textarea>
    <details class="form-guide">
      <summary>Formatting guide</summary>
      <p>Headings: <code>&lt;h2&gt;Section title&lt;/h2&gt;</code>. Lists: <code>&lt;ul&gt;&lt;li&gt;Item&lt;/li&gt;&lt;/ul&gt;</code>.</p>
      <p>Link: <code>&lt;a href="https://example.com"&gt;Link text&lt;/a&gt;</code>. HTTP/HTTPS and site-relative links are supported.</p>
    </details>
  </div>

  <div class="form-row">
    <div class="form-group">
      <label>External / live URL</label>
      <input type="url" name="external_url" value="<?= e($_POST['external_url'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label>GitHub URL</label>
      <input type="url" name="github_url" value="<?= e($_POST['github_url'] ?? '') ?>">
    </div>
  </div>

  <div class="form-row">
    <div class="form-group">
      <label><input type="checkbox" name="is_featured" <?= !empty($_POST['is_featured']) ? 'checked' : '' ?> style="width:auto;display:inline;margin-right:8px;"> Featured</label>
    </div>
    <div class="form-group">
      <label><input type="checkbox" name="is_visible" <?= !empty($_POST['is_visible']) ? 'checked' : '' ?> style="width:auto;display:inline;margin-right:8px;"> Visible on live site</label>
    </div>
  </div>

  <div class="form-group">
    <label>SEO meta title</label>
    <input type="text" name="meta_title" maxlength="180" value="<?= e($_POST['meta_title'] ?? '') ?>">
  </div>
  <div class="form-group">
    <label>SEO meta description</label>
    <input type="text" name="meta_description" maxlength="300" value="<?= e($_POST['meta_description'] ?? '') ?>">
  </div>

  <button type="submit" class="btn btn-primary">Update project</button>
  <a href="projects.php" class="btn btn-ghost">Cancel</a>
</form>
<?php require __DIR__ . '/includes/admin-footer.php'; ?>
