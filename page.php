<?php
require_once __DIR__ . '/config/config.php';

$view = $_GET['view'] ?? 'about';
$pages = [
    'about' => ['title' => 'About', 'eyebrow' => 'About', 'heading' => 'The person behind the work.', 'description' => 'Background, focus, and the thinking behind the portfolio.'],
    'work' => ['title' => 'Selected work', 'eyebrow' => 'Selected work', 'heading' => 'Projects built to move ideas forward.', 'description' => 'A selection of projects, campaigns, and digital work.'],
    'services' => ['title' => 'Services', 'eyebrow' => 'What I do', 'heading' => 'Clear strategy. Strong execution.', 'description' => 'Creative direction, digital marketing, and web experiences for ambitious teams.'],
    'experience' => ['title' => 'Experience', 'eyebrow' => 'Career', 'heading' => 'A record of meaningful work.', 'description' => 'Professional experience, leadership, and the teams behind the work.'],
    'skills' => ['title' => 'Skills', 'eyebrow' => 'Toolkit', 'heading' => 'The tools behind the outcomes.', 'description' => 'Skills, platforms, and capabilities used across creative and digital work.'],
    'social' => ['title' => 'Social links', 'eyebrow' => 'Follow along', 'heading' => 'Find the work in progress.', 'description' => 'Social profiles and places to connect.'],
    'social-content' => ['title' => 'Social content', 'eyebrow' => 'Watch and follow', 'heading' => 'The ideas beyond the homepage.', 'description' => 'Browse featured videos and posts from across the social platforms.'],
    'testimonials' => ['title' => 'Testimonials', 'eyebrow' => 'Client words', 'heading' => 'Good work leaves a trace.', 'description' => 'Feedback from people and teams I have worked with.'],
    'contact' => ['title' => 'Contact', 'eyebrow' => 'Let\'s talk', 'heading' => 'Have a useful problem to solve?', 'description' => 'Start a conversation about your next project, campaign, or digital product.'],
];

if (!isset($pages[$view])) {
    http_response_code(404);
    $view = 'about';
}
$page = $pages[$view];
$about = $pdo->query('SELECT * FROM about_content WHERE id = 1')->fetch() ?: [];

$page_title = $page['title'] . ' — ' . get_setting($pdo, 'site_title');
$page_description = $page['description'];
$page_image = $about['profile_image'] ?? '';
require __DIR__ . '/includes/header.php';
?>
<section class="section page-intro">
  <p class="eyebrow"><?= e($page['eyebrow']) ?></p>
  <h1 class="display"><?= e($page['heading']) ?></h1>
  <p class="page-lede"><?= e($page['description']) ?></p>
</section>

<?php if ($view === 'about'): ?>
  <section class="section page-content-grid">
    <div>
      <?php if (!empty($about['profile_image'])): ?><img class="page-profile-image" src="<?= e(UPLOAD_URL . $about['profile_image']) ?>" alt="<?= e($about['full_name'] ?? 'Profile photo') ?>" loading="lazy"><?php endif; ?>
    </div>
    <div>
      <p class="eyebrow"><?= e($about['full_name'] ?? '') ?></p>
      <h2><?= e($about['role_title'] ?? '') ?></h2>
      <p class="page-copy"><?= nl2br(e($about['bio_text'] ?? 'Add your bio from the admin dashboard.')) ?></p>
    </div>
  </section>
<?php elseif ($view === 'work'): ?>
  <?php $items = $pdo->query('SELECT p.*, c.name AS category_name FROM projects p LEFT JOIN project_categories c ON c.id = p.category_id WHERE p.is_visible = 1 ORDER BY p.is_featured DESC, p.sort_order ASC, p.created_at DESC')->fetchAll(); ?>
  <section class="section"><div class="work-grid">
    <?php foreach ($items as $item): ?><article class="project-card<?= $item['is_featured'] ? ' featured' : '' ?>"><div class="project-media"><?php if ($item['cover_image']): ?><a class="project-image-link" href="<?= e(SITE_ROOT_URL . '/project.php?slug=' . urlencode($item['slug'])) ?>" aria-label="View project: <?= e($item['title']) ?>"><img src="<?= e(UPLOAD_URL . $item['cover_image']) ?>" alt="<?= e($item['title']) ?>" loading="lazy" width="640" height="400"></a><?php endif; ?></div><div class="project-body"><p class="project-cat"><?= e($item['category_name'] ?? 'Project') ?></p><h2 class="project-title"><a href="<?= e(SITE_ROOT_URL . '/project.php?slug=' . urlencode($item['slug'])) ?>"><?= e($item['title']) ?></a></h2><?php if (!empty($item['short_description'])): ?><div class="project-desc"><?= limit_rich_text_words((string) $item['short_description'], 20) ?></div><?php endif; ?><div class="project-links"><?php if ($item['external_url']): ?><a href="<?= e($item['external_url']) ?>" target="_blank" rel="noopener noreferrer">Visit live site</a><?php endif; ?><?php if ($item['github_url']): ?><a href="<?= e($item['github_url']) ?>" target="_blank" rel="noopener noreferrer">GitHub</a><?php endif; ?></div><a class="project-open" href="<?= e(SITE_ROOT_URL . '/project.php?slug=' . urlencode($item['slug'])) ?>">Full project details <span aria-hidden="true">&#8599;</span></a></div></article><?php endforeach; ?>
  </div><?php if (!$items): ?><p class="empty-state">Projects will appear here as they are published from the dashboard.</p><?php endif; ?></section>
