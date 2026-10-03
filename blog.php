<?php
require_once __DIR__ . '/config/config.php';

$posts = $pdo->query('SELECT * FROM blog_posts WHERE is_published = 1 ORDER BY published_at DESC')->fetchAll();

$page_title = 'Blog — ' . get_setting($pdo, 'site_title');
$page_description = 'Insights on creative direction, social media, and digital marketing.';

require __DIR__ . '/includes/header.php';
?>
<section class="section">
  <div class="section-head">
    <p class="eyebrow">Insights</p>
    <h2>All posts</h2>
  </div>
  <?php if (!$posts): ?>
    <p style="color:var(--text-muted);">No posts published yet.</p>
  <?php else: ?>
    <div class="blog-grid">
      <?php foreach ($posts as $post): ?>
        <a class="blog-card" href="<?= e(SITE_ROOT_URL . '/post.php?slug=' . urlencode($post['slug'])) ?>">
          <?php if ($post['cover_image']): ?>
            <div class="blog-media"><img src="<?= e(UPLOAD_URL . $post['cover_image']) ?>" alt="<?= e($post['title']) ?>" loading="lazy"></div>
          <?php endif; ?>
          <div class="blog-body">
            <p class="blog-date"><?= e(date('M j, Y', strtotime($post['published_at']))) ?></p>
            <p class="blog-title"><?= e($post['title']) ?></p>
            <?php if ($post['excerpt']): ?><div class="blog-excerpt"><?= limit_rich_text_words((string) $post['excerpt'], 20) ?></div><?php endif; ?>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
