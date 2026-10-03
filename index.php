<?php
require_once __DIR__ . '/config/config.php';

// ---- Fetch all content up front (prepared statements throughout) ----
$about = $pdo->query('SELECT * FROM about_content WHERE id = 1')->fetch() ?: [];

$projects = $pdo->query(
    'SELECT p.*, c.name AS category_name FROM projects p
     LEFT JOIN project_categories c ON c.id = p.category_id
     WHERE p.is_visible = 1
     ORDER BY p.is_featured DESC, p.sort_order ASC, p.created_at DESC
    LIMIT 3'
)->fetchAll();

$services = $pdo->query('SELECT * FROM services WHERE is_visible = 1 ORDER BY sort_order ASC')->fetchAll();
$experience = $pdo->query('SELECT * FROM experience WHERE is_visible = 1 ORDER BY sort_order ASC')->fetchAll();
$skills = $pdo->query('SELECT * FROM skills ORDER BY sort_order ASC')->fetchAll();
$testimonials = $pdo->query('SELECT * FROM testimonials WHERE is_visible = 1 ORDER BY sort_order ASC LIMIT 6')->fetchAll();
$socialLinks = $pdo->query('SELECT * FROM social_links WHERE is_visible = 1 ORDER BY sort_order ASC')->fetchAll();
$recentPosts = $pdo->query('SELECT * FROM blog_posts WHERE is_published = 1 ORDER BY published_at DESC LIMIT 3')->fetchAll();

$page_title = $about['full_name'] . ' — ' . $about['role_title'];
$page_description = $about['tagline'] ?: get_setting($pdo, 'site_description');
$page_image = $about['profile_image'];
$body_class = 'home-page';
$heroFocusOptions = array_values(array_filter([
  trim(get_setting($pdo, 'hero_focus_1', get_setting($pdo, 'hero_focus', 'creative direction'))),
  trim(get_setting($pdo, 'hero_focus_2', 'digital marketing')),
  trim(get_setting($pdo, 'hero_focus_3', 'web experiences')),
]));
if (!$heroFocusOptions) {
  $heroFocusOptions = ['creative direction'];
}

require __DIR__ . '/includes/header.php';
?>

<!-- HERO -->
<section class="hero hero-home" data-parallax-section>
  <div>
    <h1 class="hero-name">
      <span class="reveal" data-reveal><?= e($about['full_name']) ?></span>
    </h1>
    <p class="hero-role"><?= e($about['role_title']) ?></p>
    <p class="hero-tagline"><span data-typing="Hello, I’m <?= e($about['full_name']) ?>. I build brands, campaigns, and digital experiences."></span><span class="typing-cursor" aria-hidden="true">|</span></p>
    <p class="hero-skill-line">Currently focused on <span data-skill-rotate data-skills="<?= e(json_encode($heroFocusOptions, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>"><?= e($heroFocusOptions[0]) ?></span></p>
    <div class="hero-actions">
      <a href="#work" class="btn btn-primary">See my work</a>
      <?php if (!empty($about['resume_file'])): ?>
        <a href="<?= e(UPLOAD_URL . $about['resume_file']) ?>" class="btn btn-ghost" download>Download CV</a>
      <?php endif; ?>
      <a href="#contact" class="btn btn-ghost">Get in touch</a>
    </div>
  </div>
  <div class="hero-photo">
    <?php if (!empty($about['profile_image'])): ?>
      <img src="<?= e(UPLOAD_URL . $about['profile_image']) ?>" alt="<?= e($about['full_name']) ?>" loading="eager">
    <?php endif; ?>
  </div>
</section>

<?php
$bioText = trim((string) ($about['bio_text'] ?? ''));
$bioWords = preg_split('/\s+/', $bioText);
$bioPreview = $bioText;

if (!empty($bioWords) && count($bioWords) > 150) {
  $bioPreview = implode(' ', array_slice($bioWords, 0, 150)) . '...';
}
?>

<!-- ABOUT -->
<?php if (!empty($about['bio_text'])): ?>
<section class="section" id="about">
  <div class="section-head">
    <p class="eyebrow">About</p>
    <h2>A quick introduction</h2>
  </div>

  <p class="about-summary"><?= nl2br(e($bioPreview)) ?></p>

  <a href="<?= e(SITE_ROOT_URL . '/page.php?view=about') ?>" class="btn btn-ghost section-cta about-more-link">
    More about me <span aria-hidden="true">&rarr;</span>
  </a>
</section>
<?php endif; ?>

