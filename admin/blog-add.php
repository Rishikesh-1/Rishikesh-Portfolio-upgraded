<?php
require_once __DIR__ . '/../config/config.php';
require_admin_login();

$error = '';
$excerptError = '';
$publishedInput = date('Y-m-d\TH:i');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title = trim($_POST['title'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $metaTitle = trim($_POST['meta_title'] ?? '');
    $metaDescription = trim($_POST['meta_description'] ?? '');
    $isPublished = isset($_POST['is_published']) ? 1 : 0;
    $publishedInput = $_POST['published_at'] ?? '';
    $excerptError = rich_text_word_count($excerpt) > 20 ? 'Use 20 words or fewer for this preview.' : '';

    if ($title === '') {
        $error = 'Title is required.';
    } elseif (mb_strlen($excerpt) > 300 || mb_strlen($metaDescription) > 300) {
        $error = 'Excerpt and meta description must be 300 characters or fewer.';
    } elseif ($excerptError !== '') {
        $error = $excerptError;
    } else {
        $coverImage = null;
        try {
            $coverImage = handle_image_upload($_FILES['cover_image'] ?? [], 'cover image');
            if (!$coverImage && !empty($_POST['cover_image_existing'])) {
                $cand = basename(trim($_POST['cover_image_existing']));
                if (is_file(UPLOAD_DIR . $cand)) {
                    $coverImage = $cand;
                }
            }
            $baseSlug = make_slug($title);
            if ($baseSlug === '') {
                throw new RuntimeException('Title must contain letters or numbers so a URL can be created.');
            }
            $slug = $baseSlug;
            $suffix = 1;
            $check = $pdo->prepare('SELECT COUNT(*) c FROM blog_posts WHERE slug = :slug');
            do {
                $check->execute([':slug' => $slug]);
                if ((int)$check->fetch()['c'] === 0) break;
                $slug = $baseSlug . '-' . (++$suffix);
            } while (true);

            $publishedAt = null;
            if ($isPublished) {
                $publishedAt = $publishedInput !== '' ? str_replace('T', ' ', $publishedInput) . ':00' : date('Y-m-d H:i:s');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO blog_posts
                 (title, slug, cover_image, excerpt, body, tags, is_published, meta_title, meta_description, published_at)
                 VALUES (:title, :slug, :cover_image, :excerpt, :body, :tags, :is_published, :meta_title, :meta_description, :published_at)'
            );
            $stmt->execute([
                ':title' => $title,
                ':slug' => $slug,
                ':cover_image' => $coverImage,
                ':excerpt' => $excerpt,
                ':body' => $body,
                ':tags' => $tags,
                ':is_published' => $isPublished,
                ':meta_title' => $metaTitle,
                ':meta_description' => $metaDescription,
                ':published_at' => $publishedAt,
            ]);

            header('Location: blog.php?added=1');
            exit;
        } catch (RuntimeException $e) {
            if ($coverImage) delete_uploaded_file($coverImage);
            $error = $e->getMessage();
        }
    }
}

$active = 'blog';
require __DIR__ . '/includes/admin-header.php';
?>
<h1 class="display" style="font-size:1.6rem;margin-bottom:var(--space-3);">Add blog post</h1>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="admin-card">
  <?= csrf_field() ?>
  <div class="form-group"><label>Title</label><input type="text" name="title" required maxlength="200" value="<?= e($_POST['title'] ?? '') ?>"></div>
  <div class="form-row">
    <div class="form-group">
      <label>Cover image (JPG, PNG, WEBP, SVG, or GIF — max 10MB)</label>
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:8px;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="chooseFromMediaLibrary('cover_image', 'Cover Image')" style="display:inline-flex;align-items:center;gap:6px;">
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
    <div class="form-group"><label>Tags</label><input type="text" name="tags" maxlength="255" placeholder="design, marketing" value="<?= e($_POST['tags'] ?? '') ?>"></div>
  </div>
    <div class="form-group"><label>Excerpt (20-word preview)</label><textarea name="excerpt" rows="3" maxlength="300" class="<?= $excerptError !== '' ? 'field-invalid' : '' ?>" <?= $excerptError !== '' ? 'aria-invalid="true" aria-describedby="excerpt-error"' : '' ?>><?= e($_POST['excerpt'] ?? '') ?></textarea><?php if ($excerptError !== ''): ?><p class="field-error" id="excerpt-error" role="alert"><?= e($excerptError) ?></p><?php endif; ?></div>
    <div class="form-group">
        <label>Body</label>
        <?php render_inline_image_helper('body'); ?>
        <textarea name="body" rows="12"><?= e($_POST['body'] ?? '') ?></textarea>
        <details class="form-guide">
            <summary>Formatting guide</summary>
            <p>Structure: <code>&lt;h2&gt;Heading&lt;/h2&gt;</code>, <code>&lt;p&gt;Paragraph&lt;/p&gt;</code>, <code>&lt;ul&gt;&lt;li&gt;Item&lt;/li&gt;&lt;/ul&gt;</code>, <code>&lt;strong&gt;Bold&lt;/strong&gt;</code>, <code>&lt;em&gt;Italic&lt;/em&gt;</code>.</p>
            <p>Images: Click <strong>Upload &amp; Insert Image</strong> above, or use <code>&lt;img src="uploads/filename.webp" alt="Description"&gt;</code>.</p>
            <p>Images with caption: <code>&lt;figure&gt;&lt;img src="uploads/..." alt="..."&gt;&lt;figcaption&gt;Your caption&lt;/figcaption&gt;&lt;/figure&gt;</code>.</p>
            <p>Link: <code>&lt;a href="https://example.com"&gt;Link text&lt;/a&gt;</code>.</p>
        </details>
    </div>
  <div class="form-row">
    <div class="form-group"><label><input type="checkbox" name="is_published" checked style="width:auto;display:inline;margin-right:8px;"> Publish on live site</label></div>
    <div class="form-group"><label>Publication date</label><input type="datetime-local" name="published_at" value="<?= e($publishedInput) ?>"></div>
  </div>
  <div class="form-group"><label>SEO meta title</label><input type="text" name="meta_title" maxlength="200" value="<?= e($_POST['meta_title'] ?? '') ?>"></div>
  <div class="form-group"><label>SEO meta description</label><textarea name="meta_description" rows="3" maxlength="300"><?= e($_POST['meta_description'] ?? '') ?></textarea></div>
  <button type="submit" class="btn btn-primary">Save blog post</button>
  <a href="blog.php" class="btn btn-ghost">Cancel</a>
</form>
<?php require __DIR__ . '/includes/admin-footer.php'; ?>
