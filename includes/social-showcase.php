<?php
$socialQuery = 'SELECT * FROM social_posts
     WHERE is_visible = 1
  ORDER BY is_featured DESC, sort_order ASC, created_at DESC';
$isAllSocialContent = (($view ?? '') === 'social-content');
if (!$isAllSocialContent) $socialQuery .= ' LIMIT 8';
$socialPosts = $pdo->query($socialQuery)->fetchAll();
?>
<?php if ($socialPosts): ?>
<section class="section social-showcase" id="social-showcase">
  <div class="section-head social-showcase-head">
    <div>
      <p class="eyebrow">Beyond the portfolio</p>
      <h2>See the work in motion.</h2>
    </div>
    <p class="page-lede">Watch, follow, and join the conversation across the platforms where the ideas keep moving.</p>
  </div>
  <?php if (!$isAllSocialContent): ?><div class="social-slider" data-social-slider>
    <div class="social-slider-controls" aria-label="Social content controls">
      <button type="button" class="social-slider-button" data-social-prev aria-label="Previous social post">&larr;</button>
      <span class="social-slider-count" data-social-count>1 / <?= count($socialPosts) ?></span>
      <button type="button" class="social-slider-button" data-social-next aria-label="Next social post">&rarr;</button>
    </div>
    <div class="social-feature-grid" data-social-track>
  <?php else: ?><div class="social-feature-grid social-feature-grid-all">
  <?php endif; ?>
    <?php foreach ($socialPosts as $post): ?>
      <article class="social-feature-card<?= $post['is_featured'] ? ' is-featured' : '' ?> platform-<?= e(strtolower($post['platform'])) ?>">
        <div class="social-feature-media">
          <?php if ($post['embed_url']): ?><iframe src="<?= e($post['embed_url']) ?>" title="<?= e($post['title']) ?>" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
          <?php elseif ($post['thumbnail_image']): ?><img src="<?= e(UPLOAD_URL . $post['thumbnail_image']) ?>" alt="<?= e($post['title']) ?>" loading="lazy">
          <?php elseif ($post['thumbnail_url']): ?><img src="<?= e($post['thumbnail_url']) ?>" alt="<?= e($post['title']) ?>" loading="lazy">
          <?php elseif (social_thumbnail_url($post['url'], $post['platform'])): ?><img src="<?= e(social_thumbnail_url($post['url'], $post['platform'])) ?>" alt="<?= e($post['title']) ?>" loading="lazy">
          <?php else: ?><span class="social-feature-placeholder" aria-hidden="true"><?= e(strtoupper(substr($post['platform'], 0, 1))) ?></span><?php endif; ?>
          <span class="social-play" aria-hidden="true">↗</span>
        </div>
        <div class="social-feature-body">
          <p class="social-platform-label"><?= e($post['platform']) ?><?= $post['is_featured'] ? ' · Featured' : '' ?></p>
          <h3><?= e($post['title']) ?></h3>
          <?php if ($post['description']): ?><p><?= e($post['description']) ?></p><?php endif; ?>
          <a class="social-watch" href="<?= e($post['url']) ?>" target="_blank" rel="noopener noreferrer">Open on <?= e($post['platform']) ?> <span aria-hidden="true">→</span></a>
        </div>
      </article>
    <?php endforeach; ?>
    </div>
  <?php if (!$isAllSocialContent): ?></div><?php endif; ?>
  <?php if (!$isAllSocialContent): ?><a class="section-action" href="<?= e(SITE_ROOT_URL) ?>/page.php?view=social-content">View all social content <span aria-hidden="true">&rarr;</span></a><?php endif; ?>
</section>
<?php endif; ?>