<!-- WORK -->
<?php if ($projects): ?>
<section class="section" id="work">
  <div class="section-head">
    <p class="eyebrow">Selected Work</p>
    <h2>Projects &amp; campaigns</h2>
  </div>
  <div class="work-grid">
    <?php foreach ($projects as $i => $p): ?>
      <article class="project-card<?= $p['is_featured'] ? ' featured' : '' ?>" data-project-card data-project-title="<?= e($p['title']) ?>" data-project-description="<?= e($p['description'] ?: $p['short_description'] ?: 'A focused project built with strategy, craft, and measurable intent.') ?>" data-project-category="<?= e($p['category_name'] ?? 'Project') ?>" data-project-image="<?= e($p['cover_image'] ? UPLOAD_URL . $p['cover_image'] : '') ?>">
        <div class="project-media">
          <?php if ($p['cover_image']): ?>
            <img src="<?= e(UPLOAD_URL . $p['cover_image']) ?>" alt="<?= e($p['title']) ?>" loading="lazy" width="640" height="400">
          <?php endif; ?>
        </div>
        <div class="project-body">
          <?php if ($p['category_name']): ?><p class="project-cat"><?= e($p['category_name']) ?></p><?php endif; ?>
          <h3 class="project-title"><?= e($p['title']) ?></h3>
          <?php if ($p['short_description']): ?><p class="project-desc"><?= e($p['short_description']) ?></p><?php endif; ?>
          <div class="project-links">
            <?php if ($p['external_url']): ?><a href="<?= e($p['external_url']) ?>" target="_blank" rel="noopener noreferrer">Visit</a><?php endif; ?>
            <?php if ($p['github_url']): ?><a href="<?= e($p['github_url']) ?>" target="_blank" rel="noopener noreferrer">GitHub</a><?php endif; ?>
          </div>
          <button class="project-open" type="button" data-project-open>Explore project <span aria-hidden="true">↗</span></button>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
  <a class="btn btn-ghost section-cta work-more-link" href="<?= e(SITE_ROOT_URL) ?>/page.php?view=work">Explore all projects <span aria-hidden="true">&rarr;</span></a>
</section>
<?php endif; ?>

<!-- SERVICES -->
<?php if ($services): ?>
<section class="section" id="services">
  <div class="section-head">
    <p class="eyebrow">What I Do</p>
    <h2>Services</h2>
  </div>
  <div class="services-grid">
    <?php foreach ($services as $s): ?>
      <div class="service-item">
        <h3><?= e($s['title']) ?></h3>
        <p><?= e($s['description']) ?></p>
        <?php if ($s['price_label']): ?><p class="service-price"><?= e($s['price_label']) ?></p><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- EXPERIENCE -->
<?php if ($experience): ?>
<section class="section" id="experience">
  <div class="section-head">
    <p class="eyebrow">Career</p>
    <h2>Experience</h2>
  </div>
  <div class="timeline">
    <?php foreach ($experience as $i => $ex): ?>
      <div class="timeline-item" data-index="<?= $i + 1 ?>">
        <button class="timeline-toggle" type="button" data-experience-toggle aria-expanded="false" aria-controls="experience-panel-<?= (int) $ex['id'] ?>">
          <span class="timeline-summary">
            <span class="timeline-role"><?= e($ex['role_title']) ?></span>
            <span class="timeline-meta">
              <span class="timeline-org"><?= e($ex['organization']) ?><?= $ex['location'] ? ' · ' . e($ex['location']) : '' ?></span>
              <span class="timeline-date"><?= e($ex['start_date']) ?> — <?= e($ex['end_date']) ?></span>
            </span>
          </span>
          <span class="timeline-chevron" aria-hidden="true"></span>
        </button>
        <div class="timeline-panel" id="experience-panel-<?= (int) $ex['id'] ?>" inert>
          <div class="timeline-panel-inner">
            <?php if (!empty($ex['description'])): ?>
              <p class="timeline-desc"><?= nl2br(e($ex['description'])) ?></p>
            <?php else: ?>
              <p class="timeline-desc">No additional details available.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <a class="btn btn-ghost section-cta experience-more-link" href="<?= e(SITE_ROOT_URL) ?>/page.php?view=experience">View full experience <span aria-hidden="true">&rarr;</span></a>
</section>
<?php endif; ?>

<!-- SKILLS -->
<?php if ($skills): ?>
<section class="section" id="skills">
  <div class="section-head">
    <p class="eyebrow">Toolkit</p>
    <h2>Skills &amp; stack</h2>
  </div>
  <div class="skills-grid">
    <?php foreach ($skills as $sk): ?>
      <span class="skill-pill"><span class="skill-pill-head"><span><?= e($sk['skill_name']) ?></span><span><?= (int)$sk['proficiency'] ?>%</span></span><span class="skill-meter"><span style="--skill-level:<?= max(0, min(100, (int)$sk['proficiency'])) ?>%"></span></span></span>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- TESTIMONIALS -->
