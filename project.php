<?php
require_once __DIR__ . '/config/config.php';
ensure_projects_schema($pdo);

$slug = trim($_GET['slug'] ?? '');
$stmt = $pdo->prepare(
		'SELECT p.*, c.name AS category_name
		 FROM projects p
		 LEFT JOIN project_categories c ON c.id = p.category_id
		 WHERE p.slug = :slug AND p.is_visible = 1
		 LIMIT 1'
);
$stmt->execute([':slug' => $slug]);
$project = $stmt->fetch();

if (!$project) {
		http_response_code(404);
		$page_title = 'Project not found';
		require __DIR__ . '/includes/header.php';
		echo '<section class="section"><h1>Project not found</h1><p><a href="' . e(SITE_ROOT_URL . '/page.php?view=work') . '">Back to work</a></p></section>';
		require __DIR__ . '/includes/footer.php';
		exit;
}

$tagStmt = $pdo->prepare(
		'SELECT t.name FROM tags t
		 INNER JOIN project_tags pt ON pt.tag_id = t.id
		 WHERE pt.project_id = :project_id ORDER BY t.name ASC'
);
$tagStmt->execute([':project_id' => $project['id']]);
$tags = $tagStmt->fetchAll(PDO::FETCH_COLUMN);

$page_title = $project['meta_title'] ?: $project['title'];
$page_description = $project['meta_description'] ?: ($project['short_description'] ?: $project['description']);
$page_image = $project['cover_image'];
$page_url = SITE_ROOT_URL . '/project.php?slug=' . rawurlencode($project['slug']);
require __DIR__ . '/includes/header.php';
?>
<article class="project-detail">
	<a class="detail-backlink" href="<?= e(SITE_ROOT_URL . '/page.php?view=work') ?>"><span aria-hidden="true">&larr;</span> All projects</a>
	<header class="project-detail-header">
		<p class="project-detail-kicker"><span>Selected work</span><span aria-hidden="true">/</span><span><?= e($project['category_name'] ?? 'Project') ?></span></p>
		<h1 class="display"><?= e($project['title']) ?></h1>
	</header>
	<div class="project-detail-grid<?= empty($project['cover_image']) ? ' no-image' : '' ?>">
		<?php if (!empty($project['cover_image'])): ?>
			<div class="project-detail-media">
				<figure class="project-detail-visual">
					<img class="project-detail-image" src="<?= e(UPLOAD_URL . $project['cover_image']) ?>" alt="<?= e($project['title']) ?>" loading="eager">
				</figure>
				<?php if (!empty($project['external_url'])): ?><a class="project-live-link" href="<?= e($project['external_url']) ?>" target="_blank" rel="noopener noreferrer">Visit live site <span aria-hidden="true">&#8599;</span></a><?php endif; ?>
			</div>
		<?php endif; ?>
		<?php if (!empty($project['short_description'])): ?>
			<div class="project-detail-summary">
				<p class="detail-section-label">Overview</p>
				<div class="project-detail-lede"><?= limit_rich_text_words((string) $project['short_description'], 20) ?></div>
			</div>
		<?php endif; ?>
		<?php if (!empty($project['description'])): ?>
			<section class="project-detail-overview">
				<p class="detail-section-label">Project overview</p>
				<div class="project-detail-copy"><?= sanitize_rich_text((string) $project['description']) ?></div>
			</section>
		<?php endif; ?>
		<aside class="project-detail-aside">
			<p class="detail-section-label">Project details</p>
			<dl class="project-detail-facts">
				<?php if (!empty($project['job_role'])): ?>
					<div><dt>Role / Position</dt><dd><?= e($project['job_role']) ?></dd></div>
				<?php endif; ?>
				<div><dt>Discipline</dt><dd><?= e($project['category_name'] ?? 'Project') ?></dd></div>
				<div><dt>Published</dt><dd><?= e(date('M Y', strtotime($project['created_at']))) ?></dd></div>
			</dl>
			<?php if ($tags): ?><ul class="project-tags" aria-label="Project tags"><?php foreach ($tags as $tag): ?><li><?= e($tag) ?></li><?php endforeach; ?></ul><?php endif; ?>
			<?php if ((!empty($project['external_url']) && empty($project['cover_image'])) || !empty($project['github_url'])): ?>
				<div class="project-detail-links">
					<?php if (!empty($project['external_url']) && empty($project['cover_image'])): ?><a class="detail-text-link" href="<?= e($project['external_url']) ?>" target="_blank" rel="noopener noreferrer">Visit live site <span aria-hidden="true">&#8599;</span></a><?php endif; ?>
					<?php if (!empty($project['github_url'])): ?><a class="detail-text-link" href="<?= e($project['github_url']) ?>" target="_blank" rel="noopener noreferrer">View source <span aria-hidden="true">&#8599;</span></a><?php endif; ?>
				</div>
			<?php endif; ?>
		</aside>
	</div>
</article>
<?php require __DIR__ . '/includes/footer.php'; ?>