<?php elseif ($view === 'services'): ?>
  <?php
  $items = $pdo->query('SELECT * FROM services WHERE is_visible = 1 ORDER BY is_featured DESC, sort_order ASC')->fetchAll();
  $serviceProjectCounts = service_project_counts($pdo);
  ?>
  <section class="section">
    <div class="svc-grid">
      <?php foreach ($items as $i => $item): ?>
        <?php render_service_card($item, $serviceProjectCounts[(int) $item['id']] ?? 0, $i); ?>
      <?php endforeach; ?>
    </div>
    <?php if (!$items): ?><p class="empty-state">Services will appear here as they are added from the dashboard.</p><?php endif; ?>
  </section>
<?php elseif ($view === 'experience'): ?>
  <?php $items = $pdo->query('SELECT * FROM experience WHERE is_visible = 1 ORDER BY sort_order ASC, id DESC')->fetchAll(); ?>
  <section class="section"><div class="timeline"><?php foreach ($items as $item): ?><article class="timeline-item"><p class="timeline-role"><?= e($item['role_title']) ?></p><p class="timeline-org"><?= e($item['organization']) ?><?= $item['location'] ? ' · ' . e($item['location']) : '' ?></p><p class="timeline-date"><?= e($item['start_date']) ?> — <?= e($item['end_date']) ?></p><p class="timeline-desc"><?= e($item['description'] ?? '') ?></p></article><?php endforeach; ?></div><?php if (!$items): ?><p class="empty-state">Experience will appear here as it is added from the dashboard.</p><?php endif; ?></section>
<?php elseif ($view === 'skills'): ?>
  <?php $items = $pdo->query('SELECT * FROM skills ORDER BY sort_order ASC')->fetchAll(); ?>
  <section class="section"><div class="skills-grid"><?php foreach ($items as $item): ?><span class="skill-pill"><?= e($item['skill_name']) ?></span><?php endforeach; ?></div><?php if (!$items): ?><p class="empty-state">Skills will appear here as they are added from the dashboard.</p><?php endif; ?></section>
<?php elseif ($view === 'social'): ?>
  <?php $items = $pdo->query('SELECT * FROM social_links WHERE is_visible = 1 ORDER BY sort_order ASC')->fetchAll(); ?>
  <section class="section"><div class="social-grid"><?php foreach ($items as $item): ?><a class="social-card" href="<?= e($item['url']) ?>" target="_blank" rel="noopener noreferrer"><?= social_icon($item['platform']) ?><p class="social-platform"><?= e($item['platform']) ?><?= $item['handle'] ? ' · ' . e($item['handle']) : '' ?></p><p class="social-followers"><?= e($item['followers_label'] ?? 'Connect') ?></p></a><?php endforeach; ?></div><?php if (!$items): ?><p class="empty-state">Social links will appear here as they are added from the dashboard.</p><?php endif; ?></section>
  <?php require __DIR__ . '/includes/social-showcase.php'; ?>
<?php elseif ($view === 'testimonials'): ?>
  <?php $items = $pdo->query('SELECT * FROM testimonials WHERE is_visible = 1 ORDER BY sort_order ASC')->fetchAll(); ?>
  <section class="section"><div class="testimonial-grid"><?php foreach ($items as $item): ?><article class="testimonial-card"><?php if (!empty($item['client_photo'])): ?><img class="testimonial-photo" src="<?= e(UPLOAD_URL . $item['client_photo']) ?>" alt="<?= e($item['client_name']) ?>" loading="lazy" width="64" height="64"><?php endif; ?><p class="testimonial-quote">&ldquo;<?= e($item['quote']) ?>&rdquo;</p><p class="testimonial-name"><?= e($item['client_name']) ?></p><p class="testimonial-role"><?= e($item['client_role'] ?? '') ?></p></article><?php endforeach; ?></div><?php if (!$items): ?><p class="empty-state">Testimonials will appear here as they are added from the dashboard.</p><?php endif; ?></section>
<?php elseif ($view === 'social-content'): ?>
  <?php require __DIR__ . '/includes/social-showcase.php'; ?>
<?php elseif ($view === 'contact'): ?>
  <section class="section contact-page-grid"><div><p class="page-copy">Tell me what you are building, where it is stuck, and what a useful outcome looks like.</p><p class="contact-detail"><?= e(get_setting($pdo, 'contact_email')) ?></p><p class="contact-detail"><?= e(get_setting($pdo, 'location')) ?></p></div><div><?php if (!empty($_SESSION['contact_success'])): unset($_SESSION['contact_success']); ?><div class="contact-status contact-status-success" role="status">Message sent successfully. I&rsquo;ll get back to you soon.</div><?php endif; ?><?php if (!empty($_SESSION['contact_errors'])): $contactErrors = $_SESSION['contact_errors']; unset($_SESSION['contact_errors']); ?><div class="contact-status contact-status-error" role="alert"><?php foreach ($contactErrors as $contactError): ?><?= e($contactError) ?><br><?php endforeach; ?></div><?php endif; ?><form class="contact-form" action="contact-submit.php" method="POST"><?= csrf_field() ?><input type="text" name="name" placeholder="Your name" required maxlength="120"><input type="email" name="email" placeholder="Your email" required maxlength="150"><input type="text" name="subject" placeholder="Subject" maxlength="200"><textarea name="message" placeholder="Message" required maxlength="4000"></textarea><button type="submit" class="btn btn-primary">Send message</button></form></div></section>
<?php endif; ?>
<?php if ($view !== 'contact'): ?>
  <?php require __DIR__ . '/includes/clients-slider.php'; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
