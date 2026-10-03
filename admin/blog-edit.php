<?php
require_once __DIR__ . '/../config/config.php';
require_admin_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM blog_posts WHERE id = :id');
$stmt->execute([':id' => $id]);
$post = $stmt->fetch();

if (!$post) {
    header('Location: blog.php');
    exit;
}

$error = '';
$excerptError = '';
$publishedInput = $post['published_at'] ? date('Y-m-d\TH:i', strtotime($post['published_at'])) : date('Y-m-d\TH:i');

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
        $newImage = null;
        try {
            $newImage = handle_image_upload($_FILES['cover_image'] ?? [], 'cover image');
            $coverImage = $post['cover_image'];
            if ($newImage) {
                delete_uploaded_file($coverImage);
                $coverImage = $newImage;
            } elseif (isset($_POST['remove_image'])) {
                delete_uploaded_file($coverImage);
                $coverImage = null;
            }

            $publishedAt = null;
            if ($isPublished) {
                $publishedAt = $publishedInput !== '' ? str_replace('T', ' ', $publishedInput) . ':00' : date('Y-m-d H:i:s');
            }

            $stmt = $pdo->prepare(
                'UPDATE blog_posts SET title = :title, cover_image = :cover_image, excerpt = :excerpt,
                 body = :body, tags = :tags, is_published = :is_published, meta_title = :meta_title,
                 meta_description = :meta_description, published_at = :published_at WHERE id = :id'
            );
            $stmt->execute([
                ':title' => $title,
                ':cover_image' => $coverImage,
                ':excerpt' => $excerpt,
                ':body' => $body,
                ':tags' => $tags,
                ':is_published' => $isPublished,
                ':meta_title' => $metaTitle,
                ':meta_description' => $metaDescription,
                ':published_at' => $publishedAt,
                ':id' => $id,
            ]);

            header('Location: blog.php?updated=1');
            exit;
        } catch (RuntimeException $e) {
            if ($newImage) delete_uploaded_file($newImage);
            $error = $e->getMessage();
        }
    }
} else {
    $_POST = $post;
    $_POST['is_published'] = $post['is_published'];
    $_POST['published_at'] = $publishedInput;
}

$active = 'blog';
require __DIR__ . '/includes/admin-header.php';
?>
<h1 class="display" style="font-size:1.6rem;margin-bottom:var(--space-3);">Edit blog post</h1>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="admin-card">
  <?= csrf_field() ?>
  <div class="form-group"><label>Title</label><input type="text" name="title" required maxlength="200" value="<?= e($_POST['title'] ?? '') ?>"></div>
  <div class="form-row">
    <div class="form-group">
      <label>Cover image</label>
      <?php if ($post['cover_image']): ?><img src="<?= e(UPLOAD_URL . $post['cover_image']) ?>" alt="" style="width:120px;border-radius:4px;margin-bottom:8px;"><label style="font-weight:400;"><input type="checkbox" name="remove_image" style="width:auto;display:inline;margin-right:6px;"> Remove current image</label><?php endif; ?>
      <input type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp">
    </div>
    <div class="form-group"><label>Tags</label><input type="text" name="tags" maxlength="255" value="<?= e($_POST['tags'] ?? '') ?>"></div>
  </div>
    <div class="form-group"><label>Excerpt (20-word preview)</label><textarea name="excerpt" rows="3" maxlength="300" class="<?= $excerptError !== '' ? 'field-invalid' : '' ?>" <?= $excerptError !== '' ? 'aria-invalid="true" aria-describedby="excerpt-error"' : '' ?>><?= e($_POST['excerpt'] ?? $post['excerpt']) ?></textarea><?php if ($excerptError !== ''): ?><p class="field-error" id="excerpt-error" role="alert"><?= e($excerptError) ?></p><?php endif; ?></div>
    <div class="form-group">
        <label>Body</label>
        <textarea name="body" rows="12"><?= e($_POST['body'] ?? $post['body']) ?></textarea>
        <details class="form-guide">
            <summary>Formatting guide</summary>
            <p>Use <code>&lt;h2&gt;</code>, <code>&lt;p&gt;</code>, <code>&lt;ul&gt;</code>, <code>&lt;ol&gt;</code>, <code>&lt;li&gt;</code>, <code>&lt;strong&gt;</code>, or <code>&lt;em&gt;</code>.</p>
            <p>Link: <code>&lt;a href="https://example.com"&gt;Link text&lt;/a&gt;</code>. Formatting and links also work in the excerpt.</p>
        </details>
    </div>
  <div class="form-row">
    <div class="form-group"><label><input type="checkbox" name="is_published" <?= !empty($_POST['is_published']) ? 'checked' : '' ?> style="width:auto;display:inline;margin-right:8px;"> Publish on live site</label></div>
    <div class="form-group"><label>Publication date</label><input type="datetime-local" name="published_at" value="<?= e($_POST['published_at'] ?? $publishedInput) ?>"></div>
  </div>
  <div class="form-group"><label>SEO meta title</label><input type="text" name="meta_title" maxlength="200" value="<?= e($_POST['meta_title'] ?? '') ?>"></div>
  <div class="form-group"><label>SEO meta description</label><textarea name="meta_description" rows="3" maxlength="300"><?= e($_POST['meta_description'] ?? '') ?></textarea></div>
  <button type="submit" class="btn btn-primary">Update blog post</button>
  <a href="blog.php" class="btn btn-ghost">Cancel</a>
</form>
<?php require __DIR__ . '/includes/admin-footer.php'; ?>
