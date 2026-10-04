<?php
require_once __DIR__ . '/config/config.php';
ensure_services_schema($pdo);
ensure_products_schema($pdo);

// ---- Fetch all content up front (prepared statements throughout) ----
$about = $pdo->query('SELECT * FROM about_content WHERE id = 1')->fetch() ?: [];

$projects = $pdo->query(
    'SELECT p.*, c.name AS category_name FROM projects p
     LEFT JOIN project_categories c ON c.id = p.category_id
     WHERE p.is_visible = 1
     ORDER BY p.is_featured DESC, p.sort_order ASC, p.created_at DESC
    LIMIT 3'
)->fetchAll();

$services = $pdo->query('SELECT * FROM services WHERE is_visible = 1 ORDER BY is_featured DESC, sort_order ASC LIMIT 6')->fetchAll();
$serviceProjectCounts = service_project_counts($pdo);
$saleProducts = $pdo->query('SELECT * FROM products WHERE is_visible = 1 ORDER BY is_featured DESC, sort_order ASC, id DESC LIMIT 6')->fetchAll();
$contactPhone = get_setting($pdo, 'contact_phone', '');
$experience = $pdo->query('SELECT * FROM experience WHERE is_visible = 1 ORDER BY sort_order ASC, id DESC')->fetchAll();
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
    <?php
    $customTagline = trim($about['tagline'] ?? '');
    if ($customTagline !== '') {
        $taglineDisplay = (preg_match('/^(hello|hi|hey|i am|i\'m|i’m)/i', $customTagline))
            ? $customTagline
            : 'Hello, I’m ' . $about['full_name'] . '. ' . $customTagline;
    } else {
        $taglineDisplay = 'Hello, I’m ' . $about['full_name'] . '. I build brands, campaigns, and digital experiences.';
    }
    ?>
    <p class="hero-tagline"><span data-typing="<?= e($taglineDisplay) ?>"></span><span class="typing-cursor" aria-hidden="true">|</span></p>
    <p class="hero-skill-line">Currently focused on <span data-skill-rotate data-skills="<?= e(json_encode($heroFocusOptions, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>"><?= e($heroFocusOptions[0]) ?></span></p>
    <div class="hero-actions">
      <a href="#work" class="btn btn-primary">See my work</a>
      <?php if (!empty($about['resume_file'])): ?>
        <a href="<?= e(UPLOAD_URL . $about['resume_file']) ?>" class="btn btn-ghost" download><?= e(get_setting($pdo, 'resume_button_text', 'Download CV')) ?></a>
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

<?php require __DIR__ . '/includes/clients-slider.php'; ?>

<!-- WORK -->
<?php if ($projects): ?>
<section class="section" id="work">
  <div class="section-head">
    <p class="eyebrow">Selected Work</p>
    <h2>Projects &amp; campaigns</h2>
  </div>
  <div class="work-grid">
    <?php foreach ($projects as $i => $p): ?>
      <article class="project-card<?= $p['is_featured'] ? ' featured' : '' ?>">
        <div class="project-media">
          <?php if ($p['cover_image']): ?>
            <a class="project-image-link" href="<?= e(SITE_ROOT_URL . '/project.php?slug=' . urlencode($p['slug'])) ?>" aria-label="View project: <?= e($p['title']) ?>"><img src="<?= e(UPLOAD_URL . $p['cover_image']) ?>" alt="<?= e($p['title']) ?>" loading="lazy" width="640" height="400"></a>
          <?php endif; ?>
        </div>
        <div class="project-body">
          <?php if ($p['category_name']): ?><p class="project-cat"><?= e($p['category_name']) ?></p><?php endif; ?>
          <h3 class="project-title"><a href="<?= e(SITE_ROOT_URL . '/project.php?slug=' . urlencode($p['slug'])) ?>"><?= e($p['title']) ?></a></h3>
          <?php if ($p['short_description']): ?><div class="project-desc"><?= limit_rich_text_words((string) $p['short_description'], 20) ?></div><?php endif; ?>
          <div class="project-links">
            <?php if ($p['external_url']): ?><a href="<?= e($p['external_url']) ?>" target="_blank" rel="noopener noreferrer">Visit live site</a><?php endif; ?>
            <?php if ($p['github_url']): ?><a href="<?= e($p['github_url']) ?>" target="_blank" rel="noopener noreferrer">GitHub</a><?php endif; ?>
          </div>
          <a class="project-open" href="<?= e(SITE_ROOT_URL . '/project.php?slug=' . urlencode($p['slug'])) ?>">Explore project <span aria-hidden="true">&#8599;</span></a>
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
    <h2>Services &amp; capabilities</h2>
  </div>
  <div class="svc-grid">
    <?php foreach ($services as $i => $s): ?>
      <?php render_service_card($s, $serviceProjectCounts[(int) $s['id']] ?? 0, $i); ?>
    <?php endforeach; ?>
  </div>
  <a class="btn btn-ghost section-cta" style="margin-top:var(--space-3);" href="<?= e(SITE_ROOT_URL) ?>/page.php?view=services">Explore all services &amp; packages <span aria-hidden="true">&rarr;</span></a>
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

