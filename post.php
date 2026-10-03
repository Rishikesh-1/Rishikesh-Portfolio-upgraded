<?php
require_once __DIR__ . '/config/config.php';

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare('SELECT * FROM blog_posts WHERE slug = :slug AND is_published = 1 LIMIT 1');
$stmt->execute([':slug' => $slug]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    $page_title = 'Post not found';
    require __DIR__ . '/includes/header.php';
    echo '<section class="section"><h2>Post not found</h2><p><a href="/blog.php">&larr; Back to blog</a></p></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS blog_post_stats (
        post_id INT UNSIGNED PRIMARY KEY,
        views INT UNSIGNED NOT NULL DEFAULT 0,
        FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS blog_post_likes (
        post_id INT UNSIGNED NOT NULL,
        visitor_hash CHAR(64) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (post_id, visitor_hash),
        FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS blog_comments (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        post_id INT UNSIGNED NOT NULL,
        name VARCHAR(80) NOT NULL,
        body TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_blog_comments_post (post_id, created_at),
        FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

$visitorHash = hash('sha256', session_id());
$postUrl = SITE_ROOT_URL . '/post.php?slug=' . rawurlencode($post['slug']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $redirectAnchor = ($_POST['action'] ?? '') === 'comment' ? '#comments' : '';

    if (($_POST['action'] ?? '') === 'like') {
        $likeStmt = $pdo->prepare('INSERT IGNORE INTO blog_post_likes (post_id, visitor_hash) VALUES (:post_id, :visitor_hash)');
        $likeStmt->execute([':post_id' => $post['id'], ':visitor_hash' => $visitorHash]);
        if ($likeStmt->rowCount() === 0) {
            $likeStmt = $pdo->prepare('DELETE FROM blog_post_likes WHERE post_id = :post_id AND visitor_hash = :visitor_hash');
            $likeStmt->execute([':post_id' => $post['id'], ':visitor_hash' => $visitorHash]);
        }
    } elseif (($_POST['action'] ?? '') === 'comment') {
        $name = trim($_POST['name'] ?? '');
        $body = trim($_POST['body'] ?? '');
        if ($name !== '' && mb_strlen($name) <= 80 && $body !== '' && mb_strlen($body) <= 2000) {
            $commentStmt = $pdo->prepare('INSERT INTO blog_comments (post_id, name, body) VALUES (:post_id, :name, :body)');
            $commentStmt->execute([':post_id' => $post['id'], ':name' => $name, ':body' => $body]);
            $_SESSION['blog_comment_status'] = 'Your comment has been posted.';
        } else {
            $_SESSION['blog_comment_status'] = 'Enter your name and a comment of up to 2,000 characters.';
        }
    }

    header('Location: ' . $postUrl . $redirectAnchor);
    exit;
}

if (empty($_SESSION['blog_viewed_posts'][$post['id']])) {
    $viewStmt = $pdo->prepare(
        'INSERT INTO blog_post_stats (post_id, views) VALUES (:post_id, 1)
         ON DUPLICATE KEY UPDATE views = views + 1'
    );
    $viewStmt->execute([':post_id' => $post['id']]);
    $_SESSION['blog_viewed_posts'][$post['id']] = true;
}

$statsStmt = $pdo->prepare('SELECT views FROM blog_post_stats WHERE post_id = :post_id');
$statsStmt->execute([':post_id' => $post['id']]);
$views = (int) ($statsStmt->fetchColumn() ?: 0);
$likesStmt = $pdo->prepare('SELECT COUNT(*) FROM blog_post_likes WHERE post_id = :post_id');
$likesStmt->execute([':post_id' => $post['id']]);
$likes = (int) $likesStmt->fetchColumn();
$likedStmt = $pdo->prepare('SELECT 1 FROM blog_post_likes WHERE post_id = :post_id AND visitor_hash = :visitor_hash');
$likedStmt->execute([':post_id' => $post['id'], ':visitor_hash' => $visitorHash]);
$liked = (bool) $likedStmt->fetchColumn();
$commentsStmt = $pdo->prepare('SELECT name, body, created_at FROM blog_comments WHERE post_id = :post_id ORDER BY created_at DESC');
$commentsStmt->execute([':post_id' => $post['id']]);
$comments = $commentsStmt->fetchAll();
$commentStatus = $_SESSION['blog_comment_status'] ?? '';
unset($_SESSION['blog_comment_status']);
$publishedAt = $post['published_at'] ?: $post['created_at'];
$readMinutes = max(1, (int) ceil(str_word_count(strip_tags((string) $post['body'])) / 220));
$postTags = array_values(array_filter(array_map('trim', explode(',', (string) ($post['tags'] ?? '')))));
$bodyHasMarkup = preg_match('/<\/?[a-z][^>]*>/i', (string) $post['body']) === 1;
$bodyParagraphs = preg_split('/\R{2,}/', trim((string) $post['body'])) ?: [];

$page_title = $post['meta_title'] ?: $post['title'];
$page_description = $post['meta_description'] ?: $post['excerpt'];
$page_image = $post['cover_image'];
$page_url = $postUrl;
require __DIR__ . '/includes/header.php';
?>
<article class="blog-post">
  <header class="blog-post-header">
    <a class="detail-backlink" href="<?= e(SITE_ROOT_URL . '/blog.php') ?>"><span aria-hidden="true">&larr;</span> All articles</a>
    <p class="blog-post-meta"><time datetime="<?= e(date('Y-m-d', strtotime($publishedAt))) ?>"><?= e(date('F j, Y', strtotime($publishedAt))) ?></time><span aria-hidden="true">/</span><span><?= $readMinutes ?> min read</span></p>
    <h1 class="display"><?= e($post['title']) ?></h1>
  </header>
  <div class="blog-post-hero<?= empty($post['cover_image']) ? ' no-image' : '' ?>">
    <?php if (!empty($post['cover_image'])): ?>
      <figure class="blog-post-visual"><img class="blog-post-image" src="<?= e(UPLOAD_URL . $post['cover_image']) ?>" alt="<?= e($post['title']) ?>" loading="eager"></figure>
    <?php endif; ?>
    <div class="blog-post-intro">
      <?php if (!empty($post['excerpt'])): ?><div class="blog-post-excerpt"><?= limit_rich_text_words((string) $post['excerpt'], 20) ?></div><?php endif; ?>
      <?php if ($postTags): ?><ul class="blog-post-tags" aria-label="Article tags"><?php foreach ($postTags as $tag): ?><li><?= e($tag) ?></li><?php endforeach; ?></ul><?php endif; ?>
    </div>
  </div>
  <div class="blog-post-layout">
    <div class="blog-post-copy">
      <?php if ($bodyHasMarkup): ?>
        <?= sanitize_rich_text((string) $post['body']) ?>
      <?php else: ?>
        <?php foreach ($bodyParagraphs as $paragraph): ?><p><?= nl2br(e(trim($paragraph))) ?></p><?php endforeach; ?>
      <?php endif; ?>
    </div>
    <aside class="blog-engagement" aria-label="Article engagement">
      <p class="detail-section-label">Join the conversation</p>
      <p class="blog-view-count"><strong><?= number_format($views) ?></strong><span>views</span></p>
      <a class="blog-comment-jump" href="#comments"><?= count($comments) ?> comments <span aria-hidden="true">&darr;</span></a>
      <form method="POST" action="<?= e($postUrl) ?>">
        <?= csrf_field() ?><input type="hidden" name="action" value="like">
        <button class="blog-like-button<?= $liked ? ' is-liked' : '' ?>" type="submit" aria-pressed="<?= $liked ? 'true' : 'false' ?>"><span aria-hidden="true">&#9829;</span> <?= $liked ? 'Liked' : 'Like this article' ?><strong><?= number_format($likes) ?></strong></button>
      </form>
    </aside>
  </div>
</article>

<section class="blog-comments" id="comments">
  <div class="blog-discussion-inner">
    <header class="blog-discussion-heading">
      <p class="eyebrow">Conversation</p>
      <h2>Join the discussion <span><?= count($comments) ?></span></h2>
    </header>
    <div class="blog-discussion-grid">
      <form class="contact-form comment-form" method="POST" action="<?= e($postUrl) ?>#comments">
        <?= csrf_field() ?><input type="hidden" name="action" value="comment">
        <p class="detail-section-label">Leave a comment</p>
        <?php if ($commentStatus): ?><p class="contact-status contact-status-success" role="status"><?= e($commentStatus) ?></p><?php endif; ?>
        <label for="comment-name">Your name</label>
        <input id="comment-name" type="text" name="name" maxlength="80" required>
        <label for="comment-body">Your thoughts</label>
        <textarea id="comment-body" name="body" maxlength="2000" required></textarea>
        <button type="submit" class="btn btn-primary">Post comment <span aria-hidden="true">&#8594;</span></button>
      </form>
      <div class="comment-list">
        <p class="detail-section-label">Reader comments</p>
        <?php foreach ($comments as $comment): ?>
          <article class="comment-item">
            <p class="comment-meta"><strong><?= e($comment['name']) ?></strong><time datetime="<?= e(date('Y-m-d', strtotime($comment['created_at']))) ?>"><?= e(date('M j, Y', strtotime($comment['created_at']))) ?></time></p>
            <p><?= nl2br(e($comment['body'])) ?></p>
          </article>
        <?php endforeach; ?>
        <?php if (!$comments): ?><p class="comment-empty">No comments yet. Start the conversation.</p><?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