<?php if ($testimonials): ?>
<section class="section" id="testimonials">
  <div class="section-head">
    <p class="eyebrow">Client Words</p>
    <h2>Testimonials</h2>
  </div>
  <div class="testimonial-grid">
    <?php foreach ($testimonials as $t): ?>
      <div class="testimonial-card">
        <?php if (!empty($t['client_photo'])): ?><img class="testimonial-photo" src="<?= e(UPLOAD_URL . $t['client_photo']) ?>" alt="<?= e($t['client_name']) ?>" loading="lazy" width="64" height="64"><?php endif; ?>
        <p class="testimonial-quote">&ldquo;<?= e($t['quote']) ?>&rdquo;</p>
        <p class="testimonial-name"><?= e($t['client_name']) ?></p>
        <?php if ($t['client_role']): ?><p class="testimonial-role"><?= e($t['client_role']) ?></p><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <a class="btn btn-ghost section-cta testimonials-more-link" href="<?= e(SITE_ROOT_URL) ?>/page.php?view=testimonials">Read client stories <span aria-hidden="true">&rarr;</span></a>
</section>
<?php endif; ?>

<!-- BLOG PREVIEW -->
<?php if ($recentPosts): ?>
<section class="section" id="blog">
  <div class="section-head">
    <p class="eyebrow">Insights</p>
    <h2>From the blog</h2>
  </div>
  <div class="blog-grid">
    <?php foreach ($recentPosts as $post): ?>
      <a class="blog-card" href="/post.php?slug=<?= urlencode($post['slug']) ?>">
        <?php if ($post['cover_image']): ?>
          <div class="blog-media"><img src="<?= e(UPLOAD_URL . $post['cover_image']) ?>" alt="<?= e($post['title']) ?>" loading="lazy"></div>
        <?php endif; ?>
        <div class="blog-body">
          <p class="blog-date"><?= e(date('M j, Y', strtotime($post['published_at']))) ?></p>
          <p class="blog-title"><?= e($post['title']) ?></p>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
  <a class="btn btn-ghost section-cta section-action" href="<?= e(SITE_ROOT_URL) ?>/blog.php">Browse all articles <span aria-hidden="true">&rarr;</span></a>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/social-showcase.php'; ?>

<!-- SOCIAL PRESENCE -->
<?php if ($socialLinks): ?>
<section class="section" id="social">
  <div class="section-head">
    <p class="eyebrow">Follow Along</p>
    <h2>Social presence</h2>
  </div>
  <div class="social-grid">
    <?php foreach ($socialLinks as $s): ?>
      <a class="social-card" href="<?= e($s['url']) ?>" target="_blank" rel="noopener noreferrer">
        <?= social_icon($s['platform']) ?>
        <p class="social-platform"><?= e($s['platform']) ?><?= $s['handle'] ? ' · ' . e($s['handle']) : '' ?></p>
        <?php if ($s['followers_label']): ?><p class="social-followers"><?= e($s['followers_label']) ?></p><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- CONTACT -->
<section class="section" id="contact">
  <div class="section-head">
    <p class="eyebrow">Let's Talk</p>
    <h2>Get in touch</h2>
  </div>
    <?php if (!empty($_SESSION['contact_success'])): unset($_SESSION['contact_success']); ?><div class="contact-status contact-status-success" role="status">Message sent successfully. I&rsquo;ll get back to you soon.</div><?php endif; ?>
    <?php if (!empty($_SESSION['contact_errors'])): $contactErrors = $_SESSION['contact_errors']; unset($_SESSION['contact_errors']); ?><div class="contact-status contact-status-error" role="alert"><?php foreach ($contactErrors as $contactError): ?><?= e($contactError) ?><br><?php endforeach; ?></div><?php endif; ?>
  <div class="contact-grid">
    <div>
      <p style="color:var(--text-muted);margin-bottom:var(--space-2);">Email</p>
      <p style="font-size:1.2rem;margin-bottom:var(--space-3);"><?= e(get_setting($pdo, 'contact_email')) ?></p>
      <?php if (get_setting($pdo, 'contact_phone')): ?>
        <p style="color:var(--text-muted);margin-bottom:var(--space-2);">Phone</p>
        <p style="font-size:1.2rem;margin-bottom:var(--space-3);"><?= e(get_setting($pdo, 'contact_phone')) ?></p>
      <?php endif; ?>
      <p style="color:var(--text-muted);margin-bottom:var(--space-2);">Location</p>
      <p style="font-size:1.2rem;"><?= e(get_setting($pdo, 'location')) ?></p>
    </div>
    <form class="contact-form" action="/contact-submit.php" method="POST">
      <?= csrf_field() ?>
      <input type="text" name="name" placeholder="Your name" required maxlength="120">
      <input type="email" name="email" placeholder="Your email" required maxlength="150">
      <input type="text" name="subject" placeholder="Subject" maxlength="200">
      <textarea name="message" placeholder="Message" required maxlength="4000"></textarea>
      <button type="submit" class="btn btn-primary">Send message</button>
    </form>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