<!-- READY PROJECTS FOR SALE / EXCLUSIVE DEALS -->
<?php if ($saleProducts): ?>
<section class="section" id="deals">
  <div class="section-head" style="text-align:left;">
    <div style="display:inline-flex;align-items:center;gap:8px;padding:4px 12px;border-radius:999px;background:rgba(255,71,87,0.12);border:1px solid rgba(255,71,87,0.3);color:#ff4757;font-size:0.76rem;font-weight:800;letter-spacing:0.06em;text-transform:uppercase;margin-bottom:8px;">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
      <span>Special Offer &bull; Buy Ready-to-Deploy Code</span>
    </div>
    <h2>Turnkey Projects &amp; Apps for Sale</h2>
    <p style="color:var(--text-muted);font-size:1rem;max-width:640px;margin-top:4px;">
      Buy exact pre-built applications, websites &amp; portfolio systems at fixed promotional prices. Deploy today or request custom additions.
    </p>
  </div>
  <div class="prod-grid">
    <?php foreach ($saleProducts as $i => $prod): ?>
      <?php render_product_card($prod, $i, $contactPhone ?? ''); ?>
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
      <a class="blog-card" href="<?= e(SITE_ROOT_URL . '/post.php?slug=' . urlencode($post['slug'])) ?>">
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
      <?php 
      $homeContactEmail = get_setting($pdo, 'contact_email');
      $homeContactPhone = get_setting($pdo, 'contact_phone');
      $homeLocation     = get_setting($pdo, 'location');
      $homeWaDigits     = preg_replace('/\D+/', '', (string)$homeContactPhone);
      ?>
      <?php if ($homeContactEmail): ?>
        <p style="color:var(--text-muted);margin-bottom:var(--space-2);">Email</p>
        <p style="font-size:1.2rem;margin-bottom:var(--space-3);"><a href="mailto:<?= e($homeContactEmail) ?>" style="color:var(--text);"><?= e($homeContactEmail) ?></a></p>
      <?php endif; ?>
      <?php if ($homeContactPhone): ?>
        <p style="color:var(--text-muted);margin-bottom:var(--space-2);">Phone</p>
        <p style="font-size:1.2rem;margin-bottom:var(--space-3);"><a href="tel:<?= e($homeContactPhone) ?>" style="color:var(--text);"><?= e($homeContactPhone) ?></a></p>
      <?php endif; ?>
      <?php if ($homeLocation): ?>
        <p style="color:var(--text-muted);margin-bottom:var(--space-2);">Location</p>
        <p style="font-size:1.2rem;margin-bottom:var(--space-3);"><?= e($homeLocation) ?></p>
      <?php endif; ?>
      <?php if ($homeWaDigits !== '' && strlen($homeWaDigits) >= 8): ?>
        <div style="margin-top:20px;">
          <a href="https://wa.me/<?= e($homeWaDigits) ?>?text=<?= rawurlencode('Hi! I would like to connect regarding a project.') ?>" class="btn btn-secondary" target="_blank" rel="noopener noreferrer" style="display:inline-flex;align-items:center;gap:8px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
            Chat on WhatsApp
          </a>
        </div>
      <?php endif; ?>
    </div>
    <form class="contact-form" action="<?= e(SITE_ROOT_URL) ?>/contact-submit.php" method="POST">
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
