<?php
require_once __DIR__ . '/../config/config.php';
require_admin_login();

$categories = $pdo->query('SELECT * FROM project_categories ORDER BY name ASC')->fetchAll();
$tags = $pdo->query('SELECT * FROM tags ORDER BY name ASC')->fetchAll();
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
    $tagIds            = array_values(array_unique(array_map('intval', $_POST['tag_ids'] ?? [])));
    $shortDescriptionError = rich_text_word_count($shortDescription) > 20 ? 'Use 20 words or fewer for this preview.' : '';

    if ($title === '') {
        $error = 'Title is required.';
    } elseif ($shortDescriptionError !== '') {
      $error = $shortDescriptionError;
    } else {
        try {
            $coverImage = handle_image_upload($_FILES['cover_image'] ?? [], 'cover image');
            if (!$coverImage && !empty($_POST['cover_image_existing'])) {
                $cand = basename(trim($_POST['cover_image_existing']));
                if (is_file(UPLOAD_DIR . $cand)) {
                    $coverImage = $cand;
                }
            }

            // Build a unique slug.
            $baseSlug = make_slug($title);
            $slug = $baseSlug;
            $i = 1;
            $check = $pdo->prepare('SELECT COUNT(*) c FROM projects WHERE slug = :slug');
            do {
                $check->execute([':slug' => $slug]);
                if ($check->fetch()['c'] == 0) break;
                $slug = $baseSlug . '-' . (++$i);
            } while (true);

            $stmt = $pdo->prepare(
                'INSERT INTO projects
                 (title, slug, category_id, short_description, description, cover_image, external_url, github_url, is_featured, is_visible, meta_title, meta_description)
                 VALUES (:title, :slug, :category_id, :short_description, :description, :cover_image, :external_url, :github_url, :is_featured, :is_visible, :meta_title, :meta_description)'
            );
            $stmt->execute([
                ':title'             => $title,
                ':slug'              => $slug,
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
            ]);

              $projectId = (int)$pdo->lastInsertId();
              $tagStmt = $pdo->prepare('INSERT INTO project_tags (project_id, tag_id) VALUES (:project_id, :tag_id)');
              foreach ($tagIds as $tagId) {
                $tagStmt->execute([':project_id' => $projectId, ':tag_id' => $tagId]);
              }

            header('Location: projects.php?added=1');
            exit;
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
}

$active = 'projects';
require __DIR__ . '/includes/admin-header.php';
?>
<h1 class="display" style="font-size:1.6rem;margin-bottom:var(--space-3);">Add project</h1>
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
          <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Cover image (JPG, PNG, WEBP, SVG, or GIF — max 10MB)</label>
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:8px;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="chooseFromMediaLibrary('cover_image', 'Project Cover Image')" style="display:inline-flex;align-items:center;gap:6px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
          Choose from Media Library
        </button>
        <span class="badge" id="cover_image_selected_badge" style="display:none;background:rgba(92,225,255,0.15);color:var(--accent);border:1px solid rgba(92,225,255,0.3);padding:4px 8px;border-radius:4px;font-size:0.8rem;"></span>
        <button type="button" class="btn btn-ghost btn-sm" id="cover_image_clear_btn" style="display:none;color:#ff6b6b;font-size:0.8rem;padding:3px 8px;" onclick="clearMediaSelection('cover_image')">Clear</button>
      </div>

      <div id="cover_image_preview_wrap" style="display:none;margin-bottom:8px;align-items:center;gap:10px;">
        <img id="cover_image_preview_img" src="" alt="" style="max-height:90px;max-width:180px;object-fit:cover;border-radius:4px;border:1px solid var(--accent);">
      </div>

      <input type="hidden" name="cover_image_existing" id="cover_image_existing" value="">
      <small style="color:var(--text-muted);display:block;margin-top:4px;">Click above to select or upload an image via the Media Library.</small>
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
    <?php if (!$tags): ?><p style="color:var(--text-muted);">No tags yet. You can create tags in <a href="taxonomy.php?type=tags" target="_blank" style="color:var(--accent);">Project Tags</a>.</p><?php endif; ?>
  </div>

  <div class="form-group">
    <label>Full description</label>
    <?php render_inline_image_helper('description'); ?>
    <textarea name="description" rows="8"><?= e($_POST['description'] ?? '') ?></textarea>
    <details class="form-guide">
      <summary>Formatting guide</summary>
      <p>Structure: <code>&lt;h2&gt;Section title&lt;/h2&gt;</code>, <code>&lt;p&gt;Paragraph&lt;/p&gt;</code>, <code>&lt;ul&gt;&lt;li&gt;Item&lt;/li&gt;&lt;/ul&gt;</code>, <code>&lt;strong&gt;Bold&lt;/strong&gt;</code>, <code>&lt;em&gt;Italic&lt;/em&gt;</code>.</p>
      <p>Images: Click <strong>Upload &amp; Insert Image</strong> above, or use <code>&lt;img src="uploads/filename.webp" alt="Description"&gt;</code>.</p>
      <p>Images with caption: <code>&lt;figure&gt;&lt;img src="uploads/..." alt="..."&gt;&lt;figcaption&gt;Your caption&lt;/figcaption&gt;&lt;/figure&gt;</code>.</p>
      <p>Link: <code>&lt;a href="https://example.com"&gt;Link text&lt;/a&gt;</code>.</p>
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
      <label><input type="checkbox" name="is_featured" style="width:auto;display:inline;margin-right:8px;"> Featured (shown larger on homepage)</label>
    </div>
    <div class="form-group">
      <label><input type="checkbox" name="is_visible" checked style="width:auto;display:inline;margin-right:8px;"> Visible on live site</label>
    </div>
  </div>

  <div class="form-group">
    <label>SEO meta title (optional — defaults to project title)</label>
    <input type="text" name="meta_title" maxlength="180" value="<?= e($_POST['meta_title'] ?? '') ?>">
  </div>
  <div class="form-group">
    <label>SEO meta description (optional)</label>
    <input type="text" name="meta_description" maxlength="300" value="<?= e($_POST['meta_description'] ?? '') ?>">
  </div>

  <button type="submit" class="btn btn-primary">Save project</button>
  <a href="projects.php" class="btn btn-ghost">Cancel</a>
</form>
<?php require __DIR__ . '/includes/admin-footer.php'; ?>
